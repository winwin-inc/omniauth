<?php

namespace winwin\omniauth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ClosureRequestHandler implements RequestHandlerInterface
{
    /**
     * @var callable
     */
    private $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }


    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return call_user_func($this->handler, $request);
    }
}