<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-3-8
 * Time: 下午4:36
 */
namespace App\Http\Controllers;

use App\Models\HtSensor;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataGrid\DataGrid;

class IndexController extends BaseController
{
    public function index()
    {
//        $where = [];
//        if (!Input::get('search', null)) {
//            $date = date('Y-m-d', time());
//            //默认取出当天的订单
//            $where = [
//                ['pay_date', '=', $date]
//            ];
//        }
exit('666');
        $title = "合泰传感器数据";
        $filter = DataFilter::source(HtSensor::rapydGrid());

        $filter->add('machine', '机器', 'select')->option([''=>'全部机器']+HtSensor::$machineText);
        $filter->add('created_at', '采集时间', 'daterange')
            ->scope(function ($query, $value) {
                $value = explode('|', $value);
                if (!empty($value[0]))
                    $query = $query->where('created_at', '>=', $value[0]);
                if (!empty($value[1])) {
                    $value[1] = date('Y-m-d', strtotime($value[1]) + 24 * 60 * 60);//增加一天，date_paid比后一天的00:00:00小就好
                    $query = $query->where('created_at', '<=', $value[1]);
                }
                return $query;
            })->format('Y-m-d', 'zh-CN');
        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', '流水ID', false);
        $grid->add('created_at', '采集时间', true);
        $grid->add('machine', '机器', false);
        $grid->add('temperature', '温度', true);
        $grid->add('humidity', '湿度', true);
        $grid->add('pressure', '压力', true);
        $grid->add('airquality', '空气质量', true);
        $grid->row(function($row){
            $row->cell('machine')->value = $row->data->machine.'号机';
        });
        $grid->orderBy('created_at', 'desc');

        $grid->paginate(self::DEFAULT_PER_PAGE);
        $grid->build();
        return view('rapyd.filtergrid', compact('filter', 'grid', 'title'));
    }
}

