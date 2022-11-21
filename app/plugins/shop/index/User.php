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
namespace app\plugins\shop\index;

use app\service\SeoService;
use app\plugins\shop\index\Base;
use app\plugins\shop\service\StatisticalService;

/**
 * 多商户 - 卖家中心
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class User extends Base
{
    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-30
     * @desc    description
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        $this->IsLogin();
    }

    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 结算收益总数
        $profit_0 = StatisticalService::ProfitPriceTotal([0,1], $this->user['id']);
        $profit_1 = StatisticalService::ProfitPriceTotal(2, $this->user['id']);
        $profit_2 = StatisticalService::ProfitPriceTotal(3, $this->user['id']);
        $profit_3 = StatisticalService::ProfitPriceTotal(4, $this->user['id']);
        MyViewAssign('profit_0', $profit_0);
        MyViewAssign('profit_1', $profit_1);
        MyViewAssign('profit_2', $profit_2);
        MyViewAssign('profit_3', $profit_3);

        // 订单数量总数
        $order_0 = StatisticalService::OrderCountTotal([0,1], $this->user['id']);
        $order_1 = StatisticalService::OrderCountTotal([2,3], $this->user['id']);
        $order_2 = StatisticalService::OrderCountTotal(4, $this->user['id']);
        $order_3 = StatisticalService::OrderCountTotal([5,6], $this->user['id']);
        MyViewAssign('order_0', $order_0);
        MyViewAssign('order_1', $order_1);
        MyViewAssign('order_2', $order_2);
        MyViewAssign('order_3', $order_3);

        // 结算收益走势图
        $profit = StatisticalService::ProfitThirtyDayTotal(['user'=>$this->user]);
        MyViewAssign('profit_chart', $profit['data']);

        // 订单总量走势图
        $order = StatisticalService::OrderThirtyDayTotal(['user'=>$this->user]);
        MyViewAssign('order_chart', $order['data']);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('商家中心', 1));

        return MyView('../../../plugins/view/shop/index/user/index');
    }
}
?>