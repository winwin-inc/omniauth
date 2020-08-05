<?php

declare(strict_types=1);

namespace winwin\omniauth;

class PhpSessionStorage implements StorageInterface
{
    public function __construct()
    {
        if ('' !== session_id()) {
            return;
        }

        if (headers_sent()) {
            throw new \RuntimeException('HTTP headers already sent to browser and Hybridauth won\'t be able to start/resume PHP session. To resolve this, session_start() must be called before outputing any data.');
        }

        if (!session_start()) {
            throw new \RuntimeException('PHP session failed to start.');
        }
    }

    public function get(string $key)
    {
        return $_SESSION[$key] ?? null;
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
