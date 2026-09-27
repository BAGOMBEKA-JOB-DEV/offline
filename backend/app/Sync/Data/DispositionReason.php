<?php

declare(strict_types=1);

namespace App\Sync\Data;

/**
 * Why a record in a flush payload did not produce a new committed row.
 */
enum DispositionReason: string
{
    /** New record, written for the first time. */
    case Committed = 'committed';

    /** Byte-identical replay already committed by this or another device. */
    case Duplicate = 'duplicate';

    /** Seen earlier inside this very same batch payload. */
    case DuplicateWithinBatch = 'duplicate_within_batch';

    /** Same UUID, different business data: a correction, so it overwrites. */
    case CorrectionSuperseded = 'correction_superseded';

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
