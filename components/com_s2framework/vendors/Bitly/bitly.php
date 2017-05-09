<?php

namespace ClickFWD\Bitly;

class Bitly
{
    public $accessToken = null;

    public $apiUrl = 'https://api-ssl.bitly.com/';

    public $timeout = 4;

    public $connectTimeout = 2;

    static function respondWith($status_code, $msg = null, $response = null)
    {
    	return array(
    		'status' => $status_code,
    		'message' => $msg,
    		'data' => $response
    	);
    }

	public function setAccessToken($token)
	{
		$this->accessToken = $token;

		return $this;
	}

    public function shorten($url)
    {
        $params = array('longUrl' => $url);
        $results = $this->call('v3/shorten', $params);
        return $results;
    }

    protected function call($endpoint, Array $params)
    {
        $params['format'] = 'json';

        if ($this->accessToken)
        {
            $params['access_token'] = $this->accessToken;
        }
        else {
            return self::respondWith((int) 403, 'MISSING_ACCESS_TOKEN');
        }

        $url = $this->apiUrl . $endpoint . '?' . http_build_query($params);;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);

        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($status_code !== 200) {
            return self::respondWith((int) $status_code, 'CONNECTION_ERROR');
        }

        $result = json_decode($result, true);

        if ($result['status_code'] !== 200)
        {
        	return $this->respondWith((int) $result['status_code'], $result['status_txt']);
        }

        return $this->respondWith($status_code, '', $result['data']);
    }
}