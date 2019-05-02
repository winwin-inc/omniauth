<?php

use Psr\Http\Message\ServerRequestInterface;
use winwin\omniauth\ClosureRequestHandler;
use winwin\omniauth\Omniauth;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;

require __DIR__ . '/../vendor/autoload.php';
\Dotenv\Dotenv::create(__DIR__)->load();

$auth = new Omniauth([
    'route' => '/auth/:strategy/:action',
    'strategies' => [
        'password' => [
            'users' => [
                [
                    'user_id' => 'admin',
                    'display_name' => 'Administrator',
                    'password' => "$2y$10$1xtzJNWlfv6l1PDyXwiXb.8lpU968CeSXV0p/uTvd6qaqMC2/4GXa"
                ]
            ]
        ],
        'git-hub' => [
            'keys' => [
                'id' => getenv("GITHUB_APP_ID"),
                'secret' => getenv("GITHUB_APP_SECRET"),
            ],
            'strategy_class' => \winwin\omniauth\HybridOAuth2Strategy::class,
        ],
        'provider' => [
            "provider" => '/auth/provider/provide',
            'key' => 'ahPho9eenaewaqu8oojiehoS3vah3lae'
        ]
    ],
    'identity_transformer' => function (array $identity, $strategy) {
        if ($strategy instanceof \winwin\omniauth\HybridOAuth2Strategy) {
            return ['user_id' => $identity['identifier']] + $identity;
        } else {
            return $identity;
        }
    }
]);

\winwin\omniauth\HybridOAuth2Strategy::setUp();

$response = $auth->process(ServerRequestFactory::fromGlobals(), new ClosureRequestHandler(function (ServerRequestInterface $request) use ($auth) {
    $path = $request->getUri()->getPath();
    if ($path == '/logout') {
        session_destroy();
        return new RedirectResponse("/");
    }
    if ($path == '/auth/provider/provide') {
        return $auth->transport($request->getQueryParams()['redirect_uri'], ['user_id' => 'demo', 'display_name' => 'Demo user']);
    }
    return new Zend\Diactoros\Response\HtmlResponse("Current user " . $auth->getIdentity()['user_id']
        . ". <br /> User detail info: <pre>" . var_export($auth->getIdentity(), true) . "</pre>"
        . '. <a href="/logout">Click to logout</a>');
}));

(new SapiEmitter())->emit($response);
