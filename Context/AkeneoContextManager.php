<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Context;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class AkeneoContextManager
{
    /** @var SessionInterface */
    protected $session;

    /** @var string */
    protected $locale;

    /** @var string */
    protected $scope;

    /**
     * AkeneoContextManager constructor.
     *
     * @param SessionInterface $session
     * @param string           $locale
     * @param string           $scope
     */
    public function __construct(SessionInterface $session, string $locale, string $scope)
    {
        $this->session = $session;
        $this->locale = $locale;
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        if ($this->session->isStarted()) {
            return $this->session->get('locale', $this->locale);
        }

        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale)
    {
        if ($this->session->isStarted()) {
            $this->session->set('locale', $locale);
        } else {
            $this->locale = $locale;
        }
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        if ($this->session->isStarted()) {
            return $this->session->get('scope', $this->scope);
        }

        return $this->scope;
    }

    /**
     * @param string $scope
     */
    public function setScope(string $scope)
    {
        if ($this->session->isStarted()) {
            $this->session->set('scope', $scope);
        } else {
            $this->scope = $scope;
        }
    }

    /**
     * @param string|null $locale
     * @param string|null $scope
     *
     * @return bool
     */
    public function isContextMatching(string $locale = null, string $scope = null): bool
    {
        return in_array($locale, [null, $this->getLocale()], true)
            && in_array($scope, [null, $this->getScope()], true);
    }

    /**
     * @param $data
     *
     * @return mixed|null
     * @throws \RuntimeException
     */
    public function getValue($data)
    {
        if (empty($data)) {
            return $data;
        }

        if ($this->isLocalizableValue($data)) {
            return $this->getLocalizableValue($data);
        }

        if (\is_array($data) && 1 === \count($data) && isset($data[0]) && array_key_exists('data', $data[0])) {
            return $data[0]['data'];
        }

        throw new \RuntimeException(sprintf('The value %s is not supported', json_encode($data)));
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function isLocalizableValue(array $data)
    {
        return count($data) && \count(array_filter(array_column($data, 'locale')));
    }

    /**
     * @param array $data
     *
     * @return mixed|null
     */
    public function getLocalizableValue(array $data)
    {
        if (empty($data)) {
            return null;
        }

        $key = array_search($this->locale, array_column($data, 'locale'), true);

        return false !== $key ? $data[$key]['data'] : null;
    }

    /**
     * @param array       $data
     * @param             $value
     * @param string|null $locale
     *
     * @return array
     * @throws \RuntimeException
     */
    public function transformValue($value, array $data = null, string $locale = null): array
    {
        if (null !== $locale) {
            return $this->transformLocalizableValue($value, $data, $locale);
        }

        if (empty($data)) {
            $data = [[
                'scope' => null,
                'locale' => null,
                'data' => $value
            ]];
        } elseif (1 === \count($data)) {
            $data[0]['data'] = $value;
        } else {
            throw new \RuntimeException(sprintf('This attribute is not supported for this function with this values %s', json_encode(\func_get_args())));
        }

        return $data;
    }

    /**
     * @param             $value
     * @param array|null  $data
     * @param string|null $locale
     *
     * @return array
     */
    public function transformLocalizableValue($value, array $data = null, string $locale = null): array
    {
        if (empty($data)) {
            $data = [];
        }

        $key = array_search($locale ?: $this->locale, array_column($data, 'locale'), true);

        if (false === $key) {
            $data[] = [
                'locale' => $locale ?: $this->locale,
                'scope' => null,
                'data' => $value,
            ];
        } else {
            $data[$key]['data'] = $value;
        }

        return $data;
    }

    /**
     * @param array       $data
     * @param string|null $locale
     *
     * @return null
     */
    public function getLabel(array $data, string $locale = null)
    {
        return $data['labels'][$locale ?: $this->locale] ?? null;
    }


    /**
     * @param Request $request
     */
    public function handleRequest(Request $request)
    {
        $locale = $request->get('locale');
        $scope = $request->get('scope');
        if ($locale) {
            $this->setLocale($locale);
        }
        if ($scope) {
            $this->setScope($scope);
        }
    }


    public function isPropertyExit(string $attributeCode, array $product): bool
    {
        return array_key_exists($attributeCode, $product['values'])
            && !empty($product['values'][$attributeCode]);
    }

    /**
     * @param string $attributeCode
     * @param array  $product
     *
     * @return null|string
     */
    public function getPropertyValue(string $attributeCode, array $product) :? string
    {
        if (!$this->isPropertyExit($attributeCode, $product)) {
            return null;
        }

        return $this->getValue($product['values'][$attributeCode]);
    }
}
