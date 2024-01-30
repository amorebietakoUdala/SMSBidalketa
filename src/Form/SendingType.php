<?php

namespace App\Form;

use App\DTO\SendingDTO;
use App\Entity\Label;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

class SendingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('telephone', TextType::class, [
                'constraints' => new Regex('/^(71|72|73|74)\d{7}+$|^6\d{8}+$/'),
            ])
            ->add('labels', EntityType::class, [
                'class' => Label::class,
                'multiple' => 'multiple',
                'label' => 'contact.labels',
                'choice_label' => 'name',
            ])
            ->add('selected', HiddenType::class)
            ->add('message', TextareaType::class, [
                    'attr' => ['maxlength' => 160],
                    'label' => 'sending.message',
                    'help' => 'Remaining characters',
            ])
            ->add('date', DateTimeType::class, [
                    'widget' => 'single_text',
                    'label' => 'sending.date',
                    'html5' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SendingDTO::class,
        ]);
    }
}
