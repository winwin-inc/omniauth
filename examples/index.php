<?php

use Psr\Http\Message\ServerRequestInterface;
use winwin\omniauth\ClosureRequestHandler;
use winwin\omniauth\Omniauth;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;

require __DIR__ . '/../vendor/autoload.php';
session_start();

$auth = new Omniauth([
    'route' => '/auth/:strategy/:action',
    'strategies' => [
        'password' => [
            'users' => [
                [
                    'user_id' => 'admin',
                    'password' => "$2y$10$1xtzJNWlfv6l1PDyXwiXb.8lpU968CeSXV0p/uTvd6qaqMC2/4GXa"
                ]
            ]
        ],
        'provider' => [
            "provider" => '/auth/provider/provide',
            'key' => 'ahPho9eenaewaqu8oojiehoS3vah3lae'
        ]
    ]
]);

$response = $auth->process(ServerRequestFactory::fromGlobals(), new ClosureRequestHandler(function (ServerRequestInterface $request) use ($auth) {
    $path = $request->getUri()->getPath();
    if ($path == '/logout') {
        session_destroy();
        return new RedirectResponse("/");
    }
    if ($path == '/auth/provider/provide') {
        return $auth->transport($request->getQueryParams()['redirect_uri'], ['user_id' => 'demo']);
    }
    return new Zend\Diactoros\Response\HtmlResponse("Current user " . $auth->getIdentity()['user_id']
        . '. <a href="/logout">Click to logout</a>');
}));

(new SapiEmitter())->emit($response);
