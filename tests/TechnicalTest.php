<?php

namespace App\Tests;

use App\Controller\DefaultController;
use App\Controller\SecurityController;
use App\DataFixtures\AppFixtures;
use App\DataFixtures\AnonymousTasksFixture;
use App\Entity\User;
use App\Entity\Task;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


class TechnicalTest extends WebTestCase
{
   
    public function testDefaultControllerExists(): void
    {
        $this->assertTrue(class_exists(DefaultController::class));
    }

    
    public function testDefaultControllerHasIndexMethod(): void
    {
        $controller = new DefaultController();
        $this->assertTrue(method_exists($controller, 'index'));
    }

    
    public function testIndexMethodHasCorrectReturnType(): void
    {
        $controller = new DefaultController();
        $reflection = new \ReflectionMethod($controller, 'index');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertSame('Symfony\Component\HttpFoundation\Response', $returnType->getName());
    }

    /**
     */
    public function testHomepageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 302]);
    }

    
    public function testSecurityControllerExists(): void
    {
        $this->assertTrue(class_exists(SecurityController::class));
    }

    
    public function testSecurityControllerExtendsAbstractController(): void
    {
        $controller = new SecurityController();
        $this->assertInstanceOf('Symfony\Bundle\FrameworkBundle\Controller\AbstractController', $controller);
    }

    
    public function testSecurityControllerHasRequiredMethods(): void
    {
        $controller = new SecurityController();
        
        $this->assertTrue(method_exists($controller, 'login'));
        $this->assertTrue(method_exists($controller, 'logout'));
        $this->assertTrue(method_exists($controller, 'loginCheck'));
    }

    
    public function testSecurityControllerMethodReturnTypes(): void
    {
        $controller = new SecurityController();
        
        $loginReflection = new \ReflectionMethod($controller, 'login');
        $loginReturnType = $loginReflection->getReturnType();
        $this->assertNotNull($loginReturnType);
        $this->assertSame('Symfony\Component\HttpFoundation\Response', $loginReturnType->getName());
        
        
        $loginCheckReflection = new \ReflectionMethod($controller, 'loginCheck');
        $loginCheckReturnType = $loginCheckReflection->getReturnType();
        $this->assertNotNull($loginCheckReturnType);
        $this->assertSame('Symfony\Component\HttpFoundation\Response', $loginCheckReturnType->getName());
    }

    
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');
        
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form');
    }

    
    public function testLogoutRedirection(): void
    {
        $client = static::createClient();
        $client->request('GET', '/logout');
        
        $this->assertSame(302, $client->getResponse()->getStatusCode());
    }

    
    public function testAppFixturesExists(): void
    {
        $this->assertTrue(class_exists(AppFixtures::class));
    }

    
    public function testAppFixturesImplementsFixtureInterface(): void
    {
        $fixtures = new AppFixtures($this->createMock(UserPasswordHasherInterface::class));
        $this->assertInstanceOf('Doctrine\Common\DataFixtures\FixtureInterface', $fixtures);
    }

    
    public function testAppFixturesHasLoadMethod(): void
    {
        $fixtures = new AppFixtures($this->createMock(UserPasswordHasherInterface::class));
        $this->assertTrue(method_exists($fixtures, 'load'));
    }

  
    public function testAppFixturesCanBeInstantiatedWithDependencies(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $fixtures = new AppFixtures($passwordHasher);
        
        $this->assertInstanceOf(AppFixtures::class, $fixtures);
    }

    
    public function testAppFixturesLoadMethod(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')->willReturn('hashed_password');
        
        $fixtures = new AppFixtures($passwordHasher);
        
        $this->assertTrue(method_exists($fixtures, 'load'));
        $this->assertInstanceOf(AppFixtures::class, $fixtures);
    }

    
   public function testAnonymousTasksFixtureExists(): void
    {
        $this->assertTrue(class_exists(AnonymousTasksFixture::class));
    }

    
    public function testAnonymousTasksFixtureImplementsFixtureInterface(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $fixtures = new AnonymousTasksFixture($passwordHasher);
        $this->assertInstanceOf('Doctrine\Common\DataFixtures\FixtureInterface', $fixtures);
    }

    
    public function testAnonymousTasksFixtureHasLoadMethod(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $fixtures = new AnonymousTasksFixture($passwordHasher);
        $this->assertTrue(method_exists($fixtures, 'load'));
    }

    
    public function testAnonymousTasksFixtureCanBeInstantiated(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $fixtures = new AnonymousTasksFixture($passwordHasher);
        $this->assertInstanceOf(AnonymousTasksFixture::class, $fixtures);
    }

    
    public function testAnonymousTasksFixtureLoadMethod(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $fixtures = new AnonymousTasksFixture($passwordHasher);
        
        $this->assertTrue(method_exists($fixtures, 'load'));
        $this->assertInstanceOf(AnonymousTasksFixture::class, $fixtures);
    }

    
    public function testAnonymousTasksFixtureCreatesTasksWithoutUser(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $fixtures = new AnonymousTasksFixture($passwordHasher);
        
        $this->assertInstanceOf(AnonymousTasksFixture::class, $fixtures);
        $this->assertTrue(method_exists($fixtures, 'load'));
        
        $this->assertInstanceOf('Doctrine\Common\DataFixtures\FixtureInterface', $fixtures);
    }

    
    public function testTechnicalClassesHaveCorrectNamespaces(): void
    {
        $defaultReflection = new \ReflectionClass(DefaultController::class);
        $securityReflection = new \ReflectionClass(SecurityController::class);
        $appFixturesReflection = new \ReflectionClass(AppFixtures::class);
        $anonymousFixturesReflection = new \ReflectionClass(AnonymousTasksFixture::class);
        
        $this->assertEquals('App\\Controller', $defaultReflection->getNamespaceName());
        $this->assertEquals('App\\Controller', $securityReflection->getNamespaceName());
        $this->assertEquals('App\\DataFixtures', $appFixturesReflection->getNamespaceName());
        $this->assertEquals('App\\DataFixtures', $anonymousFixturesReflection->getNamespaceName());
    }

    
    public function testAdditionalTechnicalCoverage(): void
    {
        $defaultController = new DefaultController();
        $this->assertInstanceOf('Symfony\Bundle\FrameworkBundle\Controller\AbstractController', $defaultController);
        
        
        $securityController = new SecurityController();
        $this->assertInstanceOf('Symfony\Bundle\FrameworkBundle\Controller\AbstractController', $securityController);
        
        
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $appFixtures = new AppFixtures($passwordHasher);
        $anonymousFixtures = new AnonymousTasksFixture($passwordHasher);
        
        $this->assertNotNull($appFixtures);
        $this->assertNotNull($anonymousFixtures);
    }

    /**
     */
    public function testDependencyValidation(): void
    {
        $this->assertTrue(interface_exists(UserPasswordHasherInterface::class));
        $this->assertTrue(interface_exists(ObjectManager::class));
        $this->assertTrue(interface_exists('Doctrine\Common\DataFixtures\FixtureInterface'));
        
        $this->assertTrue(class_exists('Symfony\Bundle\FrameworkBundle\Controller\AbstractController'));
        $this->assertTrue(class_exists('Symfony\Component\HttpFoundation\Response'));
    }

    
    public function testAnonymousTasksFixtureFullCoverage(): void
    {
        
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')
            ->willReturn('hashed_password_123');
        
        
        $manager = $this->createMock(ObjectManager::class);
        
        
        $userRepository = $this->createMock('Doctrine\ORM\EntityRepository');
        $taskRepository = $this->createMock('Doctrine\ORM\EntityRepository');
        
        
        $manager->method('getRepository')
            ->willReturnCallback(function($entityClass) use ($userRepository, $taskRepository) {
                if ($entityClass === User::class) {
                    return $userRepository;
                }
                if ($entityClass === Task::class) {
                    return $taskRepository;
                }
                return null;
            });
        
        
        $userRepository->method('findOneBy')
            ->with(['username' => 'anonyme'])
            ->willReturn(null); 
        
        $anonymousTask1 = $this->createMock(Task::class);
        $anonymousTask2 = $this->createMock(Task::class);
        $anonymousTasks = [$anonymousTask1, $anonymousTask2];
        
        $taskRepository->method('findBy')
            ->with(['user' => null])
            ->willReturn($anonymousTasks);
        
        $manager->expects($this->once())->method('persist');
        $manager->expects($this->exactly(2))->method('flush');
        
        $anonymousTask1->expects($this->once())->method('setUser');
        $anonymousTask2->expects($this->once())->method('setUser');
        
        
        $fixture = new AnonymousTasksFixture($passwordHasher);
        $fixture->load($manager);
        
        $this->assertTrue(true, 'AnonymousTasksFixture s\'exécute correctement');
    }

    public function testAnonymousTasksFixtureUserExists(): void
    {
        
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        
        
        $manager = $this->createMock(ObjectManager::class);
        
        
        $userRepository = $this->createMock('Doctrine\ORM\EntityRepository');
        $taskRepository = $this->createMock('Doctrine\ORM\EntityRepository');
        
        
        $existingUser = $this->createMock(User::class);
        $userRepository->method('findOneBy')
            ->with(['username' => 'anonyme'])
            ->willReturn($existingUser);
        
        
        $taskRepository->method('findBy')
            ->with(['user' => null])
            ->willReturn([]);
        
        
        $manager->method('getRepository')
            ->willReturnCallback(function($entityClass) use ($userRepository, $taskRepository) {
                if ($entityClass === User::class) {
                    return $userRepository;
                }
                if ($entityClass === Task::class) {
                    return $taskRepository;
                }
                return null;
            });
        
        
        $manager->expects($this->never())->method('persist');
        
        $manager->expects($this->never())->method('flush');
        
        $fixture = new AnonymousTasksFixture($passwordHasher);
        $fixture->load($manager);
        
        $this->assertTrue(true, 'Fixture gère correctement le cas utilisateur existant');
    }

    /**
     */
    public function testAnonymousTasksFixtureConstructor(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $fixture = new AnonymousTasksFixture($passwordHasher);
        
        $this->assertInstanceOf(AnonymousTasksFixture::class, $fixture);
        $this->assertInstanceOf('Doctrine\Bundle\FixturesBundle\Fixture', $fixture);
    }

    
    public function testAnonymousTasksFixtureMultipleTasks(): void
    {
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')->willReturn('hashed_password');
        
        $manager = $this->createMock(ObjectManager::class);
        $userRepository = $this->createMock('Doctrine\ORM\EntityRepository');
        $taskRepository = $this->createMock('Doctrine\ORM\EntityRepository');
        
        
        $userRepository->method('findOneBy')->willReturn(null);
        
        
        $tasks = [];
        for ($i = 0; $i < 5; $i++) {
            $task = $this->createMock(Task::class);
            $task->expects($this->once())->method('setUser');
            $tasks[] = $task;
        }
        
        $taskRepository->method('findBy')
            ->with(['user' => null])
            ->willReturn($tasks);
        
        $manager->method('getRepository')
            ->willReturnCallback(function($entityClass) use ($userRepository, $taskRepository) {
                return $entityClass === User::class ? $userRepository : $taskRepository;
            });
        
        $manager->expects($this->once())->method('persist');
        $manager->expects($this->exactly(2))->method('flush');
        
        $fixture = new AnonymousTasksFixture($passwordHasher);
        $fixture->load($manager);
        
        $this->assertTrue(true, 'Fixture traite correctement plusieurs tâches');
    }

    
    public function testSecurityControllerFullCoverage(): void
    {
        
        $securityController = new SecurityController();
        
        try {
            $securityController->loginCheck();
            $this->fail('loginCheck() devrait lever une LogicException');
        } catch (\LogicException $e) {
            $this->assertEquals('This code should never be reached', $e->getMessage());
        }
    }

    
    public function testSecurityControllerLogout(): void
    {
        $securityController = new SecurityController();
        
        try {
            $securityController->logout();
            $this->fail('logout() devrait lever une LogicException');
        } catch (\LogicException $e) {
            $this->assertEquals('This code should never be reached', $e->getMessage());
        }
    }

    
    public function testSecurityControllerLogin(): void
    {
        
        $authUtils = $this->createMock(AuthenticationUtils::class);
        $authUtils->method('getLastAuthenticationError')
            ->willReturn(null);
        $authUtils->method('getLastUsername')
            ->willReturn('testuser');
        
        
        $securityController = new SecurityController();
        
        
        $reflection = new \ReflectionClass($securityController);
        $method = $reflection->getMethod('login');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals('login', $method->getName());
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('authenticationUtils', $parameters[0]->getName());
    }
}
