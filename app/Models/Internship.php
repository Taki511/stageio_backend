<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Internship extends Model
{
    /** @use HasFactory<\Database\Factories\InternshipFactory> */
    use HasFactory;

    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'application_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-check status when retrieving
        static::retrieved(function ($internship) {
            $internship->autoCompleteIfEnded();
        });
    }

    /**
     * Get the application that led to this internship.
     */
    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * Get the internship agreement for this internship.
     */
    public function agreement()
    {
        return $this->hasOne(InternshipAgreement::class, 'internship_id');
    }

    /**
     * Check if internship has ended.
     */
    public function hasEnded(): bool
    {
        return Carbon::now()->startOfDay()->gt($this->end_date);
    }

    /**
     * Auto-complete internship if end date has passed.
     */
    public function autoCompleteIfEnded(): void
    {
        if ($this->status === self::STATUS_ONGOING && $this->hasEnded()) {
            $this->status = self::STATUS_COMPLETED;
            $this->saveQuietly();
        }
    }

    /**
     * Check if internship is completed.
     */
    public function isCompleted(): bool
    {
        $this->autoCompleteIfEnded();
        return $this->status === self::STATUS_COMPLETED;
    }
}
