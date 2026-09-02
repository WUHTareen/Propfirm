<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Accounts made here are created by staff, so there is nobody to click a
     * verification link — mark them verified and give them the referral code
     * registration would have generated.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['email_verified_at'] ??= now();
        $data['referral_code'] ??= Str::upper(Str::random(8));

        return $data;
    }
}
