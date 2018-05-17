<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix' => config('admin.route.prefix'),
    'namespace' => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

//    头条内容
    $router->get('headlines', 'HeadlineController@index');
    $router->any('headlines/create', 'HeadlineController@anyForm');
    $router->any('headlines/edit', 'HeadlineController@anyEdit');
    $router->any('headlines/html', 'HeadlineController@editHtml');
    $router->post('headlines/jx_html', 'HeadlineController@updateHtml');
    $router->get('headlines/showhtml', 'HeadlineController@showHtml');

//    OSS
    $router->get('headlines/oss', 'OssController@showObject');
    $router->get('oss/auth', 'OssController@auth');

//    头条标签
    $router->get('headline/tags', 'HeadlineTagController@index');
    $router->any('headline/tags/edit', 'HeadlineTagController@anyEdit');

//    用户画像、头条标签关联
    $router->get('industries', 'IndustryController@index');
    $router->any('industries/edit', 'IndustryController@anyEdit');
    $router->get('industries/cache', 'IndustryController@cleanCache')->name('industries.cache.clean');

//    用户分群
    $router->get('user/groups', 'UserGroupController@index');
    $router->any('user/groups/create', 'UserGroupController@anyForm');
    $router->any('user/groups/edit', 'UserGroupController@anyEdit');
    $router->get('user/groups/check_member', 'UserGroupController@checkMember');

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
    // 会员价格表
    $router->get('customer_vips/packages', 'CustomerVipController@package');
    $router->any('customer_vips/packages/edit', 'CustomerVipController@packageEdit');
    // 会员优惠
    $router->get('customer_vips/discounts', 'CustomerVipController@discount');
    $router->any('customer_vips/discounts/edit', 'CustomerVipController@discountEdit');
    $router->any('customer_vips/discounts/rule', 'CustomerVipController@discountRuleEdit');
    $router->get('customer_vips/discounts/cache', 'CustomerVipController@cleanDiscountCache');
    $router->get('customer_vips/discounts/data', 'CustomerVipController@dataShow');
    $router->get('customer_vips/discounts/data_detail', 'CustomerVipController@dataDetail');

    //模态窗
    $router->get('modal', 'ModalController@index');
    $router->any('modal/edit', 'ModalController@anyEdit');
    $router->get('modal/oss', 'OssController@showObject');
    $router->get('modal/cache', 'ModalController@cleanCache');


    $router->get('error', 'BaseController@error');

    //用户支付
    $router->get("payment", 'UserPaymentController@index');
    $router->get("payment/edit", 'UserPaymentController@anyEdit');

    //banner相关
    $router->get('banners/{layout}/cache', 'BannerController@cleanCache');
    $router->get('banners/layouts/list', 'LayoutController@index');
    $router->any('banners/layouts/edit', 'LayoutController@anyEdit');
    $router->any('banners/layouts/create', 'LayoutController@anyEdit');
    $router->get('banners/oss', 'OssController@showObject');
    $router->get('banners/{layout}','BannerController@index');
    $router->any('banners/{layout}/create', 'BannerController@anyEdit');
    $router->any('banners/{layout}/edit', 'BannerController@anyEdit');
    
    //平台配置
    $router->get('mina/version', 'PublicController@minaVersion');
    $router->post('mina/jx_version', 'PublicController@editMinaVersion');
    $router->get('plat/config', 'PublicController@platConfig');
    $router->post('plat/config/jx_clean_sign/{uid}', 'PublicController@cleanSign');
    $router->post('plat/config/jx_clean_login/{username}', 'PublicController@cleanLogin');

    //会员用户
    $router->get('user/vip', 'UserVipController@index');
    $router->any('user/vip/edit', 'UserVipController@anyEdit');

    //日签图片管理
    $router->any('user/sign_image/edit', 'SigninImageController@anyEdit');
    $router->get('user/sign_image', 'SigninImageController@index');
    $router->get('user/sign_image/cache', 'SigninImageController@cleanCache');
    $router->get('user/sign_image/oss', 'OssController@showObject');
    
    //日签文案管理
    $router->get('signin_text','SigninTextController@index');
    $router->any('signin_text/edit','SigninTextController@anyEdit');
    
    //增值税专用发票信息管理
    $router->get('invoice/{invoiceType}/info', 'InvoiceSpecialInfoController@index');
    $router->any('invoice/special/info/edit', 'InvoiceSpecialInfoController@anyEdit');
    $router->any('invoice/special/info/download', 'InvoiceSpecialInfoController@downloadImg');

    //发票管理
    $router->get('invoice/{type}','InvoiceController@index');
    $router->any('invoice/{type}/edit','InvoiceController@anyEdit');
    $router->any('invoice/{type}/audit','InvoiceController@anyAudit');
    $router->any('invoice/{type}/send','InvoiceController@anySend');
    $router->post('invoice/upload','InvoiceController@upload');
    
    //用户筛选器
    $router->get('user/filters','UserFilterController@index');
    
    //主动通知管理
    $router->get('message','MessageController@index');
    $router->any('message/edit','MessageController@anyEdit');
    $router->get('message/push','MessageController@push');
    $router->post('message/push/test','MessageController@pushTest');

    
    //公众号素材管理
    $router->get('official_account/material','WechatController@material');
    $router->get('official_account/image','WechatController@image');


    //公众号通知
    $router->get('official_account','OfficialAccountInformController@index');
    $router->any('official_account/{alias}/create','OfficialAccountInformController@anyEdit');
    $router->any('official_account/{alias}/edit','OfficialAccountInformController@anyEdit');
});
