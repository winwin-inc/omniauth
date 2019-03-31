<?php

use Psr\Http\Message\ServerRequestInterface;
use winwin\omniauth\ClosureRequestHandler;
use winwin\omniauth\Omniauth;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;

require __DIR__ . '/../vendor/autoload.php';
session_start();

$auth = new Omniauth(require __DIR__ . '/config.php');

$response = $auth->process(ServerRequestFactory::fromGlobals(), new ClosureRequestHandler(function (ServerRequestInterface $request) use ($auth) {
    $path = $request->getUri()->getPath();
    if ($path == '/logout') {
        session_destroy();
        return new RedirectResponse("/");
    }
    if ($path == '/auth') {
        return $auth->transport($request->getQueryParams()['redirect_uri'], ['user_id' => 'demo']);
    }
    if (!$auth->isAuthenticated()) {
        return new RedirectResponse($auth->getDefaultAuthUrl());
    }
    return new Zend\Diactoros\Response\HtmlResponse("Current user " . $auth->getIdentity()['user_id'] . '. <a href="/logout">Logout</a>');
}));

(new SapiEmitter())->emit($response);
