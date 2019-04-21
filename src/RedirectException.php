<?php


namespace winwin\omniauth;


use Throwable;

class RedirectException extends \LogicException
{
    /**
     * @var string
     */
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
        parent::__construct("");
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}