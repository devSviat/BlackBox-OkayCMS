<?php

namespace Okay\Modules\Sviat\BlackBox\Helpers;

use Okay\Core\Request;
use Okay\Core\Settings;

class BlackBoxApiHelper
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Query BlackBox v1 API by phone or name.
     * @param string|null $phone E.g. 067xxxxxxx
     * @param string|null $name  Last name or full name
     * @return array{success:bool,data:mixed,count_query?:int,error?:array}|null
     */
    public function lookup(?string $phone, ?string $name = null): ?array
    {
        $apiKey = (string) $this->settings->get('blackbox_api_key');
        if ($apiKey === '' || (empty($phone) && empty($name))) {
            return null;
        }

        $payload = [
            'id' => (string) (time()),
            'params' => array_filter([
                'phonenumber' => $this->normalizePhone($phone),
                'name'        => $name,
                'api_key'     => $apiKey,
            ]),
        ];

        $url = 'https://blackbox.net.ua/api?data=' . rawurlencode(json_encode($payload));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0 || $response === false) {
            return [
                'success' => false,
                'error' => ['code' => 105, 'message' => 'cURL error'],
            ];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'error' => ['code' => 105, 'message' => 'Invalid JSON'],
            ];
        }

        return $decoded;
    }

    /**
     * Add client to BlackBox via v2 API.
     * Required fields: api_key, id, method=add, type_track, phonenumber, ttn, last_name
     * @param array $data
     * @return array{success:bool,request_id?:string,count_query?:int,error?:array}
     */
    public function add(array $data): array
    {
        $apiKey = (string) $this->settings->get('blackbox_api_key');
        $payload = array_merge([
            'api_key' => $apiKey,
            'id' => (string) time(),
            'method' => 'add',
        ], $data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://blackbox.net.ua/api_v2');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0 || $response === false) {
            return [
                'success' => false,
                'error' => ['code' => 154, 'message' => 'cURL error'],
            ];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'error' => ['code' => 154, 'message' => 'Invalid JSON'],
            ];
        }

        return $decoded;
    }

    public function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }
        $digits = preg_replace('~\D+~', '', $phone);
        if ($digits === null) {
            return null;
        }
        if (strlen($digits) === 12 && substr($digits, 0, 2) === '38') {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 9) {
            $digits = '0' . $digits;
        }
        return $digits;
    }
}
