<?php
declare(strict_types=1);

namespace App\Modules\User\Application\LogoutAuthUser;

use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;
use App\Modules\User\Domain\Contract\AuthUserServiceInterface;

class LogoutAuthUserUseCase implements UseCaseInterface
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        /** @var \App\Modules\User\Application\LogoutAuthUser\LogoutAuthUserRequest $request */
        $user = $this->service->logout($request->tokenId);

        $response = new LogoutAuthUserResponse();

        $response->user = $user;

        return $response;
    }
}
