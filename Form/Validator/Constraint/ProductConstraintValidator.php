<?php


namespace CleverAge\EAVManager\AkeneoProductBundle\Form\Validator\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;

class ProductConstraintValidator extends \Symfony\Component\Validator\ConstraintValidator
{
    /**
     * @param mixed                        $product
     * @param ProductConstraint|Constraint $constraint
     *
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function validate($product, Constraint $constraint)
    {
        $this->filterConstraints($constraint);

        if ($this->validateProperties($product, $constraint)) {
            $this->validateConstraints($product, $constraint);
        }
    }

    /**
     * @param mixed                        $product
     * @param ProductConstraint|Constraint $constraint
     *
     * @return bool
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function validateProperties($product, Constraint $constraint): bool
    {
        if (empty($constraint->properties)) {
            return true;
        }

        $violations = $this->context->getValidator()
            ->inContext($this->context)
            ->atPath('[values]')
            ->validate(
                $product['values'],
                 new Collection([
                    'allowExtraFields' => true,
                    'allowMissingFields' => true,
                    'groups' => $constraint->groups,
                    'fields' => $constraint->properties,
                ])
            )
            ->getViolations();

        return 0 === \count($violations);
    }

    /**
     * @param mixed                        $product
     * @param ProductConstraint|Constraint $constraint
     */
    public function validateConstraints($product, Constraint $constraint): void
    {
        foreach ($constraint->constraints as $subConstraint) {
            $this->context->getValidator()
                ->inContext($this->context)
                ->validate($product, $subConstraint);
        }
    }

    /**
     * @param ProductConstraint|Constraint $constraint
     */
    protected function filterConstraints(Constraint $constraint)
    {
        if (!empty($constraint->groups) && Constraint::DEFAULT_GROUP !== $constraint->groups[0]) {
            foreach ($constraint->constraints as $key => $subConstraint) {
                $excessGroups = array_diff($constraint->groups, $subConstraint->groups);
                if (count($excessGroups) > 0) {
                    unset($constraint->constraints[$key]);
                }
            }

            foreach ($constraint->properties as $propertyKey => $propertyConstraints) {
                foreach ($propertyConstraints as $key => $subConstraint) {
                    $excessGroups = array_diff($constraint->groups, $subConstraint->groups);
                    if (count($excessGroups) > 0) {
                        unset($constraint->properties[$propertyKey][$key]);
                    }
                }

            }
        }
    }
}
