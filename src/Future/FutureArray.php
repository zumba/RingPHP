<?php
namespace GuzzleHttp\Ring\Future;

/**
 * Represents a future array value that when dereferenced returns an array.
 */
class FutureArray implements FutureArrayInterface
{
    use MagicFutureTrait;

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->_value[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->_value[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->_value[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->_value[$offset]);
    }

    public function count(): int
    {
        return count($this->_value);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->_value);
    }
}
