<?php

namespace winwin\omniauth;

use Psr\Http\Message\ServerRequestInterface;

interface StrategyInterface
{
    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request);
}
