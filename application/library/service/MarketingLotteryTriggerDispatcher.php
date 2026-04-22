<?php

namespace service;

class MarketingLotteryTriggerDispatcher
{
    public const REDIS_QUEUE = 'jobs:work:queue:marketing-lottery-trigger';

    public static function enqueue(array $payload): void
    {
        try {
            if (!function_exists('jobs2')) {
                return;
            }
            if (!isset($payload['trace_id']) || !is_string($payload['trace_id']) || $payload['trace_id'] === '') {
                $payload['trace_id'] = 'marketing_lottery:' . date('YmdHis') . ':' . substr(bin2hex(random_bytes(8)), 0, 16);
            }
            $payload['enqueued_at'] = time();
            jobs2([self::class, 'consume'], [$payload], self::REDIS_QUEUE);
        } catch (\Throwable $e) {
            errLog('MarketingLotteryTriggerDispatcher::enqueue: ' . $e->getMessage());
        }
    }

    public static function trigger(string $trigger, array $payload, bool $async = true): void
    {
        try {
            $payload['trigger'] = $trigger;
            if ($async) {
                self::enqueue($payload);
                return;
            }
            self::consume($payload);
        } catch (\Throwable $e) {
            errLog('MarketingLotteryTriggerDispatcher::trigger: ' . $e->getMessage());
        }
    }

    public static function triggerPaySuccess(
        string $source,
        array $notify,
        array $order,
        ?array $product,
        \MemberModel $member,
        bool $async = true
    ): void {
        try {
            self::trigger('pay_success', self::buildPayPayload($source, $notify, $order, $product, $member), $async);
        } catch (\Throwable $e) {
            errLog('MarketingLotteryTriggerDispatcher::triggerPaySuccess: ' . $e->getMessage());
        }
    }

    public static function buildPayPayload(
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
            'trigger_from' => $source,
            'order_id' => (string) ($notify['order_id'] ?? $order['order_id'] ?? ''),
            'third_id' => (string) ($notify['third_id'] ?? ''),
            'pay_money_yuan' => $notify['pay_money'] ?? '',
            'pay_time' => $notify['pay_time'] ?? '',
            'uuid' => (string) ($order['uuid'] ?? $member->uuid ?? ''),
            'uid' => (int) $member->uid,
            'aff' => (int) $member->aff,
            'product_id' => $order['product_id'] ?? '',
            'product_type' => $productType,
            'order_type' => (int) ($order['order_type'] ?? 0),
            'oauth_type' => (string) ($member->oauth_type ?? ''),
            'build_id' => (string) ($order['build_id'] ?? $member->build_id ?? ''),
            'channel' => (string) ($order['channel'] ?? ''),
        ];
    }

    public static function consume(array $payload): void
    {
        try {
            (new MarketingLotteryTriggerService())->trigger($payload);
        } catch (\Throwable $e) {
            errLog('MarketingLotteryTriggerDispatcher::consume: ' . $e->getMessage() . ' payload=' . json_encode($payload, JSON_UNESCAPED_UNICODE));
        }
    }
}
