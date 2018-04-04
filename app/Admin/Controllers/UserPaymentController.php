<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-3-8
 * Time: 下午4:36
 */
namespace App\Admin\Controllers;

use App\Models\Platv4PayPlatform;
use App\Models\Platv4Terminal;
use App\Models\Platv4UserPayment;
use Illuminate\Support\Facades\Input;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\Url;

class UserPaymentController extends BaseController
{
    public function index()
    {
        $this->route = '/payment';
        $where = [];
        if (!Input::get('search', null)) {
            $date = date('Y-m-d', time());
            //默认取出当天的订单
            $where = [
                ['pay_date', '=', $date]
            ];
        }

        $title = "用户支付管理";
        $filter = DataFilter::source(Platv4UserPayment::rapydGrid($where));

        $filter->add('order_id', '订单Id', 'text')
            ->scope(function ($query, $value) {
                return $value ? $query->where('up.order_id', $value) : $query;
            });
        $filter->add('uid', '用户Id', 'text')
            ->scope(function ($query, $value) {
                return $value ? $query->where('up.uid', $value) : $query;
            });
        $filter->add('order_type', '支付类型', 'select')->options(['' => '全部类型'] + Platv4PayPlatform::pluck('name', 'alias')->toArray())
            ->scope(function ($query, $value) {
                return $value ? $query->where('up.order_type', $value) : $query;
            });
        $filter->add('status', '状态', 'select')->options(['' => '全部状态'] + Platv4UserPayment::$statusText)
            ->scope(function ($query, $value) {
                return $value ? $query->where('up.status', $value) : $query;
            });
        $filter->add('pay_source', '设备', 'select')->options(['' => '全部设备'] + Platv4Terminal::pluck('description', 'name')->toArray())
            ->scope(function ($query, $value) {
                return $value ? $query->where('up.pay_source', $value) : $query;
            });
        $filter->add('bundle_id', '包Id', 'text')
            ->scope(function ($query, $value) {
                return $value ? $query->where('up.bundle_id', $value) : $query;
            });
        $filter->add('app_version', '版本', 'text')
            ->scope(function ($query, $value) {
                return $value ? $query->where('up.app_version', $value) : $query;
            });
        $filter->add('date_paid', '支付时间', 'daterange')
            ->scope(function ($query, $value) {
                $value = explode('|', $value);
                if (!empty($value[0]))
                    $query = $query->where('up.date_paid', '>=', $value[0]);
                if (!empty($value[1])) {
                    $value[1] = date('Y-m-d', strtotime($value[1]) + 24 * 60 * 60);//增加一天，date_paid比后一天的00:00:00小就好
                    $query = $query->where('up.date_paid', '<=', $value[1]);
                }
                return $query;
            })->format('Y-m-d', 'zh-CN');
        $filter->add('product_id', '商品Id', 'text')
            ->scope(function ($query, $value) {
                return $value ? $query->where('up.product_id', $value) : $query;
            });
        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', '流水ID', false);
        $grid->add('order_id', '订单ID', false);
        $grid->add('uid', '用户ID', false);
        $grid->add('type_name', '支付类型', false);
        $grid->add('order_amount', '订单原价', false);
        $grid->add('pay_amount', '支付价', false);
        $grid->add('status', '状态', false);
        $grid->add('description', '设备', false);
        $grid->add('pay_channel', '来源', false);
        $grid->add('bundle_id', '包', false);
        $grid->add('app_version', '版本', false);
        $grid->add('date_paid', '支付时间', true);
        $grid->add('create_time', '创建时间', true);
        $grid->add('product_id', '商品ID', false);
        $grid->add('product_name', '商品名', false);
        $grid->add('product_quantity', '数量', false);
        $grid->add('product_price', '单价', false);
        $grid->add('product_total', '总价', false);
        $grid->add('product_purpose', '类型商品', false);
        $grid->add('operation', '操作', false);

        $grid->orderBy('create_time', 'desc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);

        //改变status
        $grid->row(function ($row) {
            $row->cell('status')->value = Platv4UserPayment::$statusText[$row->data->status];
            $options = [
                'btn_class' => 'btn btn-primary',
                'btn_text' => '查看详情'
            ];
            $link = config('admin.route.prefix') . $this->route . "/edit?modify=" . $row->data->id;
            $row->cell('operation')->value = $this->getFrameBtn($link, $options, false,400,500);
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
        //配置保存和撤销改动的位置
        $edit = DataEdit::source(new Platv4UserPayment());
        $edit->label('订单详情');
        return $edit->view('userpayment.frameEdit',compact('edit'));
    }
}

