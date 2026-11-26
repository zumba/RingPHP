<?php
namespace GuzzleHttp\Ring\Future;

/**
 * Represents a future array that has been completed successfully.
 */
class CompletedFutureArray extends CompletedFutureValue implements FutureArrayInterface
{
    public function __construct(array $result)
    {
        parent::__construct($result);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->result[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->result[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->result[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->result[$offset]);
    }

    public function count(): int
    {
        return count($this->result);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->result);
    }
}
