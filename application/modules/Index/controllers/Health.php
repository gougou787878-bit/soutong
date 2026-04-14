<?php
use Yaf\Controller_Abstract;
use Yaf\Registry;

class HealthController extends Controller_Abstract
{
    private $group_id = '';
    private $secret_key = '';
    private $mysql_ip = '';
    private $mysql_port = '';
    private $redis_ip = '';
    private $redis_port = '';
    private $es_ip = '';
    private $es_port = '';

    public function init()
    {
       $this->group_id = config('monitor.group_id') ?? '';
       $this->secret_key = config('monitor.secret_key') ?? '';
       $config = Registry::get('database.conf')->toArray();
       $this->mysql_ip = $config['database']['write']['host'] ?? '';
       $this->mysql_port = $config['database']['port'] ?? '';
       $redis_host = $config['redis']['host'];
       $this->redis_ip = $redis_host[0] ?? '';
       $this->redis_port = $config['redis']['port'] ?? '';
       $this->es_ip = $config['es']['host'] ?? '';
       $this->es_port = $config['es']['port'] ?? '';
    }

    /**
     * POST /api/health/ping
     */
    public function pingAction()
    {
        try {
            if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                return $this->json(405, 'Method Not Allowed');
            }

            $body = $this->readJsonBody();

            $groupId   = (string)($body['groupId'] ?? '');
            $timestamp = (int)($body['timestamp'] ?? 0);
            $sign      = (string)($body['sign'] ?? '');

            if (empty($groupId) || $timestamp <= 0 || empty($sign)) {
                return $this->json(10000, '参数错误：groupId/timestamp/sign 必填');
            }

            if ($groupId != $this->group_id){
                return $this->json(10000, '参数错误：groupId参数错误');
            }

            // 5分钟窗口
            $now = time();
            if (abs($now - $timestamp) > 300) {
                return $this->json(10000, '参数错误：timestamp 超出 5 分钟有效期');
            }

            $expected = $this->makeSign($this->group_id, $timestamp, $this->secret_key);
            if (!$this->hashEquals(strtolower($sign), strtolower($expected))) {
                return $this->json(10000, '签名错误');
            }

            // 采集健康信息
            $data = $this->collectHealthData($now);

            return $this->json(200, 'success', $data);

        } catch (Throwable $e) {
            // 生产建议写入日志
            // error_log($e->getMessage());
            return $this->json(400, '服务器内部错误'. $e->getMessage());
        }
    }

    /* ================= 工具方法 ================= */

    private function json(int $code, string $msg, $data = null)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['code' => $code, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
        return false; // Yaf action return false = 不继续渲染
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') return [];
        $arr = json_decode($raw, true);
        return is_array($arr) ? $arr : [];
    }

    private function makeSign(string $groupId, int $timestamp, string $secretKey): string
    {
        return hash_hmac('sha256', $groupId . ':' . $timestamp, $secretKey);
    }

    private function hashEquals(string $a, string $b): bool
    {
        return function_exists('hash_equals') ? hash_equals($a, $b) : ($a === $b);
    }

    private function collectHealthData(int $now): array
    {
        $cpu    = $this->getCpuInfo();
        $memory = $this->getMemInfoGb();
        $disk   = $this->getDiskInfoGb('/');

        // 中间件探测（按需改 host/port）
        $mysql   = $this->tcpCheck($this->mysql_ip, $this->mysql_port);
//        $mongodb = $this->tcpCheck('127.0.0.1', 27017);
        $redis   = $this->tcpCheck($this->redis_ip, $this->redis_port);

        $data =  [
            'serverName' => config('click.report.app_id') . "|" . config("system.cn_name"),
            'serverIp'   => $this->getLocalIp(),
            'cpu'        => $cpu,
            'memory'     => $memory,
            'disk'       => $disk,
            'mysql'      => $mysql,
//            'mongodb'    => $mongodb,
            'redis'      => $redis,
            //'elasticsearch' => $es,
            // 'kafka'         => ['status' => 'disabled', 'msg' => 'disabled'],
            'timestamp'  => $now,
        ];

        //ES
        if ($this->es_ip){
            $data['elasticsearch'] = $this->tcpCheck($this->es_ip, $this->es_port);
        }

        return $data;
    }

    private function getCpuInfo(): array
    {
        $cpuinfo = @file_get_contents('/proc/cpuinfo') ?: '';
        preg_match_all('/^processor\s*:/m', $cpuinfo, $m);
        $cores = max(1, (int)count($m[0]));

        $load = sys_getloadavg();
        $used = is_array($load) && isset($load[0]) ? (float)$load[0] : 0.0;

        return ['cores' => $cores, 'used' => round($used, 2)];
    }

    private function getMemInfoGb(): array
    {
        $meminfo = @file_get_contents('/proc/meminfo');
        if (!$meminfo) return ['total' => 0, 'used' => 0];

        $kv = [];
        foreach (explode("\n", $meminfo) as $line) {
            if (preg_match('/^(\w+):\s+(\d+)\s+kB$/', trim($line), $m)) {
                $kv[$m[1]] = (int)$m[2];
            }
        }

        $totalKb = $kv['MemTotal'] ?? 0;
        $availKb = $kv['MemAvailable'] ?? 0;
        $usedKb  = $totalKb > 0 ? max(0, $totalKb - $availKb) : 0;

        return [
            'total' => (int)round($totalKb / 1024 / 1024),
            'used'  => (int)round($usedKb  / 1024 / 1024),
        ];
    }

    private function getDiskInfoGb(string $path = '/'): array
    {
        $total = @disk_total_space($path);
        $free  = @disk_free_space($path);
        if ($total === false || $free === false || $total <= 0) return ['total' => 0, 'used' => 0];

        $used = $total - $free;
        return [
            'total' => (int)round($total / 1024 / 1024 / 1024),
            'used'  => (int)round($used  / 1024 / 1024 / 1024),
        ];
    }

    private function tcpCheck(string $host, int $port, float $timeout = 0.6): array
    {
        $errno = 0; $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp) {
            fclose($fp);
            return ['status' => 'ok', 'msg' => 'success'];
        }
        return ['status' => 'err', 'msg' => $errstr !== '' ? $errstr : ("connect failed: $errno")];
    }

    private function getLocalIp(): string
    {
        if (!empty($_SERVER['SERVER_ADDR'])) return (string)$_SERVER['SERVER_ADDR'];
        $host = gethostname();
        $ip = $host ? gethostbyname($host) : '127.0.0.1';
        return $ip ?: '127.0.0.1';
    }
}
