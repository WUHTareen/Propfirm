<?php

namespace App\Http\Controllers;

use App\Models\KycDocument;
use App\Services\Kyc\KycService;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return view('dashboard.kyc', [
            'locked' => ! $user->hasFundedAccount(),
            'documents' => $user->kycDocuments()->latest()->get(),
        ]);
    }

    public function store(Request $request, KycService $kyc)
    {
        $user = $request->user();
        abort_unless($user->hasFundedAccount(), 403, 'You need a funded account before applying for KYC.');

        $data = $request->validate([
            'document_type' => 'required|in:id_card,passport,driver_license,proof_of_address,selfie',
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:8192',
        ]);

        $kyc->upload($user, $data['document_type'], $request->file('document'));

        return back()->with('status', 'Document uploaded — pending review.');
    }

    /**
     * Streams a KYC file to reviewing staff (never a public URL — KYC is private).
     */
    public function download(Request $request, KycDocument $document, KycService $kyc)
    {
        abort_unless($request->user()?->can('review kyc'), 403);

        return $kyc->download($document);
    }
}
