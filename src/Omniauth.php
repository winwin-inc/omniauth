<?php

declare(strict_types=1);

namespace winwin\omniauth;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use winwin\omniauth\exception\StrategyNotFoundException;
use winwin\omniauth\strategy\ProviderStrategy;
use winwin\omniauth\strategy\StrategyDetectorInterface;
use winwin\omniauth\strategy\StrategyInterface;

class Omniauth
{
    public const REQUEST_AUTH_KEY = '__OMNIAUTH';

    public const PROVIDER_STRATEGY = 'provider';
    private const STRATEGY_KEY = '_omniauth_strategy';

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StorageInterface
     */
    private $storage;

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
     * @var array<string,StrategyInterface>
     */
    private $strategies;

    public function __construct(ServerRequestInterface $request, Config $config, StorageInterface $storage, StrategyFactoryInterface $strategyFactory, ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory, StrategyDetectorInterface $strategyDetector, IdentityTransformerInterface $identityTransformer)
    {
        $this->request = $request;
        $this->config = $config;
        $this->storage = $storage;
        $this->strategyFactory = $strategyFactory;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->strategyDetector = $strategyDetector;
        $this->identityTransformer = $identityTransformer;
    }

    public static function get(ServerRequestInterface $request): ?Omniauth
    {
        return $request->getAttribute(self::REQUEST_AUTH_KEY);
    }

    public function authenticate(): ?ResponseInterface
    {
        $matches = $this->match($this->request);
        if (null === $matches) {
            if ($this->config->isAutoLogin() && !$this->isAuthenticated()) {
                return $this->responseFactory->createResponse(302)
                    ->withHeader('location', $this->getDefaultAuthUrl());
            }

            return null;
        } else {
            return $this->doAuthenticate($matches[1], $matches[2] ?? null);
        }
    }

    private function doAuthenticate(string $strategyName, ?string $action): ?ResponseInterface
    {
        try {
            $strategy = $this->createStrategy($strategyName);
        } catch (StrategyNotFoundException $e) {
            var_export(['no strategy', $strategyName, $e->getMessage()]);

            return null;
        }
        if (Text::isEmpty($action) || '/' === $action) {
            $action = 'authenticate';
        } else {
            $action = trim($action, '/');
        }
        if (method_exists($strategy, $action)) {
            return $strategy->$action();
        }

        return null;
    }

    public function match(ServerRequestInterface $request): ?array
    {
        if (preg_match($this->config->getRouteRegex(), $request->getUri()->getPath(), $matches)) {
            return $matches;
        }

        return null;
    }

    public function isAuthenticated(): bool
    {
        return null !== $this->getIdentity();
    }

    public function clear(): void
    {
        $this->storage->delete($this->config->getAuthKey());
        $strategy = $this->storage->get(self::STRATEGY_KEY);
        if (null !== $strategy) {
            $this->storage->delete(self::STRATEGY_KEY);
            try {
                $this->createStrategy($strategy)->clear();
            } catch (StrategyNotFoundException $e) {
            }
        }
    }

    /**
     * @return mixed
     */
    public function getIdentity()
    {
        return $this->storage->get($this->config->getAuthKey());
    }

    public function setIdentity(array $identity, string $strategyName): void
    {
        $this->storage->set($this->config->getAuthKey(), $this->identityTransformer->transform($identity, $strategyName));
        $this->storage->set(self::STRATEGY_KEY, $strategyName);
    }

    public function buildUrl(string $strategy, string $action): string
    {
        return strtr($this->config->getRoute(), [
            ':strategy' => $strategy,
            ':action' => $action,
        ]);
    }

    public function getCallbackUrl(): string
    {
        $redirect = $this->storage->get($this->config->getRedirectUriKey());
        if (Text::isNotEmpty($redirect)) {
            $this->storage->delete($this->config->getRedirectUriKey());

            return $redirect;
        }

        return $this->config->getCallbackUrl();
    }

    /**
     * @param string|UriInterface|null $redirectUri
     *
     * @return string
     */
    public function getDefaultAuthUrl($redirectUri = null): string
    {
        if (null === $redirectUri) {
            $redirectUri = $this->request->getUri();
        }
        if ($redirectUri instanceof UriInterface) {
            $redirectUri = Text::isNotEmpty($redirectUri->getQuery())
                ? $redirectUri->getPath().'?'.$redirectUri->getQuery()
                : $redirectUri->getPath();
        }
        $this->storage->set($this->config->getRedirectUriKey(), (string) $redirectUri);

        return $this->buildUrl($this->strategyDetector->detect($this->request), '');
    }

    /**
     * @param string            $redirectUrl
     * @param array             $identity
     * @param string|array|null $error
     *
     * @return ResponseInterface
     */
    public function transport(string $redirectUrl, array $identity, $error = null): ResponseInterface
    {
        try {
            /** @var ProviderStrategy $strategy */
            $strategy = $this->createStrategy(self::PROVIDER_STRATEGY);

            return $strategy->transport($redirectUrl, $identity, $error);
        } catch (StrategyNotFoundException $e) {
            throw new \RuntimeException("please add 'provider' strategy option in configuration 'strategies'");
        }
    }

    /**
     * @param string $name
     *
     * @return StrategyInterface
     *
     * @throws StrategyNotFoundException
     */
    public function createStrategy(string $name): StrategyInterface
    {
        if (!isset($this->strategies[$name])) {
            $this->strategies[$name] = $this->strategyFactory->create($this, $name);
        }

        return $this->strategies[$name];
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return StorageInterface
     */
    public function getStorage(): StorageInterface
    {
        return $this->storage;
    }

    /**
     * @return ResponseFactoryInterface
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * @return StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
