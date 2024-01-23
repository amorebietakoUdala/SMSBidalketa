<?php

namespace App\Form;

use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('telephone', null, [
                'constraints' => [
                    new NotBlank(),
                    new Regex('/^(71|72|73|74)\d{7}+$|^6\d{8}+$/'),
                ],
                'label' => 'contact.telephone',
            ])
            ->add('name', null, [
                'constraints' => [],
                'label' => 'contact.name',
            ])
            ->add('surname1', null, [
                'constraints' => [],
                'label' => 'contact.surname1',
            ])
            ->add('surname2', null, [
                'label' => 'contact.surname2',
            ])
            ->add('labels', CollectionType::class, [
                'entry_type' => LabelType::class,
                'allow_delete' => true,
                'allow_add' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
