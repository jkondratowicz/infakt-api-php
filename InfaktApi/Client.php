<?php

namespace InfaktApi;

use InfaktApi\OAuth;

class Client {

    protected $apiUrl = 'https://www.infakt.pl/api/v2/';
    protected $requestTokenUrl = 'https://www.infakt.pl/oauth/request_token';
    protected $accessTokenUrl = 'https://www.infakt.pl/oauth/access_token';
    protected $authorizeUrl = 'https://www.infakt.pl/oauth/authorize';
    protected $consumer = NULL;
    protected $signatureMethod = NULL;
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

    public function __construct($consumer_key, $consumer_secret) {
        $this->signatureMethod = new OAuth\SignatureMethod\HMACSHA1();

        $this->consumer = new OAuth\Consumer($consumer_key, $consumer_secret);
    }

    public function oAuthRequest($url, $method, $parameters) {
        if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
            $url = "{$this->apiUrl}{$url}.{$this->format}";
        }
        //printf("do oauth: %s method: %s", $url, $method);
        $request = OAuth\Request::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
        $request->sign_request($this->signatureMethod, $this->consumer, $this->token);
        //var_dump($request->to_url());exit;
        switch ($method) {
            case 'GET':
                return $this->http($request->to_url(), 'GET');
            default:
                return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
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
        curl_setopt($ci, CURLOPT_HTTPHEADER, $this->requestHeaders());
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
        $response = curl_exec($ci);
        $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
        $this->url = $url;
        curl_close($ci);

        $httpcode = intval($this->http_code);
        if ($httpcode !== 200) {
            if ($httpcode === 401) {
                throw new Exception\UnauthorizedException();
            } else {
                throw new Exception\BadRequestException();
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

    public function get($url, $parameters = array()) {
        $response = $this->oAuthRequest($url, 'GET', $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode($response);
        }
        return $response;
    }

    /**
     * POST wrapper for oAuthRequest.
     */
    public function post($url, $parameters = array()) {
        $response = $this->oAuthRequest($url, 'POST', $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode($response);
        }
        return $response;
    }

    /**
     * DELETE wrapper for oAuthReqeust.
     */
    public function delete($url, $parameters = array()) {
        $response = $this->oAuthRequest($url, 'DELETE', $parameters);
        if ($this->format === 'json' && $this->decode_json) {
            return json_decode($response);
        }
        return $response;
    }

    public function authorize($username, $password) {
        $parameters = array();
        $parameters['x_auth_username'] = $username;
        $parameters['x_auth_password'] = $password;
        $parameters['x_auth_mode'] = 'client_auth';

        $oauth_request = OAuth\Request::from_consumer_and_token($this->consumer, null, 'GET', $this->requestTokenUrl);
        $oauth_request->sign_request($this->signatureMethod, $this->consumer, null);
        $response = $this->http($oauth_request->to_url(), 'GET');
        parse_str($response, $response);

        $this->token = new OAuth\Token($response['oauth_token'], $response['oauth_token_secret']);
        $oauth_request = OAuth\Request::from_consumer_and_token($this->consumer, $this->token, 'POST', $this->accessTokenUrl, $parameters);
        $oauth_request->sign_request($this->signatureMethod, $this->consumer, $this->token);

        $response = $this->http($oauth_request->to_url(), 'GET');
        parse_str($response, $token_array);

        $this->token = new OAuth\Consumer($token_array['oauth_token'], $token_array['oauth_token_secret']);

        return true;
    }

}
