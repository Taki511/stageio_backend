<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicationFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REFUSED = 'refused';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'student_id',
        'internship_offer_id',
        'student_cv_id',
        'application_date',
        'status',
        'is_confirmed',
        'confirmed_at',
        'cover_letter',
    ];

    protected $casts = [
        'application_date' => 'date',
        'is_confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($application) {
            // If application is confirmed, auto-cancel other accepted applications
            if ($application->is_confirmed && $application->getOriginal('is_confirmed') === false) {
                $application->autoCancelOtherApplications();
            }
        });
    }

    /**
     * Auto-cancel other accepted applications when one is confirmed.
     */
    public function autoCancelOtherApplications(): void
    {
        self::where('student_id', $this->student_id)
            ->where('id', '!=', $this->id)
            ->where('status', self::STATUS_ACCEPTED)
            ->where('is_confirmed', false)
            ->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Cancel this application and update offer status if needed.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
        
        // Update offer status if it was closed
        $offer = $this->internshipOffer;
        if ($offer && $offer->status === InternshipOffer::STATUS_CLOSED) {
            $offer->autoUpdateStatus();
        }
    }

    /**
     * Get the student who submitted the application.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the internship offer applied for.
     */
    public function internshipOffer()
    {
        return $this->belongsTo(InternshipOffer::class, 'internship_offer_id');
    }

    /**
     * Get the CV used in the application.
     */
    public function studentCv()
    {
        return $this->belongsTo(StudentCV::class, 'student_cv_id');
    }

    /**
     * Get the internship created from this application (if validated).
     */
    public function internship()
    {
        return $this->hasOne(Internship::class, 'application_id');
    }
}
