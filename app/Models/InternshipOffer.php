<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipOffer extends Model
{
    /** @use HasFactory<\Database\Factories\InternshipOfferFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'company_profile_id',
        'title',
        'description',
        'wilaya',
        'start_date',
        'internship_type',
        'duration',
        'status',
        'max_students',
        'deadline',
    ];

    protected $casts = [
        'start_date' => 'date',
        'deadline' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-check status when retrieving
        static::retrieved(function ($offer) {
            $offer->autoUpdateStatus();
        });
    }

    /**
     * Get the company profile that posted the offer.
     */
    public function companyProfile()
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    /**
     * Get the skills required for this offer.
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'internship_offer_skill');
    }

    /**
     * Get the applications received for this offer.
     */
    public function applications()
    {
        return $this->hasMany(Application::class, 'internship_offer_id');
    }

    /**
     * Get accepted applications count.
     */
    public function acceptedApplicationsCount(): int
    {
        return $this->applications()
            ->whereIn('status', [Application::STATUS_ACCEPTED, Application::STATUS_VALIDATED])
            ->count();
    }

    /**
     * Check if offer has reached max students.
     */
    public function hasReachedMaxStudents(): bool
    {
        return $this->acceptedApplicationsCount() >= $this->max_students;
    }

    /**
     * Check if offer deadline has passed.
     */
    public function hasDeadlinePassed(): bool
    {
        if (!$this->deadline) {
            return false;
        }
        return Carbon::now()->startOfDay()->gt($this->deadline);
    }

    /**
     * Auto-update status based on conditions.
     */
    public function autoUpdateStatus(): void
    {
        // If deadline passed, close permanently
        if ($this->hasDeadlinePassed() && $this->status === self::STATUS_OPEN) {
            $this->status = self::STATUS_CLOSED;
            $this->saveQuietly();
            return;
        }

        // If max students reached, close
        if ($this->hasReachedMaxStudents() && $this->status === self::STATUS_OPEN) {
            $this->status = self::STATUS_CLOSED;
            $this->saveQuietly();
            return;
        }

        // If spots available and deadline not passed, reopen if closed
        if (!$this->hasReachedMaxStudents() && !$this->hasDeadlinePassed() && $this->status === self::STATUS_CLOSED) {
            $this->status = self::STATUS_OPEN;
            $this->saveQuietly();
        }
    }

    /**
     * Check if offer is open for applications.
     */
    public function isOpen(): bool
    {
        $this->autoUpdateStatus();
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Scope for open offers.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope for closed offers.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }
}
