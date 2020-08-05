<?php

declare(strict_types=1);

namespace winwin\omniauth;

use kuiper\web\security\SecurityContext;
use Psr\Http\Message\ServerRequestInterface;

class SessionStorageFactory implements StorageFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ServerRequestInterface $request): StorageInterface
    {
        return new SessionStorage(SecurityContext::fromRequest($request)->getSession());
    }
}
