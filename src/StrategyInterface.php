<?php

namespace winwin\omniauth;

use Psr\Http\Message\ServerRequestInterface;

interface StrategyInterface
{
    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request);

    /**
     * Initiate the appropriate protocol and process/automate the authentication or authorization flow.
     */
    public function authenticate();
}
