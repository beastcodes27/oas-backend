<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\VerificationStatus;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'necta_verified_at' => 'datetime',
            'status' => ApplicationStatus::class,
            'verification_status' => VerificationStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * The status shown to applicants.
     *
     * Remains "pending" until the school publishes its selection results,
     * so internal review decisions are never exposed early.
     */
    public function visibleStatusForApplicant(): ApplicationStatus
    {
        if ($this->school?->selections_published_at === null) {
            return ApplicationStatus::Pending;
        }

        return $this->status->isFinal() ? $this->status : ApplicationStatus::Pending;
    }
}
