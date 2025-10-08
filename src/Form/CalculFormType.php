<?php

namespace App\Form;


use App\Entity\Calcul;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CalculFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeEvenement', ChoiceType::class, [
                'label' => 'type d\'événement',
                'choices' => [
                    'Apéritif' => 'apero',
                    'Diner' => 'diner',
                    'Anniversaire' => 'birthday',
                    'Baptême' => 'bapteme',
                    'Mariage' => 'mariage',
                    'Barbecue' => 'barbecue',
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])

            ->add('viandesBarbecue', ChoiceType::class, [
                'label' => 'Sélectionnez vos viandes',
                'choices' => [
                    'Merguez' => 'merguez',
                    'Saucisses' => 'saucisses',
                    'Brochettes' => 'brochettes',
                    'Steaks hachés' => 'steaks',
                    'Côtes de porc' => 'cotes_porc',
                    'Grosses côtes de bœuf à partager' => 'cotes_boeuf',
                    'Pilons de poulet' => 'pilons_poulet',
                    'Cuisses de poulet' => 'cuisses_poulet',
                    'Travers de porc' => 'travers_porc',
                    'Chipolatas' => 'chipolatas',
                ],
                'multiple' => true,
                'expanded' => true, //affiche des checkboxes
                'required' => false,
                'attr' => [
                    'class' => 'viandes-barbecue-container'
                ],
                'label_attr' => [
                    'class' => 'form-label fw-bold'
                ],
            ])

            ->add('sansAlcool', CheckboxType::class, [
                'label' => 'Evénement sans alcool',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
                'help' => 'Cochez cette case pour exlure les boissons alcoolisée'
            ])



            ->add('nbPersonnes', IntegerType::class, [
                'label' => 'Nombre d\'adultes',
                'attr'  => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => 'Entrer le nombre d\'adultes'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Ce champ est requis']),
                    new Assert\Positive(['message' => 'Le nombre doit être positif'])
                ],
                'help' => 'Nombre de personnes adultes (12 ans et plus)'
            ])

            // ✅ NOUVEAU CHAMP nbEnfants
            ->add('nbEnfants', IntegerType::class, [
                'label' => 'Nombre d\'enfants',
                'attr'  => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => 'Entrez le nombre d\'enfants',
                    'value' => 0
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le nombre doit être positif ou zéro'])
                ],
                'help' => 'Nombre d\'enfants (moins de 12 ans) - Comptent pour moitié dans les calculs',
                'required' => false,
                'data' => 0 // Valeur par défaut
            ])

        

            ->add('dateEvenement', DateType::class, [
                'label' => 'Date de l\'événement',
                'widget' => 'single_text',
                'input' => 'datetime_immutable', // ✅ IMPORTANT
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => false,
            ])
            ->add('calculer', SubmitType:: class, [
                'label' => 'Calculer',
                'attr' => [
                    'class' => 'btn btn-success w-100'
                ]

            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Calcul::class,
        ]);
    }
}
