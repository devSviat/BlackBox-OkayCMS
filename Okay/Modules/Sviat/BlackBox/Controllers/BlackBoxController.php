<?php

namespace Okay\Modules\Sviat\BlackBox\Controllers;

use Okay\Core\EntityFactory;
use Psr\Log\LoggerInterface;
use Okay\Entities\OrdersEntity;
use Okay\Controllers\AbstractController;
use Okay\Modules\Sviat\BlackBox\Extenders\BackendExtender;
use Okay\Modules\Sviat\BlackBox\Helpers\BlackBoxApiHelper;
use Okay\Modules\Sviat\BlackBox\Entities\BlackBoxCacheEntity;

class BlackBoxController extends AbstractController
{
    public function updateClientInfo(
        EntityFactory $entityFactory,
        BackendExtender $backendExtender
    ) {
        $orderId = $this->request->get('order_id', 'integer');

        /** @var OrdersEntity $ordersEntity */
        $ordersEntity = $entityFactory->get(OrdersEntity::class);

        $result = [
            'blackbox_info' => [
                'success'   => false,
                'data'      => null,
                'error'     => null,
                'cached_at' => null,
            ]
        ];

        if (!$orderId || !$order = $ordersEntity->get($orderId)) {
            $result['blackbox_info']['error'] = ['code' => 404, 'message' => 'order not found'];
            $this->response->setContent(json_encode($result), RESPONSE_JSON);
            return;
        }

        $backendExtender->updateBlackBoxOrderInfo($order);

        /** @var BlackBoxCacheEntity $cacheEntity */
        $cacheEntity = $entityFactory->get(BlackBoxCacheEntity::class);
        $cache = $cacheEntity->findOne([
            'phone'     => $order->phone,
            'last_name' => $order->last_name,
        ]);

        if ($cache) {
            $payloadDecoded = json_decode($cache->payload, true);

            $result['blackbox_info']['data'] = $cache->status === 'found' ? $payloadDecoded : null;
            $result['blackbox_info']['error'] = $cache->status === 'error' ? ($payloadDecoded['error'] ?? ['code' => 0, 'message' => 'cached error']) : null;
            $result['blackbox_info']['cached_at'] = $cache->updated_at ?? null;
            $result['blackbox_info']['success'] = true;

            if (!empty($payloadDecoded['request_id'])) {
                $result['blackbox_info']['request_id'] = $payloadDecoded['request_id'];
            }
        }

        $this->response->setContent(json_encode($result), RESPONSE_JSON);
    }


    public function addClientInfo(
        EntityFactory $entityFactory,
        BlackBoxApiHelper $apiHelper,
        BackendExtender $backendExtender,
        LoggerInterface $logger
    ) {
        $type_track = $this->request->get('blackbox-type_track', 'integer');
        $ttn = $this->request->get('blackbox-ttn', 'string');
        $phonenumber = $this->request->get('blackbox-phonenumber', 'string');
        $cost = $this->request->get('blackbox-cost', 'float');
        $last_name = $this->request->get('blackbox-last_name', 'string');
        $first_name = $this->request->get('blackbox-first_name', 'string');
        $city = $this->request->get('blackbox-city', 'string');
        $date = $this->request->get('blackbox-date', 'string');
        $comment = $this->request->get('blackbox-comment', 'string');
        $orderId = $this->request->get('order_id', 'integer');

        /** @var OrdersEntity $ordersEntity */
        $ordersEntity = $entityFactory->get(OrdersEntity::class);

        if (!$orderId || !$order = $ordersEntity->get($orderId)) {
            $this->response->setContent(json_encode(['error' => 'order not found']), RESPONSE_JSON);
            $logger->warning('order not found for BlackBox addClientInfo', ['order_id' => $orderId]);
            return;
        }

        // Використовуємо дані з замовлення, якщо не передані
        $phonenumber = $phonenumber ?: $order->phone;
        $last_name = $last_name ?: $order->last_name;
        if (!$first_name && $order->name) {
            $nameParts = explode(' ', trim($order->name), 2);
            $first_name = $nameParts[0] ?? '';
        }
        if (!$city && $order->address) {
            $city = explode(',', $order->address)[0] ?? '';
        }
        $date = $date ?: date('d.m.Y');

        // Перевірка обов'язкових полів
        if (!$type_track || !in_array($type_track, [1, 4])) {
            $this->response->setContent(json_encode(['error' => 'invalid type_track']), RESPONSE_JSON);
            return;
        }
        if (!$phonenumber || !$ttn || !$last_name || !$cost || $cost <= 0) {
            $this->response->setContent(json_encode(['error' => 'missing required fields']), RESPONSE_JSON);
            return;
        }

        // Нормалізація телефону
        $normalizedPhone = $apiHelper->normalizePhone($phonenumber);
        if (!$normalizedPhone) {
            $this->response->setContent(json_encode(['error' => 'invalid phonenumber']), RESPONSE_JSON);
            return;
        }

        // Підготовка даних для API v2
        $data = [
            'type_track' => $type_track,
            'phonenumber' => $normalizedPhone,
            'ttn' => $ttn,
            'last_name' => $last_name,
            'first_name' => $first_name,
            'comment' => $comment,
            'city' => $city,
            'date' => $date,
            'cost' => $cost,
        ];

        // $logger->info('Sending data to BlackBox API', ['data' => $data]);

        $apiResponse = $apiHelper->add($data);

        if (!$apiResponse['success']) {
            $errorCode = $apiResponse['error']['code'] ?? 0;
            $errorMessage = $apiResponse['error']['message'] ?? 'API error';
            $logger->error('BlackBox API error', ['response' => $apiResponse]);

            $this->response->setContent(json_encode([
                'success' => false,
                'error'   => [
                    'code'    => $errorCode,
                    'message' => $errorMessage,
                ],
            ]), RESPONSE_JSON);
            return;
        }


        $backendExtender->updateBlackBoxOrderInfo($order);

        $this->response->setContent(json_encode(['success' => true]), RESPONSE_JSON);
    }
}
