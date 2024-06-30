<?php
declare(strict_types=1);

namespace App\Modules\User\Application\RegisterAuthUser;

use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;
use App\Modules\User\Domain\Contract\AuthUserServiceInterface;

class RegisterAuthUserUseCase implements UseCaseInterface
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): RegisterAuthUserResponse
    {
        /** @var \App\Modules\User\Application\RegisterAuthUser\RegisterAuthUserRequest $request */
        [$user, $token] = $this->service->register($request->email, $request->password, $request->firstName,
            $request->lastName, $request->deviceName);

        $response = new RegisterAuthUserResponse();

        $response->token = $token;
        $response->user = $user;

        return $response;
    }
}
