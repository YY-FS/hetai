<?php

namespace App\Admin\Controllers;

use App\Models\Platv4VipClass;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\Url;

class VipClassController extends BaseController
{
    protected $route = '/vipclass';

    public function index()
    {
        $title = '团队版';
        $filter = DataFilter::source(new Platv4VipClass());

        $filter->add('id', 'ID', 'text');
        $filter->add('status', '状态', 'select')->options(['' => '全部状态'] + Platv4VipClass::$commonStatusText);

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);

        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', 'ID', true);
        $grid->add('vipclass', '版本', true);
        $grid->add('trial_version', '试用版本', true);
        $grid->add('give_customer_vip_id', '购买赠送的用户会员', true);
        $grid->add('name', '名称', true);
//        $grid->add('{!! $yearprice/100 !!}', '价格', true);
        $grid->add('yearprice', '价格', true);
        $grid->add('description', '描述', false);
        $grid->add('remark', '备注', false);
        $grid->add('logo', 'logo', false);
        $grid->add('status', '状态', true);

        $grid->add('operation','操作', false);

        $grid->orderBy('id', 'asc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.prefix') . $this->route . '/edit', '新增', 'TR', ['class' => 'btn btn-default']);

        $grid->row(function ($row) {
            $row->cell('logo')->value = '<img style="width:50px;height:auto" src="' . $row->data->logo . '" />';

            $row->cell('status')->value = Platv4VipClass::$commonStatusText[$row->data->status];

            $status = Platv4VipClass::COMMON_STATUS_OFFLINE;
            $statusText = '下线';
            if ($row->data->status == Platv4VipClass::COMMON_STATUS_NORMAL) {
                $row->cell('status')->style("color: #333333;");
            }

            if ($row->data->status == Platv4VipClass::COMMON_STATUS_OFFLINE) {
                $row->cell('status')->style("color: #CECECE;");
                $status = Platv4VipClass::COMMON_STATUS_NORMAL;
                $statusText = '上线';
            }

            $row->cell('operation')->value = $this->getEditBtn($row->data->id) . $this->getStatusBtn($row->data->id, $status, $statusText) . $this->getDeleteBtn($row->data->id);
        });

        if (Input::get('export') == 1) {
            $grid->build();
            return $grid->buildCSV($title, 'Ymd');
        } else {
            $grid->paginate(self::DEFAULT_PER_PAGE);
            $grid->build();
            return view('rapyd.filtergrid', compact('filter', 'grid', 'title'));
        }

    }



    public function anyEdit()
    {
//        软删除
        $deleteId = Input::get('delete', null);
        if ($deleteId) {
            Platv4VipClass::where('id', $deleteId)->update(['status' => Platv4VipClass::COMMON_STATUS_DELETE]);
            return redirect($this->route);
        }

//        上下线
        if (!is_null($status = Input::get('status', null)) && !is_null($id = Input::get('id', null))) {
            Platv4VipClass::where('id', $id)->update(['status' => $status]);
            return redirect($this->route);
        }

        $edit = DataEdit::source(new Platv4VipClass());

        $edit->label('团队版信息');
        $edit->link(config('admin.route.prefix') . $this->route, "列表", "TR")->back();

        $edit->add('name', '名称', 'text')
            ->rule("required|min:2")
            ->placeholder("请输入 名称");

        $edit->add('yearprice', '年费', 'text')
            ->rule("required|min:2")
            ->placeholder("请输入 年费");

        $edit->add('description', '描述', 'textarea')
            ->attributes(array('rows' => 4))
            ->rule("required|min:2")
            ->placeholder("请输入 描述");

        $edit->add('status', '状态', 'select')->options(Platv4VipClass::$commonStatusText);

        $edit->add('remark', '备注', 'text')
            ->placeholder("备注");

        $edit->saved(function () use ($edit) {
//            清缓存
            Redis::del('QUERYSERVICE:VIP_CLASS_LIST');
            Redis::del('VIP_CLASS');
        });

        $edit->build();

        return $edit->view('rapyd.edit', compact('edit'));
    }


}
