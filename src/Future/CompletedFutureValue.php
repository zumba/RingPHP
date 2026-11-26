<?php
namespace GuzzleHttp\Ring\Future;

use React\Promise\PromiseInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Represents a future value that has been resolved or rejected.
 */
class CompletedFutureValue implements FutureInterface
{
    protected $result;
    protected $error;

    private $cachedPromise;

    /**
     * @param mixed      $result Resolved result
     * @param \Exception $e      Error. Pass a GuzzleHttp\Ring\Exception\CancelledFutureAccessException
     *                           to mark the future as cancelled.
     */
    public function __construct($result, ?\Exception $e = null)
    {
        $this->result = $result;
        $this->error = $e;
    }

    public function wait()
    {
        if ($this->error) {
            throw $this->error;
        }

        return $this->result;
    }

    public function cancel(): void {}

    public function promise(): PromiseInterface
    {
        if (!$this->cachedPromise) {
            $this->cachedPromise = $this->error
                ? reject($this->error)
                : resolve($this->result);
        }

        return $this->cachedPromise;
    }

    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): PromiseInterface
    {
        return $this->promise()->then($onFulfilled, $onRejected);
    }

    public function catch(callable $onRejected): PromiseInterface
    {
        return $this->promise()->catch($onRejected);
    }

    public function finally(callable $onFulfilledOrRejected): PromiseInterface
    {
        return $this->promise()->finally($onFulfilledOrRejected);
    }

    public function otherwise(callable $onRejected): PromiseInterface
    {
        return $this->catch($onRejected);
    }

    public function always(callable $onFulfilledOrRejected): PromiseInterface
    {
        return $this->finally($onFulfilledOrRejected);
    }
}
