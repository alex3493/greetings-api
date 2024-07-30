<?php

namespace App\Modules\Greeting\Application\ListGreetings;

use App\Modules\Shared\Application\Contract\RequestInterface;

class ListGreetingsRequest implements RequestInterface
{
    public int $limit;

    public int $offset;

    public string $beforeId;

    public function __construct(int $limit, int $offset, string $beforeId)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->beforeId = $beforeId;
    }
}
