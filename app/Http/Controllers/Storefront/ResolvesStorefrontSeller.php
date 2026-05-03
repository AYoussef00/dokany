<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ResolvesStorefrontSeller
{
    protected function storefrontSellerFromSlug(string $slug): User
    {
        $seller = User::query()
            ->where('store_slug', $slug)
            ->where('role', User::ROLE_SELLER)
            ->where('merchant_subscription_status', User::MERCHANT_SUBSCRIPTION_ACTIVE)
            ->first();

        if ($seller === null) {
            throw new NotFoundHttpException;
        }

        return $seller;
    }
}
