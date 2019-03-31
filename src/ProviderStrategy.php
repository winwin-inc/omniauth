<?php

namespace winwin\omniauth;

/**
 * Class ProviderStrategy.
 *
 * options:
 *  - provider
 *  - timeout
 *  - key
 *  - transport_method GET|POST
 */
class ProviderStrategy extends AbstractStrategy
{
    const SIGN_TYPE = 'sha256';

    protected $defaults = [
        'timeout' => '2 minutes',
        'transport_method' => 'GET',
    ];

    public function request()
    {
        $authUrl = $this->options['provider'];
        $authUrl .= (false === strpos($authUrl, '?') ? '?' : '&').'redirect_uri='
            .urlencode($this->request->getUri()->withPath($this->action('callback')));

        return $this->responseFactory->createResponse(302)
            ->withHeader('location', $authUrl);
    }

    public function callback()
    {
        $this->omniauth->setIdentity($this->getIdentity());

        return $this->responseFactory->createResponse(302)
            ->withHeader('location', $this->omniauth->getCallbackUrl());
    }

    public function transport($redirectUrl, array $identity, $error = null)
    {
        $data = [
            'timestamp' => date('c'),
            'nonce' => uniqid('', true),
        ];
        if (isset($error)) {
            $data['error'] = json_encode($error);
        } else {
            $data['auth'] = json_encode($identity);
        }
        $data['signature'] = $this->sign($data);
        if ('GET' == $this->options['transport_method']) {
            return $this->responseFactory->createResponse(302)
                ->withHeader('location', $redirectUrl.(false === strpos($redirectUrl, '?') ? '?' : '&').'auth='.base64_encode(json_encode($data)));
        } else {
            $html = '<!doctype html><html><body onload="postit();"><form name="auth" method="post" action="'.$redirectUrl.'">';
            foreach ($data as $key => $value) {
                $html .= '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'">';
            }

            $html .= '</form>';
            $html .= '<script type="text/javascript">function postit(){ document.auth.submit(); }</script>';
            $html .= '</body></html>';

            return $this->responseFactory->createResponse()
                ->withBody($this->streamFactory->createStream($html));
        }
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
        if (!isset($data['signature'])) {
            throw new AuthParameterException('signature is missing');
        }
        if (!isset($data['timestamp'])) {
            throw new AuthParameterException('timestamp is missing');
        }
        if (strtotime($data['timestamp']) < strtotime('-'.$this->options['timeout'])) {
            throw new AuthParameterException('auth response expired');
        }
        $sign = $data['signature'];
        unset($data['signature']);
        if ($sign != $this->sign($data)) {
            throw new AuthParameterException('signature not match');
        }

        return json_decode($data['auth'], true);
    }

    private function sign(array $data)
    {
        ksort($data);
        $signType = isset($data['sign_type']) ? $data['sign_type'] : self::SIGN_TYPE;

        return hash_hmac($signType, http_build_query($data), $this->options['key']);
    }
}
