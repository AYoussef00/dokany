<?php

declare(strict_types=1);

namespace App\Actions\Merchant;

use App\Models\MerchantInvoice;
use App\Models\MerchantPayment;
use App\Models\StorefrontOrder;
use Illuminate\Support\Str;

final class IssueMerchantInvoiceAndPaymentForOrder
{
    public function handle(StorefrontOrder $order): void
    {
        if (MerchantInvoice::query()->where('storefront_order_id', $order->id)->exists()) {
            return;
        }

        $invoice = MerchantInvoice::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $order->user_id,
            'storefront_order_id' => $order->id,
            'buyer_name' => $order->buyer_name,
            'buyer_phone' => $order->buyer_phone,
            'buyer_address' => $order->buyer_address,
            'buyer_maps_url' => $order->buyer_maps_url,
            'lines' => $order->lines,
            'subtotal' => $order->subtotal,
            'currency_label_ar' => $order->currency_label_ar,
            'currency_label_en' => $order->currency_label_en,
        ]);

        MerchantPayment::query()->create([
            'user_id' => $order->user_id,
            'storefront_order_id' => $order->id,
            'merchant_invoice_id' => $invoice->id,
            'amount' => $order->subtotal,
            'currency_label_ar' => $order->currency_label_ar,
            'currency_label_en' => $order->currency_label_en,
            'payment_method' => 'instapay',
            'note' => 'دفعة واردة من طلب متجر — بعد قبول التاجر للطلب.',
        ]);
    }
}
