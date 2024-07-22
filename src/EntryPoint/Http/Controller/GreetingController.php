<?php

namespace App\EntryPoint\Http\Controller;

use App\EntryPoint\Http\Contract\AbstractApiController;
use App\Modules\Greeting\Application\CreateGreeting\CreateGreetingRequest;
use App\Modules\Greeting\Application\CreateGreeting\CreateGreetingUseCase;
use App\Modules\Greeting\Application\DeleteGreeting\DeleteGreetingRequest;
use App\Modules\Greeting\Application\DeleteGreeting\DeleteGreetingUseCase;
use App\Modules\Greeting\Application\ListGreetings\ListGreetingsRequest;
use App\Modules\Greeting\Application\ListGreetings\ListGreetingsUseCase;
use App\Modules\Greeting\Application\ReadGreeting\ReadGreetingRequest;
use App\Modules\Greeting\Application\ReadGreeting\ReadGreetingUseCase;
use App\Modules\Greeting\Application\UpdateGreeting\UpdateGreetingRequest;
use App\Modules\Greeting\Application\UpdateGreeting\UpdateGreetingUseCase;
use App\Modules\Greeting\Domain\GreetingVariant;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class GreetingController extends AbstractApiController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Modules\Greeting\Application\CreateGreeting\CreateGreetingUseCase $useCase
     * @param \Symfony\Component\Mercure\Discovery $discovery
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Modules\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/web/greetings', name: 'web-greeting-create', methods: ['POST'])]
    #[Route('/app/greetings', name: 'app-greeting-create', methods: ['POST'])]
    public function create(Request $request, CreateGreetingUseCase $useCase, Discovery $discovery): JsonResponse
    {
        $authorId = $this->ensureCurrentUserId();

        $jsonData = $this->getRequestData($request, [
            'text',
            'variant',
        ], [
            'text',
        ]);

        // TODO: just testing, not sure we need it here.
        $discovery->addLink($request);

        $useCaseRequest = new CreateGreetingRequest($jsonData['text'],
            $jsonData['variant'] ?? GreetingVariant::SECONDARY, $authorId);

        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize([
            'greeting' => $response->greeting,
        ], 'json', ['groups' => ['greeting']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Modules\Greeting\Application\ListGreetings\ListGreetingsUseCase $useCase
     * @param \Symfony\Component\Mercure\Discovery $discovery
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/web/greetings', name: 'web-greeting-index', methods: ['GET'])]
    #[Route('/app/greetings', name: 'app-greeting-index', methods: ['GET'])]
    public function index(Request $request, ListGreetingsUseCase $useCase, Discovery $discovery): JsonResponse
    {
        $queryData = $this->getRequestQuery($request, [
            ['limit', 'limit', 10],
            ['offset', 'offset', 0],
            ['afterId', 'afterId', ''],
        ]);

        $discovery->addLink($request);

        $useCaseRequest = new ListGreetingsRequest($queryData['limit'], $queryData['offset'], $queryData['afterId']);

        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize([
            'greetings' => $response->greetings,
        ], 'json', ['groups' => ['greeting']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $greetingId
     * @param \App\Modules\Greeting\Application\UpdateGreeting\UpdateGreetingUseCase $useCase
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Modules\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/web/greeting/{greetingId}', name: 'web-greeting-update', methods: ['PATCH'])]
    #[Route('/app/greeting/{greetingId}', name: 'app-greeting-update', methods: ['PATCH'])]
    public function update(Request $request, string $greetingId, UpdateGreetingUseCase $useCase): JsonResponse
    {
        $causerId = $this->ensureCurrentUserId();

        $jsonData = $this->getRequestData($request, [
            'text',
            'variant',
        ], [
            'text',
        ]);

        $useCaseRequest = new UpdateGreetingRequest($greetingId, $jsonData['text'], $jsonData['variant'] ?? null,
            $causerId);

        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize([
            'greeting' => $response->greeting,
        ], 'json', ['groups' => ['greeting']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $greetingId
     * @param \App\Modules\Greeting\Application\DeleteGreeting\DeleteGreetingUseCase $useCase
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     */
    #[Route('/web/greeting/{greetingId}', name: 'web-greeting-delete', methods: ['DELETE'])]
    #[Route('/app/greeting/{greetingId}', name: 'app-greeting-delete', methods: ['DELETE'])]
    public function delete(Request $request, string $greetingId, DeleteGreetingUseCase $useCase): JsonResponse
    {
        $causerId = $this->ensureCurrentUserId();

        $useCaseRequest = new DeleteGreetingRequest($greetingId, $causerId);

        $response = $useCase->run($useCaseRequest);

        return $this->json($response);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $greetingId
     * @param \App\Modules\Greeting\Application\ReadGreeting\ReadGreetingUseCase $useCase
     * @param \Symfony\Component\Mercure\Discovery $discovery
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[Route('/web/greeting/{greetingId}', name: 'web-greeting-read', methods: ['GET'])]
    #[Route('/app/greeting/{greetingId}', name: 'app-greeting-read', methods: ['GET'])]
    public function read(Request $request, string $greetingId, ReadGreetingUseCase $useCase, Discovery $discovery
    ): JsonResponse {
        // TODO: just testing, not sure we need it here.
        $discovery->addLink($request);

        $useCaseRequest = new ReadGreetingRequest($greetingId);

        $response = $useCase->run($useCaseRequest);

        $data = $this->serializer->serialize([
            'greeting' => $response->greeting,
        ], 'json', ['groups' => ['greeting']]);

        return $this->jsonResponse($data);
    }
}
