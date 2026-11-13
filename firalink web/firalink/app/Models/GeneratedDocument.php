<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneratedDocument extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_READY_FOR_EXPORT = 'ready_for_export';
    const STATUS_FINALIZED = 'finalized';
    const STATUS_ARCHIVED = 'archived';

    protected $guarded = ['id'];

    protected $casts = [
        'data_payload' => 'array',
        'calculated_summary' => 'array',
        'finalized_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(ExportShipment::class, 'export_shipment_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'document_template_version_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(DocumentRevision::class)->orderByDesc('version_number');
    }

    public function downloadEvents(): HasMany
    {
        return $this->hasMany(DocumentDownloadEvent::class);
    }

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    /**
     * Create an immutable revision snapshot.
     */
    public function snapshotRevision(?int $userId = null, ?string $summary = null): DocumentRevision
    {
        $nextVersion = ($this->revisions()->max('version_number') ?? 0) + 1;
        $hash = hash('sha256', json_encode($this->data_payload));

        return $this->revisions()->create([
            'version_number' => $nextVersion,
            'snapshot_payload' => $this->data_payload,
            'change_summary' => $summary ?: "Version {$nextVersion} update",
            'created_by_user_id' => $userId ?: auth()->id(),
            'sha256_hash' => $hash,
        ]);
    }
}

