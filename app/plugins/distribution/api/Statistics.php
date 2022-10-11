<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace app\plugins\distribution\api;

use app\plugins\distribution\api\Common;
use app\plugins\distribution\service\StatisticalService;

/**
 * 分销 - 数据统计
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Statistics extends Common
{
    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     */
    public function __construct()
    {
        parent::__construct();

        // 是否登录
        $this->IsLogin();
    }

    /**
     * 列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-19
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 推广用户总数
        $user_total = StatisticalService::UserExtensionTotal(['user'=>$this->user]);

        // 收益汇总
        $user_profit_stay_price = StatisticalService::UserProfitPriceTotal(0, $this->user['id']);
        $user_profit_vaild_price = StatisticalService::UserProfitPriceTotal(1, $this->user['id']);
        $user_profit_already_price = StatisticalService::UserProfitPriceTotal(2, $this->user['id']);

        // 图表-收益
        $profit = StatisticalService::UserProfitFifteenTodayTotal(['user'=>$this->user]);

        // 图表-推广用户
        $user = StatisticalService::UserExtensionFifteenTodayTotal(['user'=>$this->user]);

        // 返回数据
        $result = [
            'user_total'                => $user_total,
            'user_profit_stay_price'    => PriceNumberFormat($user_profit_stay_price),
            'user_profit_vaild_price'   => PriceNumberFormat($user_profit_vaild_price),
            'user_profit_already_price' => PriceNumberFormat($user_profit_already_price),
            'user_profit_total_price'   => PriceNumberFormat($user_profit_stay_price+$user_profit_vaild_price+$user_profit_already_price),
            'profit_chart'              => $profit['data'],
            'user_chart'                => $user['data'],
        ];
        return DataReturn('success', 0, $result);
    }
}
?>