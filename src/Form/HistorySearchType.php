<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HistorySearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fromDate', null, [
                'label' => 'history.fromDate',
            ])
            ->add('toDate', null, [
                'label' => 'history.toDate',
            ])
            ->add('rctpNameNumber', null, [
                'label' => 'history.rctpNameNumber',
            ])
            ->add('text', null, [
                'label' => 'history.text',
            ])
            ->add('status', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'history.status',
                'choices' => [
                    'choice.blank' => null,
                    'choice.sent' => 'SENT',
                    'choice.failed' => 'FAILED',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
