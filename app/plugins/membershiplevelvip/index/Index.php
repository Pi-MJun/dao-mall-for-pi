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

use app\service\PluginsService;
use app\service\UserService;
use app\service\SeoService;
use app\plugins\membershiplevelvip\index\Common;
use app\plugins\membershiplevelvip\service\BaseService;
use app\plugins\membershiplevelvip\service\LevelService;
use app\plugins\membershiplevelvip\service\IntroduceService;
use app\plugins\membershiplevelvip\service\LevelBuyService;
use app\plugins\membershiplevelvip\service\PayService;

/**
 * 会员等级增强版插件 - 首页
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
     * @datetime 2019-08-11T15:36:19+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 等级列表
        $ret = LevelService::DataList(['where'=>['is_enable'=>1, 'is_supported_pay_buy'=>1]]);
        MyViewAssign('level_list', $ret['data']);

        // 介绍列表
        $ret = IntroduceService::DataList();
        MyViewAssign('introduce_list', $ret['data']);

        // 基础配置
        $plugins_base = BaseService::BaseConfig();
        MyViewAssign('plugins_base', $plugins_base['data']);

        // 支付方式
        MyViewAssign('payment_list', BaseService::HomeBuyPaymentList());

        // 浏览器名称
        if(!empty($plugins_base['data']['application_name']))
        {
            MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($plugins_base['data']['application_name'], 1));
        }

        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/membershiplevelvip/index/index/index');
    }
}
?>