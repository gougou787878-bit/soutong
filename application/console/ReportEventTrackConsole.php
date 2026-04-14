<?php
namespace App\console;

use AdsModel;
use service\EventTrackerService;

class ReportEventTrackConsole extends AbstractConsole
{
    public $name = 'report-event-track';

    public $description = '事件点击上报';

    const BATCH_SIZE = 10;

    public function process($argc, $argv) {
        set_time_limit(0);

        $key  = EventTrackerService::EVENT_TRACKING_REPORT_KEY;
        $take = self::BATCH_SIZE; // 10

        // 推荐在外面定义好 Lua，或者放到常量/配置里
        $lua = <<<'LUA'
local key   = KEYS[1]
local batch = tonumber(ARGV[1])

local len = redis.call('LLEN', key)
if len < batch then
    return {}
end

local res = {}
for i = 1, batch do
    local v = redis.call('RPOP', key)
    if not v then
        break
    end
    table.insert(res, v)
end

return res
LUA;

        while (true) {
            // 调用 Lua 脚本，从 Redis 原子地取出一批
            $items = redis()->eval($lua, [$key, $take], 1);

            if (empty($items)) {
                echo "[" . date('Y-m-d H:i:s') . "] not enough data, sleep 2\n";
                sleep(2);
                continue;
            }

            // 这里 items 长度就是 <= 10，一般恰好 10 条
            //（只有极小概率在 RPOP 过程中队列被外部干预才会 <10）
            $list = [];
            foreach ($items as $item) {
                $list[] = json_decode($item, true);
            }

            EventTrackerService::postForm($list);
            echo "[" . date('Y-m-d H:i:s') . "] pushed batch with " . count($list) . " items\n";
            usleep(200000); // 0.2 秒
        }
    }
}