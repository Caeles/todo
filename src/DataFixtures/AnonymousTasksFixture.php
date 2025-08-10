<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AnonymousTasksFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $anonymousUser = $manager->getRepository(User::class)->findOneBy(['username' => 'anonyme']);
        
        if (!$anonymousUser) {
            $anonymousUser = new User();
            $anonymousUser->setUsername('anonyme');
            $anonymousUser->setEmail('anonyme@todo.com');
            $anonymousUser->setRoles(['ROLE_ADMIN']);
            
            $hashedPassword = $this->passwordHasher->hashPassword($anonymousUser, 'anonyme');
            $anonymousUser->setPassword($hashedPassword);
            
            $manager->persist($anonymousUser);
            $manager->flush();
        }

        $anonymousTasks = $manager->getRepository(Task::class)->findBy(['user' => null]);

        if (count($anonymousTasks) > 0) {
            
            foreach ($anonymousTasks as $task) {
                $task->setUser($anonymousUser);
            }

            $manager->flush();
            
        } 
    }
}

