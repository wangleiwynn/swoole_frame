<?php

namespace SwoStar\Supper;

class curl
{
    private $ch;
    public $response;
    public $curl_error_code;
    public $curl_error_message;
    public $http_status_code;

    function __construct()
    {
        $this->ch = curl_init();
    }

    function setRequestHeaders($headers)
    {
        foreach ($headers as $key => $value)
            $head[] = $key . ': ' . $value;
        $this->setOpt(CURLOPT_HTTPHEADER, $head);
    }

    function get($url, $data = array())
    {
        if (count($data) > 0)
            $this->setOpt(CURLOPT_URL, $url . '?' . http_build_query($data));
        else
            $this->setOpt(CURLOPT_URL, $url);

        $this->setOpt(CURLOPT_HTTPGET, true);

        $this->exec();
    }

    private function preparePayload($data)
    {
        $this->setOpt(CURLOPT_POST, true);
        if (is_array($data) || is_object($data))
            $data = http_build_query($data);
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    function post($url, $data = array())
    {
        $this->setOpt(CURLOPT_URL, $url);
        $this->preparePayload($data);
        $this->exec();
    }

    function setRequestOptions($options)
    {
        foreach ($options as $option => $value)
            $this->setOpt(constant('CURLOPT_' . str_replace('CURLOPT_', '', strtoupper($option))), $value);
    }

    function setOpt($option, $value)
    {
        return curl_setopt($this->ch, $option, $value);
    }

    function get_request_options()
    {
        return curl_getinfo($this->ch);
    }

    private function exec()
    {
        $this->response = curl_exec($this->ch);
        $this->curl_error_code = curl_errno($this->ch);
        $this->curl_error_message = curl_error($this->ch);
        $this->http_status_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    }

    function optReset()
    {
        curl_reset($this->ch);
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }
}


