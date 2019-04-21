<?php

namespace winwin\omniauth;

class SessionStorage implements StorageInterface
{
    public function __construct()
    {
        if (session_id()) {
            return;
        }

        if (headers_sent()) {
            throw new \RuntimeException('HTTP headers already sent to browser and Hybridauth won\'t be able to start/resume PHP session. To resolve this, session_start() must be called before outputing any data.');
        }

        if (!session_start()) {
            throw new \RuntimeException('PHP session failed to start.');
        }
    }

    public function get($key)
    {
        return $_SESSION[$key] ?? null;
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }
}