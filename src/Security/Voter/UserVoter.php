<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const CREATE = 'CREATE';
    public const LIST = 'LIST';

    protected function supports(string $attribute, mixed $subject): bool
    {

        return in_array($attribute, [self::VIEW, self::EDIT, self::CREATE, self::LIST])
            && ($subject instanceof User || $subject === User::class || $subject === null);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::LIST:
                return $this->canList($user);
            case self::CREATE:
                return $this->canCreate($user);
            case self::VIEW:
                return $this->canView($user);
            case self::EDIT:
                return $this->canEdit($user);

        }

        return false;
    }

    private function canList(UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canCreate(UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canView(UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canEdit(UserInterface $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }


}
