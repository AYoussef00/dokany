<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'instapay_wallet' => ['required', 'string', 'max:64'],
            'whatsapp_phone' => ['required', 'string', 'max:32'],
            'phone' => ['required', 'string', 'max:32'],
            'address' => ['required', 'string', 'max:2000'],
            'store_logo' => ['nullable', 'image', 'max:2048'],
        ])->validate();

        $logoPath = null;
        $logo = $input['store_logo'] ?? null;
        if ($logo instanceof UploadedFile && $logo->isValid()) {
            $logoPath = $logo->store('store-logos', 'public');
        }

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => User::ROLE_SELLER,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_INACTIVE,
            'store_logo_path' => $logoPath,
            'instapay_wallet' => $input['instapay_wallet'],
            'whatsapp_phone' => $input['whatsapp_phone'],
            'phone' => $input['phone'],
            'address' => $input['address'],
        ]);

        $user->forceFill([
            'store_slug' => User::generateUniqueStoreSlug($user->name, $user->id),
        ])->save();

        return $user->fresh();
    }
}
