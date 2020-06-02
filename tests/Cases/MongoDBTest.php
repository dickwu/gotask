<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf/GoTask.
 *
 * @link     https://www.github.com/hyperf/gotask
 * @document  https://www.github.com/hyperf/gotask
 * @contact  guxi99@gmail.com
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace HyperfTest\Cases;

use Hyperf\GoTask\MongoClient\MongoClient;
use Swoole\Process;

/**
 * @internal
 * @coversNothing
 */
class MongoDBTest extends AbstractTestCase
{
    /**
     * @var Process
     */
    private $p;

    /**
     * @var mixed|MongoClient
     */
    private $client;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->p = new Process(function (Process $process) {
            $process->exec(__DIR__ . '/../../mongo', ['-address', self::UNIX_SOCKET]);
        });
        $this->p->start();
        sleep(1);
        $this->client = make(MongoClient::class);
        \Swoole\Coroutine\run(function () {
            $this->client->database('testing')->collection('unit')->drop();
        });
    }

    public function tearDown(): void
    {
        Process::kill($this->p->pid);
    }

    public function testInsert()
    {
        \Swoole\Coroutine\run(function () {
            $client = make(MongoClient::class);

            $collection = $client->database('testing')->collection('unit');
            $this->assertNotNull($collection->insertOne(['foo' => 'bar', 'tid' => 0]));
            $this->assertNotNull($collection->insertMany([['foo' => 'bar', 'tid' => 1], ['foo' => 'bar', 'tid' => 2]]));
            $this->assertEquals(3, $collection->countDocuments());
        });
    }

    public function testFind()
    {
        \Swoole\Coroutine\run(function () {
            $client = make(MongoClient::class);
            $collection = $client->database('testing')->collection('unit');
            $this->assertNotNull($collection->insertOne(['foo' => 'bar', 'tid' => 0]));
            $this->assertNotNull($collection->insertMany([['foo' => 'bar', 'tid' => 1], ['foo' => 'bar', 'tid' => 2]]));
            $result = $collection->findOne(['foo' => 'bar']);
            $this->assertEquals('bar', $result['foo']);
            $this->assertEquals('0', $result['tid']);
            $result = $collection->find(['foo' => 'bar']);
            $this->assertCount(3, $result);
            foreach ($result as $value) {
                $this->assertEquals('bar', $value['foo']);
            }
            $result = $collection->find(['foo' => 'bar'], ['skip' => 1, 'limit' => 1]);
            $this->assertCount(1, $result);
            $this->assertEquals(1, $result[0]['tid']);
        });
    }

    public function testDecimalKeys()
    {
        \Swoole\Coroutine\run(function () {
            $client = make(MongoClient::class);
            $collection = $client->database('testing')->collection('unit');
            $this->assertNotNull($collection->insertOne(['foo' => 'bar', 2 => 1]));
            $result = $collection->findOne([2 => 1]);
            $this->assertEquals('bar', $result['foo']);
            $this->assertEquals(1, $result[2]);
        });
    }

    public function testReplace()
    {
        \Swoole\Coroutine\run(function () {
            $client = make(MongoClient::class);
            $collection = $client->database('testing')->collection('unit');
            $collection->insertMany([['foo' => 'bar', 'tid' => 1], ['foo' => 'bar', 'tid' => 2]]);
            $collection->replaceOne(['tid' => 1], ['foo' => 'baz', 'tid' => 3]);
            $result = $collection->findOne(['foo' => 'baz']);
            $this->assertEquals(3, $result['tid']);
        });
    }

    public function testUpdate()
    {
        \Swoole\Coroutine\run(function () {
            $client = make(MongoClient::class);
            $collection = $client->database('testing')->collection('unit');
            $collection->insertMany([['foo' => 'bar', 'tid' => 1], ['foo' => 'bar', 'tid' => 2]]);
            $collection->updateOne(['tid' => 1], ['$inc' => ['tid' => 5]]);
            $result = $collection->findOne(['tid' => 6]);
            $this->assertEquals(6, $result['tid']);
            $collection->updateMany(['foo' => 'bar'], ['$inc' => ['tid' => 5]]);
            $result = $collection->findOne(['tid' => 7]);
            $this->assertEquals(7, $result['tid']);
            $result = $collection->findOne(['tid' => 11]);
            $this->assertEquals(11, $result['tid']);
        });
    }

    public function testDelete()
    {
        \Swoole\Coroutine\run(function () {
            $client = make(MongoClient::class);
            $collection = $client->database('testing')->collection('unit');
            $collection->insertMany([['foo' => 'bar', 'tid' => 1], ['foo' => 'bar', 'tid' => 2]]);
            $this->assertNotNull($collection->deleteOne(['foo' => 'bar']));
            $this->assertEquals(1, $collection->countDocuments());
            $collection->insertMany([['foo' => 'bar', 'tid' => 1], ['foo' => 'bar', 'tid' => 2]]);
            $this->assertNotNull($collection->deleteMany(['foo' => 'bar']));
            $this->assertEquals(0, $collection->countDocuments());
        });
    }

    public function testAggregate()
    {
        \Swoole\Coroutine\run(function () {
            $client = make(MongoClient::class);
            $collection = $client->database('testing')->collection('unit');
            $collection->insertMany([['foo' => 'bar', 'tid' => 1], ['foo' => 'bar', 'tid' => 2]]);
            $result = $collection->aggregate([
                ['$match' => ['foo' => 'bar']],
                ['$group' => ['_id' => '$foo', 'total' => ['$sum' => '$tid']]],
            ]);
            $this->assertCount(1, $result);
            $this->assertEquals(3, $result[0]['total']);
        });
    }

    public function testRunCommand()
    {
        \Swoole\Coroutine\run(function () {
            $client = make(MongoClient::class);
            $database = $client->database('testing');
            $result = $database->runCommand(['ping' => 1]);
            $this->assertCount(1, $result);
            $this->assertEquals(1, $result['ok']);
            $this->assertNotnull($result = $database->runCommandCursor(['listCollections' => 1]));
        });
    }
}