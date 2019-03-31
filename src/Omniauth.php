<?php

namespace winwin\omniauth;

use Http\Factory\Diactoros;
use Http\Factory\Guzzle;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
     * @param array                         $config
     * @param StrategyFactoryInterface|null $strategyFactory
     */
    public function __construct(array $config, StrategyFactoryInterface $strategyFactory = null)
    {
        $this->configuration = $config + [
                'route' => '/:strategy/:action',
                'callback_url' => '/',
            ];
        if (!isset($config['strategies'])) {
            throw new \InvalidArgumentException('strategies is required');
        }
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
        if (preg_match($this->getRouteRegex(), $request->getUri()->getPath(), $matches)) {
            $strategy = $this->getStrategy($matches[1]);
            if ($strategy) {
                $action = isset($matches[2]) && '/' != $matches[2] ? substr($matches[2], 1) : 'request';
                if (method_exists($strategy, $action)) {
                    $strategy->setRequest($request);

                    return call_user_func([$strategy, $action]);
                }
            }
        }

        return $handler->handle($request);
    }

    public function isAuthenticated()
    {
        return isset($_SESSION['auth']);
    }

    public function getIdentity()
    {
        return $_SESSION['auth'];
    }

    public function setIdentity(array $identity)
    {
        $_SESSION['auth'] = $identity;
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
        return $this->configuration['callback_url'];
    }

    public function getDefaultAuthUrl()
    {
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
            $re = str_replace(':strategy', '([A-Za-z0-9-_]+)', $re);
            $re = str_replace('/:action', '(/[A-Za-z0-9-_]*)?', $re);

            $this->routeRegex = '#^'.$re.'$#';
        }

        return $this->routeRegex;
    }

    private function createDefaultStrategyFactory()
    {
        if (class_exists(Diactoros\ResponseFactory::class)) {
            $factory = new StrategyFactory(new Diactoros\ResponseFactory(), new Diactoros\StreamFactory());
        } elseif (class_exists(Guzzle\ResponseFactory::class)) {
            $factory = new StrategyFactory(new Guzzle\ResponseFactory(), new Guzzle\StreamFactory());
        } else {
            throw new \RuntimeException('No psr/http-factory-implementation found, please install http-interop/http-factory-guzzle or http-interop/http-factory-diactoros');
        }
        $factory->register(self::PROVIDER_STRATEGY, ProviderStrategy::class);

        return $factory;
    }

    /**
     * @param string $name
     *
     * @return StrategyInterface|null
     */
    private function getStrategy($name)
    {
        if (isset($this->strategies[$name])) {
            return $this->strategies[$name];
        }
        if ($this->strategyFactory->has($name)) {
            return $this->strategies[$name] = $this->strategyFactory->create($name);
        }

        return null;
    }
}
