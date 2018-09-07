<?php
namespace CleverAge\EAVManager\AkeneoProductBundle\Form\Type;

use Sidus\EAVModelBundle\Entity\DataInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Sidus\EAVBootstrapBundle\Form\Type\AutocompleteDataSelectorType;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Doctrine\ORM\EntityManager;

class AkeneoEavPreFilterType  extends AutocompleteDataSelectorType
{
    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     *
     * @throws \RuntimeException
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $data = $form->getData();
        $data = $data ?? [];
        $data = \is_array($data) ? $data : [$data];
        $attribute = $options['attribute'];

        if (\in_array($attribute->getType()->getCode(), ['embed', 'related'])) {
            foreach($data as $val) { // Set the current data in the choices
                $entity = $this->repository->find($val);

                if ($entity instanceof DataInterface) { // Set the current data in the choices
                    $label = $this->computeLabelHelper->computeLabel($entity, $val, $options);
                    $view->vars['choices'][$val] =  new ChoiceView($entity, $val, $label);
                }
            }
        } else {
            foreach($data as $val) { // Set the current data in the choices
                $view->vars['choices'][$val] = new ChoiceView($val, $val, $val);
            }
        }

        if ($options['auto_init']) {
            if (empty($view->vars['attr']['class'])) {
                $view->vars['attr']['class'] = '';
            } else {
                $view->vars['attr']['class'] .= ' ';
            }

            $view->vars['attr']['class'] .= 'select2';

            if (!$options['required']) {
                $view->vars['attr']['data-allow-clear'] = 'true';
                $view->vars['attr']['data-placeholder'] = '';
            }
        }

        $view->vars['attr']['data-query-uri'] = $options['query_uri'];
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws TransformationFailedException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetModelTransformers();
        $builder->addModelTransformer(
            new CallbackTransformer(
                function ($originalData) {
                    return $originalData;
                },
                function ($submittedData) use ($options) {
                    if (null === $submittedData || '' === $submittedData) {
                        return null;
                    }

                    return $submittedData;
                }
            )
        );

        $builder->resetViewTransformers();
        $builder->addViewTransformer(
            new CallbackTransformer(
                function ($originalData) {
                    if ($originalData instanceof DataInterface) {
                        return $originalData->getId();
                    }

                    return $originalData;
                },
                function ($submittedData) {
                    return $submittedData;
                }
            )
        );
    }
}