<?php

declare(strict_types=1);

namespace winwin\omniauth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OminauthMiddleware implements MiddlewareInterface
{
    /**
     * @var OmniauthFactory
     */
    private $omniauthFactory;

    public function __construct(OmniauthFactory $omniauthFactory)
    {
        $this->omniauthFactory = $omniauthFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = $this->omniauthFactory->create($request);

        $response = $auth->authenticate();
        if (null !== $response) {
            return $response;
        }

        return $handler->handle($request->withAttribute(Omniauth::REQUEST_AUTH_KEY, $auth));
    }
}
