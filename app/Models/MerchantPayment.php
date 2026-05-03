<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'storefront_order_id',
    'merchant_invoice_id',
    'amount',
    'currency_label_ar',
    'currency_label_en',
    'payment_method',
    'note',
])]
class MerchantPayment extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<StorefrontOrder, $this>
     */
    public function storefrontOrder(): BelongsTo
    {
        return $this->belongsTo(StorefrontOrder::class);
    }

    /**
     * @return BelongsTo<MerchantInvoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(MerchantInvoice::class, 'merchant_invoice_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }
}
