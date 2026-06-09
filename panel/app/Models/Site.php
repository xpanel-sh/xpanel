<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'server_node_id',
        'domain',
        'project_type',
        'web_server',
        'php_version',
        'status',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function managedDatabases()
    {
        return $this->hasMany(ManagedDatabase::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }
}
