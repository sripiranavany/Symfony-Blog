<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = array();
        foreach ($options['cate'] as $category) {
            $choices[$category->getName()] = $category->getId();
        }
        $builder
            ->add('title',TextType::class,[
                'label' => 'Article Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter the Article Title'
                ]
            ])
            ->add('body',TextareaType::class,[
                'label' => 'Article body',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter the Article'
                ]
            ])
            ->add('category',ChoiceType::class,[
               'choices' => $choices,
               'label' => 'Article Category',
               'attr' => [
                   'class' => 'form-control',
                   'placeholder' => 'Select the Category'
               ]
            ])
            ->add('post_image',FileType::class,[
                'label' => 'Image',
                'attr' => [
                    'class' => 'form-control mb-3'
                ]
            ])
            ->add('Save',SubmitType::class,[
                'attr' => [
                    'class' => 'btn btn-info btn-block'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'cate' => null
        ]);
    }
}
