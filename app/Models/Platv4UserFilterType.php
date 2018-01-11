<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4UserFilterType extends BaseModel
{
    protected $table='platv4_user_filter_type';
    protected $connection = 'plat';

    const GROUP_MODE_AUTO = 'auto';
    const GROUP_MODE_HAND = 'manual';

    public static $groupMode = [
        self::GROUP_MODE_AUTO => '自动分群',
        self::GROUP_MODE_HAND => '手动分群',
    ];

    public static function rapydEdit()
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