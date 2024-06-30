<?php
declare(strict_types=1);

namespace App\Modules\Greeting\Application\UpdateGreeting;

use App\Modules\Greeting\Domain\Contract\GreetingServiceInterface;
use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;

class UpdateGreetingUseCase implements UseCaseInterface
{
    private GreetingServiceInterface $service;

    public function __construct(GreetingServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): UpdateGreetingResponse
    {
        /** @var \App\Modules\Greeting\Application\UpdateGreeting\UpdateGreetingRequest $request */
        $greeting = $this->service->update($request->id, $request->text, $request->variant, $request->causerId);

        $response = new UpdateGreetingResponse();
        $response->greeting = $greeting;

        return $response;
    }
}
