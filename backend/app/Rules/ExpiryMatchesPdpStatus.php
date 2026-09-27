<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\PdpStatus;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Validator;

/**
 * Cross-field rule: an expiry year must agree with the plan's status.
 *
 * `Missing` plans have no expiry by definition, and an `Expiring` plan without
 * an expiry year is a contradiction that would silently vanish from any
 * renewal report. Rejecting both at the edge is cheaper than explaining the bad
 * data downstream.
 *
 * Bound to `records.*` so it can read the sibling `pdp_status`, but the failure
 * is reported against `records.*.expiry_year` — the field the officer actually
 * has to fix — rather than against the whole record.
 */
final class ExpiryMatchesPdpStatus implements DataAwareRule, ValidationRule, ValidatorAwareRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    private Validator $validator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function setValidator(Validator $validator): void
    {
        $this->validator = $validator;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $status = $value['pdp_status'] ?? null;
        $year = $value['expiry_year'] ?? null;
        $key = $attribute.'.expiry_year';

        if ($status === PdpStatus::Missing->value && $year !== null) {
            $this->addError($key, 'A missing plan cannot have an expiry year.');
        }

        if ($status === PdpStatus::Expiring->value && $year === null) {
            $this->addError($key, 'A plan with an expiring status requires an expiry year.');
        }
    }

    private function addError(string $key, string $message): void
    {
        $this->validator->errors()->add($key, $message);
    }
}
