<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Terminal state of a single offline queue flush.
 */
enum SyncOutcome: string
{
    /** Every record in the payload was new and valid. */
    case Committed = 'committed';

    /** The flush was fully absorbed as a replay of an earlier flush. */
    case Replayed = 'replayed';

    /** At least one record was dropped as a duplicate or invalid. */
    case Partial = 'partial';

    /** Every record was rejected; nothing was written. */
    case Rejected = 'rejected';

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
}
