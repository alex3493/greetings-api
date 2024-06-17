<?php

namespace App\Modules\Greeting\Application\DeleteGreeting;

use App\Modules\Greeting\Domain\Contract\GreetingServiceInterface;
use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;
use App\Modules\Shared\Application\MessageResponse;

class DeleteGreetingUseCase implements UseCaseInterface
{
    private GreetingServiceInterface $service;

    public function __construct(GreetingServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        /** @var \App\Modules\Greeting\Application\DeleteGreeting\DeleteGreetingRequest $request */
        $this->service->delete($request->id, $request->causerId);

        return new MessageResponse('Greeting deleted');
    }
}
