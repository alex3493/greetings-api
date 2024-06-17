<?php

namespace App\Modules\Greeting\Application\ListGreetings;

use App\Modules\Greeting\Domain\Contract\GreetingServiceInterface;
use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;

class ListGreetingsUseCase implements UseCaseInterface
{
    private GreetingServiceInterface $service;

    public function __construct(GreetingServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        /** @var \App\Modules\Greeting\Application\ListGreetings\ListGreetingsRequest $request */
        $greetings = $this->service->list($request->limit, $request->offset);

        $response = new ListGreetingsResponse();
        $response->greetings = $greetings;

        return $response;
    }
}
