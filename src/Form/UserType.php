<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
       
        $isEdit = $options['data']->getId() !== null;
        
        $builder
            ->add('username', TextType::class, ['label' => "Nom d'utilisateur"])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                'required' => !$isEdit, 
                'first_options'  => [
                    'label' => $isEdit ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe',
                    'attr' => $isEdit ? ['placeholder' => 'Laissez vide pour conserver le mot de passe actuel'] : []
                ],
                'second_options' => [
                    'label' => $isEdit ? 'Confirmez le nouveau mot de passe' : 'Tapez le mot de passe à nouveau'
                ],
            ])
            ->add('email', EmailType::class, ['label' => 'Adresse email'])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Type de compte',
                'required' => true,
                'data' => in_array('ROLE_ADMIN', $options['data']->getRoles()) ? 'ROLE_ADMIN' : 'ROLE_USER',
                'mapped' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
