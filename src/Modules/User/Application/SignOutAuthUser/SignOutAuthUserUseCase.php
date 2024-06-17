<?php
declare(strict_types=1);

namespace App\Modules\User\Application\SignOutAuthUser;

use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;
use App\Modules\User\Application\LogoutAuthUser\LogoutAuthUserResponse;
use App\Modules\User\Domain\Contract\AuthUserServiceInterface;

class SignOutAuthUserUseCase implements UseCaseInterface
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        /** @var \App\Modules\User\Application\SignOutAuthUser\SignOutAuthUserRequest $request */
        $user = $this->service->signOut($request->userId);

        $response = new LogoutAuthUserResponse();

        $response->user = $user;

        return $response;
    }
}
