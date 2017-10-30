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
    $router->get('headlines/oss/{id}', 'OssController@headlineObject');
    $router->get('oss/auth', 'OssController@auth');

//    头条标签
    $router->get('headline/tags', 'HeadlineTagController@index');
    $router->any('headline/tags/edit', 'HeadlineTagController@anyEdit');

//    用户画像和头条标签关联
    $router->get('industries', 'IndustryController@index');
    $router->any('industries/edit', 'IndustryController@anyEdit');
});
