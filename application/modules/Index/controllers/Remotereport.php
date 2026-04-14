<?php

use service\RemoteReportService;

class RemotereportController extends \Yaf\Controller_Abstract
{
    private const KEY_SIZE = 32; // 32 bytes = AES-256
    private const GCM_IV_LEN = 12; // 12 bytes IV
    private const GCM_TAG_LEN = 16; // 16 bytes tag (128-bit)

    public function encrypt_data_center($data): string
    {
        $key = self::decode_key(config('click.report.secret'));

        $plaintext = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        test_assert($plaintext !== false, 'JSON encode failed');

        $iv = random_bytes(self::GCM_IV_LEN);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::GCM_TAG_LEN);

        test_assert($ciphertext !== false, 'Encryption failed');
        test_assert(strlen($tag) === self::GCM_TAG_LEN, 'Encryption failed');

        $out = $iv . $ciphertext . $tag;
        return self::b64_no_pad_encode($out);
    }

    public function decrypt_data_center(string $encrypted_base64): string
    {
        $key = self::decode_key(config('click.report.secret'));
        $data = self::b64_no_pad_decode($encrypted_base64);

        $minLen = self::GCM_IV_LEN + self::GCM_TAG_LEN + 1; // 至少还有1字节密文
        test_assert(strlen($data) >= $minLen, 'Invalid ciphertext');

        $iv = substr($data, 0, self::GCM_IV_LEN);
        $tag = substr($data, -self::GCM_TAG_LEN);
        $ct = substr($data, self::GCM_IV_LEN, -self::GCM_TAG_LEN);

        $plain = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        test_assert($plain !== false, 'Decryption failed');
        return $plain; // 返回 JSON 字符串
    }

    /* ----------------- helpers ----------------- */

    private static function decode_key(string $key_base64): string
    {
        $key = self::b64_no_pad_decode($key_base64);
        test_assert(strlen($key) === self::KEY_SIZE, 'Key must be 32 bytes for AES-256');
        return $key;
    }

    // Base64 去补位编码（等价 Java withoutPadding）
    private static function b64_no_pad_encode(string $bin): string
    {
        return rtrim(base64_encode($bin), '=');
    }

    // Base64 去补位解码（自动补齐到4的倍数）
    private static function b64_no_pad_decode(string $b64): string
    {
        $pad = (4 - (strlen($b64) % 4)) % 4;
        return base64_decode($b64 . str_repeat('=', $pad), true);
    }


    /**
     * @param {
     *      "app_id": "YC-010",
     *      "start_time": "2026-01-14 10:00:00",
     *      "end_time": "2026-01-14 10:59:59",
     *      "time_zone": "Asia/Shanghai",
     *      "currency": "CNY",
     * }
     * @return array|string
     */
    public function user_order_reportAction()
    {
        try {
            $txt = file_get_contents('php://input');
            $post_data = json_decode($txt, true);

            $app_id = $post_data['app_id'];
            $start_time = $post_data['start_time'] ?? '';
            $end_time = $post_data['end_time'] ?? '';
            $time_zone = $post_data['time_zone'] ?? '';
            $currency = $post_data['currency'] ?? '';

            $origin_app_id = config("click.report.app_id");
            test_assert($app_id == $origin_app_id, 'app_id 错误');

            $service = new RemoteReportService();
            $result = $service->get_user_order_aggregate($start_time, $end_time, $time_zone, $currency);
            $result = $this->encrypt_data_center($result);

            return $this->response($result);
        } catch (Throwable $e) {
            return $this->response(null, 1, "fail");
        }
    }


    /**
     * @param {
     *      "app_id": "YC-010",
     *      "start_time": "2026-01-14 10:00:00",
     *      "end_time": "2026-01-14 10:59:59",
     *      "time_zone": "Asia/Shanghai",
     *      "page_size": 2000,
     *      "page": 1
     * }
     * @return array|string
     */
    public function user_listAction()
    {
        try {
            test_assert($this->getRequest()->isPost(), "Method unsupported");

            $txt = file_get_contents('php://input');
            $post_data = json_decode($txt, true);

            $app_id = $post_data['app_id'];
            $start_time = $post_data['start_time'] ?? '';
            $end_time = $post_data['end_time'] ?? '';
            $time_zone = $post_data['time_zone'] ?? '';
            $page_size = (int)$post_data['page_size'] ?? 50;
            $page = (int)$post_data['page'] ?? 1;

            $origin_app_id = config("click.report.app_id");
            test_assert($app_id == $origin_app_id, 'app_id 错误');

            $service = new RemoteReportService();
            $result = $service->get_user_list($start_time, $end_time, $time_zone, $page_size, $page);
            $result = $this->encrypt_data_center($result);

            return $this->response($result);
        } catch (Throwable $e) {
            return $this->response(null, 1, "fail");
        }
    }

    /**
     * @param {
     *      "app_id": "YC-010",
     *      "start_time": "2026-01-14 10:00:00",
     *      "end_time": "2026-01-14 10:59:59",
     *      "time_zone": "Asia/Shanghai",
     *      "page_size": 2000,
     *      "page": 1
     * }
     * @return array|string
     */
    public function order_listAction()
    {
        try {
            test_assert($this->getRequest()->isPost(), "Method unsupported");

            $txt = file_get_contents('php://input');
            $post_data = json_decode($txt, true);

            $app_id = $post_data['app_id'];
            $start_time = $post_data['start_time'] ?? '';
            $end_time = $post_data['end_time'] ?? '';
            $time_zone = $post_data['time_zone'] ?? '';
            $page_size = (int)$post_data['page_size'] ?? 50;
            $page = (int)$post_data['page'] ?? 1;

            $origin_app_id = config("click.report.app_id");
            test_assert($app_id == $origin_app_id, 'app_id 错误');

            $service = new RemoteReportService();
            $result = $service->get_order_list($start_time, $end_time, $time_zone, $page_size, $page);
            $result = $this->encrypt_data_center($result);

            return $this->response($result);
        } catch (Throwable $e) {
            return $this->response(null, 1, "fail");
        }
    }

    private function response($data, int $status = 0, string $msg = "ok")
    {
        $returnData = [
            'message' => $msg,
            'data'    => $data,
            'code'  => $status
        ];
        @header('Content-Type: application/json');
        return $this->getResponse()->setBody(json_encode($returnData, JSON_UNESCAPED_UNICODE));
    }
}