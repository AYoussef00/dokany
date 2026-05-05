<?php

declare(strict_types=1);

namespace App\Enums;

enum StorefrontProductCategory: string
{
    case NewIn = 'new_in';
    case Bestsellers = 'bestsellers';
    case Sale = 'sale';
    case Accessories = 'accessories';

    public function labelAr(): string
    {
        return match ($this) {
            self::NewIn => 'جديد',
            self::Bestsellers => 'الأكثر مبيعاً',
            self::Sale => 'عروض',
            self::Accessories => 'إكسسوارات',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $c): array => [
                'value' => $c->value,
                'label' => $c->labelAr(),
            ],
            self::cases(),
        );
    }
}
