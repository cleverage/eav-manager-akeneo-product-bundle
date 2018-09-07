<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Controller;

use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

class ProductModelController extends ProductController
{
    /**
     * @param Request $request
     * @param int     $code
     *
     * @return Response
     * @internal param int $identifier
     *
     * @throws \Exception
     */
    public function editAction(Request $request, $code)
    {
        $data = $this->getProduct($code);
        $data['id'] = $code; // Just a little hack to allow reuse of existing templates
        $data['family'] = $request->get('family');
        $form = $this->getForm($request, $data, $this->getFormOptions($request, $data));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->handleForm($request, $form);
        }

        return $this->renderAction($this->getViewParameters($request, $form, $data));
    }

    /**
     * @param $identifier
     *
     * @return array
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     */
    protected function getProduct($identifier): array
    {
        /** @var ProductModelApiInterface $productModelApi */
        $productModelApi = $this->get(AkeneoPimClientInterface::class)->getProductModelApi();

        return $productModelApi->get($identifier);
    }

    /**
     * @param mixed $data
     * @param bool  $wthFlash
     *
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    protected function saveEntity($data, bool $wthFlash = true)
    {
        $code = $data['code'];
        unset($data['id'], $data['family'], $data['code'], $data['_eav_data']);

        $this->get(AkeneoPimClientInterface::class)->getProductModelApi()->upsert($code, $data);

        $action = $this->admin->getCurrentAction();

        if (true === $wthFlash) {
            $this->addFlash('success', $this->translate("admin.flash.{$action->getCode()}.success"));

        }
    }
}
