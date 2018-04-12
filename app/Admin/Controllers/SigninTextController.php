<?php

namespace App\Admin\Controllers;

use App\Models\Platv4SigninText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\Url;

class SigninTextController extends BaseController
{
    public function index()
    {
        $this->route = '/signin_text';
        $title = '日签文案管理';
        $filter = DataFilter::source(Platv4SigninText::rapydGrid());
        $filter->add('id', '文案ID', 'number');
        $filter->add('title', '文案内容', 'text')
            ->scope(function ($query, $value) {
                $query->where('text', 'like', "%{$value}%");
            });
        $filter->add('date', '显示日期', 'daterange')
            ->scope(function ($query, $value) {
                $value = explode('|', $value);
                if (!empty($value[0]))
                    $query = $query->where('date', '>=', $value[0]);
                if (!empty($value[1])) {
                    $value[1] = date('Y-m-d', strtotime($value[1] + 24 * 60 * 60));
                    $query = $query->where('date', '<=', $value[1]);
                }
                return $query;
            })->format('Y-m-d', 'zh-CN');
        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', 'ID', false);
        $grid->add('text', '文案', false);
        $grid->add('date', '显示日期', true);
        $grid->add('operation', '操作', false);
        $grid->row(function ($row) use ($grid) {
            $row->cell('operation')->value = $this->getEditBtn($row->data->id) . $this->getDeleteBtn($row->data->id);
        });
        //顶部按钮
        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.perfix') . $this->route . '/edit', '添加', 'TR', ['class' => 'btn btn-success']);

        $grid->paginate(self::DEFAULT_PER_PAGE);
        $grid->build();
        return view('rapyd.filtergrid', compact('filter', 'grid', 'title'));
    }

    public function anyEdit()
    {
        //软删除
        $deleteId = Input::get('delete', null);
        if ($deleteId) {
            Platv4SigninText::where('id', $deleteId)->update(['status' => Platv4SigninText::COMMON_STATUS_DELETE]);
            return redirect()->back();
        }

        $this->route = '/signin_text';
        $edit = DataEdit::source(new Platv4SigninText());
        $edit->link($this->route, '列表', 'TR')->back();

        //新增时默认上线状态
        if (!Input::get('modify', null)) $edit->model->status = 1;

        $edit->label('日签文案管理');
        $edit->add('date', '显示日期', 'date')->rule('required|unique:plat.platv4_signin_text,date,' . $edit->model->date . ',date');
        $edit->add('text', '文案', 'textarea')->rule('required');
        $edit->build();
        return $edit->view('rapyd.edit', compact('edit'));
    }
}