<?php

namespace App\Form;

use App\Entity\Challenge;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

class ChallengeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('description')
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Frontend' => 'frontend',
                    'Backend' => 'backend',
                    'Fullstack' => 'fullstack',
                ],
                'constraints' => [
                    new Choice(['frontend', 'backend', 'fullstack']),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Todo' => 1,
                    'In progress' => 2,
                    'Done' => 3,
                ],
                'constraints' => [
                    new Choice([1, 2, 3]),
                ],
            ])
            ->add('github')
            ->add('images', FileType::class, [
                'label' => false,
                'multiple' => true,
                'mapped' => false,
                'required' => false,
                'attr'     => [
                    'accept' => 'image/*',
                    'multiple' => 'multiple'
                ],
            ])
            ->add('createdAt', null, [
                'widget' => 'single_text',
                'data' => new \DateTimeImmutable(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Challenge::class,
        ]);
    }
}
