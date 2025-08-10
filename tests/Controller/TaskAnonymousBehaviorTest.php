<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskAnonymousBehaviorTest extends WebTestCase
{
    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function createUser(string $username, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username.'@todolist.com');
        $user->setRoles($roles);
        $user->setPassword('password');

        $this->em()->persist($user);
        $this->em()->flush();
        return $user;
    }

    private function createTask(string $title, User $owner): Task
    {
        $task = new Task();
        $task->setTitle($title);
        $task->setContent('content');
        $task->setUser($owner);
        $this->em()->persist($task);
        $this->em()->flush();
        return $task;
    }

    public function testAdminSeesAnonymousTasksInList(): void
    {
        $client = static::createClient();

        $anonymous = $this->em()->getRepository(User::class)->findOneBy(['username' => 'anonyme'])
            ?? $this->createUser('anonyme', ['ROLE_ADMIN']);

        $admin = $this->createUser('admin-list', ['ROLE_ADMIN']);

        $ownTask = $this->createTask('Tâche admin', $admin);
        $anonTask = $this->createTask('Tâche anonyme visible', $anonymous);

        $client->loginUser($admin);

        $client->request('GET', '/tasks');
        $this->assertResponseIsSuccessful();
        $html = $client->getResponse()->getContent();
        $this->assertStringContainsString('Tâche anonyme visible', $html);
        $this->assertStringContainsString('Créé par :', $html);
        $this->assertStringContainsString('anonyme', $html);
    }

    public function testUserDoesNotSeeAnonymousTasksInList(): void
    {
        $client = static::createClient();

        $anonymous = $this->em()->getRepository(User::class)->findOneBy(['username' => 'anonyme'])
            ?? $this->createUser('anonyme', ['ROLE_ADMIN']);

        $user = $this->createUser('user-list', ['ROLE_USER']);

        $ownTask = $this->createTask('Tâche user', $user);
        $anonTask = $this->createTask('Tâche anonyme cachée', $anonymous);

        $client->loginUser($user);
        $client->request('GET', '/tasks');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Tâche user', $client->getResponse()->getContent());
        $this->assertStringNotContainsString('Tâche anonyme cachée ', $client->getResponse()->getContent());
    }

    public function testAdminCanDeleteAnonymousTask(): void
    {
        $client = static::createClient();

        $anonymous = $this->em()->getRepository(User::class)->findOneBy(['username' => 'anonyme'])
            ?? $this->createUser('anonyme', ['ROLE_ADMIN']);
        $admin = $this->createUser('admin-del', ['ROLE_ADMIN']);

        $anonTask = $this->createTask('Tâche anonyme à supprimer', $anonymous);
        $taskId = $anonTask->getId();

        $client->loginUser($admin);
        $client->request('GET', "/tasks/{$taskId}/delete");
        $this->assertResponseRedirects('/tasks');

        $this->em()->clear();
        $deleted = $this->em()->getRepository(Task::class)->find($taskId);
        $this->assertNull($deleted, 'La tâche anonyme doit être supprimée par un admin.');
    }

    public function testNonOwnerCannotDeleteOthersTask(): void
    {
        $client = static::createClient();

        $owner = $this->createUser('owner-x', ['ROLE_USER']);
        $other = $this->createUser('other-y', ['ROLE_USER']);

        $task = $this->createTask('Tâche d\'un autre', $owner);
        $taskId = $task->getId();

        $client->loginUser($other);
        $client->request('GET', "/tasks/{$taskId}/delete");
        $this->assertResponseRedirects('/tasks');

        $this->em()->clear();
        $stillThere = $this->em()->getRepository(Task::class)->find($taskId);
        $this->assertNotNull($stillThere, 'Un utilisateur non propriétaire ne doit pas pouvoir supprimer la tâche.');
    }

    public function testOwnerCanDeleteOwnTask(): void
    {
        $client = static::createClient();

        $owner = $this->createUser('owner-del', ['ROLE_USER']);
        $task = $this->createTask('Ma tâche à supprimer', $owner);
        $taskId = $task->getId();

        $client->loginUser($owner);
        $client->request('GET', "/tasks/{$taskId}/delete");
        $this->assertResponseRedirects('/tasks');

        $this->em()->clear();
        $deleted = $this->em()->getRepository(Task::class)->find($taskId);
        $this->assertNull($deleted, 'Le propriétaire doit pouvoir supprimer sa tâche.');
    }

    public function testGuestCannotDeleteTask(): void
    {
        $client = static::createClient();

        $owner = $this->createUser('someone', ['ROLE_USER']);
        $task = $this->createTask('Task non supprimable par invité', $owner);
        $taskId = $task->getId();

        $client->request('GET', "/tasks/{$taskId}/delete");
        $this->assertTrue(in_array($client->getResponse()->getStatusCode(), [302, 303, 307, 308]));

        $this->em()->clear();
        $stillThere = $this->em()->getRepository(Task::class)->find($taskId);
        $this->assertNotNull($stillThere, "L'invité ne doit pas pouvoir supprimer la tâche.");
    }

    public function testAdminCannotDeleteNonAnonymousOthersTask(): void
    {
        $client = static::createClient();

        $owner = $this->createUser('regular-owner', ['ROLE_USER']);
        $admin = $this->createUser('admin-cannot-del', ['ROLE_ADMIN']);
        $task = $this->createTask('Tâche non anonyme appartenant à un autre', $owner);
        $taskId = $task->getId();

        $client->loginUser($admin);
        $client->request('GET', "/tasks/{$taskId}/delete");
        $this->assertResponseRedirects('/tasks');

        $this->em()->clear();
        $stillThere = $this->em()->getRepository(Task::class)->find($taskId);
        $this->assertNotNull($stillThere, "L'admin ne doit pas pouvoir supprimer une tâche d'un autre utilisateur non-anonyme.");
    }
}
