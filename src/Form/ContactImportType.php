<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Description of ReceiptUploadTypeForm.
 *
 * @author ibilbao
 */
class ContactImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];
        $builder->add('file', FileType::class, [
            'label' => 'contactImport.file',
            'attr' => [
                'class' => 'custom-file',
            ],
            'constraints' => [
                new NotBlank(),
            ],
            'disabled' => $readonly,
            ])
            ->add('labels', CollectionType::class, [
                'entry_type' => LabelType::class,
                'allow_delete' => true,
                'allow_add' => true,
                'label' => 'contact.labels',
            ])
            ->add('send', SubmitType::class, [
            'label' => 'btn.send',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        'csrf_protection' => true,
        'data_class' => null,
        'readonly' => false,
    ]);
    }
}
