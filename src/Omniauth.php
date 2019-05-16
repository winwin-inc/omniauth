<?php

namespace winwin\omniauth;

use Http\Factory\Diactoros;
use Http\Factory\Guzzle;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Omniauth implements MiddlewareInterface
{
    const PROVIDER_STRATEGY = 'provider';
    const STRATEGY_KEY = '_omniauth_strategy';
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
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * Omniauth constructor.
     *
     * @param array $config
     * @param StorageInterface|null $storage
     * @param StrategyFactoryInterface|null $strategyFactory
     */
    public function __construct(array $config, StorageInterface $storage = null, StrategyFactoryInterface $strategyFactory = null)
    {
        if (!isset($config['strategies'])) {
            throw new \InvalidArgumentException('strategies is required');
        }
        if (!isset($config['strategies'][self::PROVIDER_STRATEGY])) {
            $config['strategies'][self::PROVIDER_STRATEGY] = [];
        }
        if (!isset($config['strategies'][self::PROVIDER_STRATEGY]['strategy_class'])) {
            $config['strategies'][self::PROVIDER_STRATEGY]['strategy_class'] = ProviderStrategy::class;
        }
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

        $this->storage = $storage ?: new SessionStorage();

        $this->strategyFactory = $strategyFactory ?: new StrategyFactory();
        $this->strategyFactory->setOmniauth($this);
        $this->strategyFactory->setStrategies($config['strategies']);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $response = $this->authenticate($request);
        if ($response) {
            return $response;
        } elseif ($this->configuration['auto_login'] && !$this->isAuthenticated() && !$this->match($request)) {
            return $this->getResponseFactory()->createResponse(302)
                ->withHeader("location", $this->getDefaultAuthUrl($request));
        }

        return $next($request, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $this->authenticate($request);
        if ($response) {
            return $response;
        } elseif ($this->configuration['auto_login'] && !$this->isAuthenticated() && !$this->match($request)) {
            return $this->getResponseFactory()->createResponse(302)
                ->withHeader("location", $this->getDefaultAuthUrl($request));
        }

        return $handler->handle($request);
    }

    public function authenticate(ServerRequestInterface $request)
    {
        if ($matches = $this->match($request)) {
            try {
                $strategy = $this->getStrategy($matches[1]);
                $action = isset($matches[2]) && '/' != $matches[2] ? substr($matches[2], 1) : 'authenticate';
                if (method_exists($strategy, $action)) {
                    $strategy->setRequest($request);

                    return call_user_func([$strategy, $action]);
                }
            } catch (StrategyNotFoundException $e) {
            }
        }
    }

    public function match(ServerRequestInterface $request)
    {
        if (preg_match($this->getRouteRegex(), $request->getUri()->getPath(), $matches)) {
            return $matches;
        }
    }

    public function isAuthenticated()
    {
        return $this->storage->get($this->configuration['auth_key']) !== null;
    }

    public function clear()
    {
        $this->storage->delete($this->configuration['auth_key']);
        $strategy = $this->storage->get(self::STRATEGY_KEY);
        if ($strategy) {
            $this->storage->delete(self::STRATEGY_KEY);
            $this->getStrategy($strategy)->clear();
        }
    }

    public function getIdentity()
    {
        return $this->storage->get($this->configuration['auth_key']);
    }

    public function setIdentity(array $identity, $strategyName)
    {
        $identity =  call_user_func($this->configuration['identity_transformer'], $identity, $this->getStrategy($strategyName));
        $this->storage->set($this->configuration['auth_key'], $identity);
        $this->storage->set(self::STRATEGY_KEY, $strategyName);
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

    public function getDefaultAuthUrl(ServerRequestInterface $request, $redirectUri = null)
    {
        if (!$redirectUri) {
            $redirectUri = $request->getUri();
        }
        if ($redirectUri instanceof UriInterface) {
            $redirectUri = $redirectUri->getQuery() ? $redirectUri->getPath() . '?' . $redirectUri->getQuery()
                : $redirectUri->getPath();
        }
        $this->storage->set($this->configuration['redirect_uri_key'], (string)$redirectUri);
        $default = $this->configuration['default'] ?? array_keys($this->configuration['strategies'])[0];
        if (is_callable($default)) {
            $default = $default($request);
        }
        return $this->buildUrl($default, '');
    }

    public function transport($redirectUrl, array $identity, $error = null)
    {
        try {
            /** @var ProviderStrategy $strategy */
            $strategy = $this->getStrategy(self::PROVIDER_STRATEGY);
            return $strategy->transport($redirectUrl, $identity, $error);
        } catch (StrategyNotFoundException $e) {
            throw new \RuntimeException("please add 'provider' strategy option in configuration 'strategies'");
        }
    }

    /**
     * @param string $name
     *
     * @return StrategyInterface
     * @throws StrategyNotFoundException
     */
    public function getStrategy($name)
    {
        if (!isset($this->strategies[$name])) {
            $this->strategies[$name] = $this->strategyFactory->create($name);
        }

        return $this->strategies[$name];
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

    public function getResponseFactory()
    {
        if (!$this->responseFactory) {
            if (class_exists(Diactoros\ResponseFactory::class)) {
                $this->responseFactory = new Diactoros\ResponseFactory();
            } elseif (class_exists(Guzzle\ResponseFactory::class)) {
                $this->responseFactory = new Guzzle\ResponseFactory();
            } else {
                throw new \RuntimeException('No psr/http-factory-implementation found, please install http-interop/http-factory-guzzle or http-interop/http-factory-diactoros');
            }
        }
        return $this->responseFactory;
    }

    public function getStreamFactory()
    {
        if (!$this->streamFactory) {
            if (class_exists(Diactoros\StreamFactory::class)) {
                $this->streamFactory = new Diactoros\StreamFactory();
            } elseif (class_exists(Guzzle\StreamFactory::class)) {
                $this->streamFactory = new Guzzle\StreamFactory();
            } else {
                throw new \RuntimeException('No psr/http-factory-implementation found, please install http-interop/http-factory-guzzle or http-interop/http-factory-diactoros');
            }
        }
        return $this->streamFactory;
    }
}
