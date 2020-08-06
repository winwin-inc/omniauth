<?php

declare(strict_types=1);

namespace winwin\omniauth;

use Psr\Http\Message\ServerRequestInterface;

interface AuthorizeRequestMatcherInterface
{
    /**
     * Checks whether the request should be authorized.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool return true if the request should be authorized
     */
    public function match(ServerRequestInterface $request): bool;
}
