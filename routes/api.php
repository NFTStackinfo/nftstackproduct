<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'api/v1'], function () use ($router) {
    $router->get('/', 'MainController@main');

    $router->post('/sendEmail', 'WaitListController@sendEmail');
    $router->post('/unsubscribe/{id}', 'WaitListController@unsubscribe');

    $router->get('/login-message/', 'Web3AuthController@message');
    $router->post('/login-verify', 'Web3AuthController@verify');
    $router->get('/logout/{address}', 'Web3AuthController@logOut');

    $router->post('/user/create', 'UsersController@create');
    $router->post('/user/updateEmail', 'UsersController@updateEmail');

    $router->get('/contract/compile', 'ContractController@compile');

    $router->group(['middleware' => 'auth'], function () use ($router) {
        $router->get('/main', 'MainController@main');

        $router->post('/contract/create', 'ContractController@create');
        $router->post('/contract/update', 'ContractController@update');
        $router->get('/contract/get/{id}', 'ContractController@get');
        $router->get('/contract/get/all', 'ContractController@getUserContracts');
    });
});



