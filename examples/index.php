<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use kuiper\di\ContainerBuilder;
use kuiper\di\PropertiesDefinitionSource;
use function kuiper\helper\env;
use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use kuiper\web\http\DiactorosHttpMessageFactoryConfiguration;
use kuiper\web\middleware\Session;
use kuiper\web\session\PhpSessionFactory;
use kuiper\web\WebConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use winwin\omniauth\IdentityTransformerInterface;
use winwin\omniauth\OminauthMiddleware;
use winwin\omniauth\Omniauth;
use winwin\omniauth\OmniauthConfiguration;
use winwin\omniauth\strategies\PasswordStrategy;
use winwin\omniauth\strategy\HybridOAuth2Strategy;

require __DIR__.'/../vendor/autoload.php';

Dotenv::createImmutable(__DIR__)->safeLoad();

$builder = new ContainerBuilder();
$builder->addConfiguration(new OmniauthConfiguration());
$builder->addConfiguration(new DiactorosHttpMessageFactoryConfiguration());
$builder->addConfiguration(new WebConfiguration());
$properties = Properties::create([
    'application' => [
        'omniauth' => [
            'route' => '/auth/:strategy/:action',
            'strategies' => [
                'password' => [
                    'strategy_class' => PasswordStrategy::class,
                    'users' => [
                        [
                            'user_id' => 'admin',
                            'display_name' => 'Administrator',
                            'password' => '$2y$10$1xtzJNWlfv6l1PDyXwiXb.8lpU968CeSXV0p/uTvd6qaqMC2/4GXa',
                        ],
                    ],
                ],
                'git-hub' => [
                    'strategy_class' => HybridOAuth2Strategy::class,
                    'keys' => [
                        'id' => env('GITHUB_APP_ID'),
                        'secret' => env('GITHUB_APP_SECRET'),
                    ],
                ],
                'provider' => [
                    'provider' => '/auth/provider/provide',
                    'key' => 'ahPho9eenaewaqu8oojiehoS3vah3lae',
                ],
            ],
        ],
    ],
]);
$builder->addDefinitions(new PropertiesDefinitionSource($properties));
$builder->addDefinitions([
    PropertyResolverInterface::class => $properties,
    IdentityTransformerInterface::class => new class() implements IdentityTransformerInterface {
        /**
         * {@inheritdoc}
         */
        public function transform(array $identity, string $strategy)
        {
            if ('git-hub' === $strategy) {
                return ['user_id' => $identity['identifier']] + $identity;
            } else {
                return $identity;
            }
        }
    },
]);
$container = $builder->build();

HybridOAuth2Strategy::setUp();

$app = $container->get(App::class);
$app->add($container->get(OminauthMiddleware::class));
$app->add(new Session(new PhpSessionFactory(['auto_start' => true])));

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
