<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

//    头条内容
    $router->get('headlines', 'HeadlineController@index');
    $router->any('headlines/create', 'HeadlineController@anyForm');
    $router->any('headlines/edit', 'HeadlineController@anyEdit');
    $router->get('headlines/html', 'HeadlineController@editHtml');
    $router->post('headlines/html', 'HeadlineController@updateHtml');

//    OSS
    $router->get('headlines/oss/{imageDir}', 'OssController@headlineObject');
    $router->get('oss/auth', 'OssController@auth');

//    头条标签
    $router->get('headline/tags', 'HeadlineTagController@index');
    $router->any('headline/tags/edit', 'HeadlineTagController@anyEdit');

//    用户画像和头条标签关联
    $router->get('industries', 'IndustryController@index');
    $router->any('industries/edit', 'IndustryController@anyEdit');

//    设计师余额
    $router->get('designers/balance', 'DesignerBalanceController@index');
    $router->any('designers/balance/create', 'DesignerBalanceController@anyForm');
    $router->get('designers/jx_balance', 'DesignerBalanceController@jxDesignerBalance');

//    团队版
    $router->get('vipclass', 'VipClassController@index');
    $router->any('vipclass/edit', 'VipClassController@anyEdit');

//    用户会员
    $router->get('customer_vips', 'CustomerVipController@index');
    $router->any('customer_vips/edit', 'CustomerVipController@anyEdit');
    $router->get('customer_vips/cache', 'CustomerVipController@cleanCache');
    $router->get('customer_vips/packages', 'CustomerVipController@package');
    $router->any('customer_vips/packages/edit', 'CustomerVipController@packageEdit');
});
