<?php

declare(strict_types=1);

namespace winwin\omniauth;

class Config
{
    /**
     * @var string
     */
    private $authKey;

    /**
     * @var string
     */
    private $redirectUriKey;

    /**
     * @var string
     */
    private $route;

    /**
     * @var bool
     */
    private $autoLogin;

    /**
     * @var string
     */
    private $callbackUrl;

    /**
     * @var string
     */
    private $routeRegex;

    /**
     * @return string
     */
    public function getAuthKey(): string
    {
        return $this->authKey;
    }

    /**
     * @param string $authKey
     */
    public function setAuthKey(string $authKey): void
    {
        $this->authKey = $authKey;
    }

    /**
     * @return string
     */
    public function getRedirectUriKey(): string
    {
        return $this->redirectUriKey;
    }

    /**
     * @param string $redirectUriKey
     */
    public function setRedirectUriKey(string $redirectUriKey): void
    {
        $this->redirectUriKey = $redirectUriKey;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @param string $route
     */
    public function setRoute(string $route): void
    {
        $this->route = $route;
    }

    /**
     * @return bool
     */
    public function isAutoLogin(): bool
    {
        return $this->autoLogin;
    }

    /**
     * @param bool $autoLogin
     */
    public function setAutoLogin(bool $autoLogin): void
    {
        $this->autoLogin = $autoLogin;
    }

    /**
     * @return string
     */
    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    /**
     * @param string $callbackUrl
     */
    public function setCallbackUrl(string $callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @param string[] $strategies
     */
    public function buildRouteRegex(array $strategies): void
    {
        $strategyRe = implode('|', array_map('preg_quote', $strategies));
        $re = str_replace([':strategy', '/:action'],
            ['('.$strategyRe.')', '(/[A-Za-z0-9-_]*)?'],
            $this->route);

        $this->routeRegex = '#^'.$re.'$#';
    }

    /**
     * @return string
     */
    public function getRouteRegex(): string
    {
        if (null === $this->routeRegex) {
            throw new \LogicException('call buildRouteRegex first');
        }

        return $this->routeRegex;
    }
}
