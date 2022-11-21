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
namespace app\plugins\shop\admin;

use app\plugins\shop\admin\Common;
use app\plugins\shop\service\BaseService;
use app\plugins\shop\service\CrontabService;
use app\plugins\shop\service\StatisticalService;
use app\plugins\shop\service\ShopCategoryService;

/**
 * 多商户 - 管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin extends Common
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
        // 结算脚本自动运行一次
        CrontabService::ProfitSettlement();

        // 结算收益总数
        $profit_0 = StatisticalService::ProfitPriceTotal([0,1]);
        $profit_1 = StatisticalService::ProfitPriceTotal(2);
        $profit_2 = StatisticalService::ProfitPriceTotal(3);
        $profit_3 = StatisticalService::ProfitPriceTotal(4);
        MyViewAssign('profit_0', $profit_0);
        MyViewAssign('profit_1', $profit_1);
        MyViewAssign('profit_2', $profit_2);
        MyViewAssign('profit_3', $profit_3);

        // 结算收益走势图
        $profit = StatisticalService::ProfitThirtyDayTotal();
        MyViewAssign('profit_chart', $profit['data']);

        // 首页信息
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {
            // 店铺分类
            $category = ShopCategoryService::ShopCategoryAll(['field'=>'id,name']);
            MyViewAssign('shop_category', $category['data']);

            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/shop/admin/admin/index');
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
            // 店铺分类
            $category = ShopCategoryService::ShopCategoryAll(['field'=>'id,name']);
            MyViewAssign('shop_category', $category['data']);

            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/shop/admin/admin/saveinfo');
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
        return BaseService::BaseConfigSave($params);
    }
}
?>