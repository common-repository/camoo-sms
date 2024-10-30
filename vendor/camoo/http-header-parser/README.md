HTTP Parser
===========

A library to parse HTTP header and HTTP header fields

# Usage

## Parsing a request
```php
        $rawHttp = <<<RAW
POST /path HTTP/1.1
User-Agent: PHP-SOAP/\BeSimple\SoapClient
Host: url.com:80
Accept: */*
Accept-Encoding: deflate, gzip
Content-Type:text/xml; charset=utf-8
Content-Length: 1108
Expect: 100-continue

RAW;
        /**
         * Managing Http fields
         */
        $parser = new HttpRequestParser();
        $parser->parse($rawHttp);
        echo $parser->get('User-Agent');
        //output PHP-SOAP/\BeSimple\SoapClient
        echo $parser->get('Host');
        //output url.com:80
        /**
         * Managing Http header
         * @var HttpRequestHeader $entityHeader
         */
        $entityHeader = $parser->getHeader();
        echo $entityHeader->getMethod();
        //output POST
        echo $entityHeader->getPath();
        //output /path
        echo $entityHeader->getProtocol();
        //output HTTP/1.1

```

## Parsing a response
```php
        $rawHttp = <<<RAW
        HTTP/1.1 100 Continue

        HTTP/1.1 200 OK
        Date: Tue, 12 Apr 2016 13:58:01 GMT
        Server: Apache/2.2.14 (Ubuntu)
        X-Powered-By: PHP/5.3.14 ZendServer/5.0
        Set-Cookie: ZDEDebuggerPresent=php,phtml,php3; path=/
        Set-Cookie: PHPSESSID=6sf8fa8rlm8c44avk33hhcegt0; path=/; HttpOnly
        Expires: Thu, 19 Nov 1981 08:52:00 GMT
        Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
        Pragma: no-cache
        Vary: Accept-Encoding
        Content-Encoding: gzip
        Content-Length: 192
        Content-Type: text/xml
RAW;

        /**
         * Managing Http fields
         */
        $parser = new HttpResponseParser();
        $parser->parse($raw);
        echo $parser->get('Content-Length');
        //output 192
        echo $parser->get('Content-Type');
        //output text/xml
        /**
         * Managing Http header
         * @var HttpRequestHeader $entityHeader
         */
        $entityHeader = $parser->getHeader();
        echo $entityHeader->getMessage();
        //output OK
        echo $entityHeader->getCode();
        //output 200
        echo $entityHeader->getProtocol();
        //output HTTP/1.1

```
