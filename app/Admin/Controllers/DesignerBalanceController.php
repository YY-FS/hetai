<?php

namespace App\Admin\Controllers;

use App\Models\Platv4DesignerBalanceTmp;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataForm\DataForm;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\Url;
use App\Models\Platv4DesignerBalance;

class DesignerBalanceController extends BaseController
{

    public function index()
    {

        $title = '设计师流水账';
        $filter = DataFilter::source(new Platv4DesignerBalance());

        $filter->add('designer_id', '设计师id', 'text');
        $filter->add('balance_type', '账目类型', 'select')->options(['' => '全部类型'] + Platv4DesignerBalance::$balanceTypeText);
        $filter->add('order_time', '订单日期', 'daterange')->format('Y-m-d', 'zh-CN');

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);

        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', 'ID', true);
        $grid->add('designer_id', '设计师id', true);
        $grid->add('oid', '订单流水ID', true);
        $grid->add('product_id', '商品ID', true);
        $grid->add('withdrawal_id', '提现ID', true);
        $grid->add('balance_type', '类型', true);
        $grid->add('{!! $amount/100 !!}', '变动金额', true);
        $grid->add('{!! $balance/100 !!}', '余额', true);
        $grid->add('description', '描述', false);
        $grid->add('order_time', '订单时间', true);
        $grid->add('remark', '备注', false);
        $grid->add('create_time', '创建时间', true);

        $grid->edit('/designers/balance/edit', '操作','modify');

        $grid->orderBy('create_time', 'desc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.prefix') . '/designers/balance/create', '新增', 'TR', ['class' => 'btn btn-default']);

        $grid->row(function ($row) {
            $row->cell('balance_type')->value = Platv4DesignerBalance::$balanceTypeText[$row->data->balance_type];
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


    public function anyForm()
    {

        $form = DataForm::source(new Platv4DesignerBalance());

//        check 有结算未确认无法
        $check = Platv4DesignerBalanceTmp::count();
        if ($check) {
            $form->message("还有结算未确认，暂无法变动设计师账目");
            $form->link(config('admin.route.prefix') . '/designers/balance',"返回");
            return $form->view('rapyd.form', compact('form'));
        }

        $process = Input::get('process', 0);
        if ($process) {
            empty(Input::get('product_id', null)) && Input::offsetSet('product_id', 'default');
            empty(Input::get('description', null)) && Input::offsetSet('description', 'default');
        }

        $script = '$.get("/designers/jx_balance?designer_id=" + $("input[name=\'designer_id\']").val(),
			function (data) {
				console.log(data.data);
				$("input[name=\'old_balance\']").val((data.data.balance / 100));
				$("input[name=\'show_balance\']").val((data.data.balance / 100));
				$("input[name=\'balance\']").val(data.data.balance);
            });';

        $changeScript = '
            var change = $("input[name=\'show_amount\']").val() * 100;
            var old = $("input[name=\'balance\']").val();
            var balance = parseInt(old) + parseInt(change);
            $("input[name=\'amount\']").val(change);
            $("input[name=\'balance\']").val(balance);
            $("input[name=\'show_balance\']").val(balance / 100);
            ';


        $form->label('设计师余额信息');
        $form->link(config('admin.route.prefix') . "/designers/balance", "列表", "TR")->back();

        $form->add('designer_id', '设计师ID', 'text')
            ->rule("required|min:2")
            ->placeholder("请输入 设计师ID")
            ->onchange($script);

        $form->add('show_amount', '变动金额', 'text')
            ->rule("required")
            ->placeholder("请输入 变动金额（单位：元）")
            ->onchange($changeScript);

        $form->add('oid', '订单流水ID', 'text')
            ->placeholder("【可选】请输入 订单流水ID");

        $form->add('product_id', '商品ID', 'text')
            ->placeholder("【可选】请输入 商品ID");

        $form->add('withdrawal_id', '提现ID', 'text')
            ->placeholder("【可选】请输入 提现ID");

        $form->add('balance_type', '类型', 'hidden')->insertValue(Platv4DesignerBalance::BALANCE_TYPE_ADMIN);

        $form->add('amount', '变动金额', 'hidden');

        $form->add('old_balance', '原余额', 'text')
            ->attributes(['readOnly' => true])
            ->placeholder("设计师原余额");

        $form->add('show_balance', '余额', 'text')
            ->attributes(['readOnly' => true])
            ->placeholder("设计师变动后的余额");

        $form->add('balance', '余额', 'hidden')
            ->attributes(['readOnly' => true])
            ->placeholder("设计师变动后的余额");

        $form->add('description', '描述', 'text')
//            ->rule("required|min:2")
            ->placeholder("【可选】请输入 描述");

        $form->add('order_time', '订单时间', 'hidden')->insertValue(date('Y-m-d'));
        $form->add('remark', '备注', 'hidden')->insertValue(Admin::user()->id);

        $form->saved(function () use ($form) {
                $form->message("新建成功");
                $form->link(config('admin.route.prefix') . '/designers/balance',"返回");
        });

        $form->submit('保存');

        return $form->view('rapyd.form', compact('form'));
    }


    public function jxDesignerBalance()
    {
        $this->requestValidate([
            'designer_id' => 'required'
        ]);

        $result = Platv4DesignerBalance::where('designer_id', Input::get('designer_id'))->orderBy('id', 'DESC')->first();
        if (empty($result)) {
            $result = [
                'balance' => 0
            ];
        }
        return $this->respData($result);
    }

}
