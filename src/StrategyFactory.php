<?php

namespace winwin\omniauth;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class StrategyFactory implements StrategyFactoryInterface
{
    /**
     * @var Omniauth
     */
    private $omniauth;

    /**
     * @var array
     */
    private $strategies;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var string[]
     */
    private $strategyClasses;

    /**
     * StrategyFactory constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface   $streamFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    public function setOmniauth(Omniauth $omniauth)
    {
        $this->omniauth = $omniauth;
    }

    public function setStrategies(array $strategies)
    {
        $this->strategies = $strategies;
    }

    public function has($name)
    {
        return isset($this->strategies[$name]);
    }

    public function register($name, $strategyClass)
    {
        $this->strategyClasses[$name] = $strategyClass;
    }

    public function create($name)
    {
        if (!isset($this->strategies[$name])) {
            throw new \InvalidArgumentException("Unknown strategy '$name");
        }
        $options = $this->strategies[$name];
        if (isset($options['strategy_class'])) {
            $strategyClass = $options['strategy_class'];
            if (!class_exists($strategyClass)) {
                $strategyClass .= 'Strategy';
            }
        } elseif (isset($this->strategyClasses[$name])) {
            $strategyClass = $this->strategyClasses[$name];
        } else {
            $strategyClass = Text::camelize($name).'Strategy';
        }

        if (class_exists($strategyClass)) {
            return new $strategyClass($name, $options, $this->omniauth, $this->responseFactory, $this->streamFactory);
        } else {
            throw new \InvalidArgumentException("Strategy class $strategyClass for $name not found");
        }
    }
}
