<?php

class MarketinglotteryawardController extends BackendBaseController
{
    /**
     * 后台全局 user.default_filter 含 htmlspecialchars，
     * JSON textarea/hidden 字段容易被编码成 &quot; 导致 json_decode 失败。
     *
     * @throws \Exception
     */
    public function setextra($value)
    {
        return $this->normalizeJsonField($value, 'extra');
    }

    /**
     * @throws \Exception
     */
    public function setvip_random_product_ids($value)
    {
        return $this->normalizeJsonField($value, 'vip_random_product_ids');
    }

    /**
     * @throws \Exception
     */
    private function normalizeJsonField($value, string $field)
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            return $value;
        }
        $raw = trim(html_entity_decode($value, ENT_QUOTES));
        if ($raw === '' || strtolower($raw) === 'null') {
            return $field === 'extra' ? json_encode(new \stdClass(), JSON_UNESCAPED_UNICODE) : '';
        }
        $first = $raw[0] ?? '';
        if ($first !== '{' && $first !== '[') {
            if ($field === 'extra') {
                return json_encode(new \stdClass(), JSON_UNESCAPED_UNICODE);
            }
            throw new \Exception("{$field} 必须是 JSON（以 { 或 [ 开头）");
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($field === 'extra') {
                return json_encode(new \stdClass(), JSON_UNESCAPED_UNICODE);
            }
            throw new \Exception("{$field} JSON 格式错误：" . json_last_error_msg());
        }
        // 保持空对象语义：{} 不要被重新编码成 []
        if ($first === '{' && $decoded === []) {
            return json_encode(new \stdClass(), JSON_UNESCAPED_UNICODE);
        }
        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }

    public function saveAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->ajaxError('请求错误');
        }
        $post = $this->postArray();
        try {
            $this->validatePrizePost($post);
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }

        try {
            if ($model = $this->doSave($post)) {
                return $this->ajaxSuccessMsg('操作成功', 0, call_user_func($this->listAjaxIteration(), $model));
            }
            return $this->ajaxError('操作错误');
        } catch (\Throwable $e) {
            return $this->ajaxError($e->getMessage());
        }
    }

    private function validatePrizePost(array &$post): void
    {
        $type = (string) ($post['prize_type'] ?? '');
        test_assert($type !== '', '奖项类型不能为空');

        $coinsMode = (string) ($post['coins_mode'] ?? '');
        $coinsAmount = (int) ($post['coins_amount'] ?? 0);
        $vipDays = (int) ($post['vip_days'] ?? 0);
        $vipProductId = (int) ($post['vip_product_id'] ?? 0);
        $totalStock = (int) ($post['total_stock'] ?? -1);
        $issuedCount = isset($post['issued_count']) ? (int) $post['issued_count'] : 0;
        $perUserCap = (int) ($post['per_user_cap'] ?? 0);
        $winProbability = (int) ($post['win_probability'] ?? 0);

        test_assert($perUserCap >= 0, '每人最多中几次不能小于0');
        test_assert($winProbability >= 0 && $winProbability <= 100, '中奖概率必须在 0-100 之间');
        test_assert($totalStock === -1 || $totalStock >= 0, '发放份数必须为 -1 或 >=0');
        test_assert($issuedCount >= 0, '已发放不能小于0');
        if ($totalStock !== -1) {
            test_assert($issuedCount <= $totalStock, '已发放不能大于发放份数');
        }

        if ($type === MarketingLotteryPrizeModel::PRIZE_COINS) {
            // 前端会提交 coins_mode（fixed/random）。若 mode=random 但 coins_amount 未正确组装，
            // 这里强制按随机处理，避免误报“必须填写固定金币数量”。
            if ($coinsMode === 'random') {
                $coinsAmount = -1;
                $post['coins_amount'] = -1;
                $mn = (int) ($post['coins_random_min'] ?? 0);
                $mx = (int) ($post['coins_random_max'] ?? 0);
                test_assert($mn > 0 && $mx > 0, '金币随机范围必须填写最小/最大值');
                test_assert($mn <= $mx, '金币随机范围最小值不能大于最大值');
                $post['coins_random_min'] = $mn;
                $post['coins_random_max'] = $mx;

            }else{
                $coinsAmount = (int) ($post['coins_amount_fixed'] ?? ($post['coins_amount'] ?? 0));
                $post['coins_amount'] = $coinsAmount;
                test_assert($coinsAmount > 0, '金币奖项必须填写金币数量（固定值 >0 或 随机=-1）');
                $post['coins_random_min'] = 0;
                $post['coins_random_max'] = 0;
            }
            $post['vip_days'] = 0;
            $post['vip_product_id'] = null;
            $post['vip_random_product_ids'] = null;
        } elseif ($type === MarketingLotteryPrizeModel::PRIZE_VIP) {
            $hasProd = $vipProductId > 0;

            // 前端有显式 vip_mode（fixed/random），优先使用它判断模式；
            // 否则再按 vip_random_product_ids 是否为有效 JSON 来推断。
            $vipMode = (string) ($post['vip_mode'] ?? '');

            // vip_random_product_ids 前端固定模式会提交空字符串，这里不能把“空字符串”误判为随机模式
            $rawVipRandom = $post['vip_random_product_ids'] ?? null;
            if (is_string($rawVipRandom)) {
                $rawVipRandom = trim($rawVipRandom);
            }
            $randomProdIds = $this->normalizeIntIdList($rawVipRandom);
            $isRandom = false;
            if ($vipMode === 'random') {
                $isRandom = true;
            } elseif ($vipMode === 'fixed') {
                $isRandom = false;
            } elseif (is_array($rawVipRandom)) {
                $isRandom = true;
            } elseif (is_string($rawVipRandom)) {
                // 只有当确实提交了 JSON（以 [ 开头）时才视为随机模式；空串不算
                $isRandom = ($rawVipRandom !== '' && ($rawVipRandom[0] ?? '') === '[');
            }

            if ($isRandom) {
                // 随机模式：只要随机产品列表正确即可；若前端误提交了 vip_days / vip_product_id，直接忽略
                $post['vip_product_id'] = null;
                $post['vip_days'] = 0;
                $post['vip_random_product_ids'] = $randomProdIds; // 空数组=全部
            } else {
                // 固定模式：允许只选择 VIP 产品（你提的需求）；若同时填了天数，忽略天数以产品为准
                test_assert($hasProd || $vipDays > 0, 'VIP奖项固定模式必须选择VIP产品或填写VIP天数');
                if ($hasProd) {
                    $post['vip_days'] = 0;
                } else {
                    $post['vip_product_id'] = null;
                }
                $post['vip_random_product_ids'] = null;
            }

            // VIP 产品只能选择「上架」的 VIP 产品
            if ((int) ($post['vip_product_id'] ?? 0) > 0) {
                $ok = ProductModel::query()
                    ->where('id', (int) $post['vip_product_id'])
                    ->where('type', ProductModel::TYPE_VIP)
                    ->where('status', ProductModel::STAT_ON)
                    ->exists();
                test_assert($ok, 'VIP产品不存在或未上架，请重新选择');
            }
            if (!empty($randomProdIds)) {
                $cnt = (int) ProductModel::query()
                    ->whereIn('id', $randomProdIds)
                    ->where('type', ProductModel::TYPE_VIP)
                    ->where('status', ProductModel::STAT_ON)
                    ->count();
                test_assert($cnt === count($randomProdIds), '随机VIP产品包含不存在或未上架的产品，请重新选择');
            }

            $post['coins_amount'] = 0;
            $post['coins_random_min'] = 0;
            $post['coins_random_max'] = 0;
            if (!$hasProd) {
                $post['vip_product_id'] = null;
            }
        } elseif ($type === MarketingLotteryPrizeModel::PRIZE_PHYSICAL) {
            // 实物：用 name / prize_desc / 图片 + 库存字段即可
            $post['coins_amount'] = 0;
            $post['coins_random_min'] = 0;
            $post['coins_random_max'] = 0;
            $post['vip_days'] = 0;
            $post['vip_product_id'] = null;
            $post['vip_random_product_ids'] = null;
        } elseif ($type === MarketingLotteryPrizeModel::PRIZE_THANKS) {
            $post['coins_amount'] = 0;
            $post['coins_random_min'] = 0;
            $post['coins_random_max'] = 0;
            $post['vip_days'] = 0;
            $post['vip_product_id'] = null;
            $post['vip_random_product_ids'] = null;
            $post['is_win'] = MarketingLotteryPrizeModel::IS_WIN_NO;
        } else {
            // other：不强制，避免影响扩展（但仍建议不要混填）
            if ($vipProductId <= 0) {
                $post['vip_product_id'] = null;
            }
        }

        if ($type !== MarketingLotteryPrizeModel::PRIZE_THANKS) {
            $iw = isset($post['is_win']) ? (int) $post['is_win'] : MarketingLotteryPrizeModel::IS_WIN_YES;
            test_assert(
                $iw === MarketingLotteryPrizeModel::IS_WIN_NO || $iw === MarketingLotteryPrizeModel::IS_WIN_YES,
                '是否中奖取值无效'
            );
            $post['is_win'] = $iw;
        }

        // issued_count 不允许后台直接改（避免破坏库存一致性）
        unset($post['issued_count']);
    }

    private function parseExtraJson($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw)) {
            return [];
        }
        $arr = json_decode($raw, true);
        return is_array($arr) ? $arr : [];
    }

    /**
     * @param mixed $raw
     * @return int[]
     */
    private function normalizeIntIdList($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $v) {
            $id = (int) $v;
            if ($id > 0) {
                $out[$id] = 1;
            }
        }
        return array_map('intval', array_keys($out));
    }

    protected function listAjaxIteration()
    {
        return function (MarketingLotteryPrizeModel $item) {
            static $activityNameCache = [];
            $item->status_str = MarketingLotteryPrizeModel::STATUS_TIPS[$item->status] ?? '';
            $item->prize_type_str = MarketingLotteryPrizeModel::PRIZE_TYPE_TIPS[$item->prize_type] ?? $item->prize_type;
            $item->is_win_str = MarketingLotteryPrizeModel::IS_WIN_TIPS[(int) ($item->is_win ?? 1)] ?? '';
            // 已发放：以 redemption 成功记录为准（不从后台填写/不依赖 prize 表字段）
            $item->issued_count = (int) MarketingLotteryRedemptionModel::query()
                ->where('prize_id', $item->id)
                ->where('status', MarketingLotteryRedemptionModel::STATUS_SUCCESS)
                ->count();
            $ex = $item->extra;
            $item->setAttribute('extra', is_array($ex) ? json_encode($ex, JSON_UNESCAPED_UNICODE) : ($ex ?? ''));
            $item->prize_image_full = url_ads($item->prize_image ?? '');
            $item->prize_icon_full = url_ads($item->prize_icon ?? '');
            $item->vip_level = null;
            $item->vip_level_label = '';
            $item->vip_product_str = '';
            if ($item->vip_product_id) {
                $prod = ProductModel::query()->find($item->vip_product_id);
                if ($prod) {
                    $lvl = (int) $prod->vip_level;
                    $item->vip_level = $lvl;
                    $item->vip_level_label = MemberModel::USER_VIP_TYPE[$lvl] ?? (string) $lvl;
                    $item->vip_product_str = $prod->pname . ' #' . $prod->id;
                } else {
                    $item->vip_product_str = 'id:' . $item->vip_product_id;
                }
            }

            // VIP 发放类型展示：固定/随机
            $vipGrantType = '';
            $vipGrantTypeStr = '';
            $vipRandomIdsStr = '';
            if ((string) ($item->prize_type ?? '') === MarketingLotteryPrizeModel::PRIZE_VIP) {
                $ids = $item->vip_random_product_ids;
                if (is_array($ids)) {
                    $vipGrantType = 'random';
                    $vipGrantTypeStr = '随机';
                    if (empty($ids)) {
                        $vipRandomIdsStr = '全部';
                    } else {
                        $vipRandomIdsStr = implode(',', array_map('intval', $ids));
                    }
                } elseif ((int) ($item->vip_product_id ?? 0) > 0) {
                    $vipGrantType = 'fixed';
                    $vipGrantTypeStr = '固定';
                } elseif ((int) ($item->vip_days ?? 0) > 0) {
                    $vipGrantType = 'days';
                    $vipGrantTypeStr = '天数';
                } else {
                    $vipGrantType = 'fixed';
                    $vipGrantTypeStr = '固定';
                }
            }
            // 返回数组并强制带上编辑弹窗依赖的原始字段。
            // Layui 表格行缓存若缺少这些 key，保存成功后 obj.update 合并时会丢键，
            // 导致再次点“编辑”时表单无法回填（尤其是 0 / 空数组 / null 等值）。
            $row = $item->toArray();
            $row['id'] = (int) $item->getAttribute('id');
            $row['activity_id'] = (int) $item->getAttribute('activity_id');
            $aid = $row['activity_id'];
            if ($aid > 0) {
                if (!array_key_exists($aid, $activityNameCache)) {
                    $activityNameCache[$aid] = (string) (\MarketingLotteryActivityModel::query()->where('id', $aid)->value('name') ?? '');
                }
                $row['activity_name'] = $activityNameCache[$aid];
            } else {
                $row['activity_name'] = '';
            }
            $row['name'] = (string) ($item->getAttribute('name') ?? '');
            $row['prize_desc'] = (string) ($item->getAttribute('prize_desc') ?? '');
            $row['prize_image'] = (string) ($item->getAttribute('prize_image') ?? '');
            $row['prize_icon'] = (string) ($item->getAttribute('prize_icon') ?? '');
            $row['prize_type'] = (string) ($item->getAttribute('prize_type') ?? '');
            $row['is_win'] = (int) ($item->getAttribute('is_win') ?? 1);
            $row['status'] = (int) ($item->getAttribute('status') ?? 1);
            $row['sort_order'] = (int) ($item->getAttribute('sort_order') ?? 0);
            $row['win_probability'] = (int) ($item->win_probability ?? 0);
            $row['total_stock'] = (int) ($item->getAttribute('total_stock') ?? -1);
            $row['per_user_cap'] = (int) ($item->getAttribute('per_user_cap') ?? 0);
            $row['coins_amount'] = (int) ($item->getAttribute('coins_amount') ?? 0);
            $row['coins_random_min'] = (int) ($item->getAttribute('coins_random_min') ?? 0);
            $row['coins_random_max'] = (int) ($item->getAttribute('coins_random_max') ?? 0);
            $row['vip_days'] = (int) ($item->getAttribute('vip_days') ?? 0);
            $row['vip_product_id'] = $item->getAttribute('vip_product_id') !== null ? (int) $item->getAttribute('vip_product_id') : null;
            $row['vip_random_product_ids'] = $item->vip_random_product_ids; // cast=array|null
            $row['vip_grant_type'] = $vipGrantType;
            $row['vip_grant_type_str'] = $vipGrantTypeStr;
            $row['vip_random_product_ids_str'] = $vipRandomIdsStr;
            $row['extra'] = $item->extra; // cast=array|null；前端模板会 stringify
            return $row;
        };
    }

    public function indexAction()
    {
        $this->assign('vip_products_by_level_json', json_encode(self::vipProductsGroupedByLevel(), JSON_UNESCAPED_UNICODE));
        $this->display();
    }

    /**
     * 上架 VIP 产品按 vip_level 分组，供后台「先选类型再选产品」
     *
     * @return array<string, array<string, string>>
     */
    private static function vipProductsGroupedByLevel(): array
    {
        $byLevel = [];
        $rows = ProductModel::query()
            ->where('type', ProductModel::TYPE_VIP)
            ->where('status', ProductModel::STAT_ON)
            ->orderBy('vip_level')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'pname', 'vip_level']);
        foreach ($rows as $row) {
            $k = (string) (int) $row->vip_level;
            $byLevel[$k][(string) (int) $row->id] = $row->pname;
        }
        return $byLevel;
    }

    protected function getModelClass(): string
    {
        return MarketingLotteryPrizeModel::class;
    }

    protected function getPkName(): string
    {
        return 'id';
    }

    protected function getLogDesc(): string
    {
        return '营销抽奖奖项';
    }

    public function delAction()
    {
        return $this->ajaxError('抽奖奖项不允许删除');
    }

    public function delAllAction()
    {
        return $this->ajaxError('抽奖奖项不允许删除');
    }
}
