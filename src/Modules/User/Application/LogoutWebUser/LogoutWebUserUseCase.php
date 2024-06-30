<?php
declare(strict_types=1);

namespace App\Modules\User\Application\LogoutWebUser;

use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;
use App\Modules\User\Domain\Contract\UserServiceInterface;

class LogoutWebUserUseCase implements UseCaseInterface
{
    private UserServiceInterface $service;

    public function __construct(UserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): LogoutWebUserResponse
    {
        /** @var \App\Modules\User\Application\LogoutWebUser\LogoutWebUserRequest $request */
        $this->service->logout($request->userId);

        $response = new LogoutWebUserResponse();
        $response->message = 'You have successfully logged out';

        return $response;
    }
}
