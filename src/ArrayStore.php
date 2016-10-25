<?php
namespace CheatCodes\GuzzleHsts;

use DateInterval;
use DateTime;

class ArrayStore implements StoreInterface
{
    /**
     * List of the known HSTS hosts
     *
     * @var array[]
     */
    private $store = [];

    /**
     * Set the given domain name as a known HSTS host
     *
     * @param string $domainName
     * @param int    $expirySeconds
     * @param array  $policy
     */
    public function set($domainName, $expirySeconds, array $policy)
    {
        $expiryDate = new DateTime();
        $expiryDate->add(new DateInterval('PT' . $expirySeconds . 'S'));

        $this->store[$domainName] = [
            'expiry' => $expiryDate,
            'policy' => $policy,
        ];
    }

    /**
     * Check if the given domain name is a known HSTS host
     *
     * @param string $domainName
     * @return bool|array
     */
    public function get($domainName)
    {
        if (isset($this->store[$domainName])) {
            if ($this->store[$domainName]['expiry'] > new DateTime()) {
                return $this->store[$domainName]['policy'];
            } else {
                $this->delete($domainName);
            }
        }

        return false;
    }

    /**
     * Forget the given domain name as a known HSTS host
     *
     * @param string $domainName
     */
    public function delete($domainName)
    {
        unset($this->store[$domainName]);
    }
}
