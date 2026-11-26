<?php
namespace GuzzleHttp\Tests\Ring\Future;

use GuzzleHttp\Ring\Exception\CancelledFutureAccessException;
use GuzzleHttp\Ring\Future\FutureValue;
use GuzzleHttp\Tests\Ring\CapturesUnhandledRejections;
use React\Promise\Deferred;

class FutureValueTest extends \PHPUnit\Framework\TestCase
{
    use CapturesUnhandledRejections;

    public function testDerefReturnsValue()
    {
        $called = 0;
        $deferred = new Deferred();

        $f = new FutureValue(
            $deferred->promise(),
            function () use ($deferred, &$called) {
                $called++;
                $deferred->resolve('foo');
            }
        );

        $this->assertEquals('foo', $f->wait());
        $this->assertEquals(1, $called);
        $this->assertEquals('foo', $f->wait());
        $this->assertEquals(1, $called);
        $f->cancel();
        $this->assertTrue((fn () => $this->isRealized)->call($f));
    }

    public function testThrowsWhenAccessingCancelled()
    {
        $this->expectException(\GuzzleHttp\Ring\Exception\CancelledFutureAccessException::class);
        $f = new FutureValue(
            (new Deferred())->promise(),
            function () {},
            function () { return true; }
        );
        $f->cancel();
        $f->wait();
    }

    public function testThrowsWhenDerefFailure()
    {
        $this->expectException(\OutOfBoundsException::class);
        $called = false;
        $deferred = new Deferred();
        $f = new FutureValue(
            $deferred->promise(),
            function () use(&$called) {
                $called = true;
            }
        );
        $deferred->reject(new \OutOfBoundsException());
        $f->wait();
        $this->assertFalse($called);
    }

    public function testThrowsWhenDerefDoesNotResolve()
    {
        $this->expectException(\GuzzleHttp\Ring\Exception\RingException::class);
        $this->expectExceptionMessage('Waiting did not resolve future');
        $deferred = new Deferred();
        $f = new FutureValue(
            $deferred->promise(),
            function () use(&$called) {
                $called = true;
            }
        );
        $f->wait();
    }

    public function testThrowingCancelledFutureAccessExceptionCancels()
    {
        $deferred = new Deferred();
        $f = new FutureValue(
            $deferred->promise(),
            function () use ($deferred) {
                throw new CancelledFutureAccessException();
            }
        );
        try {
            $f->wait();
            $this->fail('did not throw');
        } catch (CancelledFutureAccessException $e) {}
    }

    public function testThrowingExceptionInDerefMarksAsFailed()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('foo');
        $deferred = new Deferred();
        $f = new FutureValue(
            $deferred->promise(),
            function () {
                throw new \Exception('foo');
            }
        );
        $f->wait();
    }

    public function testRejectedFutureReachesItsRejectionHandlers()
    {
        $calls = [];
        $reported = $this->unhandledRejections(function () use (&$calls) {
            $deferred = new Deferred();
            $f = new FutureValue($deferred->promise());
            $f->then(null, function (\Exception $e) use (&$calls) {
                $calls[] = 'onRejected ' . $e->getMessage();
            });
            $f->catch(function (\Exception $e) use (&$calls) {
                $calls[] = 'catch ' . $e->getMessage();
            });
            $f->otherwise(function () use (&$calls) {
                $calls[] = 'otherwise';
            });
            $f->always(function () use (&$calls) {
                $calls[] = 'always';
            })->catch(function (\Exception $e) use (&$calls) {
                $calls[] = 'always passed on ' . $e->getMessage();
            });
            $deferred->reject(new \Exception('foo'));
        });

        $this->assertSame(['onRejected foo', 'catch foo', 'otherwise', 'always', 'always passed on foo'], $calls);
        $this->assertSame([], $reported);
    }
}
