<?php

namespace App\Services\Kyc;

use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * KYC document handling: upload, admin approve/reject, and keeping the user's
 * overall kyc_status in sync. Files go to the private (non-public) disk —
 * switch FILESYSTEM_DISK to an S3-compatible disk for production.
 */
class KycService
{
    protected function disk(): string
    {
        // Prefer S3 when configured; fall back to the private local disk.
        return config('filesystems.default') === 's3' ? 's3' : 'local';
    }

    public function upload(User $user, string $documentType, UploadedFile $file): KycDocument
    {
        $disk = $this->disk();
        $path = $file->store("kyc/{$user->id}", $disk);

        $document = KycDocument::create([
            'user_id' => $user->id,
            'document_type' => $documentType,
            'file_path' => $path,
            'file_disk' => $disk,
            'original_name' => $file->getClientOriginalName(),
            'status' => 'pending',
        ]);

        // Any new upload puts an un-approved trader into "pending" review.
        if ($user->kyc_status !== 'approved') {
            $user->forceFill(['kyc_status' => 'pending'])->save();
        }

        return $document;
    }

    public function approve(KycDocument $document, User $reviewer): void
    {
        $document->forceFill([
            'status' => 'approved',
            'remarks' => null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();

        // One approved document verifies the trader (client can tighten later).
        $document->user->forceFill(['kyc_status' => 'approved'])->save();
    }

    public function reject(KycDocument $document, User $reviewer, string $remarks): void
    {
        $document->forceFill([
            'status' => 'rejected',
            'remarks' => $remarks,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();

        // Drop the user back unless they still have an approved document.
        if (! $document->user->kycDocuments()->where('status', 'approved')->exists()) {
            $document->user->forceFill(['kyc_status' => 'rejected'])->save();
        }
    }

    public function download(KycDocument $document)
    {
        return Storage::disk($document->file_disk)->download($document->file_path, $document->original_name);
    }
}
