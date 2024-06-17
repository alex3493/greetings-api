<?php
declare(strict_types=1);

namespace App\Modules\User\Application\LoginAuthUser;

use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;
use App\Modules\User\Domain\Contract\AuthUserServiceInterface;

class LoginAuthUserUseCase implements UseCaseInterface
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        /** @var \App\Modules\User\Application\LoginAuthUser\LoginAuthUserRequest $request */
        [$user, $token] = $this->service->login($request->email, $request->password, $request->deviceName);

        $response = new LoginAuthUserResponse();

        $response->token = $token;
        $response->user = $user;

        return $response;
    }
}
