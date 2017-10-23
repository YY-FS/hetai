<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    $router->get('headlines', 'HeadlineController@index');
    $router->any('headlines/create', 'HeadlineController@anyForm');
    $router->any('headlines/edit', 'HeadlineController@anyEdit');
    $router->get('headlines/html', 'HeadlineController@editHtml');
    $router->post('headlines/html', 'HeadlineController@updateHtml');

});
