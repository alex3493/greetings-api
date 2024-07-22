<?php

namespace App\Modules\Greeting\Domain\Contract;

use App\Modules\Greeting\Domain\Greeting;

interface GreetingServiceInterface
{
    public function create(string $text, string $variant, string $authorId): Greeting;

    public function update(string $id, string $text, ?string $variant, string $causerId): Greeting;

    public function delete(string $id, string $causerId): void;

    public function read(string $id): Greeting;

    public function list(int $limit, int $offset = 0, string $afterId = ''): array;
}
