<?php
declare(strict_types=1);

namespace App\Modules\User\Application\ChangePassword;

use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\ResponseInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;
use App\Modules\User\Domain\Contract\AuthUserServiceInterface;

class ChangePasswordUseCase implements UseCaseInterface
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        /** @var \App\Modules\User\Application\ChangePassword\ChangePasswordRequest $request */
        $user = $this->service->changePassword($request->id, $request->currentPassword, $request->password);

        $response = new ChangePasswordResponse();

        $response->user = $user;

        return $response;
    }
}
