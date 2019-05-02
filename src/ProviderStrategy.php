<?php

namespace winwin\omniauth;

/**
 * Class ProviderStrategy.
 *
 * options:
 *  - provider
 *  - timeout
 *  - key
 *  - sign_type  hmac_sha256 | rsa
 *  - rsa_private_key
 *  - rsa_public_key
 *  - transport_method GET|POST
 */
class ProviderStrategy extends AbstractStrategy
{
    const HMAC_SHA256 = 'hmac_sha256';

    protected $defaults = [
        'sign_type' => self::HMAC_SHA256,
        'timeout' => '2 minutes',
        'transport_method' => 'GET',
    ];

    public function authenticate()
    {
        $authUrl = $this->options['provider'];
        $authUrl .= (false === strpos($authUrl, '?') ? '?' : '&') . 'redirect_uri='
            . urlencode($this->buildRedirectUri());

        return $this->redirect($authUrl);
    }

    public function verify()
    {
        return $this->login($this->getIdentity());
    }

    public function transport($redirectUrl, array $identity, $error = null)
    {
        $data = [
            'timestamp' => date('c'),
            'nonce' => substr(md5(uniqid('', true)), 0, 8),
        ];
        if (isset($error)) {
            $data['error'] = json_encode($error);
        } else {
            $data['auth'] = json_encode($identity);
        }
        $data['sign_type'] = $this->options['sign_type'];
        $data['sign'] = $this->sign($data);
        if ('GET' == $this->options['transport_method']) {
            return $this->redirect($redirectUrl . (false === strpos($redirectUrl, '?') ? '?' : '&') . 'auth=' . base64_encode(json_encode($data)));
        } else {
            $html = '<!doctype html><html lang="en"><body onload="postit();"><form name="auth" method="post" action="' . $redirectUrl . '">';
            foreach ($data as $key => $value) {
                $html .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
            }

            $html .= '</form>';
            $html .= '<script type="text/javascript">function postit(){ document.auth.submit(); }</script>';
            $html .= '</body></html>';

            return $this->getResponseFactory()->createResponse()
                ->withBody($this->getStreamFactory()->createStream($html));
        }
    }

    /**
     * @return \Psr\Http\Message\UriInterface
     */
    private function buildRedirectUri()
    {
        return $this->request->getUri()->withPath($this->action('verify'))
            ->withQuery(http_build_query([
                'redirect_uri' => $this->request->getUri()
            ]));
    }

    private function getIdentity()
    {
        $request = $this->request;
        if ('GET' == $this->options['transport_method']) {
            $params = $request->getQueryParams();
            if (!isset($params['auth'])) {
                throw new AuthParameterException('auth parameter is missing');
            }
            $data = json_decode(base64_decode($params['auth']), true);
        } else {
            $data = $request->getParsedBody();
        }
        if (isset($data['error'])) {
            if (is_string($data['error'])) {
                throw new AuthFailException($data['error']);
            } elseif (isset($data['error']['code'], $data['error']['message'])) {
                throw new AuthFailException($data['error']['message'], $data['error']['code']);
            } else {
                throw new AuthParameterException("auth response is malformed");
            }
        }
        if (!isset($data['auth'])) {
            throw new AuthParameterException('auth identity is missing');
        }
        if (!isset($data['sign'])) {
            throw new AuthParameterException('signature is missing');
        }
        if (!isset($data['timestamp'])) {
            throw new AuthParameterException('timestamp is missing');
        }
        if (strtotime($data['timestamp']) < strtotime('-' . $this->options['timeout'])) {
            throw new AuthParameterException('auth response expired');
        }
        if (!$this->verifySignature($data)) {
            throw new AuthParameterException('signature not match');
        }

        return json_decode($data['auth'], true);
    }

    private function verifySignature(array $data)
    {
        $signType = isset($data['sign_type']) ? $data['sign_type'] : self::HMAC_SHA256;
        $signature = $data['sign'];
        unset($data['sign']);
        if ($signType == self::HMAC_SHA256) {
            return $signature == $this->sign($data);
        } else {
            return openssl_verify($this->getSignContext($data), base64_decode($signature), $this->getRsaPublicKey(), OPENSSL_ALGO_SHA256);
        }
    }

    private function sign(array $data)
    {
        $signType = isset($data['sign_type']) ? $data['sign_type'] : self::HMAC_SHA256;
        if ($signType == self::HMAC_SHA256) {
            if (!isset($this->options['key'])) {
                throw new \InvalidArgumentException("provider security key is required");
            }
            return hash_hmac('sha256', $this->getSignContext($data), $this->options['key']);
        } else {
            openssl_sign($this->getSignContext($data), $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA256);
            return base64_encode($sign);
        }

    }

    /**
     * @param array $data
     * @return string
     */
    private function getSignContext(array $data)
    {
        ksort($data);
        return http_build_query($data);
    }

    private function getRsaPublicKey()
    {
        if (isset($this->options['rsa_public_key']) && is_resource($this->options['rsa_public_key'])) {
            return $this->options['rsa_public_key'];
        }
        if (isset($this->options['rsa_public_key_path'])) {
            $content = file_get_contents($this->options['rsa_public_key_path']);
            if (!$content) {
                throw new \RuntimeException("Cannot read rsa public key file " . $this->options['rsa_public_key_path']);
            }
            $this->options['rsa_public_key'] = $content;
        } elseif (isset($this->options['rsa_public_key']) && is_string($this->options['rsa_public_key'])) {
            $this->options['rsa_public_key'] = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($this->options['rsa_public_key'], 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        } else {
            throw new \InvalidArgumentException("provider rsa public key is required");
        }
        $pubKey = openssl_get_publickey($this->options['rsa_public_key']);
        if (!is_resource($pubKey)) {
            throw new \InvalidArgumentException("provider rsa public key is invalid");
        }
        return $this->options['rsa_public_key'] = $pubKey;
    }

    private function getRsaPrivateKey()
    {
        if (isset($this->options['rsa_private_key']) && is_resource($this->options['rsa_private_key'])) {
            return $this->options['rsa_private_key'];
        }
        if (isset($this->options['rsa_private_key_path'])) {
            $content = file_get_contents($this->options['rsa_private_key_path']);
            if (!$content) {
                throw new \RuntimeException("Cannot read rsa private key file " . $this->options['rsa_private_key_path']);
            }
            $this->options['rsa_private_key'] = $content;
        } elseif (isset($this->options['rsa_private_key']) && is_string($this->options['rsa_private_key'])) {
            $this->options['rsa_private_key'] =  "-----BEGIN RSA PRIVATE KEY-----\n".
                wordwrap($this->options['rsa_private_key'], 64, "\n", true).
                "\n-----END RSA PRIVATE KEY-----";
        } else {
            throw new \InvalidArgumentException("provider rsa private key is required");
        }
        $pubKey = openssl_get_privatekey($this->options['rsa_private_key']);
        if (!is_resource($pubKey)) {
            throw new \InvalidArgumentException("provider rsa private key is invalid");
        }
        return $this->options['rsa_private_key'] = $pubKey;
    }
}
