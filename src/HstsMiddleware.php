<?php
namespace CheatCodes\GuzzleHsts;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class HstsMiddleware
{
    /**
     * Next handler for the Guzzle middleware
     *
     * @var callable
     */
    private $nextHandler;

    /**
     * Store instances cache
     *
     * @var StoreInterface[]
     */
    private $storeInstances = [];

    /**
     * HstsMiddleware constructor
     *
     * @param callable $nextHandler Next handler to invoke.
     */
    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }

    /**
     * Invoke the guzzle middleware
     *
     * @param Request $request
     * @param array   $options
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function __invoke(Request $request, array $options)
    {
        $fn = $this->nextHandler;

        $store = $this->getStoreInstance($options);

        $request = $this->handleHstsRewrite($request, $store);

        return $fn($request, $options)
            ->then(function (Response $response) use ($request, $store) {
                return $this->handleHstsRegistering($response, $request, $store);
            });
    }

    /**
     * Rewrite the requested uri if the requested host is a known HSTS host
     *
     * @param Request        $request
     * @param StoreInterface $store
     * @return Request
     */
    private function handleHstsRewrite(Request $request, StoreInterface $store)
    {
        $uri = $request->getUri();
        $domainName = $uri->getHost();

        if ($uri->getScheme() === 'http'
            && !$this->isIpAddress($domainName)
            && $this->isKnownHstsHosts($store, $uri->getHost())
        ) {
            $uri = $uri->withScheme('https');

            return $request->withUri($uri);
        }

        return $request;
    }

    /**
     * Register the host as a known HSTS host if the header is set properly
     *
     * @param Response       $response
     * @param Request        $request
     * @param StoreInterface $store
     * @return Response
     */
    private function handleHstsRegistering(Response $response, Request $request, StoreInterface $store)
    {
        $domainName = $request->getUri()->getHost();

        if ($request->getUri()->getScheme() === 'https'
            && $response->hasHeader('Strict-Transport-Security')
            && !$this->isIpAddress($domainName)
        ) {
            $header = $response->getHeader('Strict-Transport-Security');

            // Only process the first header, https://tools.ietf.org/html/rfc6797#section-8.1
            $policy = $this->parseHeader(array_shift($header));

            if (isset($policy['max-age'])) {
                if ($policy['max-age'] < 1) {
                    $store->delete($domainName);
                } else {
                    // Remove all unneeded data from the policy
                    $policy = array_intersect_key($policy, array_flip([
                        'max-age', 'includesubdomains',
                    ]));

                    $store->set($domainName, $policy['max-age'], $policy);
                }
            }
        }

        return $response;
    }

    /**
     * Check if the given domain is a known HSTS host
     *
     * @param StoreInterface $store
     * @param string         $domainName
     * @param bool           $includeSubDomains
     * @return bool
     */
    private function checkHstsDomain(StoreInterface $store, $domainName, $includeSubDomains)
    {
        $policy = $store->get($domainName);

        return $policy !== false && (!$includeSubDomains || isset($policy['includesubdomains']));
    }

    /**
     * Check if the given domain's superdomains are known HSTS hosts
     *
     * @param StoreInterface $store
     * @param string         $domainName
     * @return bool
     */
    private function checkHstsSuperdomains(StoreInterface $store, $domainName)
    {
        $labels = explode('.', $domainName);
        $labelCount = count($labels);

        for ($i = 1; $i < $labelCount; ++$i) {
            $domainName = implode('.', array_slice($labels, $labelCount - $i));

            if ($this->checkHstsDomain($store, $domainName, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the given domain or a superdomain is a known HSTS host
     *
     * @param StoreInterface $store
     * @param string         $domainName
     * @return bool
     */
    private function isKnownHstsHosts(StoreInterface $store, $domainName)
    {
        return $this->checkHstsDomain($store, $domainName, false)
            || $this->checkHstsSuperdomains($store, $domainName);
    }

    /**
     * Get the store instance, possibly cached
     *
     * @param array $options
     * @return StoreInterface
     * @throws InvalidArgumentException
     */
    private function getStoreInstance(array $options)
    {
        // Get option or use the default store
        $store = isset($options['hsts_store']) ? $options['hsts_store'] : ArrayStore::class;

        // Just return the store if it is already an instance
        if ($store instanceof StoreInterface) {
            return $store;
        }

        // Instanciate new store or return already instanciated store
        if (is_string($store) && class_exists($store) && class_implements($store, StoreInterface::class)) {
            if (!isset($this->storeInstances[$store])) {
                $this->storeInstances[$store] = new $store();
            }

            return $this->storeInstances[$store];
        }

        throw new InvalidArgumentException('hsts_store must be an ' . StoreInterface::class .
            ' instance or the name of a class extending ' . StoreInterface::class);
    }

    /**
     * Parse the HSTS header
     *
     * @param string $header
     * @return array
     */
    private function parseHeader($header)
    {
        $directives = explode(';', $header);
        $parsed = [];

        foreach ($directives as $directive) {
            $directive = trim($directive);

            if (preg_match('/(?<name>.+?)=[\'"]?(?<value>.+?)[\'"]?$/', $directive, $matches)) {
                $name = strtolower($matches['name']);
                $value = $matches['value'];
            } else {
                $name = strtolower($directive);
                $value = true;
            }

            $parsed[$name] = $value;
        }

        return $parsed;
    }

    /**
     * Check if a host is an ip address
     *
     * @param string $host
     * @return bool
     */
    private function isIpAddress($host)
    {
        return filter_var($host, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Handler for registering the middleware
     *
     * @return \Closure
     */
    public static function handler()
    {
        return function (callable $handler) {
            return new self($handler);
        };
    }
}
