<?php
declare(strict_types=1);

namespace App\Modules\Greeting\Application\CreateGreeting;

use App\Modules\Greeting\Domain\Contract\GreetingServiceInterface;
use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;

class CreateGreetingUseCase implements UseCaseInterface
{
    private GreetingServiceInterface $service;

    public function __construct(GreetingServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        /** @var \App\Modules\Greeting\Application\CreateGreeting\CreateGreetingRequest $request */
        $greeting = $this->service->create($request->text, $request->variant, $request->authorId);

        $response = new CreateGreetingResponse();
        $response->greeting = $greeting;

        return $response;
    }
}
