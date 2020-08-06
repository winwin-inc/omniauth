<?php

declare(strict_types=1);

namespace winwin\omniauth;

use DI\Annotation\Inject;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnProperty;
use kuiper\di\annotation\Configuration;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use winwin\omniauth\strategy\DefaultStrategyDetector;
use winwin\omniauth\strategy\StrategyDetectorInterface;

/**
 * @Configuration()
 * @ConditionalOnProperty("application.omniauth")
 */
class OmniauthConfiguration
{
    /**
     * @Bean()
     */
    public function omniauthFactory(Config $config, ContainerInterface $container): OmniauthFactory
    {
        $strategyFactory = new StrategyFactory($config->getStrategies(), [$container, 'get']);

        return new OmniauthFactory(
            $config,
            $strategyFactory,
            $container->get(StorageFactoryInterface::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(StrategyDetectorInterface::class),
            $container->get(IdentityTransformerInterface::class),
            $container->get(AuthorizeRequestMatcherInterface::class)
        );
    }

    /**
     * @Bean()
     * @Inject({"options": "application.omniauth"})
     */
    public function omniauthConfig(array $options): Config
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
        $config->setRoute($options['route'] ?? '/:strategy/:action');
        $config->buildRouteRegex(array_keys($strategyOptions));
        $config->setRedirectUriKey($options['redirect_uri_key'] ?? 'login_redirect_uri');
        $config->setCallbackUrl($options['callback_url'] ?? '/');
        $config->setStrategies($strategyOptions);

        return $config;
    }

    /**
     * @Bean()
     */
    public function strategyDetector(Config $config): StrategyDetectorInterface
    {
        $keys = array_keys($config->getStrategies());

        return new DefaultStrategyDetector($keys[0]);
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

    /**
     * @Bean()
     * @Inject({
     *     "allowList": "application.omniauth.allow-list",
     *     "allowPattern": "application.omniauth.allow-pattern",
     *     })
     */
    public function authorizeRequestMatcher(Config $config, ?array $allowList, ?string $allowPattern): AuthorizeRequestMatcherInterface
    {
        return new AuthorizeRequestMatcher($allowList ?? [], $allowPattern ?? $config->getRouteRegex());
    }
}
