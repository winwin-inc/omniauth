<?php

declare(strict_types=1);

namespace winwin\omniauth\strategy;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use winwin\omniauth\Omniauth;

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
     * @var array
     */
    protected $defaults = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Omniauth
     */
    public function getOmniauth(): Omniauth
    {
        return $this->omniauth;
    }

    /**
     * @param Omniauth $omniauth
     */
    public function setOmniauth(Omniauth $omniauth): void
    {
        $this->omniauth = $omniauth;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string     $name
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function getOption(string $name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options + $this->defaults;
    }

    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->omniauth->getResponseFactory();
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->omniauth->getStreamFactory();
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->omniauth->getRequest();
    }

    public function action(string $action = '', bool $absolute = false): string
    {
        $url = $this->omniauth->buildUrl($this->name, $action);
        if ($absolute) {
            return (string) $this->getRequest()->getUri()->withPath($url)->withQuery('');
        } else {
            return $url;
        }
    }

    /**
     * @param mixed $identity
     *
     * @return ResponseInterface
     */
    public function login($identity): ResponseInterface
    {
        $this->omniauth->setIdentity($identity, $this->name);

        return $this->redirect($this->omniauth->getCallbackUrl());
    }

    public function redirect(string $url): ResponseInterface
    {
        return $this->getResponseFactory()->createResponse(302)
            ->withHeader('location', $url);
    }

    public function clear(): void
    {
    }
}
