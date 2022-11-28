<?php

declare(strict_types=1);

namespace Saloon\Tests\Fixtures\Data;

use Saloon\Contracts\Response;
use Saloon\Contracts\WithResponse;
use Saloon\Traits\Responses\HasResponse;

class UserWithResponse implements WithResponse
{
    use HasResponse;

    /**
     * @param string $name
     * @param string $actualName
     * @param string $twitter
     */
    public function __construct(
        public string $name,
        public string $actualName,
        public string $twitter,
    ) {
        //
    }

    /**
     * @param Response $response
     * @return static
     */
    public static function fromSaloon(Response $response): self
    {
        $data = $response->json();

        return new static($data['name'], $data['actual_name'], $data['twitter']);
    }
}
