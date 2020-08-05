<?php

declare(strict_types=1);

namespace winwin\omniauth\strategy;

use Psr\Http\Message\ResponseInterface;
use winwin\omniauth\Omniauth;

interface StrategyInterface
{
    /**
     * @param string $name
     */
    public function setName(string $name): void;

    /**
     * @param array $options
     */
    public function setOptions(array $options): void;

    /**
     * @param Omniauth $omniauth
     */
    public function setOmniauth(Omniauth $omniauth): void;

    /**
     * Initiate the appropriate protocol and process/automate the authentication or authorization flow.
     */
    public function authenticate(): ResponseInterface;

    /**
     * Clear stored data.
     */
    public function clear(): void;
}
