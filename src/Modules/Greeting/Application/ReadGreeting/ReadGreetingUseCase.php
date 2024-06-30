<?php
declare(strict_types=1);

namespace App\Modules\Greeting\Application\ReadGreeting;

use App\Modules\Greeting\Domain\Contract\GreetingServiceInterface;
use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;

class ReadGreetingUseCase implements UseCaseInterface
{
    private GreetingServiceInterface $service;

    public function __construct(GreetingServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): ReadGreetingResponse
    {
        /** @var \App\Modules\Greeting\Application\ReadGreeting\ReadGreetingRequest $request */
        $greeting = $this->service->read($request->id);

        $response = new ReadGreetingResponse();
        $response->greeting = $greeting;

        return $response;
    }
}
