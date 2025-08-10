<?php

namespace App\Tests;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use App\Form\UserType;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Test\TypeTestCase;


class EntitiesFormsTest extends TypeTestCase
{
    public function testTaskCreation(): void
    {
        $task = new Task();
        $task->setTitle('Ma première tâche');
        $task->setContent('Description de la tâche');
        
       
        $this->assertSame('Ma première tâche', $task->getTitle());
        $this->assertSame('Description de la tâche', $task->getContent());
    }
    
    
    public function testTaskDefaultValues(): void
    {
        $task = new Task();
        
        
        $this->assertFalse($task->isDone());
        
        
        $this->assertInstanceOf('\DateTimeInterface', $task->getCreatedAt());
    }
    
    
    public function testTaskToggleDone(): void
    {
        $task = new Task();
        
        
        $this->assertFalse($task->isDone());
        
        
        $task->toggle(true);
        $this->assertTrue($task->isDone());
        
        
        $task->toggle(false);
        $this->assertFalse($task->isDone());
    }
    
    
    
    public function testTaskSetCreatedAt(): void
    {
        $task = new Task();
        $customDate = new \DateTime('2023-01-01 10:00:00');
        
        $task->setCreatedAt($customDate);
        
        $this->assertSame($customDate, $task->getCreatedAt());
        $this->assertSame('2023-01-01 10:00:00', $task->getCreatedAt()->format('Y-m-d H:i:s'));
    }
    
    
    public function testTaskUserAssociation(): void
    {
        $task = new Task();
        $user = new User();
        $user->setUsername('testuser');
        
        
        $this->assertNull($task->getUser());
        
        
        $task->setUser($user);
        $this->assertSame($user, $task->getUser());
        $this->assertSame('testuser', $task->getUser()->getUsername());
    }
    
  
    public function testTaskUserCanBeNull(): void
    {
        $task = new Task();
        $user = new User();
        
        
        $task->setUser($user);
        $this->assertSame($user, $task->getUser());
        
        $task->setUser(null);
        $this->assertNull($task->getUser());
    }
    
    
    public function testTaskCompleteWorkflow(): void
    {
        $task = new Task();
        $user = new User();
        $user->setUsername('johndoe');
        
        
        $task->setTitle('Faire les courses');
        $task->setContent('Acheter du pain, du lait et des œufs');
        $task->setUser($user);
        
        
        $this->assertSame('Faire les courses', $task->getTitle());
        $this->assertSame('Acheter du pain, du lait et des œufs', $task->getContent());
        $this->assertSame($user, $task->getUser());
        $this->assertFalse($task->isDone());
        
        
        $task->toggle(true);
        $this->assertTrue($task->isDone());
    }

    
     
    public function testTaskIdIsNullForNewEntity(): void
    {
        $task = new Task();
        $this->assertNull($task->getId());
    }

    
    public function testUserCreation(): void
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setPassword('password123');
        $user->setEmail('test@example.com');
        
        $this->assertSame('testuser', $user->getUsername());
        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('password123', $user->getPassword());
    }
    
    
    public function testUserIdentifier(): void
    {
        $user = new User();
        $user->setUsername('johndoe');
        
        $this->assertSame('johndoe', $user->getUserIdentifier());
    }
    
    
    public function testDefaultRoles(): void
    {
        $user = new User();
        
        
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertCount(1, $user->getRoles());
    }
    
    
    public function testSetRolesAdmin(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        
        
        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(2, $roles);
    }
    
    
    public function testSetRolesUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);
        
        // L'utilisateur ne doit avoir que ROLE_USER (pas de doublon)
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertCount(1, $roles);
    }
    
    /**
     */
    public function testGetSalt(): void
    {
        $user = new User();
        
        
        $this->assertNull($user->getSalt());
    }
    
    
    public function testEraseCredentials(): void
    {
        $user = new User();
        
        
        $this->assertNull($user->eraseCredentials());
    }
    
    public function testTasksCollection(): void
    {
        $user = new User();
        
        
        $this->assertCount(0, $user->getTasks());
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $user->getTasks());
    }

    
    public function testAddTaskToUser(): void
    {
        $user = new User();
        $task = new Task();
        $task->setTitle('Tâche de test');
        
        $user->addTask($task);
        
        $this->assertCount(1, $user->getTasks());
        $this->assertTrue($user->getTasks()->contains($task));
        $this->assertSame($user, $task->getUser());
    }

    
    public function testRemoveTaskFromUser(): void
    {
        $user = new User();
        $task = new Task();
        $task->setTitle('Tâche à supprimer');
        
        $user->addTask($task);
        $this->assertCount(1, $user->getTasks());
        
        $user->removeTask($task);
        $this->assertCount(0, $user->getTasks());
        $this->assertNull($task->getUser());
    }

    
    public function testUserIdIsNullForNewEntity(): void
    {
        $user = new User();
        $this->assertNull($user->getId());
    }

    
    public function testTaskTypeBuildForm(): void
    {
        $task = new Task();
        $task->setTitle('Test Task');
        $task->setContent('Test Content');

        $form = $this->factory->create(TaskType::class, $task);

        $this->assertTrue($form->has('title'));
        $this->assertTrue($form->has('content'));
        
        $this->assertFalse($form->has('author'));
    }

    
    public function testTaskTypeSubmitValidData(): void
    {
        $formData = [
            'title' => 'Nouvelle Tâche',
            'content' => 'Contenu de la nouvelle tâche'
        ];

        $task = new Task();
        $form = $this->factory->create(TaskType::class, $task);

        
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertSame('Nouvelle Tâche', $task->getTitle());
        $this->assertSame('Contenu de la nouvelle tâche', $task->getContent());
    }

    
    public function testTaskTypeConfigureOptions(): void
    {
        $form = $this->factory->create(TaskType::class);
        
        $config = $form->getConfig();
        $this->assertSame(Task::class, $config->getDataClass());
    }

    
    public function testTaskTypeFormView(): void
    {
        $task = new Task();
        $task->setTitle('Test Title');
        $task->setContent('Test Content');

        $form = $this->factory->create(TaskType::class, $task);
        $view = $form->createView();

        $this->assertArrayHasKey('title', $view->children);
        $this->assertArrayHasKey('content', $view->children);

        $this->assertSame('Test Title', $view->children['title']->vars['value']);
        $this->assertSame('Test Content', $view->children['content']->vars['value']);
    }

    
    public function testTaskTypeContentFieldIsTextarea(): void
    {
        $form = $this->factory->create(TaskType::class);
        
        $contentField = $form->get('content');
        $this->assertSame('Symfony\Component\Form\Extension\Core\Type\TextareaType', 
                         get_class($contentField->getConfig()->getType()->getInnerType()));
    }

    
    public function testUserTypeBuildForm(): void
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');

        $form = $this->factory->create(UserType::class, $user);

        $this->assertTrue($form->has('username'));
        $this->assertTrue($form->has('password'));
        $this->assertTrue($form->has('email'));
    }

    
    public function testUserTypeSubmitValidData(): void
    {
        $formData = [
            'username' => 'newuser',
            'password' => [
                'first' => 'newpassword',
                'second' => 'newpassword'
            ],
            'email' => 'newuser@example.com'
        ];

        $user = new User();
        $form = $this->factory->create(UserType::class, $user);

        // Soumettre les données
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertSame('newuser', $user->getUsername());
        $this->assertSame('newpassword', $user->getPassword());
        $this->assertSame('newuser@example.com', $user->getEmail());
    }

    
    public function testUserTypeConfigureOptions(): void
    {
        $user = new User(); // Créer un utilisateur pour éviter l'erreur getId() sur null
        $form = $this->factory->create(UserType::class, $user);
        
        $config = $form->getConfig();
        $this->assertSame(User::class, $config->getDataClass());
    }

    
    public function testTaskRepositoryCanBeInstantiated(): void
    {
       
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        
        $repository = new TaskRepository($managerRegistry);
        
        $this->assertInstanceOf(TaskRepository::class, $repository);
    }

    
    public function testTaskRepositorySaveMethodCallsPersistAndFlush(): void
    {
        
        $entityManager = $this->createMock(EntityManagerInterface::class);
        
        
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        
        $task = new Task();
        $task->setTitle('Test Task');
        $task->setContent('Test Content');
        
        $entityManager->expects($this->once())
                     ->method('persist')
                     ->with($task);
                     
        $entityManager->expects($this->once())
                     ->method('flush');
        
        $repository = $this->getMockBuilder(TaskRepository::class)
                          ->setConstructorArgs([$managerRegistry])
                          ->onlyMethods(['getEntityManager'])
                          ->getMock();
                          
        $repository->method('getEntityManager')->willReturn($entityManager);
        
        $repository->save($task, true);
    }

    
    public function testTaskRepositorySaveMethodWithoutFlush(): void
    {
        
        $entityManager = $this->createMock(EntityManagerInterface::class);
        
        
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        
        $task = new Task();
        $task->setTitle('Test Task No Flush');
        $task->setContent('Test Content No Flush');
        
        $entityManager->expects($this->once())
                     ->method('persist')
                     ->with($task);
                     
        $entityManager->expects($this->never())
                     ->method('flush');
        
        $repository = $this->getMockBuilder(TaskRepository::class)
                          ->setConstructorArgs([$managerRegistry])
                          ->onlyMethods(['getEntityManager'])
                          ->getMock();
                          
        $repository->method('getEntityManager')->willReturn($entityManager);
        
        $repository->save($task, false);
    }

    
    public function testTaskRepositoryRemoveMethodCallsRemoveAndFlush(): void
    {
        
        $entityManager = $this->createMock(EntityManagerInterface::class);
        
        
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        
        $task = new Task();
        $task->setTitle('Task to Remove');
        $task->setContent('Content to Remove');
        
        
        $entityManager->expects($this->once())
                     ->method('remove')
                     ->with($task);
                     
        $entityManager->expects($this->once())
                     ->method('flush');
        
        $repository = $this->getMockBuilder(TaskRepository::class)
                          ->setConstructorArgs([$managerRegistry])
                          ->onlyMethods(['getEntityManager'])
                          ->getMock();
                          
        $repository->method('getEntityManager')->willReturn($entityManager);
        
        
        $repository->remove($task, true);
    }

    
    public function testTaskRepositoryMethodsReturnVoid(): void
    {
        
        $entityManager = $this->createMock(EntityManagerInterface::class);
        
        
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        
        $task = new Task();
        $task->setTitle('Test Return Type');
        $task->setContent('Test Content');
        
        $repository = $this->getMockBuilder(TaskRepository::class)
                          ->setConstructorArgs([$managerRegistry])
                          ->onlyMethods(['getEntityManager'])
                          ->getMock();
                          
        $repository->method('getEntityManager')->willReturn($entityManager);
        
        
        $saveResult = $repository->save($task, false);
        $this->assertNull($saveResult);
        
        $removeResult = $repository->remove($task, false);
        $this->assertNull($removeResult);
    }

    
    public function testUserRepositoryCanBeInstantiated(): void
    {
        
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        
        $repository = new UserRepository($managerRegistry);
        
        $this->assertInstanceOf(UserRepository::class, $repository);
    }

    
    public function testUserRepositorySaveMethod(): void
    {
        
        $entityManager = $this->createMock(EntityManagerInterface::class);
        
        
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        
        $entityManager->expects($this->once())
                     ->method('persist')
                     ->with($user);
                     
        $entityManager->expects($this->once())
                     ->method('flush');
        
        $repository = $this->getMockBuilder(UserRepository::class)
                          ->setConstructorArgs([$managerRegistry])
                          ->onlyMethods(['getEntityManager'])
                          ->getMock();
                          
        $repository->method('getEntityManager')->willReturn($entityManager);
        
        
        $repository->save($user, true);
    }

    
    public function testUserRepositoryRemoveMethod(): void
    {
        
        $entityManager = $this->createMock(EntityManagerInterface::class);
        
        
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        
        $user = new User();
        $user->setUsername('usertoremove');
        
        
        $entityManager->expects($this->once())
                     ->method('remove')
                     ->with($user);
                     
        $entityManager->expects($this->once())
                     ->method('flush');
        
        $repository = $this->getMockBuilder(UserRepository::class)
                          ->setConstructorArgs([$managerRegistry])
                          ->onlyMethods(['getEntityManager'])
                          ->getMock();
                          
        $repository->method('getEntityManager')->willReturn($entityManager);
        
        $repository->remove($user, true);
    }

    
    public function testUserRepositoryUpgradePasswordMethod(): void
    {
        
        $entityManager = $this->createMock(EntityManagerInterface::class);
        
        
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        
        $user = new User();
        $user->setUsername('testuser');
        $newHashedPassword = 'new_hashed_password';
        
        
        $entityManager->expects($this->once())
                     ->method('persist')
                     ->with($user);
                     
        $entityManager->expects($this->once())
                     ->method('flush');
        
        $repository = $this->getMockBuilder(UserRepository::class)
                          ->setConstructorArgs([$managerRegistry])
                          ->onlyMethods(['getEntityManager'])
                          ->getMock();
                          
        $repository->method('getEntityManager')->willReturn($entityManager);
        
        
        $repository->upgradePassword($user, $newHashedPassword);
        
        $this->assertSame($newHashedPassword, $user->getPassword());
    }

    
    public function testUserRepositoryMethodsReturnVoid(): void
    {
        
        $entityManager = $this->createMock(EntityManagerInterface::class);
        
        
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        
        $user = new User();
        $user->setUsername('testuser');
        
        $repository = $this->getMockBuilder(UserRepository::class)
                          ->setConstructorArgs([$managerRegistry])
                          ->onlyMethods(['getEntityManager'])
                          ->getMock();
                          
        $repository->method('getEntityManager')->willReturn($entityManager);
        
        
        $saveResult = $repository->save($user, false);
        $this->assertNull($saveResult);
        
        $removeResult = $repository->remove($user, false);
        $this->assertNull($removeResult);
        
        $upgradeResult = $repository->upgradePassword($user, 'newpassword');
        $this->assertNull($upgradeResult);
    }

    
    public function testUserRepositoryUpgradePasswordWithInvalidUser(): void
    {
        
        $entityManager = $this->createMock(EntityManagerInterface::class);
        
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        
        $repository = $this->getMockBuilder(UserRepository::class)
                          ->setConstructorArgs([$managerRegistry])
                          ->onlyMethods(['getEntityManager'])
                          ->getMock();
                          
        $repository->method('getEntityManager')->willReturn($entityManager);
        
        $invalidUser = $this->createMock('Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface');
        
        $this->expectException('Symfony\Component\Security\Core\Exception\UnsupportedUserException');
        $this->expectExceptionMessage('Instances of');
        
        
        $repository->upgradePassword($invalidUser, 'newpassword');
    }

    

    public function testUserEntityCallMethod(): void
    {
        $user = new User();
        
        
        $result = $user->__call('getSalt', []);
        $this->assertNull($result);
        
        
        $result = $user->__call('unknownMethod', []);
        $this->assertNull($result);
    }

    
    public function testUserEntityRemoveTaskWithCondition(): void
    {
        $user = new User();
        $task = new Task();
        
        $user->addTask($task);
        $this->assertTrue($user->getTasks()->contains($task));
        $this->assertSame($user, $task->getUser());
        
        $user->removeTask($task);
        $this->assertFalse($user->getTasks()->contains($task));
        $this->assertNull($task->getUser());
    }

    
    public function testUserEntityRemoveTaskWithoutCondition(): void
    {
        $user = new User();
        $task = new Task();
        
        $user->addTask($task);
        $task->setUser(null); 
        
        $user->removeTask($task);
        $this->assertFalse($user->getTasks()->contains($task));
        $this->assertNull($task->getUser());
    }

    
    public function testEntityFormIntegration(): void
    {
        $task = new Task();
        
        $formData = [
            'title' => 'Tâche via formulaire',
            'content' => 'Contenu via formulaire'
        ];
        
        $form = $this->factory->create(TaskType::class, $task);
        $form->submit($formData);
        
        $this->assertTrue($form->isValid());
        $this->assertSame('Tâche via formulaire', $task->getTitle());
        $this->assertSame('Contenu via formulaire', $task->getContent());
        $this->assertFalse($task->isDone()); 
    }

    
    public function testAllClassesExistAndHaveCorrectNamespace(): void
    {
        $this->assertTrue(class_exists(Task::class));
        $this->assertTrue(class_exists(User::class));
        $this->assertTrue(class_exists(TaskType::class));
        $this->assertTrue(class_exists(UserType::class));
        $this->assertTrue(class_exists(TaskRepository::class));
        $this->assertTrue(class_exists(UserRepository::class));
        
        $taskReflection = new \ReflectionClass(Task::class);
        $userReflection = new \ReflectionClass(User::class);
        
        $this->assertEquals('App\\Entity', $taskReflection->getNamespaceName());
        $this->assertEquals('App\\Entity', $userReflection->getNamespaceName());
    }

    
    public function testAdditionalCoverageForPublicMethods(): void
    {
        $task = new Task();
        $user = new User();
        
        $this->assertNull($task->getId()); 
        $this->assertNull($user->getId()); 
        
        $this->assertIsString($user->getUserIdentifier());
        $this->assertIsArray($user->getRoles());
        $this->assertInstanceOf('\DateTimeInterface', $task->getCreatedAt());
    }
}
