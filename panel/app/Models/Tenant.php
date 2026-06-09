<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'domain', 'user_id', 'plan_id', 'status'];

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function emailAccounts()
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function dnsRecords()
    {
        return $this->hasMany(DnsRecord::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(HostingPlan::class, 'plan_id');
    }
}
