<?php

namespace App\Security\Voter;

use App\Entity\Task;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class TaskVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';
    public const CREATE = 'CREATE';
    public const TOGGLE = 'TOGGLE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CREATE, self::TOGGLE])
            && ($subject instanceof Task || $subject === Task::class || $subject === null);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::CREATE:
                return $this->canCreate();
            case self::VIEW:
                return $this->canView($subject, $user);
            case self::EDIT:
                return $this->canEdit($subject, $user);
            case self::DELETE:
                return $this->canDelete($subject, $user);
            case self::TOGGLE:
                return $this->canToggle($subject, $user);
        }

        return false;
    }

    private function canCreate(): bool
    {
        return true;
    }

    private function canView(Task $task, UserInterface $user): bool
    {
        if ($task->getUser() && $task->getUser()->getId() === $user->getId()) {
            return true;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $taskOwner = $task->getUser();
            if ($taskOwner && $taskOwner->getUsername() === 'anonyme') {
                return true;
            }
        }

        return false;
    }

    private function canEdit(Task $task, UserInterface $user): bool
    {
        if ($task->getUser() && $task->getUser()->getId() === $user->getId()) {
            return true;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $taskOwner = $task->getUser();
            if ($taskOwner && $taskOwner->getUsername() === 'anonyme') {
                return true;
            }
        }

        return false;
    }

    private function canDelete(Task $task, UserInterface $user): bool
    {
        if ($task->getUser() && $task->getUser()->getId() === $user->getId()) {
            return true;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $taskOwner = $task->getUser();
            if ($taskOwner && $taskOwner->getUsername() === 'anonyme') {
                return true;
            }
        }

        return false;
    }

    private function canToggle(Task $task, UserInterface $user): bool
    {
        return $this->canEdit($task, $user);
    }
}
