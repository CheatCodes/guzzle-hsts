<?php
use CheatCodes\GuzzleHsts\ArrayStore;
use PHPUnit\Framework\TestCase;

class ArrayStoreTest extends TestCase
{
    /**
     * @var ArrayStore
     */
    private $store;

    public function setUp()
    {
        $this->store = new ArrayStore();
    }

    public function testSet()
    {
        $this->store->set('example.com', 60, ['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $this->store->get('example.com'));
    }

    public function testOverwrite()
    {
        $this->store->set('example.com', 60, ['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $this->store->get('example.com'));

        $this->store->set('example.com', 60, ['foo' => 'asd']);

        $this->assertEquals(['foo' => 'asd'], $this->store->get('example.com'));
    }

    public function testExpire()
    {
        $this->store->set('example.com', 3, ['foo' => 'bar']);

        $this->assertNotFalse($this->store->get('example.com'));

        sleep(4);

        $this->assertFalse($this->store->get('example.com'));
    }

    public function testDelete()
    {
        $this->store->set('example.com', 60, ['foo' => 'bar']);

        $this->assertNotFalse($this->store->get('example.com'));

        $this->store->delete('example.com');

        $this->assertFalse($this->store->get('example.com'));
    }
}
