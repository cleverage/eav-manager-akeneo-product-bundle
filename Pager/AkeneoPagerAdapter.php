<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Pager;

use Akeneo\Pim\ApiClient\Api\Operation\ListableResourceInterface;
use Akeneo\Pim\ApiClient\Exception\UnprocessableEntityHttpException;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Pagerfanta\Adapter\AdapterInterface;
use Psr\Log\LoggerInterface;

/**
 * Fanta Pager adapter for Akeneo API
 */
class AkeneoPagerAdapter implements AdapterInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ListableResourceInterface */
    protected $listable;

    /** @var array */
    protected $queryParameters;

    /** @var PageInterface[][] */
    protected $pages;

    /** @var int */
    protected $count;

    /**
     * @param LoggerInterface $logger
     * @param ListableResourceInterface $listable
     * @param array $queryParameters
     */
    public function __construct(LoggerInterface $logger, ListableResourceInterface $listable, array $queryParameters)
    {
        $this->logger = $logger;
        $this->listable = $listable;
        $this->queryParameters = $queryParameters;
    }

    /**
     * Returns the number of results.
     *
     * @return integer The number of results.
     */
    public function getNbResults()
    {
        if (null === $this->count) {
            $this->initPage(0, 1);
        }

        return $this->count;
    }

    /**
     * Returns an slice of the results.
     *
     * @param integer $offset The offset.
     * @param integer $length The length.
     *
     * @return array|\Traversable The slice.
     */
    public function getSlice($offset, $length)
    {
        if (!isset($this->pages[$offset][$length])) {
            $this->pages[$offset][$length] = $this->initPage($offset, $length);
        }

        if ($this->pages[$offset][$length] instanceof PageInterface) {
            return $this->pages[$offset][$length]->getItems();
        }

        return [];
    }

    /**
     * @param int $offset
     * @param int $length
     *
     * @return PageInterface|null
     */
    protected function initPage(int $offset, int $length): ?PageInterface
    {
        $queryParameters = $this->queryParameters;
        $queryParameters['page'] = floor($offset / $length) + 1;
        $page = null;

        try {
            $page = $this->listable->listPerPage($length, null === $this->count, $queryParameters);

            if ($page->getCount()) {
                $this->count = $page->getCount();
            }
        } catch (UnprocessableEntityHttpException $e) {
            $this->logger->error(
                sprintf('%s : %s', UnprocessableEntityHttpException::class, $e->getMessage()),
                $this->queryParameters
            );
            $this->count = 0;
        }

        return $page;
    }
}
