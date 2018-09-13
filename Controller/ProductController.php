<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Controller;

use Akeneo\Pim\ApiClient\Exception\UnprocessableEntityHttpException;
use CleverAge\EAVManager\AdminBundle\Controller\AbstractAdminController;
use CleverAge\EAVManager\AkeneoProductBundle\Pager\AkeneoPagerAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

/**
 * Bridge to akeneo products datagrid and edition
 */
class ProductController extends AbstractAdminController
{
    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     * @throws \Symfony\Component\PropertyAccess\Exception\ExceptionInterface
     */
    public function listAction(Request $request)
    {
        $dataGrid = $this->getDataGrid();

        $this->bindDataGridRequest($dataGrid, $request);

        return $this->renderAction(
            array_merge(
                $this->getViewParameters($request),
                ['datagrid' => $dataGrid]
            )
        );
    }

    /**
     * @param Request $request
     * @param int     $identifier
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function editAction(Request $request, $identifier)
    {
        $data = $this->getProduct($identifier);
        $data['id'] = $identifier; // Just a little hack to allow reuse of existing templates
        $form = $this->getForm($request, $data, $this->getFormOptions($request, $data));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->handleForm($request, $form);
        }

        return $this->renderAction($this->getViewParameters($request, $form, $data));
    }

    /**
     * @param Request $request
     * @param string  $endpoint
     *
     * @throws \UnexpectedValueException
     *
     * @return Response
     */
    public function apiSearchAction(Request $request, string $endpoint): Response
    {
        $this->get('eav_manager.akeneo.manager.context')->handleRequest($request);
        $client = $this->get('eav_manager.akeneo.client_getter')->get($endpoint);
        /** @var LoggerInterface $logger */
        $logger = $this->get('logger');
        $adapter = new AkeneoPagerAdapter($logger, $client, ['search' => [
            $request->query->get('search_by') => [[
                'operator' => $request->query->get('operator'),
                'value' => $request->query->get('term'),
                'locale'=> $request->query->get('locale'),
            ]],
        ]]);

        $pager = new Pagerfanta($adapter);

        return $this->renderSearchResponse(
            $pager,
            $endpoint,
            $request->query->get('replace_text')
        );
    }

    /**
     * @param Request $request
     * @param array   $product
     *
     * @return array
     */
    protected function getFormOptions(Request $request, array $product): array
    {
        return array_merge(
            $this->getDefaultContext(),
            ['validation_rules' => $this->getValidationRules($request, $product)]
        );
    }

    /**
     * @return array
     */
    private function getDefaultContext(): array
    {
        return [
            'scope' => $this->get('eav_manager.akeneo.manager.context')->getScope(),
            'locale' => $this->get('eav_manager.akeneo.manager.context')->getLocale(),
        ];
    }

    /**
     * @param Request $request
     * @param array   $product
     *
     * @return array
     */
    protected function getValidationRules(Request $request, array $product): array
    {
        $formOptions = $this->admin->getCurrentAction()->getFormOptions();

        if (isset($formOptions['validation_rules'])) {
            return $this->get('eav_manager.akeneo.form.validator.constraint_loader')
                ->load($formOptions['validation_rules']);
        }

        return [];
    }

    /**
     * @param \Pagerfanta\Pagerfanta $pager
     * @param string                 $endpoint
     * @param string|null            $replaceText
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function renderSearchResponse(
        Pagerfanta $pager,
        string $endpoint,
        string $replaceText = null
    ) : JsonResponse {
        $labelProvider = $this->get('eav_manager.akeneo.registry.label_provider')->getLabelProvider($endpoint);
        $contextManager = $this->get('eav_manager.akeneo.manager.context');
        $results = [];

        /** @var array $data */
        foreach ($pager as $data) {
            $result = [
                'id' => $data['identifier'],
                'text' => $labelProvider->getLabelFromData($data),
            ];

            if (null !== $replaceText && $contextManager->isPropertyExist($replaceText, $data)) {
                $result['text'] = $contextManager->getPropertyValue($replaceText, $data);
            }

            $results[] = $result;
        }

        $headers = [
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'private',
            'Expires' => 0,
        ];

        $response = [
            'results' => $results,
            'pagination' => [
                'more' => $pager->hasNextPage(),
            ],
        ];

        return new JsonResponse($response, 200, $headers);
    }

    /**
     * @param int $id
     **
     * @return array
     */
    protected function getProduct($id): array
    {
        $productApi = $this->get(AkeneoPimClientInterface::class)->getProductApi();

        return $productApi->get($id);
    }

    /**
     * @param Request            $request
     * @param Form|FormInterface $form
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @internal param array $data
     *
     * @throws \Exception
     * @throws \LogicException
     */
    protected function handleForm(Request $request, Form $form)
    {
        $newData = $form->getData();

        try {
            $this->saveEntity($newData);
        } catch (UnprocessableEntityHttpException $e) {
            $this->get('eav_manager.akeneo.validation_mapper')->mapErrors($form, $e->getResponseErrors());
            return $this->renderAction($this->getViewParameters($request, $form, $newData));
        }

        $parameters = $request->query->all();
        $parameters['success'] = 1;

        return $this->redirectToEntity($newData, 'edit', $parameters);
    }

    /**
     * @param mixed $data
     * @param bool  $withFlash
     *
     * @throws \LogicException
     */
    protected function saveEntity($data, bool $withFlash = true)
    {
        unset($data['id'], $data['_eav_data']);
        $this->get(AkeneoPimClientInterface::class)->getProductApi()->upsert($data['identifier'], $data);

        if (true === $withFlash) {
            $this->addFlash('success', $this->translate("admin.flash.{$this->admin->getCurrentAction()->getCode()}.success"));
        }
    }
}
