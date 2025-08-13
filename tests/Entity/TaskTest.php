<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    /**
     * Test unitaire  de l'entité Task 
     */
    public function testCompleteTaskEntityBehavior(): void
    {

        $task = new Task();
        $this->assertInstanceOf(Task::class, $task, 'Une instance Task devrait être créée');
        
       
        $this->assertInstanceOf(\DateTimeInterface::class, $task->getCreatedAt(), 
            'La date de création devrait être automatiquement définie');
        $this->assertFalse($task->isDone(), 
            'Une nouvelle tâche devrait être marquée comme non terminée');
        $this->assertNull($task->getId(), 
            'L\'ID devrait être null avant persistance en base');
        $this->assertNull($task->getUser(), 
            'L\'utilisateur devrait être null par défaut');
        
        $expectedTitle = 'Apprendre les tests unitaires';
        $result = $task->setTitle($expectedTitle);
        
        $this->assertSame($task, $result, 
            'setTitle() devrait retourner l\'instance pour le chaînage');
        $this->assertSame($expectedTitle, $task->getTitle(), 
            'getTitle() devrait retourner exactement la valeur définie');
        

        $expectedContent = 'Comprendre la différence entre tests unitaires et fonctionnels';
        $result = $task->setContent($expectedContent);
        
        $this->assertSame($task, $result, 
            'setContent() devrait retourner l\'instance pour le chaînage');
        $this->assertSame($expectedContent, $task->getContent(), 
            'getContent() devrait retourner exactement la valeur définie');
        

        $customDate = new \DateTime('2025-01-15 10:30:00');
        $result = $task->setCreatedAt($customDate);
        
        $this->assertSame($task, $result, 
            'setCreatedAt() devrait retourner l\'instance pour le chaînage');
        $this->assertSame($customDate, $task->getCreatedAt(), 
            'getCreatedAt() devrait retourner exactement la date définie');
        

        $result = $task->toggle(true);
        $this->assertSame($task, $result, 
            'toggle() devrait retourner l\'instance pour le chaînage');
        $this->assertTrue($task->isDone(), 
            'La tâche devrait être marquée comme terminée après toggle(true)');
        

        $task->toggle(false);
        $this->assertFalse($task->isDone(), 
            'La tâche devrait être marquée comme non terminée après toggle(false)');
        

        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@todolist.com');
        
        $result = $task->setUser($user);
        $this->assertSame($task, $result, 
            'setUser() devrait retourner l\'instance pour le chaînage');
        $this->assertSame($user, $task->getUser(), 
            'getUser() devrait retourner exactement l\'utilisateur assigné');
        

        $task->setUser(null);
        $this->assertNull($task->getUser(), 
            'L\'utilisateur devrait pouvoir être défini à null');
        

        $this->assertSame($expectedTitle, $task->getTitle(), 
            'Le titre devrait être préservé après toutes les opérations');
        $this->assertSame($expectedContent, $task->getContent(), 
            'Le contenu devrait être préservé après toutes les opérations');
        $this->assertSame($customDate, $task->getCreatedAt(), 
            'La date devrait être préservée après toutes les opérations');
        $this->assertFalse($task->isDone(), 
            'Le statut final devrait être "non terminée"');
        
      
    }
    


    /**
     * Test unitaire des valeurs par défaut du constructeur
     */
    public function testTaskDefaultValues(): void
    {
        $task = new Task();
        
        $this->assertFalse($task->isDone(), 
            'Une nouvelle tâche devrait être non terminée par défaut');
        $this->assertInstanceOf(\DateTime::class, $task->getCreatedAt(), 
            'La date de création devrait être un objet DateTime');
        $this->assertNull($task->getTitle(), 
            'Le titre devrait être null par défaut');
        $this->assertNull($task->getContent(), 
            'Le contenu devrait être null par défaut');
        $this->assertNull($task->getUser(), 
            'L\'utilisateur devrait être null par défaut');
        
        $now = new \DateTime();
        $diff = $now->getTimestamp() - $task->getCreatedAt()->getTimestamp();
        $this->assertLessThan(5, $diff, 
            'La date de création devrait être très récente');
    }

    /**
     * Test unitaire du basculement de statut (toggle)
     */
    public function testTaskToggleBehavior(): void
    {
        $task = new Task();
        
        $this->assertFalse($task->isDone(), 'État initial : non terminée');
        
        $result = $task->toggle(true);
        $this->assertSame($task, $result, 'toggle() devrait retourner $this');
        $this->assertTrue($task->isDone(), 'Après toggle(true) : terminée');
        
        $task->toggle(false);
        $this->assertFalse($task->isDone(), 'Après toggle(false) : non terminée');
        
        $task->toggle(true);
        $this->assertTrue($task->isDone(), 'Après second toggle(true) : terminée');
        

        $task->toggle(false);
        $task->toggle(true);
        $task->toggle(false);
        $this->assertFalse($task->isDone(), 
            'Après séquence toggle : devrait être non terminée');
    }

    /**
     * Test unitaire de l'association Task-User
     * Vérifie la gestion de la relation ManyToOne avec User
     */
    public function testTaskUserAssociation(): void
    {
        $task = new Task();
        $user1 = new User();
        $user1->setUsername('user1');
        $user1->setEmail('user1@todolist.com');
        
        $user2 = new User();
        $user2->setUsername('user2');
        $user2->setEmail('user2@todolist.com');
        
       
        $result = $task->setUser($user1);
        $this->assertSame($task, $result, 'setUser() devrait retourner $this');
        $this->assertSame($user1, $task->getUser(), 
            'getUser() devrait retourner l\'utilisateur assigné');
        
        
        $task->setUser($user2);
        $this->assertSame($user2, $task->getUser(), 
            'L\'utilisateur devrait pouvoir être changé');
        $this->assertNotSame($user1, $task->getUser(), 
            'L\'ancien utilisateur ne devrait plus être associé');
        
       
        $task->setUser(null);
        $this->assertNull($task->getUser(), 
            'L\'utilisateur devrait pouvoir être supprimé (null)');
    }

    /**
     * Test unitaire de la modification des dates
     * Vérifie que les dates peuvent être modifiées correctement
     */
    public function testTaskDateManipulation(): void
    {
        $task = new Task();
        $originalDate = $task->getCreatedAt();
        
        
        $customDate = new \DateTime('2025-12-25 15:30:45');
        $result = $task->setCreatedAt($customDate);
        
        $this->assertSame($task, $result, 'setCreatedAt() devrait retourner $this');
        $this->assertSame($customDate, $task->getCreatedAt(), 
            'La date devrait être exactement celle définie');
        $this->assertNotSame($originalDate, $task->getCreatedAt(), 
            'La date devrait avoir changé par rapport à l\'originale');
        
     
        $futureDate = new \DateTime('+1 year');
        $task->setCreatedAt($futureDate);
        $this->assertSame($futureDate, $task->getCreatedAt(), 
            'Une date future devrait être acceptée');
        
     
        $pastDate = new \DateTime('2020-01-01 00:00:00');
        $task->setCreatedAt($pastDate);
        $this->assertSame($pastDate, $task->getCreatedAt(), 
            'Une date passée devrait être acceptée');
    }
}
