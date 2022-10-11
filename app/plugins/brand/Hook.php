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
namespace app\plugins\brand;

use app\plugins\brand\service\BaseService;

/**
 * 品牌 - 钩子入口
 * @author   Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2019-04-11
 * @desc    description
 */
class Hook
{
    // 配置信息
    private $plugins_config;

    // 当前模块/控制器/方法
    private $module_name;
    private $controller_name;
    private $action_name;

    // 导航名称
    private $nav_title = '品牌';

    /**
     * 应用响应入口
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function handle($params = [])
    {
        // 钩子名称
        if(!empty($params['hook_name']))
        {
            // 当前模块/控制器/方法
            $this->module_name = RequestModule();
            $this->controller_name = RequestController();
            $this->action_name = RequestAction();

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            // 快捷导航名称
            $this->nav_title = empty($this->plugins_config['application_name']) ? '积分商城' : $this->plugins_config['application_name'];

            // 快捷导航入口
            $is_user_quick = isset($this->plugins_config['is_user_quick']) ? intval($this->plugins_config['is_user_quick']) : 0;

            // 商品详情页面是否显示品牌
            $is_goods_detail = isset($this->plugins_config['is_goods_detail']) ? intval($this->plugins_config['is_goods_detail']) : 0;

            // 走钩子
            $ret = '';
            $brand_style = ['indexgoodsindex'];
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    if($is_goods_detail == 1 && in_array($this->module_name.$this->controller_name.$this->action_name, $brand_style))
                    {
                        $ret = 'static/plugins/css/brand/index/style.css';
                    }
                    break;

                // web端快捷导航操作按钮
                case 'plugins_service_quick_navigation_pc' :
                    if($is_user_quick == 1)
                    {
                        $this->WebQuickNavigationHandle($params);
                    }
                    break;

                // 小程序/APP端快捷导航操作按钮
                case 'plugins_service_quick_navigation_h5' :
                case 'plugins_service_quick_navigation_weixin' :
                case 'plugins_service_quick_navigation_alipay' :
                case 'plugins_service_quick_navigation_baidu' :
                case 'plugins_service_quick_navigation_qq' :
                case 'plugins_service_quick_navigation_toutiao' :
                    if($is_user_quick == 1)
                    {
                        $this->MiniQuickNavigationHandle($params);
                    }
                    break;

                // 在前面添加导航
                case 'plugins_service_navigation_header_handle' :
                    $ret = $this->NavigationHeaderHandle($params);
                    break;

                // 商品详情面板
                case 'plugins_view_goods_detail_panel_bottom' :
                    if($is_goods_detail == 1 && in_array($this->module_name.$this->controller_name.$this->action_name, $brand_style))
                    {
                        $ret = $this->GoodsDetailPanelHandle($params);
                    }
                    break;
            }
            return $ret;
        }
    }

    /**
     * 商品详情面板
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsDetailPanelHandle($params = [])
    {
        if(!empty($params['goods']) && !empty($params['goods']['brand_id']))
        {
            $brand_info = BaseService::BrandInfo($params['goods']['brand_id']);
            if(!empty($brand_info))
            {
                MyViewAssign('brand_info', $brand_info);
                return MyView('../../../plugins/view/brand/index/public/goods_detail');
            }
        }
    }

    /**
     * 中间大导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function NavigationHeaderHandle($params = [])
    {
        if(is_array($params['header']))
        {
            // 获取应用数据
            if(!empty($this->plugins_config['application_name']))
            {
                $nav = [
                    'id'                    => 0,
                    'pid'                   => 0,
                    'name'                  => $this->plugins_config['application_name'],
                    'url'                   => PluginsHomeUrl('brand', 'index', 'index'),
                    'data_type'             => 'custom',
                    'is_show'               => 1,
                    'is_new_window_open'    => 0,
                    'items'                 => [],
                ];
                array_unshift($params['header'], $nav);
            }
        }
    }

    /**
     * web端快捷导航操作导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function WebQuickNavigationHandle($params = [])
    {
        // 加入导航尾部
        $nav = [
            'event_type'    => 0,
            'event_value'   => PluginsHomeUrl('brand', 'index', 'index'),
            'name'          => $this->nav_title,
            'images_url'    => MyConfig('shopxo.attachment_host').'/static/plugins/images/brand/quick-nav-icon.png',
            'bg_color'      => '#468afe',
        ];
        array_push($params['data'], $nav);
    }

    /**
     * 小程序端快捷导航操作导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function MiniQuickNavigationHandle($params = [])
    {
        // 加入导航尾部
        $nav = [
            'event_type'    => 1,
            'event_value'   => '/pages/plugins/brand/index/index',
            'name'          => $this->nav_title,
            'images_url'    => MyConfig('shopxo.attachment_host').'/static/plugins/images/brand/quick-nav-icon.png',
            'bg_color'      => '#468afe',
        ];
        array_push($params['data'], $nav);
    }
}
?>