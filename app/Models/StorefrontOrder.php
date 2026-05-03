<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'uuid',
    'user_id',
    'payment_link_id',
    'buyer_name',
    'buyer_phone',
    'buyer_address',
    'buyer_maps_url',
    'lines',
    'subtotal',
    'currency_label_ar',
    'currency_label_en',
    'status',
    'payment_receipt_path',
])]
class StorefrontOrder extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAYMENT_SUBMITTED = 'payment_submitted';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<PaymentLink, $this>
     */
    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class);
    }

    /**
     * @return HasOne<MerchantInvoice, $this>
     */
    public function merchantInvoice(): HasOne
    {
        return $this->hasOne(MerchantInvoice::class, 'storefront_order_id');
    }

    /**
     * @return HasMany<MerchantPayment, $this>
     */
    public function merchantPayments(): HasMany
    {
        return $this->hasMany(MerchantPayment::class, 'storefront_order_id');
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
