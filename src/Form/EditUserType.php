<?php

namespace App\Form;

use App\Document\Users;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class EditUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email')
            ->add('first_name')
            ->add('last_name')
            ->add('avatar', ChoiceType::class, [
                'choices'  => [
                    'avatar-1' => 1,
                    'avatar-2' => 2,
                    'avatar-3' => 3,
                    'avatar-4' => 4,
                    'avatar-5' => 5,
                    'avatar-6' => 6,
                ],
                'expanded' => true,
                'multiple' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
        ]);
    }
}