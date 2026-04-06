<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipAgreement extends Model
{
    /** @use HasFactory<\Database\Factories\InternshipAgreementFactory> */
    use HasFactory;

    protected $fillable = [
        'internship_id',
        'admin_id',
        'generated_date',
        'signature_status',
        'pdf_file',
    ];

    protected $casts = [
        'generated_date' => 'date',
        'signature_status' => 'boolean',
    ];

    /**
     * Get the internship associated with this agreement.
     */
    public function internship()
    {
        return $this->belongsTo(Internship::class, 'internship_id');
    }

    /**
     * Get the admin who generated the agreement.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
