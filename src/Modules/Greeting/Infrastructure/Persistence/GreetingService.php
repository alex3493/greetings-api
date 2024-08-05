<?php

namespace App\Modules\Greeting\Infrastructure\Persistence;

use App\Modules\Greeting\Domain\Contract\GreetingServiceInterface;
use App\Modules\Greeting\Domain\Greeting;
use App\Modules\Greeting\Domain\GreetingVariant;
use App\Modules\Greeting\Infrastructure\Persistence\Doctrine\GreetingRepository;
use App\Modules\Shared\Domain\Exception\AccessDeniedDomainException;
use App\Modules\Shared\Domain\Exception\NotFoundDomainException;
use App\Modules\Shared\Domain\Exception\ValidationException;
use App\Modules\Shared\Domain\Message\MercureUpdateMessage;
use App\Modules\User\Domain\Contract\UserServiceInterface;
use App\Modules\User\Infrastructure\Security\AuthUser;
use DateTime;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GreetingService implements GreetingServiceInterface
{
    private GreetingRepository $repository;

    private UserServiceInterface $userService;

    private MessageBusInterface $bus;

    private SerializerInterface $serializer;

    private Security $security;

    private ValidatorInterface $validator;

    private ?AuthUser $authUser;

    public function __construct(
        GreetingRepository $repository, UserServiceInterface $userService, MessageBusInterface $bus,
        SerializerInterface $serializer, Security $security, ValidatorInterface $validator
    ) {
        $this->repository = $repository;
        $this->userService = $userService;
        $this->bus = $bus;
        $this->serializer = $serializer;
        $this->security = $security;
        $this->validator = $validator;

        /** @var \App\Modules\User\Infrastructure\Security\AuthUser $authUser */
        $authUser = $this->security->getUser();
        $this->authUser = $authUser;
    }

    /**
     * @param string $text
     * @param string $variant
     * @param string $authorId
     * @return \App\Modules\Greeting\Domain\Greeting
     * @throws \App\Modules\Shared\Domain\Exception\NotFoundDomainException
     * @throws \App\Modules\Shared\Domain\Exception\ValidationException
     */
    public function create(string $text, string $variant, string $authorId): Greeting
    {
        $author = $this->userService->findById($authorId);

        if (is_null($author)) {
            throw new NotFoundDomainException('Greeting author user not found');
        }

        $greeting = Greeting::create($text, $variant, $author);

        $errors = $this->validator->validate($greeting);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $this->repository->save($greeting);

        $payload = [
            'greeting' => $this->serializer->normalize($greeting, 'json', ['groups' => ['greeting']]),
            // UI subscriber must be able to detect that a new entity was created.
            'reason' => 'create',
            'causer' => $this->serializer->normalize($author, 'json', ['groups' => ['greeting']]),
            // Mobile app subscriber should be able to act differently if event was caused by self.
            'deviceId' => $this->authUser?->getDeviceId(),
        ];

        $this->bus->dispatch(new MercureUpdateMessage('https://symfony.test/greetings', $payload));

        return $greeting;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function list(int $limit, int $offset = 0, string $beforeId = ''): array
    {
        // We are using Uuid::v7 ids, so they are already ordered by date.
        $criteria = Criteria::create()->orderBy(['id' => Order::Descending]);
        if ($beforeId) {
            // $beforeId parameter wins if present.
            $criteria->andWhere(Criteria::expr()->lt("id", $beforeId))->setMaxResults($limit);
        } else {
            // Fallback to $offset.
            $criteria->setMaxResults($limit)->setFirstResult($offset);
        }

        return $this->repository->matching($criteria)->toArray();
    }

    /**
     * @param string $id
     * @param string $text
     * @param string|null $variant
     * @param string $causerId
     * @return \App\Modules\Greeting\Domain\Greeting
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Modules\Shared\Domain\Exception\NotFoundDomainException
     * @throws \App\Modules\Shared\Domain\Exception\ValidationException
     */
    public function update(string $id, string $text, ?string $variant, string $causerId): Greeting
    {
        /** @var \App\Modules\Greeting\Domain\Greeting $greeting */
        $greeting = $this->repository->find($id);

        if (is_null($greeting)) {
            throw new NotFoundDomainException('Greeting not found');
        }

        // Only author or administrator can update a greeting.
        if (! $this->security->isGranted('ROLE_ADMIN') && $greeting->getAuthor()->getId() !== $causerId) {
            throw new AccessDeniedDomainException('You do not have permission to update greeting');
        }

        $causer = $this->userService->findById($causerId);

        if (is_null($causer)) {
            throw new NotFoundDomainException('Greeting update causer user not found');
        }

        $greeting->setUpdatedBy($causer);

        $greeting->setText($text);
        if ($variant) {
            $greeting->setVariant(new GreetingVariant($variant));
        }

        $greeting->setUpdatedAt(new DateTime());

        $errors = $this->validator->validate($greeting);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $this->repository->save($greeting);

        $payload = [
            'greeting' => $this->serializer->normalize($greeting, 'json', ['groups' => ['greeting']]),
            // UI subscriber must be able to detect that one of existing greetings was updated.
            'reason' => 'update',
            'causer' => $this->serializer->normalize($causer, 'json', ['groups' => ['greeting']]),
            // Mobile app subscriber should be able to act differently if event was caused by self.
            'deviceId' => $this->authUser?->getDeviceId(),
        ];

        // Entity update event.
        $this->bus->dispatch(new MercureUpdateMessage('https://symfony.test/greeting/'.$id, $payload));

        // List update event.
        $this->bus->dispatch(new MercureUpdateMessage('https://symfony.test/greetings', $payload));

        return $greeting;
    }

    /**
     * @param string $id
     * @param string $causerId
     * @return void
     * @throws \App\Modules\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Modules\Shared\Domain\Exception\NotFoundDomainException
     */
    public function delete(string $id, string $causerId): void
    {
        /** @var \App\Modules\Greeting\Domain\Greeting $greeting */
        $greeting = $this->repository->find($id);

        if (is_null($greeting)) {
            throw new NotFoundDomainException('Greeting not found');
        }

        // Only author or administrator can delete a greeting.
        if (! $this->security->isGranted('ROLE_ADMIN') && $greeting->getAuthor()->getId() !== $causerId) {
            throw new AccessDeniedDomainException('You do not have permission to update greeting');
        }

        $causer = $this->userService->findById($causerId);

        $payload = [
            'greeting' => $this->serializer->normalize($greeting, 'json', ['groups' => ['greeting']]),
            // UI subscriber must be able to detect that one of existing greetings was deleted.
            'reason' => 'delete',
            'causer' => $this->serializer->normalize($causer, 'json', ['groups' => ['greeting']]),
            // Mobile app subscriber should be able to act differently if event was caused by self.
            'deviceId' => $this->authUser->getDeviceId(),
        ];

        $this->repository->delete($greeting);

        // Entity delete event.
        $this->bus->dispatch(new MercureUpdateMessage('https://symfony.test/greeting/'.$id, $payload));

        // List update event.
        $this->bus->dispatch(new MercureUpdateMessage('https://symfony.test/greetings', $payload));
    }

    /**
     * @param string $id
     * @return \App\Modules\Greeting\Domain\Greeting
     * @throws \App\Modules\Shared\Domain\Exception\NotFoundDomainException
     */
    public function read(string $id): Greeting
    {
        /** @var \App\Modules\Greeting\Domain\Greeting $greeting */
        $greeting = $this->repository->find($id);

        if (is_null($greeting)) {
            throw new NotFoundDomainException('Greeting not found');
        }

        return $greeting;
    }
}
