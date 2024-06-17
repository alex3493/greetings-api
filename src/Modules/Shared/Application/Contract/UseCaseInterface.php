<?php
declare(strict_types=1);

namespace App\Modules\Shared\Application\Contract;

interface UseCaseInterface
{
    public function run(RequestInterface $request): ResponseInterface;
}
