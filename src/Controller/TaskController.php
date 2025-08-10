<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends AbstractController
{
    #[Route('/tasks', name: 'task_list')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();
        $taskRepo = $entityManager->getRepository(Task::class);

            $tasks = $taskRepo->findBy(['user' => $currentUser]);

        if ($this->isGranted('ROLE_ADMIN')) {
            $anonymousUser = $entityManager->getRepository(User::class)->findOneBy(['username' => 'anonyme']);
            if ($anonymousUser) {
                $anonymousTasks = $taskRepo->findBy(['user' => $anonymousUser]);
                $byId = [];
                foreach (array_merge($tasks, $anonymousTasks) as $t) {
                    $byId[$t->getId()] = $t;
                }
                $tasks = array_values($byId);
            }
        }

        return $this->render('task/list.html.twig', ['tasks' => $tasks]);
    }

    #[Route('/tasks/create', name: 'task_create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUser($this->getUser());
            
            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'La tâche a été bien été ajoutée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/tasks/{id}/edit', name: 'task_edit')]
    public function edit(Task $task, Request $request, EntityManagerInterface $entityManager): Response
    {
        $originalUser = $task->getUser();
        
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($task->getUser() !== $originalUser) {
                $task->setUser($originalUser);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'La tâche a bien été modifiée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route('/tasks/{id}/validate', name: 'task_validate')]
    public function toggleTask(Task $task, EntityManagerInterface $entityManager): Response
    {
        $task->toggle(!$task->isDone());
        $entityManager->flush();

        $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme faite.', $task->getTitle()));

        return $this->redirectToRoute('task_list');
    }

    #[Route('/tasks/{id}/delete', name: 'task_delete')]
    public function deleteTask(Task $task, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            $this->addFlash('error', 'Vous devez être connecté pour supprimer une tâche.');
            return $this->redirectToRoute('login');
        }

        $taskOwner = $task->getUser();
        $isOwner = $taskOwner && $taskOwner->getId() === $currentUser->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isAnonymousTask = $taskOwner && $taskOwner->getUsername() === 'anonyme';

        if (!$isOwner && !($isAdmin && $isAnonymousTask)) {
            $this->addFlash('error', "Vous n'avez pas les droits pour supprimer cette tâche.");
            return $this->redirectToRoute('task_list');
        }

        $entityManager->remove($task);
        $entityManager->flush();

        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('task_list');
    }
}
