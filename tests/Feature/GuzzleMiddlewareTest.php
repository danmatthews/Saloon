<?php

use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sammyjo20\Saloon\Http\Senders\GuzzleSender;
use Sammyjo20\Saloon\Tests\Fixtures\Requests\UserRequest;

test('you can add middleware to the guzzle sender', function () {
    $request = new UserRequest();

    /** @var GuzzleSender $sender */
    $sender = $request->sender();

    $sender->pushMiddleware(Middleware::mapRequest(function (RequestInterface $r) {
        return $r->withHeader('X-Foo', 'Bar');
    }), 'a');

    $sender->pushMiddlewareAfter('a', Middleware::mapRequest(function (RequestInterface $r) {
        return $r->withHeader('X-Foo', 'Baz');
    }));

    $sender->pushMiddleware(Middleware::mapResponse(function (ResponseInterface $response) {
        return $response->withHeader('X-Foo', 'bar');
    }), 'b');

    $sender->removeMiddleware('b');

    $response = $request->send();

    dd($response->headers());
});