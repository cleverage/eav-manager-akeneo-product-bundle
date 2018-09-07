<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Form\Validator\Constraint;

use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Use to retrieve the value(s) when the validator is use directly to the response API or to the form
 *
 * @author Fabien Salles <fsalles@clever-age.com>
 */
abstract class ConstraintValidator extends \Symfony\Component\Validator\ConstraintValidator
{
    /** @var AkeneoContextManager */
    protected $contextManger;

    public function __construct(AkeneoContextManager $contextManager)
    {
        $this->contextManger = $contextManager;
    }

    /**
     * @param $value
     *
     * @return mixed|null
     * @throws \RuntimeException
     */
    public function retrievePropertyValue($value)
    {
        if (null === $value || is_scalar($value) || \is_object($value)) {
            return $value;
        }

        if (\is_array($value)) {
            return $this->contextManger->getValue($value);
        }

        throw new \RuntimeException(sprintf('The value %s is not supported', json_encode($value)));
    }

    /**
     * @return string
     * @throws \RuntimeException
     * @throws \Symfony\Component\PropertyAccess\Exception\OutOfBoundsException
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException
     * @throws \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
     */
    public function retrievePropertyName(): string
    {
        if ($this->context->getObject() instanceof FormInterface) {
            return $this->context->getObject()->getName();
        }

        $propertyPath = new PropertyPath($this->context->getPropertyPath());

        if ($propertyPath->getLength()) {
            return $propertyPath->getElement($propertyPath->getLength() - 1);
        }

        throw new \RuntimeException('The context is not supported');
    }

    /**
     * @param null $values
     *
     * @return array|mixed
     * @throws \RuntimeException
     */
    public function retrieveRootValues($values = null)
    {
        $rootValues = $values ?: $this->context->getRoot();

        if (\is_array($rootValues)) {
            return $rootValues['values'] ?? $rootValues;
        }

        if ($rootValues instanceof FormInterface) {
            return $this->retrieveRootValues($rootValues->getData());
        }

        throw new \RuntimeException(sprintf('The type of the root context %s is not supported', json_encode($this->context->getRoot())));
    }

    public function getProductIdentifier()
    {
        $root = $this->context->getRoot();
        $data = $root instanceof FormInterface ? $root->getData() : $root;

        return $data['identifier'];
    }
}
