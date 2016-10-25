<?php
namespace CheatCodes\GuzzleHsts;

interface StoreInterface
{
    /**
     * Set the given domain name as a known HSTS host
     *
     * @param string $domainName
     * @param int    $expirySeconds
     * @param array  $policy
     */
    public function set($domainName, $expirySeconds, array $policy);

    /**
     * Check if the given domain name is a known HSTS host
     *
     * @param string $domainName
     * @return bool|array
     */
    public function get($domainName);

    /**
     * Forget the given domain name as a known HSTS host
     *
     * @param string $domainName
     */
    public function delete($domainName);
}
