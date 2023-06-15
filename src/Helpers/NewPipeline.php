<?php

namespace Saloon\Helpers;

use Closure;
use Saloon\Contracts\PendingRequest;
use Saloon\Contracts\Response;

class NewPipeline
{
    public function __construct(protected array $pipes)
    {
        //
    }

    public function run(PendingRequest $pendingRequest): Response
    {
        $this->pipes[] = static function (PendingRequest $pendingRequest, Closure $next) {
            return $pendingRequest->getConnector()->sender()->sendRequest($pendingRequest);
        };

        $action = static fn(PendingRequest $pendingRequest) => $pendingRequest;

        foreach (array_reverse($this->pipes) as $pipe) {
            $action = static fn(PendingRequest $pendingRequest): Response => $pipe($pendingRequest, $action);
        }

        return $action($pendingRequest);
    }
}
