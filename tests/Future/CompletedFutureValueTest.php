<?php
namespace GuzzleHttp\Tests\Ring\Future;

use GuzzleHttp\Ring\Exception\CancelledFutureAccessException;
use GuzzleHttp\Ring\Future\CompletedFutureValue;
use GuzzleHttp\Tests\Ring\CapturesUnhandledRejections;
use React\Promise\PromiseInterface;

class CompletedFutureValueTest extends \PHPUnit\Framework\TestCase
{
    use CapturesUnhandledRejections;

    public function testReturnsValue()
    {
        $f = new CompletedFutureValue('hi');
        $this->assertEquals('hi', $f->wait());
        $f->cancel();

        $a = null;
        $f->then(function ($v) use (&$a) {
            $a = $v;
        });
        $this->assertSame('hi', $a);
    }

    public function testThrows()
    {
        $ex = new \Exception('foo');
        $f = new CompletedFutureValue(null, $ex);
        $f->cancel();
        try {
            $f->wait();
            $this->fail('did not throw');
        } catch (\Exception $e) {
            $this->assertSame($e, $ex);
        }
    }

    public function testMarksAsCancelled()
    {
        $ex = new CancelledFutureAccessException();
        $f = new CompletedFutureValue(null, $ex);
        try {
            $f->wait();
            $this->fail('did not throw');
        } catch (\Exception $e) {
            $this->assertSame($e, $ex);
        }
    }

    public function testFulfillsItsPromise()
    {
        $f = new CompletedFutureValue('hi');
        $calls = [];
        $f->then(function ($v) use (&$calls) {
            $calls[] = "then $v";
        });
        $f->catch(function () use (&$calls) {
            $calls[] = 'catch';
        });
        $f->finally(function () use (&$calls) {
            $calls[] = 'finally';
        });
        $f->always(function () use (&$calls) {
            $calls[] = 'always';
        });

        $this->assertSame(['then hi', 'finally', 'always'], $calls);
        $this->assertInstanceOf(PromiseInterface::class, $f->promise());
        $this->assertSame($f->promise(), $f->promise());
    }

    public function testRejectsItsPromiseWithTheError()
    {
        $ex = new \RuntimeException('foo');
        $calls = [];
        $reported = $this->unhandledRejections(function () use ($ex, &$calls) {
            $f = new CompletedFutureValue(null, $ex);
            $f->then(function () use (&$calls) {
                $calls[] = 'then';
            }, function ($e) use ($ex, &$calls) {
                $calls[] = $e === $ex ? 'onRejected' : 'onRejected with another error';
            });
            $f->catch(function ($e) use ($ex, &$calls) {
                $calls[] = $e === $ex ? 'catch' : 'catch with another error';
            });
            $f->otherwise(function () use (&$calls) {
                $calls[] = 'otherwise';
            });
            $f->finally(function () use (&$calls) {
                $calls[] = 'finally';
            })->catch(function ($e) use ($ex, &$calls) {
                $calls[] = $e === $ex ? 'finally passed the error on' : 'finally passed another error on';
            });
        });

        $this->assertSame(['onRejected', 'catch', 'otherwise', 'finally', 'finally passed the error on'], $calls);
        $this->assertSame([], $reported);
    }

    public function testReportsARejectionNothingHandles()
    {
        $ex = new \RuntimeException('foo');
        $reported = $this->unhandledRejections(function () use ($ex) {
            $f = new CompletedFutureValue(null, $ex);
            $f->then(function () {
            });
        });

        $this->assertSame([$ex], $reported);
    }

    public function testWaitAloneCreatesNoPromiseToReport()
    {
        $reported = $this->unhandledRejections(function () {
            $f = new CompletedFutureValue(null, new \RuntimeException('foo'));
            try {
                $f->wait();
            } catch (\RuntimeException $e) {
            }
        });

        $this->assertSame([], $reported);
    }
}
