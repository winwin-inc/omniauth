<?php

declare(strict_types=1);

namespace winwin\omniauth\strategy;

use Psr\Http\Message\ServerRequestInterface;

class DefaultStrategyDetector implements StrategyDetectorInterface
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function detect(ServerRequestInterface $request): string
    {
        return $this->name;
    }
}
