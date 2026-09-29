<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;
use Carbon\CarbonInterface;

/**
 * Records every create/update/delete in audit_logs, field by field ("Campo: antes → depois").
 * The model lists its fields in auditFields() and may format values in auditValue().
 */
trait Auditable
{
    /** Extra changes not stored in the model's own columns (e.g. pivot tables). */
    public array $auditExtra = [];

    public static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->writeAudit('created'));
        static::updated(fn ($model) => $model->writeAudit('updated'));
        static::deleted(fn ($model) => $model->writeAudit('deleted'));
    }

    /** @return array<string, string> column => label shown in the log */
    abstract public function auditFields(): array;

    abstract public function auditEntity(): string;

    public function auditTitle(): string
    {
        return (string) ($this->getAttribute('name') ?? '#'.$this->getKey());
    }

    /** Columns whose values must never be shown (only "alterada"). */
    public function auditHidden(): array
    {
        return [];
    }

    protected function auditValue(string $field, mixed $value): string
    {
        return $this->defaultAuditValue($field, $value);
    }

    protected function defaultAuditValue(string $field, mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' => '',
            is_bool($value) => $value ? 'Sim' : 'Não',
            $value instanceof CarbonInterface => $value->format('d/m/Y'),
            is_string($value) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1 => substr($value, 0, 5),
            default => (string) $value,
        };
    }

    /** Writes pending extra changes when the model itself had nothing dirty. */
    public function flushAudit(): void
    {
        if ($this->auditExtra) {
            $this->writeAudit('updated');
        }
    }

    public function writeAudit(string $event): void
    {
        $changes = [];

        if ($event !== 'deleted') {
            foreach ($this->auditFields() as $field => $label) {
                if ($event === 'updated' && ! $this->wasChanged($field)) {
                    continue;
                }

                if (in_array($field, $this->auditHidden(), true)) {
                    $changes[] = ['campo' => $label, 'antes' => '', 'depois' => $event === 'created' ? '(definida)' : '(alterada)'];
                    continue;
                }

                $before = $event === 'created' ? '' : $this->auditValue($field, $this->getOriginal($field));
                $after = $this->auditValue($field, $this->getAttribute($field));

                if ($before !== $after) {
                    $changes[] = ['campo' => $label, 'antes' => $before, 'depois' => $after];
                }
            }
        }

        $changes = array_merge($changes, $this->auditExtra);
        $this->auditExtra = [];

        if ($event === 'updated' && ! $changes) {
            return;
        }

        AuditLogger::record($this->auditEntity(), $event, $this->getKey() ? (int) $this->getKey() : null, $this->auditTitle(), $changes);
    }
}
