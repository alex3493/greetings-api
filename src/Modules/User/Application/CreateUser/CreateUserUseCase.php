<?php
declare(strict_types=1);

namespace App\Modules\User\Application\CreateUser;

use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;
use App\Modules\User\Domain\Contract\UserServiceInterface;

class CreateUserUseCase implements UseCaseInterface
{
    private UserServiceInterface $service;

    public function __construct(UserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        /** @var \App\Modules\User\Application\CreateUser\CreateUserRequest $request */
        $user = $this->service->create($request->email, $request->password, $request->firstName,
            $request->lastName, $request->roles);

        $response = new CreateUserResponse();
        $response->user = $user;

        return $response;
    }
}
