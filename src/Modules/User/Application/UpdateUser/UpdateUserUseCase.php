<?php
declare(strict_types=1);

namespace App\Modules\User\Application\UpdateUser;

use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;
use App\Modules\User\Domain\Contract\UserServiceInterface;

class UpdateUserUseCase implements UseCaseInterface
{
    private UserServiceInterface $service;

    public function __construct(UserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        /** @var \App\Modules\User\Application\UpdateUser\UpdateUserRequest $request */
        $user = $this->service->update($request->id, $request->firstName, $request->lastName);

        $response = new UpdateUserResponse();
        $response->user = $user;

        return $response;
    }
}
