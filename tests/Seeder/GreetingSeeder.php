<?php

namespace App\Tests\Seeder;

use App\Modules\Greeting\Domain\Greeting;
use App\Modules\Shared\Domain\ValueObject\EntityId;
use App\Modules\User\Domain\User;

class GreetingSeeder extends SeederBase
{
    //public function __construct(ObjectManager $manager) {
    //    parent::__construct($manager);
    //}

    public function seedGreeting(User $user, array $options = []): Greeting
    {
        $options = array_merge([
            'text' => 'Hi, there!',
            'variant' => 'primary',
            'createdAt' => new \DateTime(),
        ], $options);

        $greeting = new Greeting(EntityId::create(), $options['text'], $options['variant'], $user,
            $options['createdAt']);

        /** @var \App\Modules\Greeting\Infrastructure\Persistence\Doctrine\GreetingRepository $repository */
        $repository = $this->objectManager->getRepository(Greeting::class);

        $repository->save($greeting);

        return $greeting;
    }
}
