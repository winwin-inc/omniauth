<?php

namespace winwin\omniauth;

interface StrategyFactoryInterface
{
    /**
     * @param Omniauth $omniauth
     */
    public function setOmniauth(Omniauth $omniauth);

    /**
     * @param array $strategies
     */
    public function setStrategies(array $strategies);

    /**
     * @param string $name
     *
     * @return StrategyInterface
     *
     * @throws StrategyNotFoundException
     */
    public function create($name);
}
