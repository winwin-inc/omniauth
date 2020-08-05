<?php

declare(strict_types=1);

namespace winwin\omniauth;

use DI\Annotation\Inject;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\Configuration;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use winwin\omniauth\strategy\DefaultStrategyDetector;
use winwin\omniauth\strategy\StrategyDetectorInterface;

/**
 * @Configuration()
 */
class OmniauthConfiguration
{
    /**
     * @Bean()
     * @Inject({"options": "application.omniauth"})
     */
    public function omniauthFactory(?array $options, ContainerInterface $container): OmniauthFactory
    {
        $strategyOptions = $options['strategies'] ?? null;
        if (!isset($strategyOptions)) {
            throw new \InvalidArgumentException('strategies is required');
        }
        if (!isset($strategyOptions[Omniauth::PROVIDER_STRATEGY])) {
            $strategyOptions[Omniauth::PROVIDER_STRATEGY] = [];
        }

        $config = new Config();
        $config->setAuthKey($options['auth_key'] ?? 'auth');
        $config->setAutoLogin($options['auto_login'] ?? true);
        $config->setRoute($options['route'] ?? '/:strategy/:action');
        $config->buildRouteRegex(array_keys($strategyOptions));
        $config->setRedirectUriKey($options['redirect_uri_key'] ?? 'login_redirect_uri');
        $config->setCallbackUrl($options['callback_url'] ?? '/');

        $strategyFactory = new StrategyFactory($strategyOptions, [$container, 'get']);

        return new OmniauthFactory(
            $config,
            $strategyFactory,
            $container->get(StorageFactoryInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(StrategyDetectorInterface::class),
            $container->get(IdentityTransformerInterface::class)
        );
    }

    /**
     * @Bean()
     * @Inject("strategies": "application.omniauth.strategies")
     */
    public function strategyDetector(?array $strategies): StrategyDetectorInterface
    {
        $keys = array_keys($strategies ?? []);

        return new DefaultStrategyDetector($keys[0] ?? Omniauth::PROVIDER_STRATEGY);
    }

    /**
     * @Bean()
     */
    public function identityTransformer(): IdentityTransformerInterface
    {
        return new DefaultIdentityTransformer();
    }

    /**
     * @Bean()
     */
    public function storageFactory(): StorageFactoryInterface
    {
        return new SessionStorageFactory();
    }
}
