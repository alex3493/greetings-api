<?php
declare(strict_types=1);

namespace App\EntryPoint\Http\Controller;

use App\EntryPoint\Http\Contract\AbstractApiController;
use App\Modules\User\Application\ChangePassword\ChangePasswordRequest;
use App\Modules\User\Application\ChangePassword\ChangePasswordUseCase;
use App\Modules\User\Application\CreateUser\CreateUserRequest;
use App\Modules\User\Application\CreateUser\CreateUserUseCase;
use App\Modules\User\Application\DeleteAuthUser\DeleteAuthUserRequest;
use App\Modules\User\Application\DeleteAuthUser\DeleteAuthUserUseCase;
use App\Modules\User\Application\LoginAuthUser\LoginAuthUserRequest;
use App\Modules\User\Application\LoginAuthUser\LoginAuthUserUseCase;
use App\Modules\User\Application\LogoutAuthUser\LogoutAuthUserRequest;
use App\Modules\User\Application\LogoutAuthUser\LogoutAuthUserUseCase;
use App\Modules\User\Application\LogoutWebUser\LogoutWebUserRequest;
use App\Modules\User\Application\LogoutWebUser\LogoutWebUserUseCase;
use App\Modules\User\Application\RegisterAuthUser\RegisterAuthUserRequest;
use App\Modules\User\Application\RegisterAuthUser\RegisterAuthUserUseCase;
use App\Modules\User\Application\SignOutAuthUser\SignOutAuthUserRequest;
use App\Modules\User\Application\SignOutAuthUser\SignOutAuthUserUseCase;
use App\Modules\User\Application\UpdateUser\UpdateUserRequest;
use App\Modules\User\Application\UpdateUser\UpdateUserUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AuthUserController extends AbstractApiController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Modules\User\Application\RegisterAuthUser\RegisterAuthUserUseCase $useCase
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\BadRequestDomainException
     * @throws \App\Modules\Shared\Domain\Exception\ValidationException
     */
    #[Route('/app/register', name: 'app-register', methods: ['POST'])]
    public function register(Request $request, RegisterAuthUserUseCase $useCase): JsonResponse
    {
        $jsonData = $this->getRequestData($request, [
            'email',
            'password',
            ['passwordConfirmation', 'password_confirmation'],
            ['firstName', 'first_name'],
            ['lastName', 'last_name'],
            ['deviceName', 'device_name'],
        ], [
            'email',
            'password',
            'passwordConfirmation',
        ]);

        $useCaseRequest = new RegisterAuthUserRequest($this->validator, $jsonData['email'], $jsonData['password'],
            $jsonData['passwordConfirmation'], $jsonData['firstName'], $jsonData['lastName'], $jsonData['deviceName']);

        $useCaseRequest->validate('User');

        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize([
            'user' => $response->user,
            'token' => $response->token,
        ], 'json', ['groups' => ['user', 'user-tokens']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Modules\User\Application\CreateUser\CreateUserUseCase $useCase
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\BadRequestDomainException
     * @throws \App\Modules\Shared\Domain\Exception\ValidationException
     */
    #[Route('/web/register', name: 'web-register', methods: ['POST'])]
    public function create(Request $request, CreateUserUseCase $useCase): JsonResponse
    {
        $jsonData = $this->getRequestData($request, [
            'email',
            'password',
            ['passwordConfirmation', 'password_confirmation'],
            ['firstName', 'first_name'],
            ['lastName', 'last_name'],
        ], [
            'email',
            'password',
            'passwordConfirmation',
        ]);

        $useCaseRequest = new CreateUserRequest($this->validator, $jsonData['email'], $jsonData['password'],
            $jsonData['passwordConfirmation'], $jsonData['firstName'], $jsonData['lastName']);

        $useCaseRequest->validate('User');

        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Modules\User\Application\LoginAuthUser\LoginAuthUserUseCase $useCase
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/app/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, LoginAuthUserUseCase $useCase): JsonResponse
    {
        $jsonData = $this->getRequestData($request, [
            'email',
            'password',
            ['deviceName', 'device_name'],
        ], [
            'email',
            'password',
        ]);
        $useCaseRequest = new LoginAuthUserRequest($jsonData['email'], $jsonData['password'], $jsonData['deviceName']);

        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize([
            'user' => $response->user,
            'token' => $response->token,
        ], 'json', ['groups' => ['user', 'user-tokens']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param string $tokenId
     * @param \App\Modules\User\Application\LogoutAuthUser\LogoutAuthUserUseCase $useCase
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[Route('/app/account/logout/{tokenId}', name: 'app-logout', methods: ['DELETE'])]
    #[Route('/web/account/logout/{tokenId}', name: 'web-logout', methods: ['DELETE'])]
    public function logout(string $tokenId, LogoutAuthUserUseCase $useCase): JsonResponse
    {
        $useCaseRequest = new LogoutAuthUserRequest($tokenId);

        $response = $useCase->run($useCaseRequest);

        // Reserved: we can reinit auth tokens collection before normalizing. For now, we
        // do it in \App\Modules\User\Domain\User::getAuthTokens getter.
        //$authTokens = $response->user->getAuthTokens()->toArray();
        //$response->user->importAuthTokens($authTokens);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user', 'user-tokens']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \App\Modules\User\Application\SignOutAuthUser\SignOutAuthUserUseCase $useCase
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     */
    #[Route('/app/account/me/sign-out', name: 'me.app-sign-out', methods: ['POST'])]
    #[Route('/web/account/me/sign-out', name: 'me.web-sign-out', methods: ['POST'])]
    public function signOut(SignOutAuthUserUseCase $useCase): JsonResponse
    {
        $userId = $this->ensureCurrentUserId();

        $useCaseRequest = new SignOutAuthUserRequest($userId);

        /** @var \App\Modules\User\Application\SignOutAuthUser\SignOutAuthUserResponse $response */
        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user', 'user-tokens']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \App\Modules\User\Application\LogoutWebUser\LogoutWebUserUseCase $useCase
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     */
    #[Route('/web/account/me/logout', name: 'me.web-logout', methods: ['POST'])]
    public function webLogout(LogoutWebUserUseCase $useCase): JsonResponse
    {
        $userId = $this->ensureCurrentUserId();

        $useCaseRequest = new LogoutWebUserRequest($userId);

        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize($response, 'json');

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Modules\User\Application\UpdateUser\UpdateUserUseCase $useCase
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Modules\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/app/account/me/update', name: 'me.app-update', methods: ['PATCH'])]
    #[Route('/web/account/me/update', name: 'me.web-update', methods: ['PATCH'])]
    public function update(Request $request, UpdateUserUseCase $useCase): JsonResponse
    {
        $userId = $this->ensureCurrentUserId();

        $jsonData = $this->getRequestData($request, [
            ['firstName', 'first_name'],
            ['lastName', 'last_name'],
        ]);

        $useCaseRequest = new UpdateUserRequest($userId, $jsonData['firstName'], $jsonData['lastName']);

        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Modules\User\Application\ChangePassword\ChangePasswordUseCase $useCase
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Modules\Shared\Domain\Exception\BadRequestDomainException
     * @throws \App\Modules\Shared\Domain\Exception\ValidationException
     */
    #[Route('/app/account/me/change-password', name: 'me.app-change-password', methods: ['PATCH'])]
    #[Route('/web/account/me/change-password', name: 'me.web-change-password', methods: ['PATCH'])]
    public function changePassword(Request $request, ChangePasswordUseCase $useCase): JsonResponse
    {
        $userId = $this->ensureCurrentUserId();

        $jsonData = $this->getRequestData($request, [
            ['currentPassword', 'current_password'],
            'password',
            ['passwordConfirmation', 'password_confirmation'],
        ], [
            'currentPassword',
            'password',
            'passwordConfirmation',
        ]);

        $useCaseRequest = new ChangePasswordRequest($this->validator, $userId, $jsonData['currentPassword'],
            $jsonData['password'], $jsonData['passwordConfirmation']);

        $useCaseRequest->validate('User');

        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Modules\User\Application\DeleteAuthUser\DeleteAuthUserUseCase $useCase
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Modules\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/app/account/me/delete-account', name: 'me.app-delete-account', methods: ['POST'])]
    #[Route('/web/account/me/delete-account', name: 'me.web-delete-account', methods: ['POST'])]
    public function deleteAccount(Request $request, DeleteAuthUserUseCase $useCase): JsonResponse
    {
        $userId = $this->ensureCurrentUserId();

        $jsonData = $this->getRequestData($request, [
            'password',
        ], [
            'password',
        ]);

        $useCaseRequest = new DeleteAuthUserRequest($userId, $jsonData['password']);

        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize($response, 'json');

        return $this->jsonResponse($data);
    }
}
