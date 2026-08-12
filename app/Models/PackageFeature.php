<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageFeature extends Model
{
    protected $fillable = ['package_id', 'label', 'sort_order'];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
