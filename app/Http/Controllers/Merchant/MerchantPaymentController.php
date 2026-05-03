<?php

declare(strict_types=1);

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantPaymentController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        $payments = $seller->merchantPayments()
            ->with(['invoice:id,uuid', 'storefrontOrder:id,uuid'])
            ->latest()
            ->get();

        return Inertia::render('merchant/Payments', [
            'payments' => $payments->map(function (MerchantPayment $pay) {
                return [
                    'id' => $pay->id,
                    'amount' => (string) $pay->amount,
                    'currency_label_ar' => $pay->currency_label_ar,
                    'payment_method' => $pay->payment_method,
                    'note' => $pay->note,
                    'invoice_uuid' => $pay->invoice?->uuid,
                    'order_uuid' => $pay->storefrontOrder?->uuid,
                    'created_at' => $pay->created_at?->toIso8601String(),
                ];
            })->values(),
        ]);
    }
}
