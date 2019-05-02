<?php

namespace winwin\omniauth;

use Psr\Http\Message\ServerRequestInterface;

interface StrategyInterface
{
    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @param array $options
     */
    public function setOptions(array $options);

    /**
     * @param Omniauth $omniauth
     */
    public function setOmniauth(Omniauth $omniauth);

    /**
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request);

    /**
     * Initiate the appropriate protocol and process/automate the authentication or authorization flow.
     */
    public function authenticate();
}
