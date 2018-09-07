<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Form\Validator;

use CleverAge\EAVManager\AkeneoProductBundle\Form\Validator\Constraint\DummyConstraint;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class ValidationMapper
{
    /** @var ExecutionContextFactoryInterface */
    protected $executionContextFactory;

    /** @var ValidatorInterface */
    protected $validator;

    /**
     * ValidationMapper constructor.
     *
     * @param ExecutionContextFactoryInterface $executionContextFactory
     */
    public function __construct(ExecutionContextFactoryInterface $executionContextFactory, ValidatorInterface $validator)
    {
        $this->executionContextFactory = $executionContextFactory;
        $this->validator = $validator;
    }

    /**
     * @param FormInterface $form
     * @param array         $akeneoErrors
     */
    public function mapErrors(FormInterface $form, array $akeneoErrors)
    {
        $violations = $this->getViolations($form, $akeneoErrors);

        $this->mapViolations($violations, $form);
    }

    /**
     * @param FormInterface $form
     * @param array         $akeneoErrors
     *
     * @return ConstraintViolationListInterface
     */
    protected function getViolations(FormInterface $form, array $akeneoErrors): ConstraintViolationListInterface
    {
        $executionContext = $this->executionContextFactory->createContext($this->validator, $form);
        $executionContext->setConstraint(new DummyConstraint());

        foreach ($akeneoErrors as $error) {
            $executionContext->buildViolation($error['message'])
                ->atPath(isset($error['attribute']) ? $this->retrievePropertyPath($form, $error['attribute']) : '')
                ->addViolation();
        }

        return $executionContext->getViolations();
    }

    /**
     * Method use to create property_path like : children[__tab_required].children[__tab_required_general].children[designation]
     * @param FormInterface $form
     * @param string        $attributeCode
     * @param string        $propertyPath
     *
     * @return string
     */
    protected function retrievePropertyPath(FormInterface $form,  string $attributeCode, string $propertyPath = ''): string
    {
        if ($form->has($attributeCode)) {
            return $this->definePropertyPath($attributeCode, $propertyPath);
        }

        if (0 === $form->count()) {
            return '';
        }

        /** @var FormInterface $child */
        foreach ($form as $child) {
            $propertyPath = $this->definePropertyPath($child->getName(), $propertyPath);
            $propertyPath = $this->retrievePropertyPath($child, $attributeCode, $propertyPath);

            if (!empty($propertyPath)) {
                break;
            }
        }

        return $propertyPath;
    }

    /**
     * @param string $propertyPath
     * @param string $element
     *
     * @return string
     */
    protected function definePropertyPath(string $element, string $propertyPath = ''): string
    {
        if (!empty($propertyPath)) {
            $propertyPath .= '.';
        }

        return $propertyPath . sprintf('children[%s]', $element);
    }

    /**
     * @param ConstraintViolationListInterface $violations
     * @param FormInterface                    $form
     */
    protected function mapViolations(ConstraintViolationListInterface $violations, FormInterface $form)
    {
        $vm = new ViolationMapper();

        foreach ($violations as $violation) {
            $vm->mapViolation($violation, $form);
        }
    }
}
