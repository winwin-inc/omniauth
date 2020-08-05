<?php

declare(strict_types=1);

namespace winwin\omniauth;

use winwin\omniauth\exception\StrategyNotFoundException;
use winwin\omniauth\strategy\StrategyInterface;

interface StrategyFactoryInterface
{
    /**
     * @param string $name
     *
     * @return StrategyInterface
     *
     * @throws StrategyNotFoundException
     */
    public function create(Omniauth $omniauth, string $name): StrategyInterface;
}
