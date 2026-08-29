<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KYC document uploads (stored on S3-compatible object storage — file_path is
 * the object key, never a local path). Reviewed by admin with remarks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trading_account_id')->nullable()
                ->constrained('trading_accounts')->nullOnDelete();

            $table->enum('document_type', [
                'id_card', 'passport', 'driver_license', 'proof_of_address', 'selfie',
            ]);
            $table->string('file_path');            // object storage key
            $table->string('file_disk')->default('s3');
            $table->string('original_name')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};
