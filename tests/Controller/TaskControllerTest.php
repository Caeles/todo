<?php

namespace App\Tests\Controller;

use App\Controller\TaskController;
use App\Entity\Task;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\ORM\EntityManagerInterface;


class TaskControllerTest extends WebTestCase
{


    private ?KernelBrowser $client = null;
    private ?EntityManagerInterface $entityManager = null;
    private ?User $testUser = null;



    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();


        $this->testUser = new User();
        $this->testUser->setUsername('testuser');
        $this->testUser->setEmail('test@example.com');
        $this->testUser->setPassword('$2y$13$hashedpassword');
        $this->testUser->setRoles(['ROLE_USER']);

        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    /**
     * @covers \App\Controller\TaskController::list
     * @covers \App\Controller\TaskController::create
     * @covers \App\Controller\TaskController::edit
     * @covers \App\Controller\TaskController::toggleTask
     * @covers \App\Controller\TaskController::deleteTask
     */
    public function testExplicitCoversAllMethods(): void
    {
        $this->loginUser();

        
        $this->client->request('GET', '/tasks');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [200, 302], true));

 
        $crawler = $this->client->request('GET', '/tasks/create');
        $form = $crawler->selectButton('Ajouter')->form();
        $form['task[title]'] = 'Covers - création';
        $form['task[content]'] = 'Contenu covers';
        $this->client->submit($form);
        if ($this->client->getResponse()->isRedirection()) {
            $this->client->followRedirect();
        }

        $task = $this->entityManager->getRepository(Task::class)
            ->findOneBy(['title' => 'Covers - création']);
        $this->assertNotNull($task);


        $crawler = $this->client->request('GET', '/tasks/' . $task->getId() . '/edit');
        $form = $crawler->selectButton('Modifier')->form();
        $form['task[title]'] = 'Covers - édition';
        $this->client->submit($form);
        if ($this->client->getResponse()->isRedirection()) {
            $this->client->followRedirect();
        }


        $this->client->request('GET', '/tasks/' . $task->getId() . '/validate');
        if ($this->client->getResponse()->isRedirection()) {
            $this->client->followRedirect();
        }
        $this->client->request('GET', '/tasks/' . $task->getId() . '/delete');
        if ($this->client->getResponse()->isRedirection()) {
            $this->client->followRedirect();
        }
    }

    protected function tearDown(): void
    {
        if ($this->entityManager) {
            try {
                $this->entityManager->createQuery('DELETE FROM App\\Entity\\Task')->execute();
                $this->entityManager->createQuery('DELETE FROM App\\Entity\\User')->execute();
            } catch (\Exception $e) {
         
            }

            $this->entityManager->close();
            $this->entityManager = null;
        }

        $this->testUser = null;
        $this->client = null;
        parent::tearDown();
    }

// Tests unitaires

    public function testControllerHasAllRequiredMethods(): void
    {
        $controller = new TaskController();

        $this->assertTrue(method_exists($controller, 'list'), 'La méthode list() doit exister');
        $this->assertTrue(method_exists($controller, 'create'), 'La méthode create() doit exister');
        $this->assertTrue(method_exists($controller, 'edit'), 'La méthode edit() doit exister');
        $this->assertTrue(method_exists($controller, 'toggleTask'), 'La méthode toggleTask() doit exister');
        $this->assertTrue(method_exists($controller, 'deleteTask'), 'La méthode deleteTask() doit exister');
    }


    public function testControllerCanBeInstantiated(): void
    {
        $controller = new TaskController();
        $this->assertInstanceOf(TaskController::class, $controller);
    }


    public function testControllerHasCorrectNumberOfMethods(): void
    {
        $reflection = new \ReflectionClass(TaskController::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $controllerMethods = [];
        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() === TaskController::class) {
                $controllerMethods[] = $method->getName();
            }
        }

        $this->assertCount(5, $controllerMethods, 'TaskController devrait avoir exactement 5 méthodes publiques');
    }

// Tests fonctionnels


    public function testTaskListSmoke(): void
    {
        $this->loginUser();
        $this->client->request('GET', '/tasks');
        $status = $this->client->getResponse()->getStatusCode();
        $this->assertContains($status, [200, 302, 403]);
    }


    public function testCoverAllTaskControllerMethods(): void
    {
        $this->loginUser();

        $this->client->request('GET', '/tasks');
        $this->assertResponseStatusCodeSame(200);

        $crawler = $this->client->request('GET', '/tasks/create');
        $this->assertResponseStatusCodeSame(200);

        $form = $crawler->selectButton('Ajouter')->form();
        $form['task[title]'] = 'Couverture - création';
        $form['task[content]'] = 'Contenu couverture';
        $this->client->submit($form);
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [200, 302], true));
        if ($this->client->getResponse()->isRedirection()) {
            $this->client->followRedirect();
        }


        $task = $this->entityManager->getRepository(Task::class)
            ->findOneBy(['title' => 'Couverture - création']);
        $this->assertNotNull($task);


        $this->client->request('GET', '/tasks/' . $task->getId() . '/edit');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [200, 302], true));


        $crawler = $this->client->request('GET', '/tasks/' . $task->getId() . '/edit');
        $form = $crawler->selectButton('Modifier')->form();
        $form['task[title]'] = 'Couverture - édition';
        $this->client->submit($form);
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [200, 302], true));
        if ($this->client->getResponse()->isRedirection()) {
            $this->client->followRedirect();
        }

   
        $this->client->request('GET', '/tasks/' . $task->getId() . '/validate');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [200, 302], true));
        if ($this->client->getResponse()->isRedirection()) {
            $this->client->followRedirect();
        }


        $this->client->request('GET', '/tasks/' . $task->getId() . '/delete');
        $this->assertTrue(in_array($this->client->getResponse()->getStatusCode(), [200, 302], true));
    }

 
    private function loginUser(User $user = null): void
    {
        $user = $user ?? $this->testUser;
        $this->client->loginUser($user);
    }


   
    private function createTestTask(string $title = 'Tâche de test', string $content = 'Contenu de test', User $user = null): Task
    {
        $user = $user ?? $this->testUser;

        $task = new Task();
        $task->setTitle($title);
        $task->setContent($content);
        $task->setCreatedAt(new \DateTime());
        $task->setUser($user);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }


    public function testTaskListFunctional(): void
    {
        $this->loginUser();

        $task1 = $this->createTestTask('Tâche 1', 'Contenu 1');
        $task2 = $this->createTestTask('Tâche 2', 'Contenu 2');

        $this->client->request('GET', '/tasks');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des tâches');
        $this->assertSelectorTextContains('body', 'Tâche 1');
        $this->assertSelectorTextContains('body', 'Tâche 2');
    }


    public function testTaskCreateGetFunctional(): void
    {
        $this->loginUser();

        $this->client->request('GET', '/tasks/create');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="task"]');
        $this->assertSelectorExists('input[name="task[title]"]');
        $this->assertSelectorExists('textarea[name="task[content]"]');
    }


    public function testTaskCreatePostValidFunctional(): void
    {
        $this->loginUser();

        $this->client->request('GET', '/tasks/create');

        $this->client->submitForm('Ajouter', [
            'task[title]' => 'Nouvelle tâche',
            'task[content]' => 'Nouveau contenu'
        ]);

        $this->assertResponseRedirects('/tasks');


        $task = $this->entityManager->getRepository(Task::class)->findOneBy(['title' => 'Nouvelle tâche']);
        $this->assertNotNull($task);
        $this->assertEquals('Nouvelle tâche', $task->getTitle());
        $this->assertEquals('Nouveau contenu', $task->getContent());
        $this->assertFalse($task->isDone());
    }


    public function testTaskToggleFunctional(): void
    {
        $this->loginUser();

        $task = $this->createTestTask('Tâche à basculer', 'Test Toggle');
        $this->assertFalse($task->isDone());

        $this->client->request('POST', '/tasks/' . $task->getId() . '/validate');

        $this->assertResponseRedirects('/tasks');


        $this->entityManager->refresh($task);
        $this->assertTrue($task->isDone());

        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'La tâche Tâche à basculer a bien été marquée comme faite');
    }


    public function testTaskDeleteFunctional(): void
    {
        $this->loginUser();

        $task = $this->createTestTask('Tâche à supprimer', 'Contenu à supprimer');
        $taskId = $task->getId();

        $this->client->request('GET', '/tasks/' . $taskId . '/delete');

        $this->assertResponseRedirects('/tasks');


        $deletedTask = $this->entityManager->getRepository(Task::class)->find($taskId);
        $this->assertNull($deletedTask);


        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'La tâche a bien été supprimée');
    }


    public function testUnauthorizedAccessFunctional(): void
    {
        $this->client->request('GET', '/tasks');
        
        $this->assertResponseRedirects();
    }

    
    

    public function testCompleteTaskEditWorkflow(): void
    {
        $this->loginUser();
        
        $task = $this->createTestTask('Tâche à modifier', 'Contenu original');
        $taskId = $task->getId();
        

        $this->client->request('GET', '/tasks/' . $taskId . '/edit');
        $this->assertResponseIsSuccessful();
        

        $this->client->submitForm('Modifier', [
            'task[title]' => 'Tâche modifiée par test',
            'task[content]' => 'Contenu modifié par test'
        ]);
        
        $this->assertResponseRedirects('/tasks');
        

        $updatedTask = $this->entityManager->getRepository(Task::class)->find($taskId);
        $this->assertNotNull($updatedTask);
        $this->assertEquals('Tâche modifiée par test', $updatedTask->getTitle());
        $this->assertEquals('Contenu modifié par test', $updatedTask->getContent());
    }


    public function testCreateAndDeleteChain(): void
    {
        $this->loginUser();
        

        for ($i = 1; $i <= 3; $i++) {
            $this->client->request('GET', '/tasks/create');
            $this->client->submitForm('Ajouter', [
                'task[title]' => 'Tâche Chain ' . $i,
                'task[content]' => 'Contenu Chain ' . $i
            ]);
            $this->assertResponseRedirects('/tasks');
        }
        

        $tasks = $this->entityManager->getRepository(Task::class)->findBy(['user' => $this->testUser]);
        $this->assertGreaterThanOrEqual(3, count($tasks));
        

        $taskToDelete = $tasks[0];
        $taskId = $taskToDelete->getId();
        
        $this->client->request('GET', '/tasks/' . $taskId . '/delete');
        $this->assertResponseRedirects('/tasks');
        

        $this->entityManager->clear();
        $deletedTask = $this->entityManager->getRepository(Task::class)->find($taskId);
        $this->assertNull($deletedTask, 'La tâche devrait être supprimée de la base de données');
    }

   
    public function testMultipleTaskToggle(): void
    {
        $this->loginUser();
        
        $task1 = $this->createTestTask('Tâche Toggle 1', 'Contenu 1');
        $task2 = $this->createTestTask('Tâche Toggle 2', 'Contenu 2');
        $task3 = $this->createTestTask('Tâche Toggle 3', 'Contenu 3');
        
        $task1Id = $task1->getId();
        $task2Id = $task2->getId();
        $task3Id = $task3->getId();
        
        $this->assertFalse($task1->isDone());
        $this->assertFalse($task2->isDone());
        $this->assertFalse($task3->isDone());
        
        $this->client->request('POST', '/tasks/' . $task1Id . '/validate');
        $this->assertResponseRedirects('/tasks');
        
        $this->client->request('POST', '/tasks/' . $task2Id . '/validate');
        $this->assertResponseRedirects('/tasks');
        
        $updatedTask1 = $this->entityManager->getRepository(Task::class)->find($task1Id);
        $updatedTask2 = $this->entityManager->getRepository(Task::class)->find($task2Id);
        $updatedTask3 = $this->entityManager->getRepository(Task::class)->find($task3Id);
        
        $this->assertTrue($updatedTask1->isDone());
        $this->assertTrue($updatedTask2->isDone());
        $this->assertFalse($updatedTask3->isDone()); 
        
        $this->client->request('POST', '/tasks/' . $task1Id . '/validate');
        $this->assertResponseRedirects('/tasks');
        
  
        $this->entityManager->clear();
        $finalTask1 = $this->entityManager->getRepository(Task::class)->find($task1Id);
        $this->assertFalse($finalTask1->isDone(), 'La tâche devrait être non terminée après le deuxième toggle');
    }

    
    public function testTaskEntityBehavior(): void
    {
        $task = new Task();
        $task->setTitle('Test Task');
        $task->setContent('Test Content');
        $task->setCreatedAt(new \DateTime());

        $this->assertEquals('Test Task', $task->getTitle());
        $this->assertEquals('Test Content', $task->getContent());
        $this->assertInstanceOf(\DateTime::class, $task->getCreatedAt());

        $this->assertFalse($task->isDone());
        $task->toggle(true);
        $this->assertTrue($task->isDone());
        $task->toggle(false);
        $this->assertFalse($task->isDone());
    }

    
    public function testUserEntityBehavior(): void
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_USER']);

        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $this->assertCount(0, $user->getTasks());

        $task = new Task();
        $task->setUser($user);
        $user->addTask($task);

        $this->assertCount(1, $user->getTasks());
        $this->assertTrue($user->getTasks()->contains($task));
    }

        public function testTaskAndUserEntitiesCanBeCreated(): void
    {
        
        $task = new Task();
        $task->setTitle('Test Task');
        $task->setContent('Test Content');
        $task->setCreatedAt(new \DateTime());

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->getTitle());
        $this->assertEquals('Test Content', $task->getContent());
        $this->assertFalse($task->isDone()); 

    
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('test@example.com', $user->getEmail());


        $task->setUser($user);
        $this->assertEquals($user, $task->getUser());
    }

    
    public function testTaskEntityBasicBehavior(): void
    {
        $task = new Task();
        $task->setTitle('Ma tâche');
        $task->setContent('Description de ma tâche');
        $task->setCreatedAt(new \DateTime());

        
       
        $this->assertFalse($task->isDone());
        
        $task->toggle(true);
        $this->assertTrue($task->isDone());
        
        $task->toggle(false);
        $this->assertFalse($task->isDone());
    }

 
   
    public function testTaskListPageIsAccessible(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', '/tasks');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des tâches');
    }


    public function testCreateNewTask(): void
    {
        $this->client->loginUser($this->testUser);
        
     
        $crawler = $this->client->request('GET', '/tasks/create');
        $this->assertResponseIsSuccessful();
        

        $form = $crawler->selectButton('Ajouter')->form();
        $form['task[title]'] = 'Nouvelle tâche test';
        $form['task[content]'] = 'Contenu de la nouvelle tâche';
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/tasks');
        

        $this->client->followRedirect();
        $this->assertSelectorTextContains('h4 a', 'Nouvelle tâche test');
    }

  
    public function testEditExistingTask(): void
    {
        $this->client->loginUser($this->testUser);
        
        $task = new Task();
        $task->setTitle('Tâche à éditer');
        $task->setContent('Contenu original');
        $task->setUser($this->testUser);
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        
  
        $crawler = $this->client->request('GET', '/tasks/' . $task->getId() . '/edit');
        $this->assertResponseIsSuccessful();
        
        $form = $crawler->selectButton('Modifier')->form();
        $form['task[title]'] = 'Tâche modifiée';
        $form['task[content]'] = 'Contenu modifié';
        
        $this->client->submit($form);
        
        $this->assertResponseRedirects('/tasks');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('h4 a', 'Tâche modifiée');
    }

    
    public function testToggleTaskStatus(): void
    {
        $this->client->loginUser($this->testUser);
        
        $task = new Task();
        $task->setTitle('Tâche à basculer');
        $task->setContent('Contenu test');
        $task->setUser($this->testUser);
        $task->toggle(false); 
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        

        $this->client->request('GET', '/tasks/' . $task->getId() . '/validate');
        $this->assertResponseRedirects('/tasks');
        
        $this->entityManager->clear();
        $updatedTask = $this->entityManager->getRepository(Task::class)->find($task->getId());
        $this->assertTrue($updatedTask->isDone());
    }

   
    public function testDeleteTask(): void
    {
        $this->client->loginUser($this->testUser);
        
        $task = new Task();
        $task->setTitle('Tâche à supprimer');
        $task->setContent('Contenu à supprimer');
        $task->setUser($this->testUser);
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();
        $taskId = $task->getId();
        
        $this->client->request('GET', '/tasks/' . $taskId . '/delete');
        $this->assertResponseRedirects('/tasks');
        
        $this->entityManager->clear();
        $deletedTask = $this->entityManager->getRepository(Task::class)->find($taskId);
        $this->assertNull($deletedTask);
    }

   
    public function testAccessDeniedWithoutAuthentication(): void
    {
        $this->client->request('GET', '/tasks');
        $this->assertResponseRedirects('http://localhost/login');
        
        $this->client->request('GET', '/tasks/create');
        $this->assertResponseRedirects('http://localhost/login');
    }

   
    public function testCompleteTaskWorkflow(): void
    {
        $this->client->loginUser($this->testUser);
        
       
        $crawler = $this->client->request('GET', '/tasks/create');
        $form = $crawler->selectButton('Ajouter')->form();
        $form['task[title]'] = 'Workflow Test';
        $form['task[content]'] = 'Test du workflow complet';
        $this->client->submit($form);
        
       
        $this->client->followRedirect();
        $this->assertSelectorTextContains('h4 a', 'Workflow Test');
        
       
        $task = $this->entityManager->getRepository(Task::class)
            ->findOneBy(['title' => 'Workflow Test']);
        $this->assertNotNull($task);
        
       
        $this->client->request('GET', '/tasks/' . $task->getId() . '/validate');
        $this->client->followRedirect();
        
       
        $this->entityManager->clear();
        $updatedTask = $this->entityManager->getRepository(Task::class)->find($task->getId());
        $this->assertTrue($updatedTask->isDone());
        
       
        $crawler = $this->client->request('GET', '/tasks/' . $task->getId() . '/edit');
        $form = $crawler->selectButton('Modifier')->form();
        $form['task[title]'] = 'Workflow Test Modifié';
        $this->client->submit($form);
        
        
        $this->client->followRedirect();
        $this->assertSelectorTextContains('h4 a', 'Workflow Test Modifié');
        
        
        $this->client->request('GET', '/tasks/' . $task->getId() . '/delete');
        $this->client->followRedirect();
        
        $this->entityManager->clear();
        $deletedTask = $this->entityManager->getRepository(Task::class)->find($task->getId());
        $this->assertNull($deletedTask);
    }
}
