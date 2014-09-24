<?php

namespace Novanova\Odnoklassniki;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class Odnoklassniki
 * @package Novanova\Odnoklassniki
 */
class Odnoklassniki
{

    /**
     * @var string
     */
    private $app_id;
    /**
     * @var string
     */
    private $public_key;
    /**
     * @var string
     */
    private $secret;

    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @param $app_id
     * @param $public_key
     * @param $secret
     */
    public function __construct($app_id, $public_key, $secret)
    {
        $this->app_id = $app_id;
        $this->public_key = $public_key;
        $this->secret = $secret;

        $this->guzzle = new Client();
    }

    /**
     * @return string
     */
    public function app_id()
    {
        return $this->app_id;
    }

    /**
     * @return string
     */
    public function public_key()
    {
        return $this->public_key;
    }

    /**
     * @param $method
     * @param $params
     * @param $access_token
     * @return mixed
     * @throws OdnoklassnikiException
     */
    public function api($method, array $params = array(), $access_token = null)
    {
        $params['application_key'] = $this->public_key;
        $params['method'] = $method;
        $params['format'] = 'json';
        $params['sig'] = $this->sign($params, $access_token);
        if ($access_token) {
            $params['access_token'] = $access_token;
        }

        return $this->call('http://api.odnoklassniki.ru/fb.do', $params);
    }

    /**
     * @param $method
     * @param $params
     * @return mixed
     * @throws OdnoklassnikiException
     */
    public function promo_api($method, array $params = array())
    {
        $params['appId'] = $this->app_id;
        $params['format'] = 'json';
        $params['sig'] = $this->sign($params);

        return $this->call('http://sp.odnoklassniki.ru/projects/common/' . $method, $params);
    }

    /**
     * @param  string                 $code
     * @param  string                 $redirect_uri
     * @return mixed
     * @throws OdnoklassnikiException
     */
    public function getAccessToken($code, $redirect_uri)
    {
        $params = array(
            'client_id' => $this->app_id,
            'client_secret' => $this->secret,
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code',
        );

        return $this->call('https://api.odnoklassniki.ru/oauth/token.do', $params);
    }

    /**
     * @param $params
     * @param $access_token
     * @return string
     */
    public function sign(array $params, $access_token = null)
    {
        $sign = '';
        ksort($params);
        foreach ($params as $key => $value) {
            if ('sig' == $key || 'resig' == $key) {
                continue;
            }
            $sign .= $key . '=' . $value;
        }

        $sign .= $access_token ? md5($access_token . $this->secret) : $this->secret;

        return md5($sign);
    }

    /**
     * @param $params
     * @return mixed
     * @throws OdnoklassnikiException
     */
    private function call($url, array $params)
    {
        try {
            $response = $this->guzzle->post(
                $url,
                array(
                    'body' => $params
                )
            )->getBody();
        } catch (RequestException $e) {
            throw new OdnoklassnikiException($e->getMessage());
        }

        if (!$response = json_decode($response)) {
            throw new OdnoklassnikiException('Response parse error');
        }

        if (!empty($response->error_code) && !empty($response->error_msg)) {
            throw new OdnoklassnikiException($response->error_msg, $response->error_code);
        }

        return $response;
    }
}
