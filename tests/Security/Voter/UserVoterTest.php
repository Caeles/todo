<?php

namespace App\Tests\Security\Voter;

use App\Entity\User;
use App\Security\Voter\UserVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class UserVoterTest extends TestCase
{
    private UserVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new UserVoter();
    }

    public function testSupportsUserEntityForExistingFeatures(): void
    {
        $user = new User();
        
    
        $this->assertTrue($this->voter->supports('VIEW', $user));
        $this->assertTrue($this->voter->supports('EDIT', $user));
        $this->assertTrue($this->voter->supports('CREATE', User::class));
        $this->assertTrue($this->voter->supports('LIST', User::class));
    }

    public function testDoesNotSupportNonExistentFeatures(): void
    {
        $user = new User();
        
        $this->assertFalse($this->voter->supports('DELETE', $user));
        $this->assertFalse($this->voter->supports('INVALID', $user));
        $this->assertFalse($this->voter->supports('VIEW', 'string'));
    }

  
    public function testAdminCanListUsers(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $token = $this->createToken($admin);
        
        $result = $this->voter->vote($token, User::class, ['LIST']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotListUsers(): void
    {
        $user = $this->createUser(['ROLE_USER']);
        $token = $this->createToken($user);
        
        $result = $this->voter->vote($token, User::class, ['LIST']);
        
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

 
    public function testAdminCanCreateUsers(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $token = $this->createToken($admin);
        
        $result = $this->voter->vote($token, User::class, ['CREATE']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotCreateUsers(): void
    {
        $user = $this->createUser(['ROLE_USER']);
        $token = $this->createToken($user);
        
        $result = $this->voter->vote($token, User::class, ['CREATE']);
        
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }


    public function testAdminCanViewAnyProfile(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN'], 1);
        $user = $this->createUser(['ROLE_USER'], 2);
        $token = $this->createToken($admin);
        
        $result = $this->voter->vote($token, $user, ['VIEW']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotViewAnyProfile(): void
    {
        $user = $this->createUser(['ROLE_USER'], 1);
        $otherUser = $this->createUser(['ROLE_USER'], 2);
        $token = $this->createToken($user);
        
        $result = $this->voter->vote($token, $user, ['VIEW']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
        
        $result = $this->voter->vote($token, $otherUser, ['VIEW']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminCanEditAnyProfile(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN'], 1);
        $user = $this->createUser(['ROLE_USER'], 2);
        $token = $this->createToken($admin);
        
        $result = $this->voter->vote($token, $user, ['EDIT']);
        
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotEditAnyProfile(): void
    {
        $user = $this->createUser(['ROLE_USER'], 1);
        $otherUser = $this->createUser(['ROLE_USER'], 2);
        $token = $this->createToken($user);
        
    
        $result = $this->voter->vote($token, $user, ['EDIT']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
        
        $result = $this->voter->vote($token, $otherUser, ['EDIT']);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUnauthenticatedUserCannotDoAnything(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        
        $user = new User();
        
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, User::class, ['LIST']));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, User::class, ['CREATE']));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $user, ['VIEW']));
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $user, ['EDIT']));
    }

    private function createUser(array $roles, int $id = null): User
    {
        $user = $this->createMock(User::class);
        $user->method('getRoles')->willReturn($roles);
        
        if ($id !== null) {
            $user->method('getId')->willReturn($id);
        }
        
        return $user;
    }

    private function createToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        
        return $token;
    }
}
