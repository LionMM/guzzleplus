<?php namespace LionMM\GuzzlePlus;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Cookie\CookieJar;
use Faker\Factory as Faker;
use GuzzleHttp\Psr7\Response;

/**
 * Class GuzzlePlus
 * @package LionMM\GuzzlePlus
 */
class GuzzlePlus
{
    const REQUEST_TYPE_GET = 'GET';
    const REQUEST_TYPE_POST = 'POST';

    /** @var  Client */
    public $client;

    /** @var int|null */
    private $lastStatus;

    /**
     * GuzzlePlus constructor.
     */
    public function __construct()
    {
        $this->initHttpClient();
    }

    /**
     * @param array $cookies
     * @param bool $host
     */
    public function initHttpClient($cookies = [], $host = false)
    {
        if ($this->client) {
            unset($this->client);
        }

        $data = [
            'base_uri' => '',
            'verify' => false,
            'cookies' => true,
            'headers' => [
                'User-Agent' => with(Faker::create())->userAgent,
            ],
        ];

        if ($cookies) {
            $jar = new CookieJar();
            $jar = $jar->fromArray($cookies, $host); // array_get(parse_url($uri), 'host')
            $data['cookies'] = $jar;
        }

        $this->client = new Client($data);
    }

    /**
     * @param $uri
     * @param array $data
     * @param string $type
     * @param bool|string $proxy
     * @param int $timeout
     *
     * @return string|bool
     */
    public function uriContent($uri, $data = [], $type = 'GET', $proxy = false, $timeout = 60)
    {
        $type = strtoupper($type);
        $data = $this->prepareDataArray($uri, $data, $type, $proxy, $timeout);

        try {
            /** @var Response $response */
            $response = $this->client->request(
                $type,
                $uri,
                $data
            );

            $content_type = $response->getHeader('Content-Type');
            if (array_get($content_type, '0') === 'application/json') {
                return json_decode($response->getBody());
            }

            $this->lastStatus = $response->getStatusCode();
            return (string)$response->getBody();

        } catch (ClientException $e) {
            $this->lastStatus = $response->getStatusCode();
            return false;
        }
    }

    /**
     * @param $url
     * @param bool $proxy
     * @param int $timeout
     * @return bool|mixed|string
     */
    public function urlGetContents($url, $proxy = false, $timeout = 60)
    {
        $get_data = [];

        $url_elements = parse_url($url);
        if (array_get($url_elements, 'query')) {
            $get_appends = array_get($url_elements, 'query', '');
            parse_str($get_appends, $get_appends);
            $get_data = $get_appends;
        }

        $url =
            array_get($url_elements, 'scheme', 'http')
            . '://'
            . array_get($url_elements, 'host')
            . array_get($url_elements, 'path', '');

        return $this->uriContent($url, $get_data, 'get', $proxy, $timeout);
    }


    /**
     * @param $url
     * @param int $timeout
     * @param int $retry_cnt
     * @return bool|mixed|string
     * @throws \ErrorException
     */
    public function recursiveUrlGetContent($url, $timeout = 10, $retry_cnt = 3)
    {
        $try_cnt = 0;
        $result = false;

        while (!$result && $try_cnt <= $retry_cnt) {
            $result = $this->urlGetContents($url, false, $timeout);
            $retry_cnt++;
        }

        if ($result) {
            return $result;
        } else {
            throw new \ErrorException('Failed get content (' . $url . '): Connection timed out');
        }
    }

    /**
     * @param $uri
     * @param $data
     * @param $type
     * @param $proxy
     * @param $timeout
     *
     * @return array
     */
    private function prepareDataArray($uri, $data, $type, $proxy, $timeout)
    {
        $get_data = $type === self::REQUEST_TYPE_GET ? $data : [];
        $post_data = $type === self::REQUEST_TYPE_POST ? $data : [];

        $url_elements = parse_url($uri);
        if (array_get($url_elements, 'query')) {
            $get_appends = array_get($url_elements, 'query', '');
            parse_str($get_appends, $get_appends);
            $get_data = array_merge($get_data, $get_appends);
        }

        $data = [
            'query' => $get_data,
            'form_params' => $post_data,
            'connect_timeout' => $timeout,
            'debug' => config('guzzleplus.debug'),
            'allow_redirects' => config('guzzleplus.allow_redirects'),
            'read_timeout' => $timeout,
        ];
        if ($proxy) {
            $data['proxy'] = 'tcp://' . $proxy;
        }

        return $data;
    }

    /**
     * @return int|null
     */
    public function getLastStatus()
    {
        return $this->lastStatus;
    }

    /**
     * @param int|null $statusCode
     * @return self
     */
    public function setLastStatus($statusCode = null)
    {
        $this->lastStatus = $statusCode;

        return $this;
    }
} 