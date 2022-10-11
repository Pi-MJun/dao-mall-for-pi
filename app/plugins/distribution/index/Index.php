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
namespace app\plugins\distribution\index;

use app\service\SeoService;
use app\plugins\distribution\index\Common;
use app\plugins\distribution\service\BaseService;
use app\plugins\distribution\service\StatisticalService;
use app\plugins\distribution\service\ExtractionService;

/**
 * 分销 - 首页
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 推广用户总数
        $user_total = StatisticalService::UserExtensionTotal(['user'=>$this->user]);
        MyViewAssign('user_total', $user_total);

        // 收益汇总
        $user_profit_stay_price = StatisticalService::UserProfitPriceTotal(0, $this->user['id']);
        $user_profit_vaild_price = StatisticalService::UserProfitPriceTotal(1, $this->user['id']);
        $user_profit_already_price = StatisticalService::UserProfitPriceTotal(2, $this->user['id']);
        MyViewAssign('user_profit_stay_price', PriceNumberFormat($user_profit_stay_price));
        MyViewAssign('user_profit_vaild_price', PriceNumberFormat($user_profit_vaild_price));
        MyViewAssign('user_profit_already_price', PriceNumberFormat($user_profit_already_price));
        MyViewAssign('user_profit_total_price', PriceNumberFormat($user_profit_stay_price+$user_profit_vaild_price+$user_profit_already_price));

        // 图表-收益
        $profit = StatisticalService::UserProfitFifteenTodayTotal(['user'=>$this->user]);
        MyViewAssign('profit_chart', $profit['data']);

        // 图表-推广用户
        $user = StatisticalService::UserExtensionFifteenTodayTotal(['user'=>$this->user]);
        MyViewAssign('user_chart', $user['data']);

        // 获取取货点信息
        if(isset($this->plugins_config['is_enable_self_extraction']) && $this->plugins_config['is_enable_self_extraction'] == 1)
        {
            $extraction = ExtractionService::ExtractionData($this->user['id']);
            MyViewAssign('extraction_data', $extraction['data']);
        }

        // 上级用户
        if(isset($this->plugins_config['is_show_superior']) && $this->plugins_config['is_show_superior'] == 1)
        {
            $superior = BaseService::UserSuperiorData($this->user);
            MyViewAssign('superior', $superior);
        }

        // 阶梯返佣提示
        if(isset($this->plugins_config['is_show_profit_ladder_tips']) && $this->plugins_config['is_show_profit_ladder_tips'] == 1)
        {
            $profit_ladder = BaseService::AppointProfitLadderOrderLevel($this->plugins_config, $this->user['id']);
            MyViewAssign('profit_ladder', $profit_ladder);
        }

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('我的分销', 1));
        
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/distribution/index/index/index');
    }
}
?>