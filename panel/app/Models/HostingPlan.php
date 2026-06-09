<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'max_sites',
        'max_databases',
        'storage_mb',
        'bandwidth_gb',
        'email_accounts',
        'monthly_price',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'monthly_price' => 'decimal:2',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'plan_id');
    }
}
