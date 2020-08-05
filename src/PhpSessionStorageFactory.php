<?php

declare(strict_types=1);

namespace winwin\omniauth;

use Psr\Http\Message\ServerRequestInterface;

class PhpSessionStorageFactory implements StorageFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ServerRequestInterface $request): StorageInterface
    {
        return new PhpSessionStorage();
    }
}
