<?php

class MarketinglotteryactivityController extends BackendBaseController
{
    /** 列表筛选「无」触发情景（DB 存空字符串，与 getSearchWhereParam 跳过空值兼容） */
    public const LIST_FILTER_TRIGGER_NONE = '__none__';

    protected function listAjaxWhere(): array
    {
        $where = parent::listAjaxWhere();
        $ts = $this->getRequest()->getQuery('where')['trigger_scenario'] ?? null;
        if ($ts === self::LIST_FILTER_TRIGGER_NONE) {
            $where[] = ['trigger_scenario', '=', ''];
        }
        return $where;
    }

    protected function listAjaxIteration()
    {
        return function (MarketingLotteryActivityModel $item) {
            static $creatorCache = [];
            $item->status_str = MarketingLotteryActivityModel::STATUS_TIPS[$item->status] ?? '';
            $item->type_str = MarketingLotteryActivityModel::ACTIVITY_TYPE_TIPS[$item->activity_type] ?? $item->activity_type;
            $item->trigger_str = MarketingLotteryActivityModel::TRIGGER_SCENARIO_TIPS[$item->trigger_scenario ?? ''] ?? ($item->trigger_scenario ?? '');
            $cfg = $item->config;
            $item->setAttribute('config', is_array($cfg) ? json_encode($cfg, JSON_UNESCAPED_UNICODE) : ($cfg ?? ''));
            $exCfg = $item->extra_config;
            $item->setAttribute('extra_config', is_array($exCfg) ? json_encode($exCfg, JSON_UNESCAPED_UNICODE) : ($exCfg ?? ''));
            $item->activity_image_full = url_ads($item->activity_image ?? '');
            $item->icon_full = url_ads($item->icon ?? '');

            // 返回数组并强制带上编辑弹窗依赖的原始字段。Layui 表格行缓存若缺这些 key，
            // 保存成功后 obj.update 只会合并「已存在的键」，会导致再点编辑时 d.status / d.trigger_scenario 为空。
            $row = $item->toArray();
            $row['status'] = (int) $item->getAttribute('status');
            $row['trigger_scenario'] = (string) ($item->getAttribute('trigger_scenario') ?? '');
            $row['activity_type'] = (string) ($item->getAttribute('activity_type') ?? MarketingLotteryActivityModel::TYPE_LOTTERY);

            $creatorUid = (int) ($item->getAttribute('creator_uid') ?? 0);
            if ($creatorUid > 0) {
                if (!array_key_exists($creatorUid, $creatorCache)) {
                    $creatorCache[$creatorUid] = (string) (ManagersModel::query()->where('uid', $creatorUid)->value('username') ?? '');
                }
                $row['creator_name'] = $creatorCache[$creatorUid];
            } else {
                $row['creator_name'] = '';
            }

            return $row;
        };
    }

    public function indexAction()
    {
        $this->display();
    }

    protected function getModelClass(): string
    {
        return MarketingLotteryActivityModel::class;
    }

    protected function getPkName(): string
    {
        return 'id';
    }

    protected function getLogDesc(): string
    {
        return '营销抽奖活动';
    }

    protected function createBeforeCallback($model)
    {
        $u = $this->getUser();
        if ($u && !empty($u->uid)) {
            $model->creator_uid = (int)$u->uid;
        }
    }

    public function delAction()
    {
        return $this->ajaxError('抽奖活动不允许删除');
    }

    public function delAllAction()
    {
        return $this->ajaxError('抽奖活动不允许删除');
    }

    /**
     * Layui textarea 可能会把双引号等编码成 HTML entities，导致 json_decode 失败，
     * 进而在 Model 的 setConfigAttribute 中被静默保存为 {}（cast 后表现为 []）。
     *
     * 这里做统一还原 + 严格 JSON 校验，避免“保存成功但配置未生效”的误判。
     *
     * @throws \Exception
     */
    public function setconfig($value)
    {
        return $this->normalizeJsonTextarea($value, 'config');
    }

    /**
     * @throws \Exception
     */
    public function setextra_config($value)
    {
        return $this->normalizeJsonTextarea($value, 'extra_config');
    }

    /**
     * @throws \Exception
     */
    private function normalizeJsonTextarea($value, string $field)
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            return $value;
        }

        $raw = trim(html_entity_decode($value, ENT_QUOTES));
        if ($raw === '') {
            return '';
        }

        // 允许直接传 {} / [] / {"a":1} / [{"a":1}]
        $first = $raw[0] ?? '';
        if ($first !== '{' && $first !== '[') {
            throw new \Exception("{$field} 必须是 JSON（以 { 或 [ 开头）");
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("{$field} JSON 格式错误：" . json_last_error_msg());
        }

        // 保持空对象语义：{} 不要被重新编码成 []
        if ($first === '{' && $decoded === []) {
            return json_encode(new \stdClass(), JSON_UNESCAPED_UNICODE);
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }
}
