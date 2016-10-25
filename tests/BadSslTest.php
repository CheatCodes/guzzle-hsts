<?php
use CheatCodes\GuzzleHsts\HstsMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;

class BadSslTest extends TestCase
{
    public function testWithMiddleware()
    {
        $stack = HandlerStack::create();

        // Enable HSTS middleware
        $stack->push(HstsMiddleware::handler());

        $client = new Client(['handler' => $stack]);

        // Make first request to https to mark host as HSTS host
        $client->request('GET', 'https://hsts.badssl.com/');

        // Make second request to http to test rewrite
        $response = $client->request('GET', 'http://hsts.badssl.com/');

        $body = $response->getBody()->getContents();

        // Request was over https
        $this->assertFalse(strpos($body, 'favicon-red'));
        $this->assertNotFalse(strpos($body, 'favicon-green'));
    }

    public function testWithoutMiddleware()
    {
        $client = new Client();

        // Make first request to https
        $client->request('GET', 'https://hsts.badssl.com/');

        // Make second request to http
        $response = $client->request('GET', 'http://hsts.badssl.com/');

        $body = $response->getBody()->getContents();

        // Request was over http
        $this->assertNotFalse(strpos($body, 'favicon-red'));
        $this->assertFalse(strpos($body, 'favicon-green'));
    }

    public function testWithMiddlewareOverHttp()
    {
        $stack = HandlerStack::create();

        // Enable HSTS middleware
        $stack->push(HstsMiddleware::handler());

        $client = new Client(['handler' => $stack]);

        // Make first request to http
        $client->request('GET', 'http://hsts.badssl.com/');

        // Make second request to http
        $response = $client->request('GET', 'http://hsts.badssl.com/');

        $body = $response->getBody()->getContents();

        // Request was over http
        $this->assertNotFalse(strpos($body, 'favicon-red'));
        $this->assertFalse(strpos($body, 'favicon-green'));
    }
}
