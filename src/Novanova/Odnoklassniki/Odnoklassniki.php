<?php

namespace Novanova\Odnoklassniki;


/**
 * Class Odnoklassniki
 * @package Novanova\Odnoklassniki
 */
class Odnoklassniki
{

    private $app_id;
    private $public_key;
    private $secret;

    public function __construct($app_id, $public_key, $secret)
    {
        $this->app_id = $app_id;
        $this->public_key = $public_key;
        $this->secret = $secret;
    }

    public function app_id()
    {
        return $this->app_id;
    }

    public function public_key()
    {
        return $this->public_key;
    }

    public function api($method, $params)
    {
        $params['application_key'] = $this->public_key;
        $params['method'] = $method;
        $params['format'] = 'json';
        $params['sig'] = $this->sign($params);

        $response = file_get_contents('http://api.odnoklassniki.ru/fb.do?' . http_build_query($params));
        if (!$response = json_decode($response)) {
            throw new OdnoklassnikiException('Odnoklassniki API error');
        }
        return $response;
    }

    public function promo_api($method, $params)
    {
        $params['appId'] = $this->app_id;
        $params['format'] = 'json';
        $params['sig'] = $this->sign($params);

        $response = file_get_contents(
            'http://sp.odnoklassniki.ru/projects/common/' . $method . '?' . http_build_query($params)
        );
        if (!$response = json_decode($response)) {
            throw new OdnoklassnikiException('Odnoklassniki API error');
        }
        return $response;
    }

    public function sign($params)
    {
        $sign = '';
        ksort($params);
        foreach ($params as $key => $value) {
            if ('sig' == $key || 'resig' == $key) {
                continue;
            }
            $sign .= $key . '=' . $value;
        }

        $sign .= $this->secret;
        return md5($sign);
    }

}