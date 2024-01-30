<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\Label;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('telephone', null, [
                'label' => 'contact.telephone',
            ])
            ->add('name', null, [
                'label' => 'contact.name',
            ])
            ->add('surname1', null, [
                'label' => 'contact.surname1',
            ])
            ->add('surname2', null, [
                'label' => 'contact.surname2',
            ])
            ->add('labels', EntityType::class, [
                'class' => Label::class,
                'multiple' => 'multiple',
                'label' => 'contact.labels',
                'choice_label' => 'name',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
