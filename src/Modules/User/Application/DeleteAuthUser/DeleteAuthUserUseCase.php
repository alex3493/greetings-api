<?php
declare(strict_types=1);

namespace App\Modules\User\Application\DeleteAuthUser;

use App\Modules\Shared\Application\Contract\RequestInterface;
use App\Modules\Shared\Application\Contract\UseCaseInterface;
use App\Modules\User\Domain\Contract\AuthUserServiceInterface;

class DeleteAuthUserUseCase implements UseCaseInterface
{
    private AuthUserServiceInterface $service;

    public function __construct(AuthUserServiceInterface $service)
    {
        $this->service = $service;
    }

    public function run(RequestInterface $request): DeleteAuthUserResponse
    {
        /** @var \App\Modules\User\Application\DeleteAuthUser\DeleteAuthUserRequest $request */
        $this->service->deleteAccount($request->id, $request->password);

        $response = new DeleteAuthUserResponse();

        $response->message = 'User account deleted successfully';

        return $response;
    }
}
