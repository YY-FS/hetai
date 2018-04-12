<?php

namespace App\Admin\Controllers;

use App\Models\Platv4UserFilter;
use App\Models\Platv4UserFilterType;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\Url;

class UserFilterController extends BaseController
{
    public function index()
    {
        $title = "用户筛选器";
        $filter = DataFilter::source(Platv4UserFilter::rapydGrid());
        $filter->add('type_name', '类型', 'select')->options(['' => '全部类型'] + Platv4UserFilterType::pluck('name', 'id')->toArray())
            ->scope(function ($query, $value) {
                return $value ? $query->where('f.user_filter_type_id', $value) : $query;
            });
        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id','ID',false);
        $grid->add('type_name','类型',false);
        $grid->add('alias','别名',false);
        $grid->add('name','区间',false);
        $grid->add('remark','备注',false);
        $grid->add('total_user','总用户数',true);
        $grid->add('duration','持续时间',true);
        $grid->add('rise_time','生成时间',true);

        $url = new Url();
        $grid->link($url->append('export',1)->get(),"导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->paginate(self::DEFAULT_PER_PAGE);
        $grid->build();
        return view('rapyd.filtergrid',compact('filter','grid','title'));
    }
}