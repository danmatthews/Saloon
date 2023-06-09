<?php

use Saloon\Contracts\PendingRequest;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Helpers\NewPipeline;
use Saloon\Tests\Fixtures\Connectors\TestConnector;
use Saloon\Tests\Fixtures\Requests\ErrorRequest;
use Saloon\Tests\Fixtures\Requests\UserRequest;

test('you can process through many pipes', function () {
    $pipes = [
        static function (PendingRequest $pendingRequest, Closure $next) {
            ray(3);

            try {
                return $next($pendingRequest);
            } catch (InternalServerErrorException $exception) {
                ray('howdy', $exception);
            }
        },
        static function (PendingRequest $pendingRequest, Closure $next) {
            ray(1);

            $response = $next($pendingRequest);

            $response->throw();

            return $response;
        },
        static function (PendingRequest $pendingRequest, Closure $next) {
            ray(2);

            return $next($pendingRequest);
        },

        static function (PendingRequest $pendingRequest, Closure $next) {
            ray(4);

            $sender = $pendingRequest->getConnector()->sender();

            return $sender->sendRequest($pendingRequest);
        },
    ];

    $connector = new TestConnector;
    $pendingRequest = $connector->createPendingRequest(new ErrorRequest());

    $pipeline = new NewPipeline($pipes);
    $final = $pipeline->run($pendingRequest);

    dd('final', $final->body());
});

test('empty test', function () {
    $pipeline = new NewPipeline([]);
    $final = $pipeline->get('Hello World');

    dd($final);
});
