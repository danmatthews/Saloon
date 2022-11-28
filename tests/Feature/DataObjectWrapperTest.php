<?php

use Saloon\Contracts\WithResponse;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Tests\Fixtures\Data\User;
use Saloon\Tests\Fixtures\Data\UserWithResponse;
use Saloon\Tests\Fixtures\Requests\DTORequest;
use Saloon\Tests\Fixtures\Requests\DTOWithResponseRequest;

test('if a dto does not implement the WithResponse interface and HasResponse trait Saloon will not add the original response', function () {
    $mockClient = new MockClient([
        new MockResponse(['name' => 'Sammyjo20', 'actual_name' => 'Sam', 'twitter' => '@carre_sam']),
    ]);

    $request = new DTORequest();
    $response = $request->send($mockClient);
    $dto = $response->dto();

    expect($dto)->toBeInstanceOf(User::class);
    expect($dto)->not->toBeInstanceOf(WithResponse::class);
});

test('if a dto implements the WithResponse interface and HasResponse trait Saloon will add the original response', function () {
    $mockClient = new MockClient([
        new MockResponse(['name' => 'Sammyjo20', 'actual_name' => 'Sam', 'twitter' => '@carre_sam']),
    ]);

    $request = new DTOWithResponseRequest();
    $response = $request->send($mockClient);

    /** @var UserWithResponse $dto */
    $dto = $response->dto();

    expect($dto)->toBeInstanceOf(UserWithResponse::class);
    expect($dto)->toBeInstanceOf(WithResponse::class);
    expect($dto->getResponse())->toBe($response);
});
