<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Funnel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_active',
        'created_by',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the company that owns the funnel.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created the funnel.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active funnels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include funnels from the same company.
     */
    public function scopeFromCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Validation rules for funnel creation.
     */
    public static function validationRules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'created_by' => 'required|exists:users,id',
            'settings' => 'nullable|array',
        ];
    }

    /**
     * Validation rules for funnel update.
     */
    public static function updateValidationRules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ];
    }
}