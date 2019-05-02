<?php

namespace winwin\omniauth;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

abstract class AbstractStrategy implements StrategyInterface
{
    /**
     * @var string
     */
    protected $name;
    /**
     * @var array
     */
    protected $options;

    /**
     * @var Omniauth
     */
    protected $omniauth;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    protected $defaults = [];

    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }

    public function setOptions(array $options)
    {
        $this->options = $options + $this->defaults;
    }

    public function setOmniauth(Omniauth $omniauth)
    {
        $this->omniauth = $omniauth;
    }

    /**
     * @return Omniauth
     */
    public function getOmniauth(): Omniauth
    {
        return $this->omniauth;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function getResponseFactory()
    {
        return $this->omniauth->getResponseFactory();
    }

    public function getStreamFactory()
    {
        return $this->omniauth->getStreamFactory();
    }

    public function action($name = '', $absolute = false)
    {
        $url = $this->omniauth->buildUrl($this->name, $name);
        if ($absolute) {
            return $this->request->getUri()->withPath($url)->withQuery('');
        } else {
            return $url;
        }
    }

    public function login($identity)
    {
        $this->omniauth->setIdentity($identity, $this->name);
        return $this->redirect($this->omniauth->getCallbackUrl());
    }

    public function redirect($url)
    {
        return $this->getResponseFactory()->createResponse(302)
            ->withHeader('location', $url);
    }
}
