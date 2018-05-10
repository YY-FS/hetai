<?php

namespace App\Admin\Controllers;

use App\Models\Platv4HeadlineTag;
use App\Models\Platv4IndustryToHeadlineTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\Url;
use App\Models\Platv4Industry;
use Illuminate\Support\Facades\Redis;

class IndustryController extends BaseController
{

    public function index()
    {

        $title = '用户行业标签';
        $filter = DataFilter::source(new Platv4Industry());

        $filter->add('industry', '名称', 'text');
        $filter->add('display', '状态', 'select')->options(['' => '全部状态'] + Platv4Industry::$commonStatusText);

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);

        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', 'ID', true);
        $grid->add('industry', '名称', false);
        $grid->add('sort', '排序', true);
        $grid->add('display', '状态', true);
        $grid->add('icon', 'icon', true);
        $grid->add('headline_tags', '关联的头条标签', true);

        $grid->edit('/industries/edit', '操作','modify');

        $grid->orderBy('id', 'desc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.prefix') . '/industries/edit', '添加', 'TR', ['class' => 'btn btn-success']);

        $grid->row(function ($row) {
            $row->cell('display')->value = Platv4Industry::$commonStatusText[$row->data->display];

            if ($row->data->display == Platv4Industry::COMMON_STATUS_NORMAL) {
                $row->cell('display')->style("color: #333333;");
            }

            if ($row->data->display == Platv4Industry::COMMON_STATUS_OFFLINE) {
                $row->cell('display')->style("color: #CECECE;");
            }

            $row->cell('icon')->value = '<img style="width:30px;height:30px" src="' . $row->data->icon . '" />';

            $row->cell('headline_tags')->value = Platv4IndustryToHeadlineTag::getHeadlineTagsName($row->data->id);
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

        $id = Input::get('modify', 0);
        if ($id) {
            $tagList = Platv4IndustryToHeadlineTag::where('industry_id', $id)->get()->toArray();
            $tags = array_column($tagList, 'headline_tag_id');
            Input::offsetSet('tags', array_values($tags));   // 选中tags
        }

        $edit = DataEdit::source(new Platv4Industry());

        $edit->label('用户行业标签信息');
        $edit->link(config('admin.route.prefix') . "/industries", "列表", "TR")->back();

        $edit->add('industry', '名称', 'text')
            ->rule("required")
            ->placeholder("请输入 名称");
        $edit->add('sort', '排序', 'number')
            ->rule("required|integer")
            ->attributes(['min'=>0])
            ->placeholder("请输入 排序");
        $edit->add('display', '状态', 'radiogroup')
            ->option(Platv4Industry::COMMON_STATUS_NORMAL,Platv4Industry::$commonStatusText[Platv4Industry::COMMON_STATUS_NORMAL])
            ->option(Platv4Industry::COMMON_STATUS_OFFLINE,Platv4Industry::$commonStatusText[Platv4Industry::COMMON_STATUS_OFFLINE]);
        $edit->add('icon', 'icon', 'text')->extra('<img style="width:30px;height:30px" src="'. $edit->model->icon .'" />');
        $edit->add('tags', '标签', 'checkboxgroup')->options(Platv4HeadlineTag::where('status', Platv4HeadlineTag::COMMON_STATUS_NORMAL)->orderBy('sort', 'asc')->pluck('name', 'id'));

        $edit->saved(function () use ($edit) {
            // 清理缓存
            Redis::del('USER_INDUSTRY');

            $this->saveIndustryTag($edit->model->id, Input::get('tags'));
            $edit->model->save();
        });

        $edit->build();

        return $edit->view('rapyd.edit', compact('edit'));
    }

    private function saveIndustryTag($industryId, $tags)
    {
        if(empty($industryId)) return false;
        if(empty($tags)) return true;

        Platv4IndustryToHeadlineTag::where('industry_id', $industryId)->delete();
        $insertData = [];
        foreach ($tags as $tag) {
            $insertData[] = [
                'industry_id' => $industryId,
                'headline_tag_id' => $tag,
            ];
        }
        return DB::table('platv4_industry_to_headline_tag')->insert($insertData);
    }
}
