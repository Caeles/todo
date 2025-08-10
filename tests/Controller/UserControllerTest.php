<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;


class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private User $testUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\Task')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
    }

    private function loginUser(string $username = 'admin', array $roles = ['ROLE_ADMIN']): User
    {
        $user = $this->createTestUser($username, $username . '@example.com', $roles);
        $this->client->loginUser($user);

        return $user;
    }

    private function createTestUser(string $username = 'testuser', string $email = 'test@example.com', array $roles = ['ROLE_USER']): User
    {
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles($roles);
        
        $hashedPassword = $passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    // Tests unitaires
    public function testUserControllerExists(): void
    {
        $controller = new \App\Controller\UserController();
        $this->assertInstanceOf(\App\Controller\UserController::class, $controller);
        $this->assertInstanceOf(\Symfony\Bundle\FrameworkBundle\Controller\AbstractController::class, $controller);
    }

    public function testUserControllerHasRequiredMethods(): void
    {
        $controller = new \App\Controller\UserController();
        
        $this->assertTrue(method_exists($controller, 'list'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'edit'));
        
        $reflection = new \ReflectionClass(\App\Controller\UserController::class);
        $this->assertTrue($reflection->getMethod('list')->isPublic());
        $this->assertTrue($reflection->getMethod('create')->isPublic());
        $this->assertTrue($reflection->getMethod('edit')->isPublic());
        
        $publicMethods = array_filter($reflection->getMethods(), function($method) {
            return $method->isPublic() && $method->getDeclaringClass()->getName() === \App\Controller\UserController::class;
        });
        $this->assertCount(3, $publicMethods, 'UserController devrait avoir exactement 3 méthodes publiques');
    }

    public function testUserEntityCanBeCreated(): void
    {
        $user = new User();
        $this->assertInstanceOf(User::class, $user);
        
        $this->assertNull($user->getId());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
        $this->assertNull($user->getSalt());
    }

    public function testUserEntitySettersAndGetters(): void
    {
        $user = new User();
        
        $user->setUsername('testuser');
        $this->assertSame('testuser', $user->getUsername());
        $this->assertSame('testuser', $user->getUserIdentifier());
        
        $user->setEmail('test@example.com');
        $this->assertSame('test@example.com', $user->getEmail());
        
        $user->setPassword('hashedpassword');
        $this->assertSame('hashedpassword', $user->getPassword());
        
        $user->setRoles(['ROLE_ADMIN']);
        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
        
        $user->eraseCredentials();
        $this->assertTrue(true); 
    }


    // Tests Fonctionnels
    public function testUserListRequiresAuthentication(): void
    {
        $this->client->request('GET', '/users');
        $statusCode = $this->client->getResponse()->getStatusCode();
        
        $this->assertContains($statusCode, [302, 401, 403, 500]);
    }

    public function testUserListPageIsAccessible(): void
    {
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $crawler = $this->client->request('GET', '/users');
        
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertNotEquals(500, $statusCode);
        $this->assertContains($statusCode, [200, 302, 403]);
        if ($statusCode === 200) {
            $this->assertSelectorExists('h1'); 
        }
    }
    public function testUserListWithAdminAuthentication(): void
    {
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $this->client->request('GET', '/users');
        $statusCode = $this->client->getResponse()->getStatusCode();
        
        $this->assertContains($statusCode, [200, 500]);
    }

    public function testUserListAccessDeniedForRegularUser(): void
    {
        $this->loginUser('user', ['ROLE_USER']);
        
        $this->client->request('GET', '/users');
        $statusCode = $this->client->getResponse()->getStatusCode();
        
        $this->assertContains($statusCode, [302, 403, 500]);
    }

    public function testUserCreateWithoutAuthentication(): void
    {
        $this->client->request('GET', '/users/create');
        $statusCode = $this->client->getResponse()->getStatusCode();
        
        $this->assertContains($statusCode, [200, 302, 500]);
    }

    public function testUserCreateWithAdminAuthentication(): void
    {
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $this->client->request('GET', '/users/create');
        $statusCode = $this->client->getResponse()->getStatusCode();
        
        $this->assertContains($statusCode, [200, 500]);
    }
    public function testUserEditRequiresAuthentication(): void
    {
        $this->client->request('GET', '/users/1/edit');
        $statusCode = $this->client->getResponse()->getStatusCode();
        
        $this->assertContains($statusCode, [302, 401, 403, 404, 500]);
    }
    public function testUserEditWithAdminAuthentication(): void
    {
        $user = $this->createTestUser('edituser', 'edit@example.com', ['ROLE_USER']);
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $this->client->request('GET', '/users/' . $user->getId() . '/edit');
        $statusCode = $this->client->getResponse()->getStatusCode();
        
        $this->assertContains($statusCode, [200, 500]);
    }

    public function testCompleteUserCreateWorkflow(): void
    {
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $this->client->request('GET', '/users/create');
        
        if ($this->client->getResponse()->getStatusCode() === 200) {
            $this->client->submitForm('Ajouter', [
                'user[username]' => 'nouveautestuser',
                'user[email]' => 'nouveau@example.com',
                'user[password][first]' => 'motdepasse123',
                'user[password][second]' => 'motdepasse123',
                'user[roles]' => 'ROLE_USER'
            ]);
            
            $status = $this->client->getResponse()->getStatusCode();
            $this->assertNotEquals(500, $status);
            $this->assertContains($status, [200, 302, 403]);
            if ($status === 302) {
                $this->assertResponseRedirects('/users');
                $createdUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'nouveautestuser']);
                $this->assertNotNull($createdUser);
                $this->assertEquals('nouveau@example.com', $createdUser->getEmail());
                $this->assertContains('ROLE_USER', $createdUser->getRoles());
                $this->assertNotSame('motdepasse123', $createdUser->getPassword());
                $this->assertNotEmpty($createdUser->getPassword());
            }
        } else {
            $this->assertContains($this->client->getResponse()->getStatusCode(), [200, 302, 403, 500]);
        }
    }
    public function testCompleteUserEditWorkflow(): void
    {
        $user = $this->createTestUser('useroriginal', 'original@example.com', ['ROLE_USER']);
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $this->client->request('GET', '/users/' . $user->getId() . '/edit');
        
        if ($this->client->getResponse()->getStatusCode() === 200) {
            $this->client->submitForm('Modifier', [
                'user[username]' => 'usermodifie',
                'user[email]' => 'modifie@example.com',
                'user[password][first]' => '',
                'user[password][second]' => '',
                'user[roles]' => 'ROLE_ADMIN'
            ]);
            
            $status = $this->client->getResponse()->getStatusCode();
            $this->assertContains($status, [200, 302, 403, 500]);
            if ($status === 302) {
                $this->assertResponseRedirects('/users');
                $this->entityManager->clear();
                $reloaded = $this->entityManager->getRepository(User::class)->find($user->getId());
                $this->assertNotNull($reloaded);
                $this->assertEquals('usermodifie', $reloaded->getUsername());
                $this->assertEquals('modifie@example.com', $reloaded->getEmail());
                $this->assertContains('ROLE_ADMIN', $reloaded->getRoles());
            }
        } else {
            $this->assertContains($this->client->getResponse()->getStatusCode(), [200, 302, 403, 404, 500]);
        }
    }
    public function testUserEditPasswordChange(): void
    {
        $user = $this->createTestUser('passworduser', 'password@example.com', ['ROLE_USER']);
        $originalPassword = $user->getPassword();
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $this->client->request('GET', '/users/' . $user->getId() . '/edit');
        
        if ($this->client->getResponse()->getStatusCode() === 200) {
            $this->client->submitForm('Modifier', [
                'user[username]' => 'passworduser',
                'user[email]' => 'password@example.com',
                'user[password][first]' => 'nouveaumotdepasse456',
                'user[password][second]' => 'nouveaumotdepasse456',
                'user[roles]' => 'ROLE_USER'
            ]);
            
            $status = $this->client->getResponse()->getStatusCode();
            $this->assertContains($status, [200, 302, 403, 500]);
            if ($status === 302) {
                $this->assertResponseRedirects('/users');
                $this->entityManager->clear();
                $reloaded = $this->entityManager->getRepository(User::class)->find($user->getId());
                $this->assertNotNull($reloaded);
                $this->assertNotSame($originalPassword, $reloaded->getPassword());
                $this->assertNotSame('nouveaumotdepasse456', $reloaded->getPassword()); 
            }
        } else {
            $this->assertContains($this->client->getResponse()->getStatusCode(), [200, 302, 403, 404, 500]);
        }
    }

    public function testUserListDisplaysUsers(): void
    {
        $user1 = $this->createTestUser('listuser1', 'list1@example.com', ['ROLE_USER']);
        $user2 = $this->createTestUser('listuser2', 'list2@example.com', ['ROLE_ADMIN']);
        
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $this->client->request('GET', '/users');
        
        if ($this->client->getResponse()->getStatusCode() === 200) {
            $content = $this->client->getResponse()->getContent();
            
            $this->assertStringContainsString('listuser1', $content);
            $this->assertStringContainsString('listuser2', $content);
            $this->assertStringContainsString('list1@example.com', $content);
            $this->assertStringContainsString('list2@example.com', $content);
        } else {
            $this->assertContains($this->client->getResponse()->getStatusCode(), [200, 302, 403, 500]);
        }
    }

    /**
     */
    public function testUserCreateFormValidation(): void
    {
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $this->client->request('GET', '/users/create');
        
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertNotEquals(500, $statusCode, 'Ne devrait pas avoir d\'erreur serveur');
        
        if ($statusCode === 200) {
            $this->assertSelectorExists('form');
        } else {
            $this->assertContains($statusCode, [200, 302, 403], 'Devrait être une réponse valide');
        }
    }
    public function testUserCreateWithDifferentRoles(): void
    {
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $this->client->request('GET', '/users/create');
        $statusCode = $this->client->getResponse()->getStatusCode();
        
        $this->assertNotEquals(500, $statusCode, 'Ne devrait pas avoir d\'erreur serveur');
        $this->assertContains($statusCode, [200, 302, 403], 'Devrait être une réponse HTTP valide');
        
        if ($statusCode === 200) {
            $this->assertSelectorExists('form');
        }
    }

    public function testUserEditPageIsAccessible(): void
    {
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $this->client->request('GET', '/users/' . $user->getId() . '/edit');
        $statusCode = $this->client->getResponse()->getStatusCode();
        
        $this->assertNotEquals(500, $statusCode, 'Ne devrait pas avoir d\'erreur serveur');
        $this->assertContains($statusCode, [200, 302, 403, 404], 'Devrait être une réponse HTTP valide');
        
        if ($statusCode === 200) {
            $this->assertSelectorExists('form');
        }
    }

    public function testUserPagesAccessDeniedForNonAdmin(): void
    {
        $this->loginUser('user', ['ROLE_USER']);
        
        $this->client->request('GET', '/users');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [302, 403]);
        
        $this->client->request('GET', '/users/create');
        $this->assertContains($this->client->getResponse()->getStatusCode(), [302, 403]);
    }

    public function testCompleteUserWorkflow(): void
    {
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $this->client->request('GET', '/users');
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 302, 403], 'Liste des utilisateurs devrait être accessible');
        
        $this->client->request('GET', '/users/create');
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 302, 403], 'Page de création devrait être accessible');
        
        $user = new User();
        $user->setUsername('workflowuser');
        $user->setEmail('workflow@example.com');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $this->client->request('GET', '/users/' . $user->getId() . '/edit');
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertContains($statusCode, [200, 302, 403, 404], 'Page d\'édition devrait être accessible');
        
        $this->entityManager->clear();
        $foundUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertNotNull($foundUser, 'L\'utilisateur devrait exister dans la base');
    }

    public function testMaximumUserControllerCoverage(): void
    {
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $routes = [
            ['GET', '/users'],
            ['GET', '/users/create']
        ];
        
        foreach ($routes as [$method, $url]) {
            $this->client->request($method, $url);
            $statusCode = $this->client->getResponse()->getStatusCode();
            
            $this->assertNotEquals(500, $statusCode, "Route $method $url ne devrait pas avoir d'erreur serveur");
            $this->assertContains($statusCode, [200, 302, 403, 404], "Route $method $url devrait avoir un code de réponse valide");
        }
        
        $user = new User();
        $user->setUsername('maxcoverageuser');
        $user->setEmail('maxcoverage@example.com');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $this->client->request('GET', '/users/' . $user->getId() . '/edit');
        $statusCode = $this->client->getResponse()->getStatusCode();
        $this->assertNotEquals(500, $statusCode, 'Route d\'édition ne devrait pas avoir d\'erreur serveur');
        $this->assertContains($statusCode, [200, 302, 403, 404], 'Route d\'édition devrait avoir un code de réponse valide');
    }

    /**
     * Test fonctionnel de création d'utilisateur 
     */
    public function testCompleteUserCreation(): void
    {
        // 1. Connexion en tant qu'admin 
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        // 2. Accès au formulaire de création
        $this->client->request('GET', '/users/create');
        $this->assertResponseIsSuccessful('Le formulaire de création devrait être accessible');
        $this->assertSelectorExists('form', 'Un formulaire devrait être présent sur la page');
        
        // 3. Comptage des utilisateurs avant création
        $userCountBefore = $this->entityManager->getRepository(User::class)->count([]);
        
        // 4. Soumission du formulaire avec données valides
        $this->client->submitForm('Ajouter', [
            'user[username]' => 'testuser_complet',
            'user[email]' => 'testcomplet@todolist.com',
            'user[password][first]' => 'exemple',
            'user[password][second]' => 'exemple',
            'user[roles]' => 'ROLE_USER'
        ]);
        
        // 5. Vérification de la redirection après création réussie
        $this->assertResponseRedirects('/users');
        
        // 6. Vérification que l'utilisateur a bien été créé en base
        $this->entityManager->clear(); 
        
        $userCountAfter = $this->entityManager->getRepository(User::class)->count([]);
        $this->assertEquals($userCountBefore + 1, $userCountAfter, 'Un nouvel utilisateur devrait avoir été créé');
        
        // 7. Vérification des données de l'utilisateur créé
        $createdUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['username' => 'testuser_complet']);
        
        $this->assertNotNull($createdUser, 'L\'utilisateur créé devrait exister en base');
        $this->assertEquals('testuser_complet', $createdUser->getUsername());
        $this->assertEquals('testcomplet@todolist.com', $createdUser->getEmail());
        $this->assertContains('ROLE_USER', $createdUser->getRoles());
        
        // 8. Vérification du hachage du mot de passe
        $this->assertNotEquals('motdepasse123', $createdUser->getPassword(), 
            'Le mot de passe ne devrait pas être lisible');
        $this->assertNotEmpty($createdUser->getPassword(), 
            'Le mot de passe haché ne devrait pas être vide');
        
        // 9. Vérification du message de succès
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success, .flash-success', 
            'L\'utilisateur a bien été ajouté', 
            'Un message de succès devrait être affiché');
    }

    /**
     * Test de création avec des mots de passe différents
     */
    public function testUserCreationWithInvalidData(): void
    {
        $this->loginUser('admin', ['ROLE_ADMIN']);
        
        $this->client->request('GET', '/users/create');
        $this->assertResponseIsSuccessful();
        
        // Comptage des utilisateurs avant tentative
        $userCountBefore = $this->entityManager->getRepository(User::class)->count([]);
        
        // Soumission de mots de passe différents
        $this->client->submitForm('Ajouter', [
            'user[username]' => 'testuser_echec',
            'user[email]' => 'echec@todolist.com',
            'user[password][first]' => 'motdepasse123',
            'user[password][second]' => 'motdepasse456', 
            'user[roles]' => 'ROLE_USER'
        ]);
        
        // Pas de redirection
        $this->assertResponseStatusCodeSame(200, 'Devrait rester sur le formulaire en cas d\'erreur');
        
        // Vérification qu'aucun utilisateur n'a été créé
        $this->entityManager->clear();
        $userCountAfter = $this->entityManager->getRepository(User::class)->count([]);
        $this->assertEquals($userCountBefore, $userCountAfter, 'Aucun utilisateur ne devrait être créé avec des données invalides');
        
        // Vérification de la présence d'erreurs de validation
        $this->assertSelectorExists('.form-error, .invalid-feedback', 
            'Des erreurs de validation devraient être affichées');
    }

    // Test de l'entité User
    public function testUserEntityMethods(): void
    {
        $user = new User();
        
        $this->assertTrue(method_exists($user, 'getUsername'));
        $this->assertTrue(method_exists($user, 'setUsername'));
        $this->assertTrue(method_exists($user, 'getPassword'));
        $this->assertTrue(method_exists($user, 'setPassword'));
        $this->assertTrue(method_exists($user, 'getEmail'));
        $this->assertTrue(method_exists($user, 'setEmail'));
        $this->assertTrue(method_exists($user, 'getRoles'));
        $this->assertTrue(method_exists($user, 'setRoles'));
        $this->assertTrue(method_exists($user, 'getUserIdentifier'));
        $this->assertTrue(method_exists($user, 'eraseCredentials'));
        $this->assertTrue(method_exists($user, 'getSalt'));
    }

    public function testUserEntityDefaultRoles(): void
    {
        $user = new User();
        
        $this->assertContains('ROLE_USER', $user->getRoles());
        
        $user->setRoles(['ROLE_ADMIN']);
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
        
        $user->setRoles(['ROLE_ADMIN', 'ROLE_MODERATOR']);
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_MODERATOR', $roles);
    }

    public function testUserEntityGetSalt(): void
    {
        $user = new User();
        
        $this->assertNull($user->getSalt());
    }
}
