<?php

declare(strict_types=1);

namespace winwin\omniauth;

use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use winwin\omniauth\strategy\DefaultStrategyDetector;
use winwin\omniauth\strategy\StrategyDetectorInterface;

class OmniauthFactory
{
    /**
     * @var array
     */
    private $options;

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
     * @var IdentityTransformerInterface
     */
    private $identityTransformer;

    /**
     * @var AuthorizeRequestMatcherInterface
     */
    private $authorizeRequestMatcher;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function getConfig(): Config
    {
        if (null === $this->config) {
            $options = $this->options;
            $strategyOptions = $options['strategies'] ?? null;
            if (!isset($strategyOptions)) {
                throw new \InvalidArgumentException('strategies is required');
            }
            if (!isset($strategyOptions[Omniauth::PROVIDER_STRATEGY])) {
                $strategyOptions[Omniauth::PROVIDER_STRATEGY] = [];
            }

            $config = new Config();
            $config->setAuthKey($options['auth_key'] ?? 'auth');
            $config->setRoute($options['route'] ?? '/:strategy/:action');
            $config->buildRouteRegex(array_keys($strategyOptions));
            $config->setRedirectUriKey($options['redirect_uri_key'] ?? 'login_redirect_uri');
            $config->setCallbackUrl($options['callback_url'] ?? '/');
            $config->setStrategies($strategyOptions);

            $this->config = $config;
        }

        return $this->config;
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    /**
     * @return StorageFactoryInterface
     */
    public function getStorageFactory(): StorageFactoryInterface
    {
        if (null === $this->storageFactory) {
            $this->storageFactory = new PhpSessionStorageFactory();
        }

        return $this->storageFactory;
    }

    /**
     * @return StrategyFactoryInterface
     */
    public function getStrategyFactory(): StrategyFactoryInterface
    {
        if (null === $this->strategyFactory) {
            $this->strategyFactory = new StrategyFactory($this->getConfig()->getStrategies());
        }

        return $this->strategyFactory;
    }

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        if (null === $this->responseFactory) {
            if (!class_exists(ResponseFactory::class)) {
                throw new \RuntimeException('laminas/laminas-diactoros is required');
            }
            $this->responseFactory = new ResponseFactory();
        }

        return $this->responseFactory;
    }

    /**
     * @return StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface
    {
        if (null === $this->streamFactory) {
            if (!class_exists(StreamFactory::class)) {
                throw new \RuntimeException('laminas/laminas-diactoros is required');
            }
            $this->streamFactory = new StreamFactory();
        }

        return $this->streamFactory;
    }

    /**
     * @return StrategyDetectorInterface
     */
    public function getStrategyDetector(): StrategyDetectorInterface
    {
        if (null === $this->strategyDetector) {
            $this->strategyDetector = new DefaultStrategyDetector(array_keys($this->getConfig()->getStrategies())[0]);
        }

        return $this->strategyDetector;
    }

    /**
     * @return IdentityTransformerInterface
     */
    public function getIdentityTransformer(): IdentityTransformerInterface
    {
        if (null === $this->identityTransformer) {
            $this->identityTransformer = new DefaultIdentityTransformer();
        }

        return $this->identityTransformer;
    }

    /**
     * @return AuthorizeRequestMatcherInterface
     */
    public function getAuthorizeRequestMatcher(): AuthorizeRequestMatcherInterface
    {
        if (null === $this->authorizeRequestMatcher) {
            return new AuthorizeRequestMatcher(
                $this->options['allow_list'] ?? [],
                $this->options['allow_pattern'] ?? $this->getConfig()->getRouteRegex()
            );
        }

        return $this->authorizeRequestMatcher;
    }

    /**
     * @param StorageFactoryInterface $storageFactory
     */
    public function setStorageFactory(StorageFactoryInterface $storageFactory): void
    {
        $this->storageFactory = $storageFactory;
    }

    /**
     * @param StrategyFactoryInterface $strategyFactory
     */
    public function setStrategyFactory(StrategyFactoryInterface $strategyFactory): void
    {
        $this->strategyFactory = $strategyFactory;
    }

    /**
     * @param ResponseFactoryInterface $responseFactory
     */
    public function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param StreamFactoryInterface $streamFactory
     */
    public function setStreamFactory(StreamFactoryInterface $streamFactory): void
    {
        $this->streamFactory = $streamFactory;
    }

    /**
     * @param StrategyDetectorInterface $strategyDetector
     */
    public function setStrategyDetector(StrategyDetectorInterface $strategyDetector): void
    {
        $this->strategyDetector = $strategyDetector;
    }

    /**
     * @param IdentityTransformerInterface $identityTransformer
     */
    public function setIdentityTransformer(IdentityTransformerInterface $identityTransformer): void
    {
        $this->identityTransformer = $identityTransformer;
    }

    /**
     * @param AuthorizeRequestMatcherInterface $authorizeRequestMatcher
     */
    public function setAuthorizeRequestMatcher(AuthorizeRequestMatcherInterface $authorizeRequestMatcher): void
    {
        $this->authorizeRequestMatcher = $authorizeRequestMatcher;
    }

    public function create(ServerRequestInterface $request): Omniauth
    {
        return new Omniauth(
            $request,
            $this->getConfig(),
            $this->getStorageFactory()->create($request),
            $this->getStrategyFactory(),
            $this->getResponseFactory(),
            $this->getStreamFactory(),
            $this->getStrategyDetector(),
            $this->getIdentityTransformer(),
            $this->getAuthorizeRequestMatcher()
        );
    }
}
