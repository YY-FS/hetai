<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4UserFilterType extends BaseModel
{
    protected $table='platv4_user_filter_type';
    protected $connection = 'plat';


    public static function getFilterNType()
    {
        $result = DB::connection('plat')->table('platv4_user_filter_type as uft')
            ->leftJoin('platv4_user_filters as uf','uft.id','=','uf.user_filter_type_id')
            ->select([
                        'uft.alias',
                        'uft.name as label',
                        DB::connection('plat')->raw('GROUP_CONCAT(uf.id ORDER BY uf.sort separator \'|\' ) as uf_id'),
                        DB::connection('plat')->raw('GROUP_CONCAT(uf.name ORDER BY uf.sort separator \'|\') as uf_name ')
                    ])
            ->groupBy('uft.id')
            ->orderBy('uft.sort')
            ->get();

        return $result;

    }
}