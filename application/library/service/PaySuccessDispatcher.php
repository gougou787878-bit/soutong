<?php

namespace service;

class PaySuccessDispatcher
{
    public const REDIS_QUEUE = 'jobs:work:queue:paysuccess';

    public static function enqueue(array $payload): void
    {
        try {
            if (!function_exists('jobs2')) {
                return;
            }
            if (!isset($payload['trace_id']) || !is_string($payload['trace_id']) || $payload['trace_id'] === '') {
                $payload['trace_id'] = 'pay_success:' . date('YmdHis') . ':' . substr(bin2hex(random_bytes(8)), 0, 16);
            }
            $payload['enqueued_at'] = time();
            jobs2([self::class, 'consume'], [$payload], self::REDIS_QUEUE);
        } catch (\Throwable $e) {
            errLog('PaySuccessDispatcher::enqueue: ' . $e->getMessage());
        }
    }

    public static function enqueueForPaySuccess(
        string $source,
        array $notify,
        array $order,
        ?array $product,
        \MemberModel $member
    ): void {
        try {
            self::enqueue(self::buildPayload($source, $notify, $order, $product, $member));
        } catch (\Throwable $e) {
            errLog('PaySuccessDispatcher::enqueueForPaySuccess: ' . $e->getMessage());
        }
    }

    public static function buildPayload(
        string $source,
        array $notify,
        array $order,
        ?array $product,
        \MemberModel $member
    ): array {
        $productType = 0;
        if ($product !== null && isset($product['type'])) {
            $productType = (int) $product['type'];
        } elseif (isset($order['order_type'])) {
            $productType = (int) $order['order_type'];
        }

        return [
            'source'         => $source,
            'order_id'       => (string) ($notify['order_id'] ?? $order['order_id'] ?? ''),
            'third_id'       => (string) ($notify['third_id'] ?? ''),
            'pay_money_yuan' => $notify['pay_money'] ?? '',
            'pay_time'       => $notify['pay_time'] ?? '',
            'uuid'           => (string) ($order['uuid'] ?? $member->uuid ?? ''),
            'uid'            => (int) $member->uid,
            'aff'            => (int) $member->aff,
            'product_id'     => $order['product_id'] ?? '',
            'product_type'   => $productType,
            'order_type'     => (int) ($order['order_type'] ?? 0),
            'oauth_type'     => (string) ($member->oauth_type ?? ''),
            'build_id'       => (string) ($order['build_id'] ?? $member->build_id ?? ''),
            'channel'        => (string) ($order['channel'] ?? ''),
        ];
    }

    public static function consume(array $payload): void
    {
        try {
            $payload['trigger'] = 'pay_success';
            $payload['trigger_from'] = $payload['trigger_from'] ?? ($payload['source'] ?? 'pay_success');
            (new MarketingLotteryTriggerService())->trigger($payload);
        } catch (\Throwable $e) {
            errLog('PaySuccessDispatcher::consume: ' . $e->getMessage() . ' payload=' . json_encode($payload, JSON_UNESCAPED_UNICODE));
        }
    }
}
