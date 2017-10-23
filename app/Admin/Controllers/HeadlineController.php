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

    public function __construct()
    {
        $this->ossBucket = env('ALI_OSS_PLAT_BUCKET');
        $this->ossAppId = env('ALI_OSS_PLAT_ACCESS_KEY');
        $this->ossAppSecret = env('ALI_OSS_PLAT_ACCESS_SECRET');
        $this->ossEndpoint = env('ALI_OSS_PLAT_ENDPOINT');
    }

    public function index()
    {

        $title = '头条文章';
        $filter = DataFilter::source(new Platv4Headline());

        $filter->add('title', '标题', 'text');
        $filter->add('author', '来源', 'text');
        $filter->add('release_time', '发布日期', 'daterange')
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
        $grid->add('release_time', '发布日期', true);
        $grid->add('created_at', '创建日期', true);

        $grid->orderBy('created_at', 'desc');

        $url = new Url();
        $grid->link($url->append('export', 1)->get(), "导出Excel", "TR", ['class' => 'btn btn-export', 'target' => '_blank']);
        $grid->link(config('admin.route.prefix') . '/headlines/edit', '新增', 'TR', ['class' => 'btn btn-default']);

        $grid->edit(config('admin.route.prefix') . '/headlines/edit', '操作', 'modify|delete');

        if (Input::get('export') == 1) {
            $grid->build();
            return $grid->buildCSV($title, 'Ymd');
        } else {
            $grid->paginate(self::DEFAULT_PER_PAGE);
            $grid->build();
            return view('rapyd.filtergrid', compact('filter', 'grid', 'title'));
        }

    }


    public function edit()
    {

        $edit = DataEdit::source(new Platv4Headline());

        $edit->label('头条信息');
        $edit->link(config('admin.route.prefix') . "/headlines", "列表", "TR")->back();

        $edit->add('title', '标题', 'text')
            ->rule("required|min:2")
            ->placeholder("请输入 标题");

        $edit->add('link','内容','redactor')->rule("required");

        $edit->add('author', '来源', 'text')
            ->rule("required|min:1")
            ->placeholder("请输入 来源");

        $edit->saved(function () use ($edit) {
            try{
                $this->dealWeChatImage($edit->model->id, $edit->model->link);
            } catch (\Exception $exception) {
                throw new \exception($exception->getMessage());
            }
        });

        $edit->build();

        return $edit->view('headline.edit', compact('edit'));
    }


    private function dealWeChatImage($id, $htmlData)
    {
        $data = Platv4Headline::find($id);
        $html = new Document($htmlData);

        $client = new Client(['verify' => false]);  //忽略SSL错误

        $description = htmlspecialchars_decode($htmlData);

        foreach ($html->find('img') as $key => $item) {
            $src = $item->src;

//            后缀
            $type = 'jpg';
            $tmp = explode('wx_fmt=', $src);
            if (isset($tmp[1])) {
                $tmp = explode('&', $tmp[1]);
                isset($tmp[0]) && $type = $tmp[0];
            }

//            图片名称
            $fileName = 'IMAGE_' . $id . '_' . $key . '.' . $type;
            $file = storage_path('headline') . '/' . $fileName;
            $imageObject = 'HEADLINE/' . $fileName;

            if (!is_file($file) && strpos($src, 'http') !== false) {
                $response = $client->get($src, ['save_to' => $file]);   //保存远程url到文件
            }
//            上传图片到 OSS
            $this->uploadImageToOSS($imageObject, $file);

            $imageUrl = 'http://' . $this->ossBucket . '.' . $this->ossEndpoint . '/' . $imageObject;
            $description = str_replace($src, $imageUrl, $description);
        }

//        上传HTML到OSS
        $htmlObject = 'HEADLINE/Article_' . $id . '.html';
        $this->uploadJsonToOSS($htmlObject, $description);

        $data->link = 'http://' . $this->ossBucket . '.' . $this->ossEndpoint . '/' . $htmlObject;
        $data->save();
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
}
