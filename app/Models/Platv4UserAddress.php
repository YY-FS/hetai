<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class Platv4UserAddress extends BaseModel
{
    protected $table = 'platv4_user_address';
    protected $connection = 'plat';
    public $timestamps = false;

    public static function getInvoiceAddress($id)
    {
        $result = DB::connection('plat')->table('platv4_user_address AS a')
            ->leftJoin('platv4_province AS p', 'a.province_id', '=', 'p.province_id')
            ->leftJoin('platv4_city AS c', 'a.city_id', '=', 'c.city_id')
            ->leftJoin('platv4_district AS d', 'a.district_id', '=', 'd.district_id')
            ->find($id, [
                'a.name',
                'p.name AS province_name',
                'c.name AS city_name',
                'd.name AS district_name',
                'a.address',//收件地址
                'a.contact'// 收件人联系方式
            ]);
        $address = [];
        //收件人
        $address['name']['title'] = '收件人';
        $address['name']['value'] = $result->name;
        //收件人
        $address['address']['title'] = '收件地址';
        $address['address']['value'] = $result->province_name.$result->city_name.$result->district_name.$result->address;
        //收件人
        $address['contact']['title'] = '收件人联系方式';
        $address['contact']['value'] = $result->contact;
        return $address;
    }
}