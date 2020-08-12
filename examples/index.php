<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use winwin\omniauth\IdentityTransformerInterface;
use winwin\omniauth\OminauthMiddleware;
use winwin\omniauth\Omniauth;
use winwin\omniauth\OmniauthFactory;
use winwin\omniauth\strategies\PasswordStrategy;
use winwin\omniauth\strategy\HybridOAuth2Strategy;

require __DIR__.'/../vendor/autoload.php';

Dotenv::createImmutable(__DIR__)->safeLoad();

$factory = new OmniauthFactory([
    'route' => '/auth/:strategy/:action',
    'strategies' => [
        'password' => [
            'strategy_class' => PasswordStrategy::class,
            'users' => [
                [
                    'user_id' => 'admin',
                    'display_name' => 'Administrator',
                    'password' => '$2y$10$1xtzJNWlfv6l1PDyXwiXb.8lpU968CeSXV0p/uTvd6qaqMC2/4GXa',  // password_hash('admin', PASSWORD_BCRYPT)
                ],
            ],
        ],
        'git-hub' => [
            'strategy_class' => HybridOAuth2Strategy::class,
            'keys' => [
                'id' => $_ENV['GITHUB_APP_ID'] ?? null,
                'secret' => $_ENV['GITHUB_APP_SECRET'] ?? null,
            ],
        ],
        'provider' => [
            'provider' => '/auth/provider/provide',
            'key' => 'ahPho9eenaewaqu8oojiehoS3vah3lae',
        ],
    ],
]);
$factory->setIdentityTransformer(new class() implements IdentityTransformerInterface {
    /**
     * {@inheritdoc}
     */
    public function transform($identity, string $strategy)
    {
        if ('git-hub' === $strategy) {
            /* @var \Hybridauth\User\Profile $identity */
            return ['user_id' => $identity->identifier] + get_object_vars($identity);
        } else {
            return $identity;
        }
    }
});

HybridOAuth2Strategy::setUp();

$app = AppFactory::create();
$app->add(new OminauthMiddleware($factory));

$app->get('/logout', function (ServerRequestInterface $request, ResponseInterface $resp) {
    Omniauth::get($request)->clear();

    return $resp->withStatus(302)->withHeader('location', '/');
});

$app->get('/auth/provider/provide', function (ServerRequestInterface $request) {
    return Omniauth::get($request)->transport(
        $request->getQueryParams()['redirect_uri'],
        ['user_id' => 'demo', 'display_name' => 'Demo user']
    );
});

$app->get('/', function (ServerRequestInterface $request, ResponseInterface $resp) {
    $auth = Omniauth::get($request);
    $resp->getBody()->write('Current user '.$auth->getIdentity()['user_id']
        .'. <br /> User detail info: <pre>'.var_export($auth->getIdentity(), true).'</pre>'
        .'. <a href="/logout">Click to logout</a>');

    return $resp;
});

$app->run();
