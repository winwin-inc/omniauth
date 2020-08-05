<?php

declare(strict_types=1);

namespace winwin\omniauth\strategy;

use Psr\Http\Message\ServerRequestInterface;

interface StrategyDetectorInterface
{
    /**
     * Detects strategy name from request.
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function detect(ServerRequestInterface $request): string;
}
