<?php
/**
 * Created by PhpStorm.
 * User: liangweibin
 * Date: 18/3/15
 * Time: 下午3:09
 */

namespace App\Admin\Controllers;



use App\Models\Platv4Layout;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redis;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataGrid\DataGrid;

class LayoutController extends BaseController
{
    public function index()
    {
        $this->route = '/banners/layouts';
        $title = '展示位列表';
        $filter = DataFilter::source(Platv4Layout::rapydGrid());
        $filter->add('id', '展示位id', 'text')->scope(function($query,$value){
             $q =  $value?$query->where('l.id',$value):$query;
             return $q;
        });

        $filter->add('name', '展示位名称', 'text')->scope(function($query,$value){
            return $value?$query->where('l.name',$value):$query;
        });
        $filter->add('style', '展示策略', 'select')->options([''=>'请选择'] + Platv4Layout::getStyle());

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);
        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->link(config('admin.route.prefix') . '/banners/layouts/create', '添加', 'TR', ['class' => 'btn btn-default']);
        $grid->add('id', 'ID', false);
        $grid->add('name', '展示位名称', false);
        $grid->add('alias', '展示位别名', false);
        $grid->add('type', '展示策略', false);
        $grid->add('style', '展示样式', false);
        $grid->add('content', '展示位差异属性', false);
        $grid->add('created_at', '创建时间', true);
        $grid->add('operation', '操作', false);

        $grid->row(function ($row) use ($grid){
            $row->cell('type')->value = Platv4Layout::$typeArr[$row->data->type];
            $row->cell('operation')->value = $this->getEditBtn($row->data->id);
            $row->cell('content')->value = Platv4Layout::secToTime($row->data->content);
        });

        $grid->orderBy('created_at', 'desc');
        $grid->paginate(self::DEFAULT_PER_PAGE);
        $grid->build();

        return view('rapyd.filtergrid', compact('grid', 'filter', 'title'));
    }

    public function anyEdit()
    {
        $edit = DataEdit::source(new Platv4Layout());
        $edit->label('展示位信息');
        if(Input::get('modify',null))
            Input::offsetSet('content',Platv4Layout::secToTime($edit->model->content));

        $edit->add('name','展示位名称','text');
        $edit->add('alias','展示位别名','text')->placeholder('如： app_poster');
        $edit->add('type','展示策略','select')->options([''=>'请选择']+Platv4Layout::$typeArr);
        $edit->add('style','展示样式','select')->options([''=>'请选择']+Platv4Layout::getStyle());
        $edit->add('content','展示位差异属性','text')
            ->placeholder('为展示位设置的倒计时请填写在此处，格式：48:30:00 (表示48小时30分)');

        $edit->saved(function() use ($edit){
            $edit->model->content = Platv4Layout::timeToSec($edit->model->content);
            $edit->model->save();
            return redirect('/banners/layouts/list');
        });

        $edit->build();
        return $edit->view('rapyd.edit',compact('edit'));
    }


}