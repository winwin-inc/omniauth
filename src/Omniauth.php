<?php

namespace winwin\omniauth;

use Http\Factory\Diactoros;
use Http\Factory\Guzzle;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Omniauth implements MiddlewareInterface
{
    const PROVIDER_STRATEGY = 'provider';
    /**
     * @var array
     */
    private $configuration;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var StrategyFactoryInterface
     */
    private $strategyFactory;

    /**
     * @var StrategyInterface[]
     */
    private $strategies;

    /**
     * @var string
     */
    private $routeRegex;

    /**
     * Omniauth constructor.
     *
     * @param array $config
     * @param StrategyFactoryInterface|null $strategyFactory
     */
    public function __construct(array $config, StorageInterface $storage = null, StrategyFactoryInterface $strategyFactory = null)
    {
        $this->configuration = $config + [
                'route' => '/:strategy/:action',
                'auto_login' => true,
                'auth_key' => 'auth',
                'redirect_uri_key' => 'login_redirect_uri',
                'callback_url' => '/',
                'identity_transformer' => function(array $identity) {
                    return $identity;
                }
            ];
        if (!isset($config['strategies'])) {
            throw new \InvalidArgumentException('strategies is required');
        }
        $this->storage = $storage ?: new SessionStorage();
        if (!$strategyFactory) {
            $strategyFactory = $this->createDefaultStrategyFactory();
        }
        $strategyFactory->setOmniauth($this);
        $strategyFactory->setStrategies($config['strategies']);
        $this->strategyFactory = $strategyFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $this->authenticate($request);
        if ($response) {
            return $response;
        } elseif ($this->configuration['auto_login'] && !$this->isAuthenticated()
            && !preg_match($this->getRouteRegex(), $request->getUri()->getPath())) {
            return $this->getResponseFactory()->createResponse(302)
                ->withHeader("location", $this->getDefaultAuthUrl($request->getUri()));
        }

        return $handler->handle($request);
    }

    public function authenticate(ServerRequestInterface $request)
    {
        if (preg_match($this->getRouteRegex(), $request->getUri()->getPath(), $matches)) {
            $strategy = $this->getStrategy($matches[1]);
            if ($strategy) {
                $action = isset($matches[2]) && '/' != $matches[2] ? substr($matches[2], 1) : 'authenticate';
                if (method_exists($strategy, $action)) {
                    $strategy->setRequest($request);

                    return call_user_func([$strategy, $action]);
                }
            }
        }
    }

    public function isAuthenticated()
    {
        return $this->storage->get($this->configuration['auth_key']) !== null;
    }

    public function getIdentity()
    {
        return $this->storage->get($this->configuration['auth_key']);
    }

    public function setIdentity(array $identity, $strategyName)
    {
        $identity =  call_user_func($this->configuration['identity_transformer'], $identity, $strategyName);
        $this->storage->set($this->configuration['auth_key'], $identity);
    }

    public function buildUrl(string $strategy, $action)
    {
        return strtr($this->configuration['route'], [
            ':strategy' => $strategy,
            ':action' => $action,
        ]);
    }

    public function getCallbackUrl()
    {
        if ($redirect = $this->storage->get($this->configuration['redirect_uri_key'])) {
            $this->storage->delete($this->configuration['redirect_uri_key']);
            return $redirect;
        } else {
            return $this->configuration['callback_url'];
        }
    }

    public function getDefaultAuthUrl($redirectUri = null)
    {
        if ($redirectUri) {
            if ($redirectUri instanceof UriInterface) {
                $redirectUri = $redirectUri->getQuery() ? $redirectUri->getPath() . '?' . $redirectUri->getQuery()
                    : $redirectUri->getPath();
            }
            $this->storage->set($this->configuration['redirect_uri_key'], (string) $redirectUri);
        }
        $default = $this->configuration['default'] ?? array_keys($this->configuration['strategies'])[0];
        return $this->buildUrl($default, '');
    }

    public function transport($redirectUrl, array $identity, $error = null)
    {
        /** @var ProviderStrategy $strategy */
        $strategy = $this->getStrategy(self::PROVIDER_STRATEGY);
        if (!$strategy) {
            throw new \RuntimeException("please add 'provider' strategy option in configuration 'strategies'");
        }

        return $strategy->transport($redirectUrl, $identity, $error);
    }

    /**
     * @param string $name
     *
     * @return StrategyInterface|null
     */
    public function getStrategy($name)
    {
        if (isset($this->strategies[$name])) {
            return $this->strategies[$name];
        }
        if ($this->strategyFactory->has($name)) {
            return $this->strategies[$name] = $this->strategyFactory->create($name);
        }

        return null;
    }

    /**
     * @return StrategyFactoryInterface
     */
    public function getStrategyFactory()
    {
        return $this->strategyFactory;
    }

    private function getRouteRegex()
    {
        if (!$this->routeRegex) {
            $re = $this->configuration['route'];
            $strategyRe = implode('|', array_map('preg_quote', array_keys($this->configuration['strategies'])));
            $re = str_replace(':strategy', '('.$strategyRe.')', $re);
            $re = str_replace('/:action', '(/[A-Za-z0-9-_]*)?', $re);

            $this->routeRegex = '#^' . $re . '$#';
        }

        return $this->routeRegex;
    }

    private function createDefaultStrategyFactory()
    {
        $factory = new StrategyFactory($this->getResponseFactory(), $this->getStreamFactory());
        $factory->register(self::PROVIDER_STRATEGY, ProviderStrategy::class);

        return $factory;
    }

    private function getResponseFactory()
    {
        if (class_exists(Diactoros\ResponseFactory::class)) {
            return new Diactoros\ResponseFactory();
        } elseif (class_exists(Guzzle\ResponseFactory::class)) {
            return new Guzzle\ResponseFactory();
        } else {
            throw new \RuntimeException('No psr/http-factory-implementation found, please install http-interop/http-factory-guzzle or http-interop/http-factory-diactoros');
        }
    }

    private function getStreamFactory()
    {
        if (class_exists(Diactoros\StreamFactory::class)) {
            return new Diactoros\StreamFactory();
        } elseif (class_exists(Guzzle\StreamFactory::class)) {
            return new Guzzle\StreamFactory();
        } else {
            throw new \RuntimeException('No psr/http-factory-implementation found, please install http-interop/http-factory-guzzle or http-interop/http-factory-diactoros');
        }
    }
}
