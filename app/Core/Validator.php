<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validator
 *
 * Rules supported:
 *   required, nullable, string, integer, numeric, float,
 *   email, url, min, max, min_length, max_length,
 *   in, not_in, regex, confirmed, unique (DB), exists (DB),
 *   date, boolean, file, mimes, max_file_size
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors    = [];
    private array $validated = [];

    private static array $messages = [
        'required'      => 'The :field field is required.',
        'email'         => 'The :field must be a valid email address.',
        'url'           => 'The :field must be a valid URL.',
        'min'           => 'The :field must be at least :param.',
        'max'           => 'The :field must not exceed :param.',
        'min_length'    => 'The :field must be at least :param characters.',
        'max_length'    => 'The :field must not exceed :param characters.',
        'integer'       => 'The :field must be an integer.',
        'numeric'       => 'The :field must be numeric.',
        'string'        => 'The :field must be a string.',
        'boolean'       => 'The :field must be true or false.',
        'in'            => 'The selected :field is invalid.',
        'not_in'        => 'The selected :field is invalid.',
        'regex'         => 'The :field format is invalid.',
        'confirmed'     => 'The :field confirmation does not match.',
        'unique'        => 'The :field has already been taken.',
        'exists'        => 'The selected :field is invalid.',
        'date'          => 'The :field must be a valid date.',
        'mimes'         => 'The :field must be a file of type: :param.',
        'max_file_size' => 'The :field may not be greater than :param kilobytes.',
    ];

    public function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
    }

    // ----------------------------------------------------------------
    // Run validation
    // ----------------------------------------------------------------

    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString)
                ? $ruleString
                : explode('|', $ruleString);

            $value    = $this->getValue($field);
            $nullable = in_array('nullable', $rules);

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                // Skip other rules if nullable and empty
                if ($nullable && ($value === null || $value === '')) {
                    continue;
                }

                [$ruleName, $param] = $this->parseRule($rule);

                if (! $this->applyRule($field, $value, $ruleName, $param)) {
                    break;  // Stop on first failure for this field
                }
            }

            // Collect validated values (only fields with rules)
            if (! isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }

        return empty($this->errors);
    }

    public function fails(): bool
    {
        return ! $this->validate();
    }

    public function passes(): bool
    {
        return $this->validate();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validated;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    // ----------------------------------------------------------------
    // Rule engine
    // ----------------------------------------------------------------

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): bool
    {
        $passed = match ($rule) {
            'required'   => $this->validateRequired($value),
            'string'     => is_string($value),
            'integer'    => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'numeric'    => is_numeric($value),
            'float'      => filter_var($value, FILTER_VALIDATE_FLOAT) !== false,
            'boolean'    => in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true),
            'email'      => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url'        => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'date'       => (bool) strtotime((string) $value),
            'min'        => is_numeric($value) && (float) $value >= (float) $param,
            'max'        => is_numeric($value) && (float) $value <= (float) $param,
            'min_length' => strlen((string) $value) >= (int) $param,
            'max_length' => strlen((string) $value) <= (int) $param,
            'in'         => in_array((string) $value, explode(',', $param ?? ''), true),
            'not_in'     => ! in_array((string) $value, explode(',', $param ?? ''), true),
            'regex'      => (bool) preg_match($param ?? '/.*/', (string) $value),
            'confirmed'  => $this->validateConfirmed($field, $value),
            'unique'     => $this->validateUnique($field, $value, $param),
            'exists'     => $this->validateExists($value, $param),
            'mimes'      => $this->validateMimes($field, $param),
            'max_file_size' => $this->validateMaxFileSize($field, $param),
            default      => true,
        };

        if (! $passed) {
            $this->addError($field, $rule, $param);
        }

        return $passed;
    }

    private function validateRequired(mixed $value): bool
    {
        if ($value === null) return false;
        if (is_string($value) && trim($value) === '') return false;
        if (is_array($value) && count($value) === 0) return false;
        return true;
    }

    private function validateConfirmed(string $field, mixed $value): bool
    {
        return isset($this->data["{$field}_confirmation"])
            && $value === $this->data["{$field}_confirmation"];
    }

    private function validateUnique(string $field, mixed $value, ?string $param): bool
    {
        if (! $param) return true;

        // Format: table,column[,ignore_id]
        $parts  = explode(',', $param);
        $table  = $parts[0];
        $col    = $parts[1] ?? $field;
        $ignore = $parts[2] ?? null;

        $sql    = "SELECT COUNT(*) FROM `{$table}` WHERE `{$col}` = ?";
        $params = [$value];

        if ($ignore) {
            $sql     .= ' AND `id` != ?';
            $params[] = $ignore;
        }

        return (int) Database::getInstance()->fetchColumn($sql, $params) === 0;
    }

    private function validateExists(mixed $value, ?string $param): bool
    {
        if (! $param) return true;

        [$table, $col] = explode(',', $param . ',id');
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE `{$col}` = ?";
        return (int) Database::getInstance()->fetchColumn($sql, [$value]) > 0;
    }

    private function validateMimes(string $field, ?string $param): bool
    {
        $file = $_FILES[$field] ?? null;
        if (! $file || $file['error'] !== UPLOAD_ERR_OK) return true;

        $allowed = array_map('trim', explode(',', $param ?? ''));
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        return in_array($ext, $allowed, true);
    }

    private function validateMaxFileSize(string $field, ?string $param): bool
    {
        $file = $_FILES[$field] ?? null;
        if (! $file || $file['error'] !== UPLOAD_ERR_OK) return true;

        $maxKb = (int) ($param ?? 10240);
        return $file['size'] <= $maxKb * 1024;
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function getValue(string $field): mixed
    {
        // Support dot notation: 'address.city'
        if (str_contains($field, '.')) {
            $keys  = explode('.', $field);
            $value = $this->data;
            foreach ($keys as $key) {
                $value = $value[$key] ?? null;
            }
            return $value;
        }

        return $this->data[$field] ?? null;
    }

    private function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [$name, $param] = explode(':', $rule, 2);
            return [$name, $param];
        }
        return [$rule, null];
    }

    private function addError(string $field, string $rule, ?string $param): void
    {
        $message  = static::$messages[$rule] ?? "The :field field failed the {$rule} rule.";
        $label    = ucfirst(str_replace('_', ' ', $field));
        $message  = str_replace(':field', $label, $message);
        $message  = str_replace(':param', (string) ($param ?? ''), $message);

        $this->errors[$field][] = $message;
    }
}
