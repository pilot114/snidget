<?php

namespace Snidget;

use LogicException;
use Snidget\Exception\SnidgetException;

class Request
{
    public string $uri;
    public mixed $data;

    public function buildFromGlobal(): self
    {
        $uri = $_SERVER['QUERY_STRING']
            ?? $_SERVER['REQUEST_URI']
            ?? throw new SnidgetException('Нет строки запроса в $_SERVER');

        $this->uri = trim($uri, '/');
        $this->data = json_decode(file_get_contents('php://input'), true);
        return $this;
    }

    public function buildFromString(string $request, float $startTimeNs): self
    {
        if (str_contains($request, "\n\n")) {
            [$headers, $body] = explode("\n\n", $request);
            $headers = array_filter(explode("\n", $headers));
        } else {
            $headers = array_filter(explode("\n", $request), fn($x) => $x);
            $body = null;
        }
        [$method, $url, $protocol] = explode(' ', array_shift($headers));

        $this->uri = trim($url, '/');
        $this->data = $body ? json_decode($body, true) : $body;
        return $this;

        // TODO

//        $_SERVER['DOCUMENT_ROOT']      = $_SERVER['PWD'];
//        $_SERVER['SERVER_PROTOCOL']    = $protocol;
//        $_SERVER['REQUEST_METHOD']     = $method;
//        $_SERVER['REQUEST_URI']        = $url;
//        $_SERVER['SERVER_NAME']        = self::HOST;
//        $_SERVER['SERVER_PORT']        = self::PORT;
//        $_SERVER['REQUEST_TIME_FLOAT'] = round($startTimeNs / 1_000_000, 3);
//        $_SERVER['REQUEST_TIME']       = (int)$_SERVER['REQUEST_TIME_FLOAT'];

        /*
    [HTTP_HOST] => localhost:8000
    [HTTP_CONNECTION] => keep-alive
    [HTTP_CACHE_CONTROL] => max-age=0
    [HTTP_SEC_CH_UA] => "Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"
    [HTTP_SEC_CH_UA_MOBILE] => ?0
    [HTTP_SEC_CH_UA_PLATFORM] => "Linux"
    [HTTP_UPGRADE_INSECURE_REQUESTS] => 1
    [HTTP_USER_AGENT] => Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36
    [HTTP_SEC_FETCH_SITE] => same-origin
    [HTTP_SEC_FETCH_MODE] => navigate
    [HTTP_SEC_FETCH_USER] => ?1
    [HTTP_SEC_FETCH_DEST] => document
    [HTTP_REFERER] => http://localhost:8000/admin
    [HTTP_ACCEPT_ENCODING] => gzip, deflate, br
    [HTTP_ACCEPT_LANGUAGE] => ru,en;q=0.9,uk;q=0.8
    [HTTP_COOKIE] => _ym_uid=1653586060120340513; _ym_d=1657383905

    GET /admin/routes HTTP/1.1
    Host: localhost:8000
    Connection: keep-alive
    Cache-Control: max-age=0
    sec-ch-ua: "Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"
    sec-ch-ua-mobile: ?0
    sec-ch-ua-platform: "Linux"
    Upgrade-Insecure-Requests: 1
    User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36
    Sec-Fetch-Site: same-origin
    Sec-Fetch-Mode: navigate
    Sec-Fetch-User: ?1
    Sec-Fetch-Dest: document
    Referer: http://localhost:8000/admin
    Accept-Encoding: gzip, deflate, br
    Accept-Language: ru,en;q=0.9,uk;q=0.8
    Cookie: _ym_uid=1653586060120340513; _ym_d=1657383905
        */
    }
}