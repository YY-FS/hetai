<?php

namespace App\Admin\Controllers;

use App\Models\Platv4CustomerVip;
use App\Models\Platv4CustomerVipPackage;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\Facades\Rapyd;
use Zofe\Rapyd\Url;

class CustomerVipController extends BaseController
{
    protected $route = '/customer_vips';

    public function index()
    {
        $title = '用户会员';
        $filter = DataFilter::source(new Platv4CustomerVip());

        $filter->add('id', 'ID', 'text');
        $filter->add('status', '状态', 'select')->options(['' => '全部状态'] + Platv4CustomerVip::$commonStatusText);

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);

        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', 'ID', true);
        $grid->add('alias', '版本', true);
        $grid->add('name', '名称', true);
        $grid->add('{!! $price/100 !!}', '基准单价', true);
        $grid->add('enable_maka', 'MAKA模板', true);
        $grid->add('enable_poster', '海报模板', true);
        $grid->add('enable_danye', '单页模板', true);
        $grid->add('enable_video', '视频模板', true);
        $grid->add('enable_font', '字体', true);
        $grid->add('enable_logo', '去logo', true);
        $grid->add('enable_material', '素材、板式', true);
        $grid->add('sort', '排序', true);
        $grid->add('status', '状态', true);

        $grid->add('packages','查看', false);
        $grid->add('operation','操作', false);

        $grid->orderBy('id', 'asc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.prefix') . $this->route . '/edit', '新增', 'TR', ['class' => 'btn btn-default']);

        $cleanCache = "layer.confirm( '确定清理缓存吗？！',{ btn: ['确定','取消'] }, function(){ 
            $.get('"  . $this->route . "/cache',
                function (data) {
                    console.log(data);
                    if(data.success === true) {
                        layer.msg('清理成功');
                    } else {
                        layer.msg('清理失败');
                    }
                });
            })";
        $grid->button('清缓存', 'TR', ['class' => 'btn btn-warning', 'onclick' => $cleanCache]);

        $grid->row(function ($row) {

            $row->cell('status')->value = Platv4CustomerVip::$commonStatusText[$row->data->status];

            $status = Platv4CustomerVip::COMMON_STATUS_OFFLINE;
            $statusText = '下线';
            if ($row->data->status == Platv4CustomerVip::COMMON_STATUS_NORMAL) {
                $row->cell('status')->style("color: #333333;");
            }

            if ($row->data->status == Platv4CustomerVip::COMMON_STATUS_OFFLINE) {
                $row->cell('status')->style("color: #CECECE;");
                $status = Platv4CustomerVip::COMMON_STATUS_NORMAL;
                $statusText = '上线';
            }

            $row->cell('packages')->value = "<a class='btn btn-success' href='" . config('admin.route.prefix') . $this->route . "/packages?search=1&customer_vip_id=" . $row->data->id . "'>价格包</a>";
            $row->cell('operation')->value = $this->getEditBtn($row->data->id) . $this->getStatusBtn($row->data->id, $status, $statusText);
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
            Platv4CustomerVip::where('id', $deleteId)->update(['status' => Platv4CustomerVip::COMMON_STATUS_DELETE]);
            return redirect($this->route);
        }

//        上下线
        if (!is_null($status = Input::get('status', null)) && !is_null($id = Input::get('id', null))) {
            Platv4CustomerVip::where('id', $id)->update(['status' => $status]);
            return redirect($this->route);
        }

        $edit = DataEdit::source(new Platv4CustomerVip());

        $edit->label('用户会员信息');
        $edit->link(config('admin.route.prefix') . $this->route, "列表", "TR")->back();

        $edit->add('alias', '版本', 'text')
            ->rule("required|min:2")
            ->placeholder("请输入 版本");

        $edit->add('name', '名称', 'text')
            ->rule("required|min:2")
            ->placeholder("请输入 名称");

        $edit->add('price', '基准单价（单位：分）', 'text')
            ->rule("required")
            ->placeholder("请输入 基准单价（单位：分）");

        $edit->add('corner', '角标', 'select')->options(['normal', 'recommend']);

        $edit->add('enable_maka', 'MAKA模板', 'select')->options([0 => '不可用', 1 => '可租用']);
        $edit->add('enable_danye', '单页模板', 'select')->options([0 => '不可用', 1 => '可租用']);
        $edit->add('enable_poster', '海报模板', 'select')->options([0 => '不可用', 1 => '可租用']);
        $edit->add('enable_video', '视频模板', 'select')->options([0 => '不可用', 1 => '可租用']);
        $edit->add('enable_font', '字体', 'select')->options([0 => '不可用', 1 => '可租用']);
        $edit->add('enable_logo', '去logo', 'select')->options([0 => '不可用', 1 => '可租用']);
        $edit->add('enable_material', '素材、板式', 'select')->options([0 => '不可用', 1 => '可租用']);

        $edit->add('sort', '排序', 'text')
            ->rule("required")
            ->insertValue(99)
            ->placeholder("请输入 排序");

        $edit->add('status', '状态', 'select')->options(Platv4CustomerVip::$commonStatusText);

//        $edit->saved(function () use ($edit) {
//
//        });

        $edit->build();

        return $edit->view('rapyd.edit', compact('edit'));
    }

    public function cleanCache()
    {
        Redis::del('CUSTOMER_VIP_LIST:DEVICE:ALL:MODULO:ALL');
        Redis::del('CUSTOMER_VIP_LIST:DEVICE:ios:MODULO:ALL');
        Redis::del('CUSTOMER_VIP_LIST:DEVICE:pc:MODULO:ALL');
        Redis::del('CUSTOMER_VIP_LIST:DEVICE:wap:MODULO:ALL');
        Redis::del('CUSTOMER_VIP_LIST:DEVICE:android:MODULO:0');
        Redis::del('CUSTOMER_VIP_LIST:DEVICE:android:MODULO:1');
        Redis::del('CUSTOMER_VIP_LIST:DEVICE:android:MODULO:2');
        Redis::del('CUSTOMER_VIP_PACKAGE_LIST');

        return $this->respData();
    }

    public function package()
    {
        $this->route = '/customer_vips/packages';
        $title = '用户会员价格包';
        $filter = DataFilter::source(Platv4CustomerVipPackage::with('customerVip'));

        $filter->add('id', 'ID', 'text');
        $filter->add('customer_vip_id', '用户会员', 'select')->options(['' => '全部'] + Platv4CustomerVip::all()->pluck('name', 'id')->toArray());
        $filter->add('status', '状态', 'select')->options(['' => '全部状态'] + Platv4CustomerVipPackage::$commonStatusText);

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);

        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', 'ID', true);
        $grid->add('customerVip.name', '会员版本', true);
        $grid->add('name', '名称', true);
        $grid->add('quantity', '月份', true);
        $grid->add('{!! $total/100 !!}', '价格', true);
        $grid->add('auto_renewal', '自动续费', true);
        $grid->add('sort', '排序', true);
        $grid->add('status', '状态', true);
        $grid->add('corner', '角标', true);
        $grid->add('device', '终端', true);
        $grid->add('modulo', '灰度', true);

        $grid->add('operation','操作', false);

        $grid->orderBy('customer_vip_id', 'asc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.prefix') . $this->route . '/edit', '新增', 'TR', ['class' => 'btn btn-default']);

        $grid->row(function ($row) {

            $row->cell('status')->value = Platv4CustomerVipPackage::$commonStatusText[$row->data->status];

            $status = Platv4CustomerVipPackage::COMMON_STATUS_OFFLINE;
            $statusText = '下线';
            if ($row->data->status == Platv4CustomerVipPackage::COMMON_STATUS_NORMAL) {
                $row->cell('status')->style("color: #333333;");
            }

            if ($row->data->status == Platv4CustomerVipPackage::COMMON_STATUS_OFFLINE) {
                $row->cell('status')->style("color: #CECECE;");
                $status = Platv4CustomerVipPackage::COMMON_STATUS_NORMAL;
                $statusText = '上线';
            }

            $row->cell('operation')->value = $this->getEditBtn($row->data->id) . $this->getStatusBtn($row->data->id, $status, $statusText);
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

    public function packageEdit()
    {
        $this->route = '/customer_vips/packages';
//        软删除
        $deleteId = Input::get('delete', null);
        if ($deleteId) {
            Platv4CustomerVipPackage::where('id', $deleteId)->update(['status' => Platv4CustomerVipPackage::COMMON_STATUS_DELETE]);
            return redirect($this->route);
        }

//        上下线
        if (!is_null($status = Input::get('status', null)) && !is_null($id = Input::get('id', null))) {
            Platv4CustomerVipPackage::where('id', $id)->update(['status' => $status]);
            return redirect($this->route);
        }

        $edit = DataEdit::source(new Platv4CustomerVipPackage());

        $edit->label('用户会员价格包信息');
        $edit->link(config('admin.route.prefix') . $this->route, "列表", "TR")->back();

        $edit->add('customer_vip_id', '版本', 'select')->options(Platv4CustomerVip::all()->pluck('name', 'id'));

        $edit->add('name', '名称', 'text')
            ->rule("required|min:2")
            ->placeholder("请输入 名称");

        $edit->add('quantity', '月份', 'text')
            ->rule("required")
            ->placeholder("请输入 月份");

        $edit->add('total', '价格（单位：分）', 'text')
            ->rule("required")
            ->placeholder("请输入 价格（单位：分）");

        $edit->add('corner', '角标', 'select')->options(['normal', 'recommend']);

        $edit->add('auto_renewal', '自动续费', 'select')->options([0 => '否', 1 => '是']);
        $edit->add('device', '终端', 'select')->options(['ios', 'wap', 'android', 'pc']);
        $edit->add('modulo', '灰度', 'select')->options([0, 1, 2]);

        $edit->add('sort', '排序', 'text')
            ->rule("required")
            ->insertValue(99)
            ->placeholder("请输入 排序");

        $edit->add('status', '状态', 'select')->options(Platv4CustomerVipPackage::$commonStatusText);

        $edit->build();

        return $edit->view('rapyd.edit', compact('edit'));
    }


}
