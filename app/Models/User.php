<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable([
    'name',
    'store_slug',
    'email',
    'password',
    'role',
    'merchant_subscription_status',
    'subscription_paid_amount',
    'merchant_access_months',
    'email_verified_at',
    'store_logo_path',
    'storefront_hero_primary',
    'storefront_hero_secondary',
    'storefront_hero_banner_paths',
    'social_facebook_url',
    'social_instagram_url',
    'social_x_url',
    'social_youtube_url',
    'social_tiktok_url',
    'instapay_wallet',
    'whatsapp_phone',
    'phone',
    'address',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    public const ROLE_SELLER = 'seller';

    public const MERCHANT_SUBSCRIPTION_INACTIVE = 'inactive';

    public const MERCHANT_SUBSCRIPTION_PENDING_REVIEW = 'pending_review';

    public const MERCHANT_SUBSCRIPTION_ACTIVE = 'active';

    public const MERCHANT_SUBSCRIPTION_REJECTED = 'rejected';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $appends = [
        'store_logo_url',
    ];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSeller(): bool
    {
        return $this->role === self::ROLE_SELLER;
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<StorefrontOrder, $this>
     */
    public function storefrontOrders(): HasMany
    {
        return $this->hasMany(StorefrontOrder::class);
    }

    /**
     * @return HasMany<PaymentLink, $this>
     */
    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    /**
     * @return HasMany<MerchantInvoice, $this>
     */
    public function merchantInvoices(): HasMany
    {
        return $this->hasMany(MerchantInvoice::class);
    }

    /**
     * @return HasMany<MerchantPayment, $this>
     */
    public function merchantPayments(): HasMany
    {
        return $this->hasMany(MerchantPayment::class);
    }

    public function storefrontPublicUrl(): ?string
    {
        if (! $this->isSeller() || $this->store_slug === null || $this->store_slug === '') {
            return null;
        }

        $prefix = trim((string) config('dokany.storefront_path_prefix', 'shop'), '/');

        return url('/'.$prefix.'/'.$this->store_slug);
    }

    public static function generateUniqueStoreSlug(string $name, ?int $ignoreUserId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'merchant';
        }

        $reserved = array_map(
            static fn (string $s) => strtolower($s),
            config('dokany.reserved_store_slugs', []),
        );

        $slug = $base;
        $n = 0;

        while (true) {
            if (in_array($slug, $reserved, true)) {
                $n++;
                $slug = $base.'-'.$n;

                continue;
            }

            $query = static::query()->where('store_slug', $slug);

            if ($ignoreUserId !== null) {
                $query->where('id', '!=', $ignoreUserId);
            }

            if (! $query->exists()) {
                return $slug;
            }

            $n++;
            $slug = $base.'-'.$n;
        }
    }

    public function sellerSubscriptionLoginBlocked(): bool
    {
        if ($this->role !== self::ROLE_SELLER) {
            return false;
        }

        if (in_array($this->merchant_subscription_status, [
            self::MERCHANT_SUBSCRIPTION_PENDING_REVIEW,
            self::MERCHANT_SUBSCRIPTION_REJECTED,
        ], true)) {
            return true;
        }

        return $this->merchant_subscription_status === self::MERCHANT_SUBSCRIPTION_ACTIVE
            && $this->merchantAccessExpired();
    }

    public function sellerSubscriptionLoginBlockedMessage(): string
    {
        if ($this->merchant_subscription_status === self::MERCHANT_SUBSCRIPTION_PENDING_REVIEW) {
            return 'حسابك قيد مراجعة إثبات الدفع. سيتم إخطارك عند الموافقة على الاشتراك.';
        }

        if ($this->merchant_subscription_status === self::MERCHANT_SUBSCRIPTION_REJECTED) {
            return 'تم رفض طلب الاشتراك. لا يمكن تسجيل الدخول حالياً.';
        }

        if (
            $this->merchant_subscription_status === self::MERCHANT_SUBSCRIPTION_ACTIVE
            && $this->merchantAccessExpired()
        ) {
            return 'انتهت صلاحية اشتراكك وفقاً لفترة الوصول من تاريخ التسجيل. يرجى التواصل مع الإدارة لتجديد الاشتراك.';
        }

        return 'لا يمكن تسجيل الدخول بحسابك حالياً.';
    }

    public function merchantAccessEndsAt(): ?Carbon
    {
        if ($this->created_at === null) {
            return null;
        }

        $start = Carbon::parse($this->created_at);

        if ($this->merchant_access_months !== null && (int) $this->merchant_access_months > 0) {
            return $start->copy()->addMonths((int) $this->merchant_access_months);
        }

        $days = max(1, (int) config('dokany.subscription.merchant_access_days', 30));

        return $start->copy()->addDays($days);
    }

    public function merchantAccessExpired(): bool
    {
        $ends = $this->merchantAccessEndsAt();

        if ($ends === null) {
            return true;
        }

        return now()->greaterThan($ends);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function storeLogoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->store_logo_path !== null && $this->store_logo_path !== ''
                ? asset('storage/'.$this->store_logo_path)
                : null,
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'storefront_hero_banner_paths' => 'array',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'subscription_payment_reported_at' => 'datetime',
            'subscription_paid_amount' => 'decimal:2',
            'storefront_visit_count' => 'integer',
        ];
    }
}
