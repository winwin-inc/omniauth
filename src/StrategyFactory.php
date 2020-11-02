<?php

declare(strict_types=1);

namespace winwin\omniauth;

use winwin\omniauth\exception\StrategyNotFoundException;
use winwin\omniauth\strategy\StrategyInterface;

class StrategyFactory implements StrategyFactoryInterface
{
    /**
     * @var array
     */
    private $strategyOptions;

    /**
     * @var array<string,string>
     */
    private $strategyClasses;

    /**
     * @var callable
     */
    private $strategyInstantiator;

    /**
     * StrategyFactory constructor.
     *
     * @param array         $strategyOptions
     * @param callable|null $strategyInstantiator
     */
    public function __construct(array $strategyOptions, ?callable $strategyInstantiator = null)
    {
        $this->strategyOptions = $strategyOptions;
        $this->strategyInstantiator = $strategyInstantiator;
    }

    public function register(string $name, string $strategyClass): void
    {
        $this->strategyClasses[$name] = $strategyClass;
    }

    /**
     * {@inheritdoc}
     */
    public function create(Omniauth $omniauth, string $name): StrategyInterface
    {
        if (!isset($this->strategyOptions[$name])) {
            throw new StrategyNotFoundException("Unknown strategy '$name");
        }
        $strategyClass = $this->getStrategyClass($name);
        $strategy = $this->newStrategy($strategyClass);
        $strategy->setName($name);
        $strategy->setOptions($this->strategyOptions[$name]);
        $strategy->setOmniauth($omniauth);

        return $strategy;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getStrategyClass($name): string
    {
        $options = $this->strategyOptions[$name];
        if (isset($options['strategy_class'])) {
            $strategyClass = $options['strategy_class'];
            if (!class_exists($strategyClass)) {
                $strategyClass .= 'Strategy';
            }
        } elseif (isset($this->strategyClasses[$name])) {
            $strategyClass = $this->strategyClasses[$name];
        } else {
            $strategyClass = __NAMESPACE__.'\\strategy\\'.StringUtil::camelize($name).'Strategy';
        }

        if (!class_exists($strategyClass)) {
            throw new \InvalidArgumentException("Strategy class $strategyClass for $name not found");
        }

        return $strategyClass;
    }

    /**
     * @param string $strategyClass
     *
     * @return StrategyInterface
     */
    protected function newStrategy(string $strategyClass): StrategyInterface
    {
        return isset($this->strategyInstantiator) ? call_user_func($this->strategyInstantiator, $strategyClass) : new $strategyClass();
    }
}
