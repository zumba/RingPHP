<?php
namespace GuzzleHttp\Tests\Ring;

use function React\Promise\set_rejection_handler;

trait CapturesUnhandledRejections
{
    private function unhandledRejections(callable $fn): array
    {
        $reasons = [];
        $record = function (\Throwable $reason) use (&$reasons, &$record) {
            set_rejection_handler($record);
            $reasons[] = $reason;
        };
        $previous = set_rejection_handler($record);
        try {
            $fn();
            gc_collect_cycles();
        } finally {
            set_rejection_handler($previous);
        }

        return $reasons;
    }
}
