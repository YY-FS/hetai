<?php
/**
 * Created by PhpStorm.
 * User: yyfs
 * Date: 18-5-16
 * Time: 下午2:40
 */
namespace App\Admin\Controllers;

use EasyWeChat\Factory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Input;

class WechatController extends BaseController
{
    public function material()
    {
        $title = "公众号素材管理";
        $page = Input::get('page',1);
        //分页
        $offset = ($page-1)*self::DEFAULT_PER_PAGE;
        $material = $this->materialList($offset);
        $paginator = new LengthAwarePaginator($material,$material['total_count'],self::DEFAULT_PER_PAGE,$page,[
            'path'=>Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
        return view('officialaccount.material', compact('title','material','paginator'));
    }

    protected function materialList($offset)
    {
        $app = Factory::officialAccount(config('wechat.official_account.default'));
        return $app->material->list('news',$offset,self::DEFAULT_PER_PAGE);
    }
}