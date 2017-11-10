<?php

namespace App\Admin\Controllers;

use App\Models\Platv4HeadlineTag;
use App\Models\Platv4HeadlineToTag;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use OSS\OssClient;
use Zofe\Rapyd\DataEdit\DataEdit;
use Zofe\Rapyd\DataForm\DataForm;
use Zofe\Rapyd\DataGrid\DataGrid;
use Zofe\Rapyd\DataFilter\DataFilter;
use Zofe\Rapyd\Facades\Rapyd;
use Zofe\Rapyd\Url;
use App\Models\Platv4Headline;
use DiDom\Document;
use GuzzleHttp\Client;

if (!defined('LIBXML_HTML_NODEFDTD')) {
    define ('LIBXML_HTML_NODEFDTD', 4); // libxml 低版本缺少的常量，兼容服务器低版本
}

class HeadlineController extends BaseController
{

    public $ossBucket;
    public $ossAppId;
    public $ossAppSecret;
    public $ossEndpoint;
    public $ossViewDomain;
    public $htmlHeader;
    public $htmlFooter;

    const MAKA_EDIT_FLAG = 'MAKA-EDIT-FLAG'; //标识是否已自动加入头部，防止编辑重复

    public function __construct()
    {
        $this->ossBucket = env('ALI_OSS_PLAT_BUCKET');
        $this->ossAppId = env('ALI_OSS_PLAT_ACCESS_KEY');
        $this->ossAppSecret = env('ALI_OSS_PLAT_ACCESS_SECRET');
        $this->ossEndpoint = env('ALI_OSS_PLAT_ENDPOINT');
        $this->ossViewDomain = env('ALI_OSS_PLAT_VIEW_DOMAIN');

        $this->htmlHeader = '<!DOCTYPE html><html><head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=0">
                <meta name="apple-mobile-web-app-capable" content="yes">
                <meta name="apple-mobile-web-app-status-bar-style" content="black">
                <meta name="others" content="' . self::MAKA_EDIT_FLAG . '">
                <meta name="format-detection" content="telephone=no">' . $this->htmlStyle() . '</head>';
        $this->htmlFooter = '</html>';

    }

    public function index()
    {

        $title = '头条文章';
        $tips = '筛选标签时，只显示筛选的标签';
        $admin = ['administrator', 'operation-group'];
        $adminId = Admin::user()->id;
        (Admin::user()->inRoles($admin) == true) && $adminId = false;

        $filter = DataFilter::source(Platv4Headline::rapydGrid($adminId));

        $filter->add('title', '标题', 'text');
        $filter->add('author', '来源', 'text');
        $filter->add('status', '状态', 'select')->options(['' => '全部状态'] + Platv4Headline::$statusText);
        $filter->add('type', '类型', 'select')->options(['' => '全部类型'] + Platv4Headline::$typeText);
        $filter->add('style', '样式', 'select')->options(['' => '全部样式'] + Platv4Headline::$styleText);
        $filter->add('tags', '标签', 'select')
            ->options(['' => '全部标签'] + Platv4HeadlineTag::where('status', Platv4HeadlineTag::COMMON_STATUS_NORMAL)->pluck('name', 'id')->toArray())
            ->scope(function ($query, $value) {
                if ($value == '') {
                    return $query;
                } else {
                    return $query->where('h2t.headline_tag_id', $value);
                }
            });
        $filter->add('created_at', '创建日期', 'daterange')->scope(function ($query, $value) {
            $value = explode('|', $value);
            if (!empty($value[0])) {
                $query = $query->where('h.created_at', '>=', $value[0]);
            }

            if (!empty($value[1])) {
                $query = $query->where('h.created_at', '<=', $value[1]);
            }
            return $query;
        })->format('Y-m-d', 'zh-CN');

        (Admin::user()->inRoles($admin) == true) && $filter->add('admin_user_id', '创建人', 'select')->options(['' => '全部录入人'] + Platv4Headline::getAdminUser());

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);

        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', 'ID', true);
        $grid->add('title', '标题', false);
        $grid->add('type', '类型', true);
        $grid->add('style', '样式', true);
        $grid->add('thumb', '封面图', true);
        $grid->add('tags', '标签', false);
        $grid->add('author', '来源', true);
        $grid->add('created_at', '创建日期', true);
        $grid->add('status', '状态', true);
        $grid->add('admin_user', '创建人', 'admin_user_id');

        $grid->add('operation','操作', false);

        $grid->orderBy('created_at', 'desc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.prefix') . '/headlines/create?type=' . Platv4Headline::TYPE_ARTICLE, '新增文章', 'TR', ['class' => 'btn btn-default']);
        $grid->link(config('admin.route.prefix') . '/headlines/create?type=' . Platv4Headline::TYPE_VIDEO, '新增视频', 'TR', ['class' => 'btn btn-default']);

        $grid->row(function ($row) {
            $row->cell('type')->value = Platv4Headline::$typeText[$row->data->type];
            $row->cell('style')->value = Platv4Headline::$styleText[$row->data->style];
            $row->cell('status')->value = Platv4Headline::$statusText[$row->data->status];

            ($row->data->status == Platv4Headline::STATUS_NORMAL) && $row->cell('status')->style("color: #333333;");
            ($row->data->status == Platv4Headline::STATUS_OFFLINE) && $row->cell('status')->style("color: #CECECE;");

//            skin: 'layui-layer-rim', //加上边框
//            shadeClose: true,   //点击遮罩关闭
            if ($row->data->link) $link = $row->data->link;
            else $link = '(空)';

            $btnEditHtml = ''; // 视频无法编辑
            $contentType = 1;
            if ($row->data->type == Platv4Headline::TYPE_ARTICLE) {
                $btnEditHtml = "btn: ['编辑'],btn1: function(index, layero){
                                    //按钮【按钮一】的回调
                                    window.location.href = '" . config('admin.route.prefix') . "/headlines/html?id=" . $row->data->id . "&link=" . $row->data->link . "';
                                    //return false; //开启该代码可禁止点击该按钮关闭
                                 },";
                $link .= '?new=' . date('YmdHis');
                $contentType = 2; //layer content类型
            } else {
                $link = '<style>iframe {width: 100%}</style>' . $link;
                $link = htmlentities($link);
            }

            $btnPreview = "<button class=\"btn btn-primary\" onclick=\"layer.open({
                                                                                type: " . $contentType . ", 
                                                                                title: ['" . $row->data->title . "', false], 
                                                                                area: ['375px', '667px'], 
                                                                                " . $btnEditHtml . "
                                                                                shadeClose: true,
                                                                                scrollbar: false,
                                                                                content: '" . $link . "'
                                                                            })\">查看内容</button>";
            $btnEdit = "<a class='btn btn-default' href='" . config('admin.route.prefix') . "/headlines/edit?modify=" . $row->data->id . "'>编辑</a>";
            $btnDelete = '<button class="btn btn-danger" onclick="layer.confirm( \'确定删除吗？！\',{ btn: [\'确定\',\'取消\'] }, function(){ window.location.href = \'' . config('admin.route.prefix') . "/headlines/edit?delete=" . $row->data->id . '\'})">删除</button>';

            $row->cell('operation')->value = $btnPreview . $btnEdit . $btnDelete;

//            标签
            if (Input::get('tags', null)) {
                $row->cell('tags')->value = $row->data->tags . '...';
            }

            $thumb = '';
            foreach (explode(',', $row->data->thumb) as $img) {
                $thumb .= '<img style="height: 40px; width: auto; max-width: 60px; border-radius: 5px" src="' . $img . '" />&nbsp;';
            }
            $row->cell('thumb')->value = $thumb;
        });

        if (Input::get('export') == 1) {
            $grid->build();
            return $grid->buildCSV($title, 'Ymd');
        } else {
            $grid->paginate(self::DEFAULT_PER_PAGE);
            $grid->build();
            return view('rapyd.filtergrid', compact('filter', 'grid', 'title', 'tips'));
        }

    }


    public function anyForm()
    {
        $types = [Platv4Headline::TYPE_ARTICLE, Platv4Headline::TYPE_VIDEO];
        $this->requestValidate([
            'type' => 'required|in:"' . implode('","', $types) . '"'
        ]);
        $type = Input::get('type', Platv4Headline::TYPE_ARTICLE);

        $form = DataForm::source(new Platv4Headline());

        $form->label('头条信息');
        $form->link(config('admin.route.prefix') . "/headlines", "列表", "TR")->back();

        $form->add('title', '标题', 'text')
            ->rule("required|min:2")
            ->placeholder("请输入 标题");

        $form->add('author', '来源', 'text')
            ->rule("required|min:1")
            ->placeholder("请输入 来源");

        $form->add('type', '类型', 'hidden')->insertValue($type);

        $form->add('style', '样式', 'radiogroup')->options(Platv4Headline::$styleText)->rule("required");

        if ($type == Platv4Headline::TYPE_ARTICLE) {
            $form->add('link','内容','textarea')->rule("required")->attributes(['rows' => 15]);
            $form->link(config('admin.route.prefix') . "/headlines/create?type=" . Platv4Headline::TYPE_VIDEO, "新建视频", "TR");
        }
        else {
            $form->add('link','视频链接','text')->rule("required")->placeholder("请输入 视频链接");
            $form->link(config('admin.route.prefix') . "/headlines/create?type=" . Platv4Headline::TYPE_ARTICLE, "新建文章", "TR");
        }

        $form->add('thumb', '封面图', 'text')
            ->attributes(['readOnly' => true]);

        $form->add('tags', '标签', 'checkboxgroup')->options(Platv4HeadlineTag::where('status', Platv4HeadlineTag::COMMON_STATUS_NORMAL)->orderBy('sort', 'asc')->pluck('name', 'id'));

        $form->add('status', '状态', 'select')->options(Platv4Headline::$statusText);

        $form->add('admin_user_id', '检录员', 'hidden')->insertValue(Admin::user()->id);

        $form->saved(function () use ($form, $type) {
            try{
                $this->saveHeadlineTag($form->model->id, Input::get('tags'));

                if ($type == Platv4Headline::TYPE_ARTICLE) {
                    $imageDir = date('Ymd') . 'U' . Admin::user()->id;
                    $link = $this->dealWeChatImage($imageDir, $form->model->link);
                    $data = Platv4Headline::find($form->model->id);
                    if ($data) {
                        $data->link = $link;
                        $data->save();
                    }
                }

                $form->message("新建头条成功");
                $form->link(config('admin.route.prefix') . '/headlines',"返回");
            } catch (\Exception $exception) {
                $data = Platv4Headline::find($form->model->id);
                $data->link = '';
                $data->save();
                $form->message('** <h3>【ERROR】</h3>下载外链图片错误 **：' . $exception->getMessage());
                $form->link(config('admin.route.prefix') . '/headlines/html?id=' . $form->model->id . '&link=' . $form->model->link,"编辑HTML");
            }
        });

        $form->submit('保存');

        $imageDir = date('Ymd') . 'U' . Admin::user()->id;
        return $form->view('headline.form', compact('form', 'imageDir', 'type'));
    }


    public function anyEdit()
    {
        $deleteId = Input::get('delete', null);
        if ($deleteId) {
            Platv4Headline::where('id', $deleteId)->update(['status' => -1]);
            return redirect('/headlines');
        }

        $id = Input::get('modify', 0);
        if ($id) {
            $tagList = Platv4HeadlineToTag::where('headline_id', $id)->get()->toArray();
            $tags = array_column($tagList, 'headline_tag_id');
            Input::offsetSet('tags', array_values($tags));   // 选中tags
        }

        $edit = DataEdit::source(new Platv4Headline());

        $edit->label('头条信息');
        $edit->link(config('admin.route.prefix') . "/headlines", "列表", "TR")->back();

        $edit->add('title', '标题', 'text')
            ->rule("required|min:2")
            ->placeholder("请输入 标题");

        $edit->add('author', '来源', 'text')
            ->rule("required|min:1")
            ->placeholder("请输入 来源");

        $edit->add('style', '样式', 'radiogroup')->options(Platv4Headline::$styleText);

        $edit->add('link','链接','text')->rule("required")->placeholder("请输入 链接");

        $edit->add('thumb', '封面图', 'text')
            ->attributes(["readOnly" => true]);

        $edit->add('tags', '标签', 'checkboxgroup')->options(Platv4HeadlineTag::where('status', Platv4HeadlineTag::COMMON_STATUS_NORMAL)->orderBy('sort', 'asc')->pluck('name', 'id'));

        $edit->add('status', '状态', 'select')->options(Platv4Headline::$statusText);

        $edit->saved(function () use ($edit) {
            $this->saveHeadlineTag($edit->model->id, Input::get('tags'));
        });

        $edit->build();

        $imageDir = date('Ymd') . 'U' . Admin::user()->id;
        return $edit->view('headline.edit', compact('edit', 'id', 'imageDir'));
    }


    private function saveHeadlineTag($headlineId, $tags)
    {
        if(empty($headlineId)) return false;
        if(empty($tags)) return true;

        Platv4HeadlineToTag::where('headline_id', $headlineId)->delete();
        $insertData = [];
        foreach ($tags as $tag) {
            $insertData[] = [
                'headline_id' => $headlineId,
                'headline_tag_id' => $tag,
            ];
        }
        return DB::table('platv4_headline_to_tag')->insert($insertData);
    }

    private function saveHeadlineTagBak($headlineId, $tags)
    {
        if(empty($headlineId)) return false;
        if(empty($tags)) return true;

        Platv4HeadlineToTag::where('headline_id', $headlineId)->update(['status' => Platv4HeadlineToTag::COMMON_STATUS_DELETE]);
        $initSql = 'INSERT INTO `platv4_headline_to_tag` (`headline_id`, `headline_tag_id`, `status`)
                      VALUES ';
        $sql = '';
        foreach ($tags as $tag) {
            $sql .=  '(' . intval($headlineId) . ', ' . intval($tag) . ', ' . Platv4HeadlineToTag::COMMON_STATUS_NORMAL . '),';
        }
        $sql = substr($sql, 0, -1) . ' ON DUPLICATE KEY UPDATE `status` = VALUES(status);';
        DB::insert(DB::raw($initSql . $sql));
        return true;
    }

    private function dealWeChatImage($imageDir, $htmlData, $ajax = false)
    {
        $html = new Document($htmlData);

        $client = new Client(['verify' => false]);  //忽略SSL错误

        $description = htmlspecialchars_decode($htmlData);

        foreach ($html->find('img') as $item) {
            $src = $item->src;
            if (strpos($src, $this->ossViewDomain) !== false) {
//                OSS域图片不需处理
                \Log::info('continue :' . $src);
                continue;
            }

//            后缀
            $type = 'jpg';
            $tmp = explode('wx_fmt=', $src);
            if (isset($tmp[1])) {
                $tmp = explode('&', $tmp[1]);
                isset($tmp[0]) && $type = $tmp[0];
            }

//            图片名称
            $fileName = substr(md5($src), 8, 16) . '.' . $type;
            $file = storage_path('headline') . '/' . $fileName;
//            $imageObject = 'HEADLINE/IMAGES/' . date('Ymd') . 'U' . Admin::user()->id . '/' . $fileName;
            $imageObject = 'HEADLINE/IMAGES/' . $imageDir . '/' . $fileName;

            if ($this->isImageExist($imageObject) === false && strpos($src, 'http') !== false) {
                \Log::info('download :' . $src);
//                下载
                $response = $client->get($src, ['save_to' => $file]);   //保存远程url到文件
//                上传图片到 OSS
                $this->uploadImageToOSS($imageObject, $file);
//                删除本地文件
                @unlink($file);
            } else \Log::info('other :' . $src);

            $imageUrl = 'http://' . $this->ossViewDomain . '/' . $imageObject;
            $description = str_replace($src, $imageUrl, $description);
        }

        if ($ajax !== false) {
            return $description;
        }

//        上传HTML到OSS
        $htmlObject = 'HEADLINE/' . substr(md5('Article_' . time() . '_' . Admin::user()->id), 8, 16) . '.html';

        if (strpos($description, self::MAKA_EDIT_FLAG) === false) {
            \Log::info('--- 需要加Header ----');
            $description = $this->htmlHeader . $description . $this->htmlFooter;
        }

        $result = $this->uploadJsonToOSS($htmlObject, $description);

        $link = 'http://' . $this->ossViewDomain . '/' . $htmlObject;

        return $link;
    }


    private function uploadImageToOSS($object, $file)
    {
        if (!is_file($file)) return false;

        $oss = new OssClient($this->ossAppId, $this->ossAppSecret, $this->ossEndpoint);
        return $oss->uploadFile($this->ossBucket, $object, $file);
    }

    private function uploadJsonToOSS($object, $json)
    {
        if (empty($json)) return false;

        $oss = new OssClient($this->ossAppId, $this->ossAppSecret, $this->ossEndpoint);
        return $oss->putObject($this->ossBucket, $object, $json);
    }

    private function isImageExist($object)
    {
        $oss = new OssClient($this->ossAppId, $this->ossAppSecret, $this->ossEndpoint);
        return $oss->doesObjectExist($this->ossBucket, $object);
    }


    public function editHtml()
    {
        $id = Input::get('id', null);
        $link = Input::get('link', null);

        $content = $link ? file_get_contents($link) : '';

        $imageDir = date('Ymd') . 'U' . Admin::user()->id;

        return view('headline.article', compact('content', 'id', 'imageDir'));
    }

    public function updateHtml()
    {
        $id = Input::get('id', null);
        $imageDir = Input::get('image_dir', null);
        $content = Input::get('content', null);
        $ajax = Input::get('ajax', false);
        $result = $this->dealWeChatImage($imageDir, $content, $ajax);

        if ($ajax) return $this->respData(['content' => $result]);
        else {
            $data = Platv4Headline::find($id);
            if ($data) {
                $data->link = $result;
                $data->save();
            }
            return redirect('/headlines');
        }
    }

    private function htmlStyle()
    {
        return '<style>
                    img {
                        max-width: 100% !important;
                    }
                </style>';
    }
}
