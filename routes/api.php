<?php

/** @var \Laravel\Lumen\Routing\Router $router */



$router->group(['prefix' => 'api/v1'], function () use ($router) {
    $router->get('/', 'MainController@main');

    $router->post('/sendEmail', 'WaitListController@sendEmail');

    $router->get('/login-message', 'Web3AuthController@message');
    $router->post('/login-verify', 'Web3AuthController@verify');

    $router->group(['middleware' => 'auth'], function () use ($router) {
        $router->get('/main', 'MainController@main');
    });
});



