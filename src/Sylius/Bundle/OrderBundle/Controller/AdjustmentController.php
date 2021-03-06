<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\OrderBundle\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Order\Model\AdjustmentInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Order adjustment controller.
 *
 * @author Joseph Bielawski <stloyd@gmail.com>
 */
class AdjustmentController extends ResourceController
{
    public function lockAction(Request $request, $id)
    {
        /** @var $order OrderInterface */
        if (!$order = $this->get('sylius.repository.order')->findOneById($id)) {
            throw new NotFoundHttpException('Requested order does not exist.');
        }

        if (!$request->query->has('adjustment')) {
            $adjustments = $order->getAdjustments();
        } else {
            $id = $request->query->get('adjustment');
            $adjustments = $order->getAdjustments()->filter(function (AdjustmentInterface $adjustment) use ($id) {
                return $id == $adjustment->getId();
            });
        }

        /** @var $adjustments AdjustmentInterface[] */
        foreach ($adjustments as $adjustment) {
            if ($request->get('lock', false)) {
                $adjustment->lock();
            } else {
                $adjustment->unlock();
            }
        }

        $this->domainManager->update($order);

        return $this->redirectHandler->redirectTo($order);
    }

    /**
     * {@inheritdoc}
     */
    public function createNew()
    {
        if (null === $orderId = $this->getRequest()->get('orderId')) {
            return new JsonResponse(array('error' => 'Missing order id.'), 400);
        }

        if (!$order = $this->getOrderRepository()->find($orderId)) {
            $this->createNotFoundException('Requested order does not exist.');
        }

        $adjustment = parent::createNew();
        $adjustment->setAdjustable($order);

        return $adjustment;
    }

    /**
     * Get order repository.
     *
     * @return ObjectRepository
     */
    protected function getOrderRepository()
    {
        return $this->get('sylius.repository.order');
    }
}
