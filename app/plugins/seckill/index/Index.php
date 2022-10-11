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
namespace app\plugins\seckill\index;

use app\service\PluginsService;
use app\service\SeoService;
use app\plugins\seckill\index\Common;
use app\plugins\seckill\service\BaseService;

/**
 * 限时秒杀 - 前端独立页面入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * 首页入口
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-07
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 基础数据
        $base = PluginsService::PluginsData('seckill');
        MyViewAssign('plugins_seckill_data', isset($base['data']) ? $base['data'] : []);
        if(isset($base['data']['time_start']) && isset($base['data']['time_end']))
        {
            $time = BaseService::TimeCalculate($base['data']['time_start'], $base['data']['time_end']);
        } else {
            $time = ['msg'=>'未配置'];
        }
        MyViewAssign('plugins_seckill_countdown', $time);

        // 幻灯片
        $data_params = [
            'where'     => ['is_enable'=>1],
        ];
        $slider = BaseService::SliderList($data_params);
        MyViewAssign('plugins_seckill_slider', isset($slider['data']) ? $slider['data'] : []);

        // 商品数据
        $goods = BaseService::GoodsList();
        MyViewAssign('plugins_seckill_goods', $goods['data']);

        // 浏览器标题
        $seo_name = empty($base['data']['application_name']) ? '限时秒杀' : $base['data']['application_name'];
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($seo_name, 1));

        return MyView('../../../plugins/view/seckill/index/index/index');
    }
}
?>