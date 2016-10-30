<?php
use CheatCodes\GuzzleHsts\ArrayStore;
use CheatCodes\GuzzleHsts\HstsMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    public function testWithHeaderOverHttp()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'http://example.com/');

        $client->request('GET', 'http://example.com/');

        $this->assertCount(2, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('http', $request->getUri()->getScheme());
    }

    public function testWithHeaderOverHttps()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'https://example.com/');

        $client->request('GET', 'http://example.com/');

        $this->assertCount(2, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('https', $request->getUri()->getScheme());
    }

    public function testWithoutHeader()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200),
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'https://example.com/');

        $client->request('GET', 'http://example.com/');

        $this->assertCount(2, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('http', $request->getUri()->getScheme());
    }

    public function testWithRedirect()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(301, ['Location' => 'https://example.com']),
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'http://example.com/');

        $client->request('GET', 'http://example.com/');

        $this->assertCount(3, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[2]['request'];

        $this->assertEquals('https', $request->getUri()->getScheme());
    }

    public function testIncludeSubDomains()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000; includeSubDomains']),
            new Response(200),
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'https://example.com/');

        $client->request('GET', 'http://example.com/');

        $client->request('GET', 'http://foobar.example.com/');

        $this->assertCount(3, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('https', $request->getUri()->getScheme());

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[2]['request'];

        $this->assertEquals('https', $request->getUri()->getScheme());
    }

    public function testIncludeSubDomainsFromSub()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000; includeSubDomains']),
            new Response(200),
            new Response(200),
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'https://bar.example.com/');

        $client->request('GET', 'http://bar.example.com/');

        $client->request('GET', 'http://foo.bar.example.com/');

        $client->request('GET', 'http://example.com/');

        $this->assertCount(4, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('https', $request->getUri()->getScheme());

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[2]['request'];

        $this->assertEquals('https', $request->getUri()->getScheme());

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[3]['request'];

        $this->assertEquals('http', $request->getUri()->getScheme());
    }

    public function testIpAddressHost()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'https://127.0.0.1/');

        $client->request('GET', 'http://127.0.0.1/');

        $this->assertCount(2, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('http', $request->getUri()->getScheme());
    }

    public function testExpired()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => 'max-age=3']),
            new Response(200),
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'https://example.com/');

        $client->request('GET', 'http://example.com/');

        sleep(4);

        $client->request('GET', 'http://example.com/');

        $this->assertCount(3, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('https', $request->getUri()->getScheme());

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[2]['request'];

        $this->assertEquals('http', $request->getUri()->getScheme());
    }

    public function testMultipleHeaders()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => ['max-age=31536000', 'max-age=0']]),
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'https://example.com/');

        $client->request('GET', 'http://example.com/');

        $this->assertCount(2, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('https', $request->getUri()->getScheme());
    }

    public function testMaxAge0()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
            new Response(200, ['Strict-Transport-Security' => 'max-age=0']),
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        // Set HSTS
        $client->request('GET', 'https://example.com/');

        // Set HSTS to 0
        $client->request('GET', 'http://example.com/');

        $client->request('GET', 'http://example.com/');

        $this->assertCount(3, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('https', $request->getUri()->getScheme());

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[2]['request'];

        $this->assertEquals('http', $request->getUri()->getScheme());
    }

    public function testInvalidHeader()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => 'foobar']),
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'https://example.com/');

        $client->request('GET', 'http://example.com/');

        $this->assertCount(2, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('http', $request->getUri()->getScheme());
    }

    public function testInvalidHeaderNoMaxAge()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => 'includeSubDomains; preload']),
            new Response(200),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client(['handler' => $handler]);

        $client->request('GET', 'https://example.com/');

        $client->request('GET', 'http://example.com/');

        $this->assertCount(2, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('http', $request->getUri()->getScheme());
    }

    public function testStoreInstance()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client([
            'handler'    => $handler,
            'hsts_store' => new ArrayStore(),
        ]);

        $client->request('GET', 'https://example.com/');

        $client->request('GET', 'http://example.com/');

        $this->assertCount(2, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('https', $request->getUri()->getScheme());
    }

    public function testStoreClass()
    {
        $container = [];

        $mock = new MockHandler([
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
            new Response(200, ['Strict-Transport-Security' => 'max-age=31536000']),
        ]);

        $handler = HandlerStack::create($mock);

        $handler->push(HstsMiddleware::handler());
        $handler->push(Middleware::history($container));

        $client = new Client([
            'handler'    => $handler,
            'hsts_store' => ArrayStore::class,
        ]);

        $client->request('GET', 'https://example.com/');

        $client->request('GET', 'http://example.com/');

        $this->assertCount(2, $container);

        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $container[1]['request'];

        $this->assertEquals('https', $request->getUri()->getScheme());
    }

    public function testInvalidStore()
    {
        $handler = HandlerStack::create();

        $handler->push(HstsMiddleware::handler());

        $client = new Client([
            'handler'    => $handler,
            'hsts_store' => 'invalid store',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $client->request('GET', 'http://example.com');
    }
}
