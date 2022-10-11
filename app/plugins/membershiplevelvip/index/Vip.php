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
namespace app\plugins\membershiplevelvip\index;

use app\service\SeoService;
use app\plugins\membershiplevelvip\index\Common;
use app\plugins\membershiplevelvip\service\BaseService;
use app\plugins\membershiplevelvip\service\BusinessService;
use app\plugins\membershiplevelvip\service\LevelService;
use app\plugins\membershiplevelvip\service\StatisticalService;

/**
 * 会员等级增强版插件 - 等级信息管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Vip extends Common
{
    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-07-17
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        $this->IsLogin();

        // 公共用户数据
        $this->CommonUserView();
    }

    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-19
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 会员信息
        $this->user_vip = BusinessService::UserVip($this->user['id']);
        MyViewAssign('user_vip', $this->user_vip);

        // 等级列表
        $ret = LevelService::DataList(['where'=>['is_enable'=>1, 'is_supported_pay_buy'=>1]]);
        MyViewAssign('level_list', $ret['data']);

        // 是否开启返佣
        if(isset($this->plugins_base['is_commission']) && $this->plugins_base['is_commission'] == 1)
        {
            // 推广用户总数
            $user_total = StatisticalService::UserExtensionTotal(['user'=>$this->user]);
            MyViewAssign('user_total', $user_total);

            // 收益汇总
            $user_profit_stay_price = StatisticalService::UserProfitPriceTotal(0, $this->user['id']);
            $user_profit_already_price = StatisticalService::UserProfitPriceTotal(1, $this->user['id']);
            MyViewAssign('user_profit_stay_price', PriceNumberFormat($user_profit_stay_price));
            MyViewAssign('user_profit_already_price', PriceNumberFormat($user_profit_already_price));
            MyViewAssign('user_profit_total_price', PriceNumberFormat($user_profit_stay_price+$user_profit_already_price));

            // 图表-收益
            $profit = StatisticalService::UserProfitFifteenTodayTotal(['user'=>$this->user]);
            MyViewAssign('profit_chart', $profit['data']);

            // 图表-推广用户
            $user = StatisticalService::UserExtensionFifteenTodayTotal(['user'=>$this->user]);
            MyViewAssign('user_chart', $user['data']);
        }

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('我的会员', 1));

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/membershiplevelvip/index/vip/index');
    }
}
?>