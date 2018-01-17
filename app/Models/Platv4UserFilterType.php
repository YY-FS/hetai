<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4UserFilterType extends BaseModel
{
    protected $table='platv4_user_filter_type';
    protected $connection = 'plat';


    public static function rapydForm()
    {
        $result = DB::connection('plat')->table('platv4_user_filter_type as uft')
            ->leftJoin('platv4_user_filters as uf','uft.id','=','uf.user_filter_type_id')
            ->select([
                        'uft.alias',
                        'uft.name as label',
                        DB::raw('GROUP_CONCAT(uf.id ORDER BY uf.sort) as uf_id'),
                        DB::raw('GROUP_CONCAT(uf.name ORDER BY uf.sort) as uf_name')
                    ])
            ->groupBy('uft.id')
            ->orderBy('uft.sort');

        return $result;

    }
}