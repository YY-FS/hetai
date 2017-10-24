<?php

namespace App\Admin\Controllers;

use Encore\Admin\Facades\Admin;
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

class HeadlineController extends BaseController
{

    public $ossBucket;
    public $ossAppId;
    public $ossAppSecret;
    public $ossEndpoint;
    public $htmlHeader;
    public $htmlFooter;

    const MAKA_EDIT_FLAG = 'MAKA-EDIT-FLAG'; //标识是否已自动加入头部，防止编辑重复

    public function __construct()
    {
        $this->ossBucket = env('ALI_OSS_PLAT_BUCKET');
        $this->ossAppId = env('ALI_OSS_PLAT_ACCESS_KEY');
        $this->ossAppSecret = env('ALI_OSS_PLAT_ACCESS_SECRET');
        $this->ossEndpoint = env('ALI_OSS_PLAT_ENDPOINT');

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
        $filter = DataFilter::source(Platv4Headline::where('status', '>=', 0));

        $filter->add('title', '标题', 'text');
        $filter->add('author', '来源', 'text');
        $filter->add('created_at', '发布日期', 'daterange')
            ->format('Y-m-d', 'zh-CN');

        $filter->submit('筛选');
        $filter->reset('重置');
        $filter->build();

        $grid = DataGrid::source($filter);

        $grid->attributes(array("class" => "table table-bordered table-striped table-hover"));
        $grid->add('id', 'ID', true);
        $grid->add('title', '标题', false);
        $grid->add('style', '样式', true);
        $grid->add('author', '来源', true);
        $grid->add('created_at', '创建日期', true);

        $grid->add('operation','操作', false);

        $grid->orderBy('created_at', 'desc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.prefix') . '/headlines/create', '新增', 'TR', ['class' => 'btn btn-default']);

        $grid->row(function ($row) {
//            skin: 'layui-layer-rim', //加上边框
//            shadeClose: true,   //点击遮罩关闭
            if ($row->data->link) $link = $row->data->link;
            else $link = '(空)';
            $btnPreview = "<button class=\"btn btn-primary\" onclick=\"layer.open({
                                                                                type: 2, 
                                                                                title: ['" . $row->data->title . "', false], 
                                                                                area: ['375px', '667px'], 
                                                                                btn: ['编辑'], 
                                                                                btn1: function(index, layero){
                                                                                    //按钮【按钮一】的回调
                                                                                    window.location.href = '" . config('admin.route.prefix') . "/headlines/html?id=" . $row->data->id . "&link=" . $row->data->link . "';
                                                                                    //return false; //开启该代码可禁止点击该按钮关闭
                                                                                 },
                                                                                shadeClose: true,
                                                                                content: '" . $link . "'
                                                                            })\">查看内容</button>";
            $btnEdit = "<a class='btn btn-default' href='" . config('admin.route.prefix') . "/headlines/edit?modify=" . $row->data->id . "'>编辑</a>";
            $btnDelete = '<button class="btn btn-danger" onclick="layer.confirm( \'确定删除吗？！\',{ btn: [\'确定\',\'取消\'] }, function(){ window.location.href = \'' . config('admin.route.prefix') . "/headlines/edit?delete=" . $row->data->id . '\'})">删除</button>';

            $row->cell('operation')->value = $btnPreview . $btnEdit . $btnDelete;
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
        $nextId = intval(@Platv4Headline::orderBy('id', 'DESC')->first()->id) + 1;

        $form = DataForm::source(new Platv4Headline());

        $form->label('头条信息');
        $form->link(config('admin.route.prefix') . "/headlines", "列表", "TR")->back();

        $form->add('title', '标题', 'text')
            ->rule("required|min:2")
            ->placeholder("请输入 标题");

        $form->add('link','内容','redactor')->rule("required");
        $form->add('thumb', '封面图', 'text')->rule("required");

        $form->add('author', '来源', 'text')
            ->rule("required|min:1")
            ->placeholder("请输入 来源");


        $form->saved(function () use ($form) {
            try{
                $this->dealWeChatImage($form->model->id, $form->model->link);
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

        return $form->view('headline.form', compact('form', 'nextId'));
    }


    public function anyEdit()
    {
        $id = Input::get('delete', null);
        if ($id) {
            $data = Platv4Headline::find($id);
            $data->status = -1;
            $data->save();
            return redirect('/headlines');
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

        $edit->build();

        return $edit->view('rapyd.edit', compact('edit'));
    }


    private function dealWeChatImage($id, $htmlData, $ajax = false)
    {
        $html = new Document($htmlData);

        $client = new Client(['verify' => false]);  //忽略SSL错误

        $description = htmlspecialchars_decode($htmlData);

        foreach ($html->find('img') as $item) {
            $src = $item->src;
            if (strpos($src, $this->ossEndpoint) !== false) {
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
            $imageObject = 'HEADLINE/' . $id . '/' . $fileName;

            if ($this->isImageExist($imageObject) === false && strpos($src, 'http') !== false) {
                \Log::info('download :' . $src);
//                下载
                $response = $client->get($src, ['save_to' => $file]);   //保存远程url到文件
//                上传图片到 OSS
                $this->uploadImageToOSS($imageObject, $file);
//                删除本地文件
                @unlink($file);
            } else \Log::info('other :' . $src);

            $imageUrl = 'http://' . $this->ossBucket . '.' . $this->ossEndpoint . '/' . $imageObject;
            $description = str_replace($src, $imageUrl, $description);
        }

//        上传HTML到OSS
        $htmlObject = 'HEADLINE/' . $id . '/' . substr(md5('Article_' . $id), 8, 16) . '.html';
        $description = (strpos($description, self::MAKA_EDIT_FLAG) !== false || $ajax !== false) ? $description : ($this->htmlHeader . $description . $this->htmlFooter);
        $result = $this->uploadJsonToOSS($htmlObject, $description);

        $data = Platv4Headline::find($id);
        if ($data) {
            $data->link = 'http://' . $this->ossBucket . '.' . $this->ossEndpoint . '/' . $htmlObject;
            $data->save();
        }

        return $description;
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

        return view('headline.article', compact('content', 'id'));
    }

    public function updateHtml()
    {
        $id = Input::get('id', null);
        $content = Input::get('content', null);
        $ajax = Input::get('ajax', null);
        $description = $this->dealWeChatImage($id, $content, $ajax);

        if ($ajax) return $this->respData(['content' => $description]);
        else return redirect('/headlines');
    }

    private function htmlStyle()
    {
        return '<style>
                    span {
                        font-size: 14px !important;
                        color: #3e3e3e !important;
                    }
                    img {
                        max-width: 100% !important;
                    }
                </style>';
    }
}
