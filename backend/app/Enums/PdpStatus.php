<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Physical Development Plan lifecycle status as captured by field officers.
 */
enum PdpStatus: string
{
    case Active = 'Active';
    case Expiring = 'Expiring';
    case Missing = 'Missing';
    case Suspended = 'Suspended';
    case UnderReview = 'Under Review';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases(),
        );
    }

    /**
     * Records that are still valid for planning purposes.
     */
    public function isActionable(): bool
    {
        return match ($this) {
            self::Active, self::Expiring => true,
            default => false,
        };
    }
}
