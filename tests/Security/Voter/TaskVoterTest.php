<?php

namespace App\Tests\Security\Voter;

use App\Entity\Task;
use App\Entity\User;
use App\Security\Voter\TaskVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class TaskVoterTest extends TestCase
{
    private TaskVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TaskVoter();
    }

    public function testSupportsTaskEntityForExistingFeatures(): void
    {
        $task = new Task();
        
        $this->assertTrue($this->voter->supports('VIEW', $task));        
        $this->assertTrue($this->voter->supports('EDIT', $task));       
        $this->assertTrue($this->voter->supports('TOGGLE', $task));   
        $this->assertTrue($this->voter->supports('CREATE', Task::class)); 
    }

    public function testDoesNotSupportInvalidAttributes(): void
    {
        $task = new Task();
        
        $this->assertFalse($this->voter->supports('INVALID', $task));
        $this->assertFalse($this->voter->supports('VIEW', 'string'));
    }

    // Tests pour CREATE - Route /tasks/create
    public function testAnyUserCanCreateTasks(): void
    {
        $user = $this->createUser(['ROLE_USER'], 1);
        $token = $this->createToken($user);
        
        $result = $this->voter->vote($token, Task::class, ['CREATE']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanViewOwnTask(): void
    {
        $user = $this->createUser(['ROLE_USER'], 1);
        $task = $this->createTask($user);
        $token = $this->createToken($user);
        
        $result = $this->voter->vote($token, $task, ['VIEW']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotViewOthersTask(): void
    {
        $owner = $this->createUser(['ROLE_USER'], 1);
        $otherUser = $this->createUser(['ROLE_USER'], 2);
        $task = $this->createTask($owner);
        $token = $this->createToken($otherUser);
        
        $result = $this->voter->vote($token, $task, ['VIEW']);
        
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminCanViewAnonymousTask(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN'], 1);
        $anonymousUser = $this->createUser(['ROLE_USER'], 2, 'anonyme');
        $task = $this->createTask($anonymousUser);
        $token = $this->createToken($admin);
        
        $result = $this->voter->vote($token, $task, ['VIEW']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAdminCannotViewNonAnonymousUsersTask(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN'], 1);
        $regularUser = $this->createUser(['ROLE_USER'], 2, 'regularuser');
        $task = $this->createTask($regularUser);
        $token = $this->createToken($admin);
        
        $result = $this->voter->vote($token, $task, ['VIEW']);
        
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOwnerCanEditOwnTask(): void
    {
        $user = $this->createUser(['ROLE_USER'], 1);
        $task = $this->createTask($user);
        $token = $this->createToken($user);
        
        $result = $this->voter->vote($token, $task, ['EDIT']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAdminCanEditAnonymousTask(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN'], 1);
        $anonymousUser = $this->createUser(['ROLE_USER'], 2, 'anonyme');
        $task = $this->createTask($anonymousUser);
        $token = $this->createToken($admin);
        
        $result = $this->voter->vote($token, $task, ['EDIT']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanDeleteOwnTask(): void
    {
        $user = $this->createUser(['ROLE_USER'], 1);
        $task = $this->createTask($user);
        $token = $this->createToken($user);
        
        $result = $this->voter->vote($token, $task, ['DELETE']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAdminCanDeleteAnonymousTask(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN'], 1);
        $anonymousUser = $this->createUser(['ROLE_USER'], 2, 'anonyme');
        $task = $this->createTask($anonymousUser);
        $token = $this->createToken($admin);
        
        $result = $this->voter->vote($token, $task, ['DELETE']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAdminCannotDeleteRegularUsersTask(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN'], 1);
        $regularUser = $this->createUser(['ROLE_USER'], 2, 'regularuser');
        $task = $this->createTask($regularUser);
        $token = $this->createToken($admin);
        
        $result = $this->voter->vote($token, $task, ['DELETE']);
        
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOwnerCanToggleOwnTask(): void
    {
        $user = $this->createUser(['ROLE_USER'], 1);
        $task = $this->createTask($user);
        $token = $this->createToken($user);
        
        $result = $this->voter->vote($token, $task, ['TOGGLE']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotToggleOthersTask(): void
    {
        $owner = $this->createUser(['ROLE_USER'], 1);
        $otherUser = $this->createUser(['ROLE_USER'], 2);
        $task = $this->createTask($owner);
        $token = $this->createToken($otherUser);
        
        $result = $this->voter->vote($token, $task, ['TOGGLE']);
        
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminCanToggleAnonymousTask(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN'], 1);
        $anonymousUser = $this->createUser(['ROLE_USER'], 2, 'anonyme');
        $task = $this->createTask($anonymousUser);
        $token = $this->createToken($admin);
        
        $result = $this->voter->vote($token, $task, ['TOGGLE']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUnauthenticatedUserCannotDoAnything(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        
        $task = new Task();
        
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, Task::class, ['CREATE']));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $task, ['VIEW']));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $task, ['EDIT']));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $task, ['DELETE']));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $task, ['TOGGLE']));
    }

    private function createUser(array $roles, int $id, string $username = 'testuser'): User
    {
        $user = $this->createMock(User::class);
        $user->method('getRoles')->willReturn($roles);
        $user->method('getId')->willReturn($id);
        $user->method('getUsername')->willReturn($username);
        
        return $user;
    }

    private function createTask(User $owner): Task
    {
        $task = $this->createMock(Task::class);
        $task->method('getUser')->willReturn($owner);
        
        return $task;
    }

    private function createToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        
        return $token;
    }
}
