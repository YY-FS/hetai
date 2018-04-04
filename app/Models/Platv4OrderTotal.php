<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-4-4
 * Time: 上午10:20
 */
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4OrderTotal extends BaseModel
{
    protected $table = 'platv4_order_total';
    protected $connection = 'plat';
    public $timestamps = false;

    public static function paymentDetail($id)
    {
        return DB::connection('plat')->table('platv4_order_total')
            ->where([
                ['status','=',1],
                ['oid','=',$id]
            ])
            ->orderBy('sort','desc')//前端为倒序插入
            ->select([
                'title',
                'value'
            ])->get()->toArray();
    }
}