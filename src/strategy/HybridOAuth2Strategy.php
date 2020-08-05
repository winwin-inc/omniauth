<?php

declare(strict_types=1);

namespace winwin\omniauth\strategy;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\HttpClient\Util;
use Hybridauth\Logger\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use winwin\omniauth\exception\RedirectException;
use winwin\omniauth\exception\StopException;
use winwin\omniauth\Text;

class HybridOAuth2Strategy extends AbstractStrategy
{
    /**
     * @var OAuth2
     */
    private $hybridAuth;

    /**
     * @var \Hybridauth\Storage\StorageInterface
     */
    private static $storage;

    /**
     * @var HttpClientInterface
     */
    private static $httpClient;

    /**
     * @var LoggerInterface
     */
    private static $logger;

    /**
     * @return ResponseInterface
     *
     * @throws \Hybridauth\Exception\Exception
     * @throws \Hybridauth\Exception\NotImplementedException
     */
    public function authenticate(): ResponseInterface
    {
        try {
            $this->getHybridAuth()->authenticate();

            return $this->login(array_filter(
                get_object_vars($this->getHybridAuth()->getUserProfile()),
                static function ($value): bool {
                    return isset($value);
                }));
        } catch (RedirectException $e) {
            return $this->redirect($e->getUrl());
        } catch (StopException $e) {
            return $this->redirect($this->omniauth->getCallbackUrl());
        }
    }

    /**
     * @return ResponseInterface
     *
     * @throws \Hybridauth\Exception\Exception
     * @throws \Hybridauth\Exception\NotImplementedException
     */
    public function verify(): ResponseInterface
    {
        return $this->authenticate();
    }

    /**
     * @return OAuth2
     */
    public function getHybridAuth()
    {
        if (null === $this->hybridAuth) {
            $providerClass = $this->options['provider_class'] ?? 'Hybridauth\\Provider\\'.Text::camelize($this->name);
            $options = $this->options + [
                    'callback' => $this->action('verify', true),
                ];
            $this->hybridAuth = new $providerClass($options, self::$httpClient, self::$storage, self::$logger);
        }

        return $this->hybridAuth;
    }

    public function clear(): void
    {
        $this->getHybridAuth()->disconnect();
    }

    public static function setUp(HttpClientInterface $httpClient = null, \Hybridauth\Storage\StorageInterface $storage = null, LoggerInterface $logger = null): void
    {
        self::$httpClient = $httpClient;
        self::$storage = $storage;
        self::$logger = $logger;
        Util::setRedirectHandler(function ($url): void {
            throw new RedirectException($url);
        });
        Util::setExitHandler(function (): void {
            throw new StopException();
        });
    }
}
