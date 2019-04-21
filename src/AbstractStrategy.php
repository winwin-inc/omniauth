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
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    protected $streamFactory;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    protected $defaults = [];

    /**
     * AbstractStrategy constructor.
     *
     * @param string                   $name
     * @param array                    $options
     * @param Omniauth                 $omniauth
     * @param ResponseFactoryInterface $responseFactory
     * @param StreamFactoryInterface   $streamFactory
     */
    public function __construct($name, array $options, Omniauth $omniauth, ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->omniauth = $omniauth;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->name = $name;
        $this->options = array_merge($this->defaults, $options);
    }

    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
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

    public function redirect($url)
    {
        return $this->responseFactory->createResponse(302)
            ->withHeader('location', $url);
    }

    public function login($identity)
    {
        $this->omniauth->setIdentity($identity, $this->name);
        return $this->redirect($this->omniauth->getCallbackUrl());
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
}
