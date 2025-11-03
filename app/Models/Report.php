<?php
// app/Models/Report.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assigned_to',
        'approved_by_rt',
        'rt_approved_at',
        'rt_notes',
        'rt_recommended',
        'title',
        'complaint_description',
        'location_description',
        'report_date',
        'report_time',
        'photo',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'report_date' => 'date',
        'report_time' => 'datetime:H:i',
        'rt_approved_at' => 'datetime',
        'rt_recommended' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function history()
    {
        return $this->hasMany(ReportHistory::class);
    }

    public function assignedPetugas()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approvedByRT()
    {
        return $this->belongsTo(User::class, 'approved_by_rt');
    }

    // Scope untuk filter berdasarkan status
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOnHold($query)
    {
        return $query->where('status', 'on_hold');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeDone($query)
    {
        return $query->where('status', 'done');
    }

    // Scope untuk laporan yang sudah direkomendasikan RT
    public function scopeRTRecommended($query)
    {
        return $query->where('rt_recommended', true);
    }

    // Accessor untuk mendapatkan URL foto lengkap
    public function getPhotoUrlAttribute()
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }
}