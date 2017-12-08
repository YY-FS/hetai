<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4CustomerVipDiscount extends BaseModel
{
    protected $connection = 'plat';
    public $timestamps = false;

    public static function rapydGrid()
    {
        $result = DB::connection('plat')->table('platv4_customer_vip_discounts AS d')
            ->leftJoin('platv4_terminals AS t', 't.customer_vip_discount_id', '=', 'd.id')
            ->leftJoin('platv4_customer_vip_discount_types AS g', 'd.type', '=', 'g.name')
            ->select(
                'd.*',
                'g.description AS type_name',
                DB::connection('plat')->raw('GROUP_CONCAT(t.name) AS terminal')
            )
            ->where('d.status', '>=', 0);

        $result->groupBy('d.id');

        return $result;

    }
}
