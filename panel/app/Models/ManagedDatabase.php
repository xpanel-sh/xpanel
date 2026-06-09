<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagedDatabase extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'username',
        'password',
        'engine',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
