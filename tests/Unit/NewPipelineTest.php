<?php

use GuzzleHttp\Psr7\HttpFactory;
use Saloon\Contracts\PendingRequest;
use Saloon\Exceptions\Request\Statuses\InternalServerErrorException;
use Saloon\Helpers\NewPipeline;
use Saloon\Http\Response;
use Saloon\Tests\Fixtures\Connectors\TestConnector;
use Saloon\Tests\Fixtures\Requests\ErrorRequest;
use Saloon\Tests\Fixtures\Requests\UserRequest;

test('you can process through many pipes', function () {
    $pipes = [
        static function (PendingRequest $pendingRequest, Closure $next) {
            $pendingRequest->headers()->add('ExampleHeader', 'Howdy');

            ray(1);

            // Todo: Do we want to be able to just return early here?
            // Todo: See what happens when you return a response early with Guzzle - we should still process the response

            return new Response((new HttpFactory())->createResponse(), $pendingRequest);

            return $next($pendingRequest);
        },
        static function (PendingRequest $pendingRequest, Closure $next) {
            ray(2);

            dd('hi');

            $response = $next($pendingRequest);

            dd('yo', $response);
        },
    ];

    $connector = new TestConnector;
    $pendingRequest = $connector->createPendingRequest(new UserRequest);

    $pipeline = new NewPipeline($pipes);
    $final = $pipeline->run($pendingRequest);

    dd('final', $final);
});

test('empty test', function () {
    $pipeline = new NewPipeline([]);
    $final = $pipeline->get('Hello World');

    dd($final);
});
