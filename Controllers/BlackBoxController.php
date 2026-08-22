<?php

namespace Okay\Modules\Sviat\BlackBox\Controllers;

use Okay\Core\EntityFactory;
use Okay\Core\Managers;
use Psr\Log\LoggerInterface;
use Okay\Entities\ManagersEntity;
use Okay\Entities\OrdersEntity;
use Okay\Controllers\AbstractController;
use Okay\Modules\Sviat\BlackBox\Extenders\BackendExtender;
use Okay\Modules\Sviat\BlackBox\Helpers\BlackBoxApiHelper;
use Okay\Modules\Sviat\BlackBox\Entities\BlackBoxCacheEntity;
use Okay\Modules\Sviat\BlackBox\Security\AdminIdentity;
use Okay\Modules\Sviat\BlackBox\Security\RequestOrigin;

class BlackBoxController extends AbstractController
{
    /**
     * Кнопки модуля живуть на сторінці замовлення (BackendOrdersHelper::findOrder),
     * тож і право те саме, що в менеджера на замовлення. `settings` тут
     * замкнуло б кнопку від тих, хто з замовленнями й працює.
     */
    private const PERMISSION = 'orders';

    public function updateClientInfo(
        EntityFactory $entityFactory,
        BackendExtender $backendExtender,
        AdminIdentity $adminIdentity,
        Managers $managers,
        ManagersEntity $managersEntity
    ) {
        if (!$this->isAllowed($adminIdentity, $managers, $managersEntity)) {
            return;
        }

        $orderId = $this->request->post('order_id', 'integer');

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

        // Оновлюємо кеш BlackBox
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


    // Додати інформацію про клієнта через BlackBox API
    public function addClientInfo(
        EntityFactory $entityFactory,
        BlackBoxApiHelper $apiHelper,
        BackendExtender $backendExtender,
        LoggerInterface $logger,
        AdminIdentity $adminIdentity,
        Managers $managers,
        ManagersEntity $managersEntity
    ) {
        if (!$this->isAllowed($adminIdentity, $managers, $managersEntity)) {
            return;
        }

        $type_track = $this->request->post('blackbox-type_track', 'integer');
        $ttn = $this->request->post('blackbox-ttn', 'string');
        $phonenumber = $this->request->post('blackbox-phonenumber', 'string');
        $cost = $this->request->post('blackbox-cost', 'float');
        $last_name = $this->request->post('blackbox-last_name', 'string');
        $first_name = $this->request->post('blackbox-first_name', 'string');
        $city = $this->request->post('blackbox-city', 'string');
        $date = $this->request->post('blackbox-date', 'string');
        $comment = $this->request->post('blackbox-comment', 'string');
        $orderId = $this->request->post('order_id', 'integer');

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
        // Адреси в замовленні може не бути взагалі — самовивіз, відділення
        // без вулиці. Без ?? це попередження PHP просто в тіло відповіді.
        if (!$city && !empty($order->address)) {
            $city = explode(',', (string) $order->address)[0] ?? '';
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


        // Оновлюємо кеш після успішного додавання
        $backendExtender->updateBlackBoxOrderInfo($order);

        $this->response->setContent(json_encode(['success' => true]), RESPONSE_JSON);
    }

    /**
     * Маршрути цих методів оголошені з to_front, тобто запит іде через вітрину
     * повз авторизацію backend/index.php. Без перевірки будь-хто діставав
     * профіль клієнта з BlackBox за телефоном і прізвищем із замовлення, палив
     * платну квоту API і міг нашими ж обліковими даними внести живого клієнта
     * в чорний список.
     *
     * Перевіряються дві різні речі. *Хто* — це AdminIdentity: рушії зберігають
     * бекендову сесію по-різному. *Звідки* — RequestOrigin разом із вимогою
     * POST: кука адмінки має SameSite=Lax, тож міжсайтовий POST її не несе, а
     * top-level GET-навігація несе.
     */
    private function isAllowed(
        AdminIdentity $adminIdentity,
        Managers $managers,
        ManagersEntity $managersEntity
    ): bool {
        if (!$this->request->method('post')) {
            $this->response->setStatusCode(405);
            $this->response->setContent(json_encode(['error' => 'Method Not Allowed']), RESPONSE_JSON);
            return false;
        }

        if (!RequestOrigin::isFromThisSite()) {
            $this->response->setStatusCode(403);
            $this->response->setContent(json_encode(['error' => 'Forbidden']), RESPONSE_JSON);
            return false;
        }

        $adminLogin = $adminIdentity->login();
        if (empty($adminLogin)) {
            $this->response->setStatusCode(401);
            $this->response->setContent(json_encode(['error' => 'Unauthorized']), RESPONSE_JSON);
            return false;
        }

        $manager = $managersEntity->get($adminLogin);
        if (empty($manager) || !$managers->access(self::PERMISSION, $manager)) {
            $this->response->setStatusCode(403);
            $this->response->setContent(json_encode(['error' => 'Access denied']), RESPONSE_JSON);
            return false;
        }

        return true;
    }
}
