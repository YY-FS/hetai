<?php

namespace App\Admin\Controllers;

use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Input;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\Url;
use App\Models\Platv4HeadlineTag;

class HeadlineTagController extends BaseController
{

    public function index()
    {

        $title = '头条标签';
        $filter = DataFilter::source(Platv4HeadlineTag::where('status', '>=', 0));

        $filter->add('name', '名称', 'text');
        $filter->add('status', '状态', 'select')->options(['' => '全部状态'] + Platv4HeadlineTag::$commonStatusText);

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);

        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', 'ID', true);
        $grid->add('name', '名称', false);
        $grid->add('sort', '排序', true);
        $grid->add('status', '状态', true);
        $grid->add('remark', '备注', true);

        $grid->add('operation','操作', false);

        $grid->orderBy('id', 'desc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.prefix') . '/headline/tags/edit', '新增', 'TR', ['class' => 'btn btn-default']);

        $grid->row(function ($row) {
            $row->cell('status')->value = Platv4HeadlineTag::$commonStatusText[$row->data->status];

            $status = Platv4HeadlineTag::COMMON_STATUS_OFFLINE;
            $statusText = '下线';
            if ($row->data->status == Platv4HeadlineTag::COMMON_STATUS_NORMAL) {
                $row->cell('status')->style("color: #333333;");
            }

            if ($row->data->status == Platv4HeadlineTag::COMMON_STATUS_OFFLINE) {
                $row->cell('status')->style("color: #CECECE;");
                $status = Platv4HeadlineTag::COMMON_STATUS_NORMAL;
                $statusText = '上线';
            }

            $btnEdit = "<a class='btn btn-primary' href='" . config('admin.route.prefix') . "/headline/tags/edit?modify=" . $row->data->id . "'>编辑</a>";
            $btnStatus = '<button class="btn btn-default" onclick="layer.confirm( \'确定' . $statusText . '吗？！\',{ btn: [\'确定\',\'取消\'] }, function(){ window.location.href = \'' . config('admin.route.prefix') . "/headline/tags/edit?status=" . $status . "&id=" . $row->data->id . '\'})">' . $statusText . '</button>';
            $btnDelete = '<button class="btn btn-danger" onclick="layer.confirm( \'确定删除吗？！\',{ btn: [\'确定\',\'取消\'] }, function(){ window.location.href = \'' . config('admin.route.prefix') . "/headline/tags/edit?delete=" . $row->data->id . '\'})">删除</button>';

            $row->cell('operation')->value =  $btnEdit . $btnStatus . $btnDelete;
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
            Platv4HeadlineTag::where('id', $deleteId)->update(['status' => Platv4HeadlineTag::COMMON_STATUS_DELETE]);
            return redirect('/headline/tags');
        }

//        上下线
        if (!is_null($status = Input::get('status', null)) && !is_null($id = Input::get('id', null))) {
            Platv4HeadlineTag::where('id', $id)->update(['status' => $status]);
            return redirect('/headline/tags');
        }

        $edit = DataEdit::source(new Platv4HeadlineTag());

        $edit->label('头条标签信息');
        $edit->link(config('admin.route.prefix') . "/headline/tags", "列表", "TR")->back();

        $edit->add('name', '标签名', 'text')
            ->rule("required|min:2")
            ->placeholder("请输入 标签名");

        $edit->add('sort', '排序', 'number')
            ->rule("required|min:1")
            ->placeholder("默认排序 99")->insertValue('99');

        $edit->add('status', '状态', 'select')->options(Platv4HeadlineTag::$commonStatusText);

        $edit->add('remark', '备注', 'text')
            ->placeholder("备注");
        $edit->build();

        return $edit->view('rapyd.edit', compact('edit'));
    }

}
