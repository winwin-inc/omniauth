<?php

declare(strict_types=1);

namespace winwin\omniauth;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use winwin\omniauth\strategy\StrategyDetectorInterface;

class OmniauthFactory
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var StorageFactoryInterface
     */
    private $storageFactory;

    /**
     * @var StrategyFactoryInterface
     */
    private $strategyFactory;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var StrategyDetectorInterface
     */
    private $strategyDetector;

    /**
     * @var IdentityTransformerInterface|null
     */
    private $identityTransformer;

    public function __construct(Config $config, StrategyFactoryInterface $strategyFactory, StorageFactoryInterface $storageFactory, ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory, StrategyDetectorInterface $strategyDetector, IdentityTransformerInterface $identityTransformer)
    {
        $this->config = $config;
        $this->storageFactory = $storageFactory;
        $this->strategyFactory = $strategyFactory;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->strategyDetector = $strategyDetector;
        $this->identityTransformer = $identityTransformer;
    }

    public function create(ServerRequestInterface $request): Omniauth
    {
        return new Omniauth(
            $request,
            $this->config,
            $this->storageFactory->create($request),
            $this->strategyFactory,
            $this->responseFactory,
            $this->streamFactory,
            $this->strategyDetector,
            $this->identityTransformer
        );
    }
}
