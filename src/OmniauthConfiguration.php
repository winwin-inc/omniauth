<?php

declare(strict_types=1);

namespace winwin\omniauth;

use DI\Attribute\Inject;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnProperty;
use kuiper\di\attribute\Configuration;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use winwin\omniauth\strategy\StrategyDetectorInterface;

#[Configuration]
#[ConditionalOnProperty('application.omniauth')]
class OmniauthConfiguration
{
    #[Bean]
    public function omniauthFactory(
        #[Inject('application.omniauth')] array $options,
        ContainerInterface $container): OmniauthFactory
    {
        $omniauthFactory = new OmniauthFactory($options);
        if ($container->has(StrategyFactoryInterface::class)) {
            $omniauthFactory->setStorageFactory($container->get(StrategyFactoryInterface::class));
        } else {
            $strategyFactory = new StrategyFactory($omniauthFactory->getConfig()->getStrategies(), [$container, 'get']);
            $omniauthFactory->setStrategyFactory($strategyFactory);
        }
        if ($container->has(StorageFactoryInterface::class)) {
            $omniauthFactory->setStorageFactory($container->get(StorageFactoryInterface::class));
        }
        if ($container->has(ResponseFactoryInterface::class)) {
            $omniauthFactory->setResponseFactory($container->get(ResponseFactoryInterface::class));
        }
        if ($container->has(StreamFactoryInterface::class)) {
            $omniauthFactory->setStreamFactory($container->get(StreamFactoryInterface::class));
        }
        if ($container->has(StrategyDetectorInterface::class)) {
            $omniauthFactory->setStrategyDetector($container->get(StrategyDetectorInterface::class));
        }
        if ($container->has(IdentityTransformerInterface::class)) {
            $omniauthFactory->setIdentityTransformer($container->get(IdentityTransformerInterface::class));
        }
        if ($container->has(AuthorizeRequestMatcherInterface::class)) {
            $omniauthFactory->setAuthorizeRequestMatcher($container->get(AuthorizeRequestMatcherInterface::class));
        }

        return $omniauthFactory;
    }

    #[Bean]
    public function sessionStorage(): StorageFactoryInterface
    {
        return new SessionStorageFactory();
    }
}
