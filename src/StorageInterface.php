<?php

declare(strict_types=1);

namespace winwin\omniauth;

interface StorageInterface
{
    /**
     * Retrieve a item from storage.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key);

    /**
     * Add or Update an item to storage.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set(string $key, $value): void;

    /**
     * Delete an item from storage.
     *
     * @param string $key
     */
    public function delete(string $key): void;
}
