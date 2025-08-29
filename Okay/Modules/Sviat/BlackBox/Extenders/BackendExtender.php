<?php

namespace Okay\Modules\Sviat\BlackBox\Extenders;

use Okay\Core\Design;
use Okay\Core\Request;
use Okay\Core\EntityFactory;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Modules\Sviat\BlackBox\Helpers\BlackBoxApiHelper;
use Okay\Modules\Sviat\BlackBox\Entities\BlackBoxCacheEntity;

class BackendExtender implements ExtensionInterface
{
    private Request $request;
    private EntityFactory $entityFactory;
    private Design $design;
    private BlackBoxApiHelper $apiHelper;

    public function __construct(
        Request $request,
        EntityFactory $entityFactory,
        Design $design,
        BlackBoxApiHelper $apiHelper
    ) {
        $this->request       = $request;
        $this->entityFactory = $entityFactory;
        $this->design        = $design;
        $this->apiHelper     = $apiHelper;
    }

    /**
     * Отримати інформацію про клієнта з кешу або з API, якщо кеш відсутній.
     * Повертає масив з ключами: blackbox_result, clientInfo
     */
    public function getBlackBoxOrderInfo($order)
    {
        if (empty($order) || (empty($order->phone) && empty($order->last_name))) {
            return ['blackbox_result' => null, 'clientInfo' => null];
        }

        $cacheEntity = $this->entityFactory->get(BlackBoxCacheEntity::class);
        $cache = $cacheEntity->findOne([
            'phone'     => $order->phone,
            'last_name' => $order->last_name,
        ]);

        // Якщо кеш є – готуємо результат з нього
        if ($cache) {
            $payloadDecoded = json_decode($cache->payload, true);
            if ($cache->status === 'error') {
                $error = $payloadDecoded['error'] ?? ['code' => 0, 'message' => 'cached error'];
                $result = [
                    'success'   => false,
                    'data'      => null,
                    'error'     => $error,
                    'cached_at' => $cache->updated_at,
                ];
                if (isset($payloadDecoded['request_id'])) {
                    $result['request_id'] = $payloadDecoded['request_id'];
                }
            } else {
                $result = [
                    'success'   => $cache->status !== 'error',
                    'data'      => $cache->status === 'found' ? $payloadDecoded : null,
                    'error'     => null,
                    'cached_at' => $cache->updated_at,
                ];
            }
        } else {
            // Якщо кеша немає – йдемо в API, зберігаємо і одразу повертаємо
            $this->updateBlackBoxOrderInfo($order);
            return $this->getBlackBoxOrderInfo($order);
        }

        // Формуємо clientInfo для зручного відображення
        $clientInfo = null;
        if (is_array($result) && !empty($result['data'])) {
            $firstEntry = reset($result['data']);
            if (is_array($firstEntry)) {
                $clientInfo = [
                    'phone' => $firstEntry['phone_formatted'] ?? ($firstEntry['phone'] ?? null),
                ];
                if (!empty($firstEntry['fios']) && is_array($firstEntry['fios'])) {
                    $clientInfo['fios'] = array_values(array_filter($firstEntry['fios'], 'strlen'));
                }
                if (!empty($firstEntry['tracks']) && is_array($firstEntry['tracks'])) {
                    $clientInfo['tracks'] = [];
                    foreach ($firstEntry['tracks'] as $t) {
                        if (is_array($t)) {
                            $track = [
                                'type'      => $t['type'] ?? null,
                                'date'      => $t['date'] ?? null,
                                'city'      => $t['city'] ?? null,
                                'warehouse' => $t['warehouse'] ?? null,
                                'cost'      => isset($t['cost']) ? (string)$t['cost'] : null,
                                'comment'   => $t['comment'] ?? null,
                            ];
                            $track = array_filter($track, fn($v) => $v !== null && $v !== '');
                            if (!empty($track)) {
                                $clientInfo['tracks'][] = $track;
                            }
                        }
                    }
                }
                if (isset($clientInfo['track'])) {
                    foreach ($clientInfo['track'] as $k => $v) {
                        if ($v === null || $v === '') {
                            unset($clientInfo['track'][$k]);
                        }
                    }
                    if (empty($clientInfo['track'])) {
                        unset($clientInfo['track']);
                    }
                }
                if (isset($clientInfo['fios']) && empty($clientInfo['fios'])) {
                    unset($clientInfo['fios']);
                }
                if (empty(array_filter($clientInfo, fn($v) => !empty($v)))) {
                    $clientInfo = null;
                }
            }
        }

        $this->design->assign('clientInfo', $clientInfo);
        $this->design->assign('blackbox_result', $result);
    }

    /**
     * Оновлює інформацію про замовлення в кеші BlackBox.
     * Якщо кеш існує, оновлює його, інакше створює новий запис.
     */
    public function updateBlackBoxOrderInfo($order)
    {
        $cacheEntity = $this->entityFactory->get(BlackBoxCacheEntity::class);
        $cache = $cacheEntity->findOne([
            'phone'     => $order->phone,
            'last_name' => $order->last_name,
        ]);

        $fetched = $this->apiHelper->lookup($order->phone ?? null, $order->last_name ?? null);
        $now = date('Y-m-d H:i:s');
        if ($fetched === null) {
            return;
        }

        $status = 'not_found';
        if (!empty($fetched['error'])) {
            $status = 'error';
        } elseif (!empty($fetched['data'])) {
            $status = 'found';
        }

        $dataToSave = json_encode($fetched['data'] ?? $fetched);

        if ($cache) {
            $cacheEntity->update($cache->id, [
                'status'     => $status,
                'payload'    => $dataToSave,
                'updated_at' => $now,
            ]);
        } else {
            $cacheEntity->add([
                'phone'      => $order->phone,
                'last_name'  => $order->last_name,
                'status'     => $status,
                'payload'    => $dataToSave,
                'updated_at' => $now,
            ]);
        }

        return ExtenderFacade::execute(__METHOD__, $order, func_get_args());
    }
}
