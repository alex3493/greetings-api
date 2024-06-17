<?php
declare(strict_types=1);

namespace App\EntryPoint\Http\Controller;

use App\EntryPoint\Http\Contract\AbstractApiController;
use App\Modules\Shared\Domain\Exception\FormValidationException;
use App\Modules\Shared\Domain\Message\AdminGreetingMessage;
use App\Modules\Shared\Domain\Message\MercureUpdateMessage;
use App\Modules\User\Domain\Contract\SubscriberAuthorizationInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class NotificationController extends AbstractApiController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Modules\User\Domain\Contract\SubscriberAuthorizationInterface $authorizer
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[Route('/web/pusher-auth', name: 'web-pusher-auth', methods: ['POST'])]
    #[Route('/app/pusher-auth', name: 'app-pusher-auth', methods: ['POST'])]
    public function authorizePusherSubscriber(Request $request, SubscriberAuthorizationInterface $authorizer
    ): JsonResponse {
        // Pusher sends auth post body as plain text query string.
        $requestString = $request->getContent();
        parse_str($requestString, $jsonData);

        $response = $authorizer->authorizeSocketSubscriber($this->getUser(), $jsonData['channel_name'],
            $jsonData['socket_id']);

        return $this->json($response);
    }

    /**
     * @param \Symfony\Component\Mercure\HubInterface $hub
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[Route('/web/mercure-auth', name: 'web-mercure-auth', methods: ['GET'])]
    public function getMercureSubscriptionToken(HubInterface $hub): JsonResponse
    {
        $token = $hub->getProvider()->getJwt();

        return $this->json(['token' => $token]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Messenger\MessageBusInterface $bus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Modules\Shared\Domain\Exception\BadRequestDomainException
     * @throws \App\Modules\Shared\Domain\Exception\FormValidationException
     */
    #[Route('/web/admin-greeting', name: 'web-admin-greeting', methods: ['POST'])]
    #[Route('/app/admin-greeting', name: 'app-admin-greeting', methods: ['POST'])]
    public function sendGreeting(Request $request, MessageBusInterface $bus): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $jsonData = $this->getRequestData($request, [
            'greeting',
        ], [
            'greeting',
        ]);

        if (trim($jsonData['greeting']) === '') {
            throw new FormValidationException('Validation failed.', [
                [
                    'property' => 'greeting',
                    'errors' => ['Greeting cannot be empty.'],
                    'context' => 'AdminGreetings',
                ],
            ]);
        }

        $author = $this->getCurrentUser(true);

        $bus->dispatch(new AdminGreetingMessage($jsonData['greeting'], $author->getId(), $author->getDisplayName()));

        return $this->json([
            'message_dispatched' => 'OK',
        ]);
    }

    #[Route('/web/test-mercure', name: 'web-test-mercure', methods: ['POST'])]
    #[Route('/app/test-mercure', name: 'app-test-mercure', methods: ['POST'])]
    public function testMercure(
        Request $request, MessageBusInterface $bus
    ): JsonResponse {
        $jsonData = $this->getRequestData($request, [
            'topic',
            'payload',
        ], [
            'topic',
            'payload',
        ]);

        $bus->dispatch(new MercureUpdateMessage($jsonData['topic'], $jsonData['payload']));

        return $this->json([
            'message_dispatched' => 'OK',
        ]);
    }
}
