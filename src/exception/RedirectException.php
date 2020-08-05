<?php

declare(strict_types=1);

namespace winwin\omniauth\exception;

class RedirectException extends \LogicException
{
    /**
     * @var string
     */
    private $url;

    public function __construct(string $url)
    {
        $this->url = $url;
        parent::__construct('');
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
