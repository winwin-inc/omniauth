<?php


namespace winwin\omniauth;


use Hybridauth\Adapter\OAuth2;
use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\HttpClient\Util;
use Hybridauth\Logger\LoggerInterface;
use Psr\Http\Message\ResponseInterface;

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
     * @throws \Hybridauth\Exception\Exception
     * @throws \Hybridauth\Exception\NotImplementedException
     */
    public function authenticate()
    {
        try {
            $this->getHybridAuth()->authenticate();
            return $this->login(array_filter(get_object_vars($this->getHybridAuth()->getUserProfile()), function($value) {
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
     * @throws \Hybridauth\Exception\Exception
     * @throws \Hybridauth\Exception\NotImplementedException
     */
    public function verify()
    {
        return $this->authenticate();
    }

    /**
     * @return OAuth2
     */
    public function getHybridAuth()
    {
        if (!$this->hybridAuth) {
            $providerClass = $this->options['provider_class'] ?? 'Hybridauth\\Provider\\' . Text::camelize($this->name);
            $options = $this->options + [
                    'callback' => $this->action('verify', true)
                ];
            $this->hybridAuth = new $providerClass($options, self::$httpClient, self::$storage, self::$logger);
        }
        return $this->hybridAuth;
    }

    public static function setUp(HttpClientInterface $httpClient = null, \Hybridauth\Storage\StorageInterface $storage = null, LoggerInterface $logger = null)
    {
        self::$httpClient = $httpClient;
        self::$storage = $storage;
        self::$logger = $logger;
        Util::setRedirectHandler(function ($url) {
            throw new RedirectException($url);
        });
        Util::setExitHandler(function () {
            throw new StopException();
        });
    }
}