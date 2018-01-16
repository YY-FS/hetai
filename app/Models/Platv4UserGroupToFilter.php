<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4UserGroupToFilter extends BaseModel
{
    protected $table = 'platv4_user_group_to_filter';

    protected $connection = 'plat';
    public $timestamps = false;

    public static function getUserGroupFilter($userGroupId)
    {
        return DB::connection('plat')->table('platv4_user_group_to_filter AS g2f')
            ->leftJoin('platv4_user_filters AS f', 'g2f.user_filter_id', '=', 'f.id')
            ->leftJoin('platv4_user_filter_type AS ft', 'ft.id', '=', 'f.user_filter_type_id')
            ->select(
                DB::connection('plat')->raw('GROUP_CONCAT(g2f.user_filter_id ORDER BY f.total_user ASC) AS filter_ids'),
                DB::connection('plat')->raw('SUM(f.total_user) AS total_users'),
                'ft.alias AS filter_type_alias'
            )
            ->where('g2f.user_group_id', $userGroupId)
            ->groupBy('ft.alias')
            ->orderBy('total_users', 'ASC')
            ->get()
            ->toArray();
    }

}
