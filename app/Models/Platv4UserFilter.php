<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4UserFilter extends BaseModel
{
    protected $table = 'platv4_user_filters';
    protected $connection = 'plat';

    public $timestamps = false;

    public static function rapydGrid()
    {
        return DB::connection('plat')->table('platv4_user_filters AS f')
            ->leftJoin('platv4_user_filter_type AS ft','ft.id','=','f.user_filter_type_id')
            ->select([
                'f.id',
                'ft.name AS type_name',
                'f.alias',
                'f.name',
                'f.remark',
                'f.total_user',
                'f.duration',
                'f.rise_time'
            ]);
    }

    public static function getUserFilters()
    {
        $result = DB::connection('plat')->table('platv4_user_filter_type as ft')
            ->leftJoin('platv4_user_filters as f', 'ft.id', '=', 'f.user_filter_type_id')
            ->select([
                'ft.alias AS filter_type',
                'f.id AS filter_id',
                'f.user_filter_type_id',
                'f.alias AS filter_alias',
                'f.name AS filter_name',
                'f.remark AS filter_remark',
            ])
//            ->where('ft.id', 12) // debug
            ->orderBy('ft.sort', 'ASC')
            ->get()
            ->toArray();

        return $result;
    }


}