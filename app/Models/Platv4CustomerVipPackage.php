<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4CustomerVipPackage extends BaseModel
{
    protected $connection = 'plat';
    public $timestamps = false;

    public function customerVip()
    {
        return $this->hasOne(Platv4CustomerVip::class, 'id', 'customer_vip_id');
    }
}
