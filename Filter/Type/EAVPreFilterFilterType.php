<?php
namespace CleverAge\EAVManager\AkeneoProductBundle\Filter\Type;

use CleverAge\EAVManager\AkeneoProductBundle\Query\AkeneoQueryHandlerInterface;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Sidus\EAVFilterBundle\Filter\EAVFilterHelper;
use Sidus\EAVModelBundle\Registry\FamilyRegistry;
use Doctrine\ORM\EntityManager;
use Jardiland\CleverAge\EAVManager\EAVModelBundle\Repository\DataRepository;
use Jardiland\CleverAge\EAVManager\EAVModelBundle\Entity\Data;

class EAVPreFilterFilterType extends AbstractFilterType
{
    /** @var EAVFilterHelper */
    protected $eavFilterHelper;

    /** @var FamilyRegistry */
    protected $familyRegistry;

    /** @var EntityManager */
    protected $entityManager;

    /**
     * @param \Sidus\EAVFilterBundle\Filter\EAVFilterHelper $helper
     */
    public function setEAVFilterHelper(EAVFilterHelper $helper)
    {
        $this->eavFilterHelper = $helper;
    }

    /**
     * @param \Sidus\EAVModelBundle\Registry\FamilyRegistry $familyRegistry
     */
    public function setFamilyRegistry(FamilyRegistry $familyRegistry)
    {
        $this->familyRegistry = $familyRegistry;
    }

    /**
     * @param \Doctrine\ORM\EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data): void
    {
        if (!$queryHandler instanceof AkeneoQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, AkeneoQueryHandlerInterface::class);
        }

        // pas de données recherché.
        if (empty($data)) {
            return;
        }

        $family = $this->familyRegistry->getFamily($filter->getOption('family'));
        $attribute = $family->getAttribute($filter->getOption('render_attribute'));

        if (\in_array($attribute->getType()->getCode(), ['embed', 'related'])) {
            $newData = [[]];
            foreach ($data as $val) {
                /** @var DataRepository $repo */
                $repo = $this->entityManager->getRepository(
                    $this
                        ->familyRegistry
                        ->getFamily($attribute->getOption('allowed_families')[0])
                        ->getDataClass()
                );

                $embed = $repo->find($val);
                $newData[] = $repo->getByAttribute(
                    $this
                        ->familyRegistry
                        ->getFamily($attribute->getOption('allowed_families')[0]),
                    $filter->getOption('search_embed_attribute'),
                    $embed->get($filter->getOption('search_embed_attribute')),
                    true
                );
            }

            $data = array_merge(...$newData);
        }

        /** @var DataRepository $repo */
        $repo = $this->entityManager->getRepository($family->getDataClass());
        $entities = $repo->getByAttribute($family, $attribute->getCode(), $data, true);
        $data = [];

        /** @var Data $entity */
        foreach($entities as $entity) {
            $data[] = $entity->get($filter->getOption('search_attribute'));
        }

        $queryHandler->getSearchBuilder()->mergeUniqueFilter(
            $filter->getOption('filter_reference'),
            $filter->getOption('operator', 'IN'),
            $data,
            $this->getContext($queryHandler, $filter)
        );
    }

    /**
     * {@inheritdoc}
     * @throws \UnexpectedValueException
     */
    public function getFormOptions(QueryHandlerInterface $queryHandler, FilterInterface $filter): array
    {
        $options = $filter->getOptions();

        if (!isset($options['family'])) {
            throw new \UnexpectedValueException(
                "Filters missing option family for filter ({$filter->getCode()})"
            );
        }

        if (!isset($options['filter_reference'])) {
            throw new \UnexpectedValueException(
                "Filters missing option filter_reference for filter ({$filter->getCode()})"
            );
        }

        if (!isset($options['render_attribute'])) {
            throw new \UnexpectedValueException(
                "Filters missing option render_attribute for filter ({$filter->getCode()})"
            );
        }

        if (!isset($options['search_attribute'])) {
            throw new \UnexpectedValueException(
                "Filters missing option search_attribute for filter ({$filter->getCode()})"
            );
        }

        $family = $this->familyRegistry->getFamily($options['family']);
        $attribute = $family->getAttribute($options['render_attribute']);

        if (\in_array($attribute->getType()->getCode(), ['embed', 'related'])) {
            if (!isset($options['search_embed_attribute'])) {
                throw new \UnexpectedValueException(
                    "Filters missing option search_embed_attribute for filter ({$filter->getCode()})"
                );
            }
        }

        $eavAttributes = $this->eavFilterHelper->getEAVAttributes(
            $family,
            [$options['render_attribute']]
        );

        if (\count($eavAttributes) > 1) {
            throw new \UnexpectedValueException(
                "EAV Pre Filter filters does not support multiple family ({$filter->getCode()})"
            );
        }

        return array_merge(
            [
                'label' => $filter->getCode(),
                'attribute' => reset($eavAttributes),
            ],
            $filter->getFormOptions()
        );
    }
}

