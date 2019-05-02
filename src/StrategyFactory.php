<?php

namespace winwin\omniauth;

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
     * @var string[]
     */
    private $strategyClasses;

    /**
     * @var callable
     */
    private $strategyInstantiator;

    public function setOmniauth(Omniauth $omniauth)
    {
        $this->omniauth = $omniauth;
    }

    public function setStrategies(array $strategies)
    {
        $this->strategies = $strategies;
    }

    /**
     * @param callable $strategyInstantiator
     * @return static
     */
    public function setStrategyInstantiator($strategyInstantiator)
    {
        $this->strategyInstantiator = $strategyInstantiator;
    }

    public function register($name, $strategyClass)
    {
        $this->strategyClasses[$name] = $strategyClass;
    }

    public function create($name)
    {
        if (!isset($this->strategies[$name])) {
            throw new StrategyNotFoundException("Unknown strategy '$name");
        }
        $strategyClass = $this->getStrategyClass($name);

        if (class_exists($strategyClass)) {
            $strategy = $this->newStrategy($strategyClass);
            $strategy->setName($name);
            $strategy->setOptions($this->strategies[$name]);
            $strategy->setOmniauth($this->omniauth);
            return $strategy;
        } else {
            throw new \InvalidArgumentException("Strategy class $strategyClass for $name not found");
        }
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getStrategyClass($name)
    {
        $options = $this->strategies[$name];
        if (isset($options['strategy_class'])) {
            $strategyClass = $options['strategy_class'];
            if (!class_exists($strategyClass)) {
                $strategyClass .= 'Strategy';
            }
        } elseif (isset($this->strategyClasses[$name])) {
            $strategyClass = $this->strategyClasses[$name];
        } else {
            $strategyClass = Text::camelize($name) . 'Strategy';
        }
        return $strategyClass;
    }

    /**
     * @param string $strategyClass
     * @return StrategyInterface
     */
    protected function newStrategy(string $strategyClass)
    {
        return $this->strategyInstantiator ? call_user_func($this->strategyInstantiator, $strategyClass) : new $strategyClass();
    }
}
