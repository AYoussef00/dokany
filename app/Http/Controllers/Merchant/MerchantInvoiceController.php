<?php

declare(strict_types=1);

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantInvoice;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        $invoices = $seller->merchantInvoices()
            ->with('storefrontOrder:id,uuid')
            ->latest()
            ->get();

        return Inertia::render('merchant/Invoices', [
            'invoices' => $invoices->map(function (MerchantInvoice $inv) {
                return [
                    'uuid' => $inv->uuid,
                    'order_uuid' => $inv->storefrontOrder?->uuid,
                    'buyer_name' => $inv->buyer_name,
                    'buyer_phone' => $inv->buyer_phone,
                    'buyer_address' => $inv->buyer_address,
                    'buyer_maps_url' => $inv->buyer_maps_url,
                    'lines' => $inv->lines ?? [],
                    'subtotal' => (string) $inv->subtotal,
                    'currency_label_ar' => $inv->currency_label_ar,
                    'currency_label_en' => $inv->currency_label_en,
                    'created_at' => $inv->created_at?->toIso8601String(),
                ];
            })->values(),
        ]);
    }
}
