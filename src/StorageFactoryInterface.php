<?php

declare(strict_types=1);

namespace winwin\omniauth;

use Psr\Http\Message\ServerRequestInterface;

interface StorageFactoryInterface
{
    /**
     * Creates the storage instance.
     *
     * @param ServerRequestInterface $request
     *
     * @return StorageInterface
     */
    public function create(ServerRequestInterface $request): StorageInterface;
}
