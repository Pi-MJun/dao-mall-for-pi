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
namespace app\plugins\membershiplevelvip\admin;

use app\service\PluginsService;
use app\plugins\membershiplevelvip\service\BaseService;
use app\plugins\membershiplevelvip\service\CrontabService;
use app\plugins\membershiplevelvip\service\StatisticalService;
use app\plugins\membershiplevelvip\service\BusinessService;

/**
 * 会员等级增强版插件 - 管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 定时脚本自动运行一次
        CrontabService::OrderClose();
        CrontabService::ProfitCreate();
        CrontabService::ProfitSettlement();

        // 首页信息
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {
            // 推广用户总数
            $user_total = StatisticalService::UserExtensionTotal();
            MyViewAssign('user_total', $user_total);

            // 收益汇总
            $user_profit_stay_price = StatisticalService::UserProfitPriceTotal(0);
            $user_profit_already_price = StatisticalService::UserProfitPriceTotal(1);
            MyViewAssign('user_profit_stay_price', PriceNumberFormat($user_profit_stay_price));
            MyViewAssign('user_profit_already_price', PriceNumberFormat($user_profit_already_price));
            MyViewAssign('user_profit_total_price', PriceNumberFormat($user_profit_stay_price+$user_profit_already_price));

            // 图表-收益
            $profit = StatisticalService::UserProfitFifteenTodayTotal();
            MyViewAssign('profit_chart', $profit['data']);

            // 图表-推广用户
            $user = StatisticalService::UserExtensionFifteenTodayTotal();
            MyViewAssign('user_chart', $user['data']);

            // 等级规则
            MyViewAssign('members_level_rules_list', BaseService::$members_level_rules_list);
            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/membershiplevelvip/admin/admin/index');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {
            // 等级规则
            MyViewAssign('members_level_rules_list', BaseService::$members_level_rules_list);
            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/membershiplevelvip/admin/admin/saveinfo');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Save($params = [])
    {        
        // 保存数据
        return BaseService::BaseConfigSave($params);
    }

    /**
     * 用户列表-用户等级处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-05
     * @desc    description
     * @param   array           $params [description]
     */
    public function UserLevelView($params = [])
    {
        if(!empty($params['module_data']) && !empty($params['module_data']['id']))
        {
            $user_level = BusinessService::UserVip($params['module_data']['id']);
            MyViewAssign('plugins_user_level', $user_level);
            return MyView('../../../plugins/view/membershiplevelvip/admin/public/user_level_view');
        }
    }

    /**
     * 二维码清空
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-12T20:34:52+0800
     * @param    [array]          $params [输入参数]
     */
    public function QrcodeDelete($params = [])
    {
        return BaseService::QrcodeDelete($params);
    }
}
?>