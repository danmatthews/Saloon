<?php

namespace Saloon\Helpers;

use Saloon\Contracts\PendingRequest;

class NewPipeline
{
    public function __construct(protected array $pipes)
    {
        //
    }

    public function run(PendingRequest $pendingRequest)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes), $this->carry(), static fn($passable) => $passable
        );

        return $pipeline($pendingRequest);
    }

    protected function carry(): \Closure
    {
        return static function ($stack, $pipe) {
            return static function ($passable) use ($stack, $pipe) {
                return $pipe($passable, $stack);
            };
        };
    }


}
