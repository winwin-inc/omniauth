<?php

declare(strict_types=1);

namespace winwin\omniauth;

use kuiper\web\session\SessionInterface;

class SessionStorage implements StorageInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function get(string $key)
    {
        return $this->session->get($key);
    }

    public function set(string $key, $value): void
    {
        $this->session->set($key, $value);
    }

    public function delete(string $key): void
    {
        $this->session->remove($key);
    }
}
