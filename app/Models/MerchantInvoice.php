<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'uuid',
    'user_id',
    'storefront_order_id',
    'buyer_name',
    'buyer_phone',
    'buyer_address',
    'buyer_maps_url',
    'lines',
    'subtotal',
    'currency_label_ar',
    'currency_label_en',
])]
class MerchantInvoice extends Model
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
     * @return HasOne<MerchantPayment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(MerchantPayment::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lines' => 'array',
            'subtotal' => 'decimal:2',
        ];
    }
}
