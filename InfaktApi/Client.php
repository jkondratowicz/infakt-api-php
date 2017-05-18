<?php

namespace InfaktApi;

use InfaktApi\OAuth;

class Client {

    protected $apiUrl = 'https://api.infakt.pl/v3/';
    protected $token = NULL;
    protected $format = 'json';
    protected $timeout = 30;
    protected $connecttimeout = 30;
    protected $ssl_verifypeer = FALSE;
    protected $http_info;
    protected $useragent = 'InFakt API OAuth client';
    protected $http_code;
    protected $url;
    protected $decode_json = FALSE;

    public function __construct($apiKey) {
        $this->token = $apiKey;
    }

    public function makeRequest($url, $method, $parameters, $postdata = "") {
        if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
            $url = "{$this->apiUrl}{$url}.{$this->format}";
        }

        switch ($method) {
            case 'GET':
                return $this->http($url . '?' . http_build_query($parameters), 'GET');
            default:
                return $this->http($url, $method, $postdata);
        }
    }

    protected function requestHeaders() {
        switch ($this->format) {
            case 'json':
                return ['Content-type: application/json', 'Accept: application/json'];
            case 'xml':
                return ['Content-type: application/xml', 'Accept: application/xml'];
            default:
                return [];
        }
    }

    protected function http($url, $method, $postfields = NULL) {
        $this->http_info = array();
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
        curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $this->addAuthHeader($this->requestHeaders()));
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
        curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
        curl_setopt($ci, CURLOPT_HEADER, FALSE);

        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (!empty($postfields)) {
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
                }
                break;
            case 'DELETE':
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($postfields)) {
                    $url = "{$url}?{$postfields}";
                }
        }

        curl_setopt($ci, CURLOPT_URL, $url);
        // curl_setopt($ci, CURLOPT_VERBOSE, true);
        $response = curl_exec($ci);
        $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
        $this->url = $url;
        curl_close($ci);
        $httpcode = intval($this->http_code);
        if ($httpcode > 299) {
            if ($httpcode === 401) {
                throw new Exception\UnauthorizedException();
            } else {
                throw new Exception\BadRequestException($response);
            }
        }

        return $response;
    }

    protected function getHeader($ch, $header) {
        $i = strpos($header, ':');
        if (!empty($i)) {
            $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
            $value = trim(substr($header, $i + 2));
            $this->http_header[$key] = $value;
        }
        return strlen($header);
    }

    /**
     * GET wrapper for makeRequest
     */
    public function get($url, $parameters = array()) {
        $response = $this->makeRequest($url, 'GET', $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode($response);
        }
        return $response;
    }

    /**
     * POST wrapper for makeRequest
     */
    public function post($url, $parameters = array()) {
        if ($this->format === 'json') {
            $parameters = json_encode($parameters);
        }
        $response = $this->makeRequest($url, 'POST', array(), $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode($response);
        }
        return $response;
    }

    /**
     * DELETE wrapper for makeRequest.
     */
    public function delete($url, $parameters = array()) {
        $response = $this->makeRequest($url, 'DELETE', $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode($response);
        }
        return $response;
    }

    public function addAuthHeader($headers = []) {
        $headers[] = 'X-inFakt-ApiKey: ' . $this->token;
        return $headers;
    }

}
