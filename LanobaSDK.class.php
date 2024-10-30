<?php

define('API_BASE', 'http://api.lanoba.com/');

if (version_compare(PHP_VERSION, '5.2.0', '<')) {
    exit('LanobaSDK requires PHP 5 >= 5.2.0');
}

class LanobaSDK {

    private $api_secret, $base = 'https://api.lanoba.com/';

    // don't include
    public function setBase($base)
    {
        $this->base = $base;
    }
//------------------------------------------------------------------------------
    public function __construct($api_secret)
    {
        $this->api_secret = $api_secret;
    }
//------------------------------------------------------------------------------
    public function verify_signature($parameters)
    {       
        $lb_params = array("uid" => $parameters['uid'],
            "token" => $parameters['token'],
            "signature" => $parameters['signature']);

        if (isset($parameters['mapped_uid']))
            $lb_params["mapped_uid"] = $parameters['mapped_uid'];

        $signature = $parameters['signature'];

        unset($lb_params['signature']);
        ksort($lb_params);

        return base64_decode($signature) == hash_hmac('sha1', http_build_query($lb_params), base64_decode($this->api_secret), TRUE);
    }
//------------------------------------------------------------------------------
    public function post($method, $parameters)
    {
        $ch = curl_init($this->base . $method . '?api_secret=' . urlencode($this->api_secret));
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, TRUE);
    }
//------------------------------------------------------------------------------
    public function get($method, $parameters)
    {
        $ch = curl_init($this->base . $method . '?api_secret=' . urlencode($this->api_secret) . '&' . http_build_query($parameters));
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, TRUE);
    }

}
