<?php

declare(strict_types=1);

namespace Klehm\SyliusPayumCA3xcbPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

final class CA3xcbGatewayConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sandbox', CheckboxType::class, [
                'label' => 'klehm_payum_ca3xcb_plugin.sandbox'
            ])
            ->add('hmac', TextType::class, [
                'label' => 'klehm_payum_ca3xcb_plugin.hmac',
                'constraints' => [
                    new NotBlank([
                        'message' => 'klehm_payum_ca3xcb_plugin.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('identifiant', TextType::class, [
                'label' => 'klehm_payum_ca3xcb_plugin.identifiant',
                'constraints' => [
                    new NotBlank([
                        'message' => 'klehm_payum_ca3xcb_plugin.identifiant.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('site', TextType::class, [
                'label' => 'klehm_payum_ca3xcb_plugin.site',
                'constraints' => [
                    new NotBlank([
                        'message' => 'klehm_payum_ca3xcb_plugin.site.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
            ->add('rang', TextType::class, [
                'label' => 'klehm_payum_ca3xcb_plugin.rang',
                'constraints' => [
                    new NotBlank([
                        'message' => 'klehm_payum_ca3xcb_plugin.rang.not_blank',
                        'groups' => ['sylius']
                    ])
                ],
            ])
        ;
    }
}
