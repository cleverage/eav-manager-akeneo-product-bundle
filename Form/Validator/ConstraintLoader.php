<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Form\Validator;

use Sidus\BaseBundle\Validator\Mapping\Loader\BaseLoader;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class ConstraintLoader
{
    protected $loader;

    /**
     * ConstraintLoader constructor.
     *
     * @param BaseLoader $loader
     */
    public function __construct(BaseLoader $loader)
    {
        $this->loader = $loader;
    }


    /**
     * @param array $validationRules
     *
     * @return array
     * @throws \Symfony\Component\Validator\Exception\MappingException
     */
    public function load(array $validationRules): array
    {
        $validationRulesLoaded = [];

        if (isset($validationRules['constraints'])) {
            $validationRulesLoaded['constraints'] = $this->loader->loadCustomConstraints($validationRules['constraints']);
        }

        if (isset($validationRules['properties'])) {
            foreach ($validationRules['properties'] as $property => $constraints) {
                $validationRulesLoaded['properties'][$property] = $this->loader->loadCustomConstraints($constraints);
            }
        }

        return $validationRulesLoaded;
    }
}
