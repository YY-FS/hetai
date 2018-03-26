<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-3-23
 * Time: 上午10:39
 */
namespace App\Admin\Controllers;

use App\Models\Platv4SigninImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\DataGrid\DataGrid;

class SigninImageController extends BaseController
{
    public function index()
    {
        $this->route = '/user/sign_image';
        $title = '日签图片管理';
        $filter = DataFilter::source(Platv4SigninImage::rapydGrid());
        $filter->add('id', '图片ID', 'text');
        $filter->add('title', '图片标题', 'text');
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
        $grid->add('status', '状态', false);
        $grid->add('date', '显示日期', true);
        $grid->add('thumb', '缩略图', false);
        $grid->add('title', '图片标题', false);
        $grid->add('share', '分享次数', true);
        $grid->add('created_at', '创建时间', true);
        $grid->add('operation', '操作', false);

        $grid->row(function ($row) use ($grid) {
            $row->cell('operation')->value = $this->getEditBtn($row->data->id);
            //状态更改
            $row->cell('status')->value = Platv4SigninImage::$commonStatusText[$row->data->status];
            if ($row->data->status == Platv4SigninImage::COMMON_STATUS_OFFLINE) {
                $row->cell('status')->style('color:red;');
                $row->cell('operation')->value .= $this->getStatusBtn($row->data->id, Platv4SigninImage::COMMON_STATUS_NORMAL, Platv4SigninImage::$commonStatusText[Platv4SigninImage::COMMON_STATUS_NORMAL]) . $this->getDeleteBtn($row->data->id);
            } elseif ($row->data->status == Platv4SigninImage::COMMON_STATUS_NORMAL) {
                $row->cell('operation')->value .= $this->getStatusBtn($row->data->id, Platv4SigninImage::COMMON_STATUS_OFFLINE, Platv4SigninImage::$commonStatusText[Platv4SigninImage::COMMON_STATUS_OFFLINE]);
            } else {
                $row->cell('status')->value = '发生错误，请检查各时间是否正确!!!';
            }
            //缩略图显示
            $row->cell('thumb')->value = "<img src='http://" . env('ALI_OSS_PLAT_VIEW_DOMAIN') . '/' . $row->data->thumb . "' height=40 width=auto>";
        });

        $grid->link(config('admin.route.perfix') . '/user/sign_image/edit', '添加', 'TR', ['class' => 'btn btn-primary']);
        $grid->paginate(self::DEFAULT_PER_PAGE);
        $grid->build();
        return view('rapyd.filtergrid', compact('filter', 'grid', 'title'));
    }

    public function anyEdit()
    {
        //软删除
        $deleteId = Input::get('delete', null);
        if ($deleteId) {
            Platv4SigninImage::where('id', $deleteId)->update(['status' => Platv4SigninImage::COMMON_STATUS_DELETE]);
            return redirect()->back();
        }
        //上下线
        if (!is_null($status = Input::get('status', null)) && !is_null($id = Input::get('id', null))) {
            Platv4SigninImage::where('id', $id)->update(['status' => $status]);
            return redirect()->back();
        }
        $edit = DataEdit::source(new Platv4SigninImage());
        $edit->label('日签图片管理');
        $edit->link(url()->previous(), '列表', 'TR')->back();
        $edit->add('title', '图片标题', 'text')->rule('required|max:40')->placeholder('请输入图片标题');
        $edit->add('date', '显示日期', 'date')->rule('required')->placeholder('输入格式如：2018-03-15');
        $edit->add('thumb', '日签图片', 'text')->attributes(['readOnly' => true])->rule('required');

        $edit->saved(function () use ($edit) {
            try {
                DB::connection('plat')->beginTransaction();
                //保存后进行status状态检测
                $date = Platv4SigninImage::find($edit->model->id);
                if (time()<strtotime($date->date))
                    $edit->model->status = Platv4SigninImage::COMMON_STATUS_NORMAL;
                else
                    $edit->model->status = Platv4SigninImage::COMMON_STATUS_OFFLINE;
                $edit->model->save();
                DB::connection('plat')->commit();
                return redirect('/signinimage');
            } catch (\Exception $e) {
                \Log::error('出现错误：' . $e->getMessage());
                DB::connection('plat')->rollback();
                return redirect('error')->with([
                    'to' => config('admin.route.prefix') . '/signinimage',
                    'msg' => '日签图片保存失败:' . $e->getMessage()
                ]);
            }
        });
        $edit->build();
        //图片存储位置
        $imageDir = 'U' . \Admin::user()->id;
        return $edit->view('signinimage.edit', compact('edit', 'imageDir'));
    }
}