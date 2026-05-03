<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMerchantSubscriptionAccessActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user instanceof User
            && $user->role === User::ROLE_SELLER
            && $user->merchant_subscription_status === User::MERCHANT_SUBSCRIPTION_ACTIVE
            && $user->merchantAccessExpired()
        ) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with(
                    'status',
                    'انتهت صلاحية اشتراكك وفقاً لفترة الوصول من تاريخ التسجيل. يرجى التواصل مع الإدارة لتجديد الاشتراك.',
                );
        }

        return $next($request);
    }
}
