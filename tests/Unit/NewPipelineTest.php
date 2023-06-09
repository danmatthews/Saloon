<?php

use Saloon\Contracts\PendingRequest;
use Saloon\Helpers\NewPipeline;
use Saloon\Tests\Fixtures\Connectors\TestConnector;
use Saloon\Tests\Fixtures\Requests\UserRequest;

test('you can process through many pipes', function () {
    $pipes = [
        static function (PendingRequest $pendingRequest, Closure $next) {
            $sender = $pendingRequest->getConnector()->sender();

            return $sender->sendRequest($pendingRequest);
        },
        static function (PendingRequest $pendingRequest, Closure $next) {
            return $next($pendingRequest);
        },
        static function (PendingRequest $pendingRequest, Closure $next) {
            $response = $next($pendingRequest);

            return $response;
        },
        static function (PendingRequest $pendingRequest, Closure $next) {
            // Send Request


            return $next($pendingRequest);
        },
    ];

    $connector = new TestConnector;
    $pendingRequest = $connector->createPendingRequest(new UserRequest);

    $pipeline = new NewPipeline($pipes);
    $final = $pipeline->run($pendingRequest);

    dd('final', $final->body());
});

test('empty test', function () {
    $pipeline = new NewPipeline([]);
    $final = $pipeline->get('Hello World');

    dd($final);
});
