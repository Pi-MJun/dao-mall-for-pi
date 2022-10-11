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
namespace app\plugins\points;

use app\plugins\points\service\BaseService;
use app\plugins\points\service\PointsService;
use app\plugins\points\service\RewardUserIntegralService;

/**
 * 积分商城 - 钩子入口
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
    private $nav_title = '积分商城';

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

            // 走钩子
            $ret = '';
            $points_style = ['indexbuyindex'];
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    if(in_array($this->module_name.$this->controller_name.$this->action_name, $points_style))
                    {
                        $ret = 'static/plugins/css/points/index/style.css';
                    }
                    break;

                // 公共js
                case 'plugins_js' :
                    if(in_array($this->module_name.$this->controller_name.$this->action_name, $points_style))
                    {
                        $ret = 'static/plugins/js/points/index/style.js';
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

                // 商品列表
                case 'plugins_service_goods_handle_end' :
                    if($this->module_name != 'admin' && $this->controller_name == 'goods')
                    {
                        $this->GoodsDetailPanelIconHandle($params);
                    }
                    break;

                // 购买提交订单页面隐藏域html
                case 'plugins_view_buy_form_inside' :
                    $ret = $this->BuyFormInsideContent($params);
                    break;

                // 订单确认页面基础信息顶部
                case 'plugins_view_buy_base_confirm_top' :
                    $ret = $this->BuyBaseConfirmTopContent($params);
                    break;

                // 积分兑换/抵扣计算
                case 'plugins_service_buy_group_goods_handle' :
                    $this->DeductionExchangeCalculate($params);
                    break;

                // 订单添加成功处理
                case 'plugins_service_buy_order_insert_end' :
                    $ret = $this->OrderInsertSuccessHandle($params);
                    break;

                // 订单状态改变处理
                case 'plugins_service_order_status_change_history_success_handle' :
                    $ret = $this->OrderStatusChangeHandle($params);
                    break;

                // 下单页面积分数据
                case 'plugins_service_base_data_return_api_buy_index' :
                    $ret = $this->BuyResultHandle($params);
                    break;

                // 用户注册
                case 'plugins_service_user_register_end' :
                    $ret = $this->UserRegisterSuccessHandle($params);
                    break;

            }
            return $ret;
        }
    }

    /**
     * 用户注册
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-03
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function UserRegisterSuccessHandle($params = [])
    {
        if(isset($this->plugins_config['is_register_reward_integral']) && $this->plugins_config['is_register_reward_integral'] == 1 && !empty($params) && !empty($params['user_id']))
        {
            return RewardUserIntegralService::Run($params['user_id'], $this->plugins_config);
        }
    }

    /**
     * 下单接口数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function BuyResultHandle($params = [])
    {
        // 获取插件信息
        $data = [];
        if(!empty($params['data']) && !empty($params['data']['goods_list']))
        {
            // 下单页面用户积分信息
            $params['data']['plugins_points_data'] = PointsService::BuyUserPointsData($this->plugins_config, $params['data']['goods_list'], $params['params']);
        }
    }

    /**
     * 订单状态改变处理,状态为取消|关闭时释放积分
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function OrderStatusChangeHandle($params = [])
    {
        if(!empty($params['data']) && isset($params['data']['new_status']) && in_array($params['data']['new_status'], [5,6]) && !empty($params['order_id']))
        {
            return PointsService::OrderStatusChangeHandle($params['order_id']);
        }
    }

    /**
     * 订单添加成功处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function OrderInsertSuccessHandle($params = [])
    {
        return PointsService::OrderInsertSuccessHandle($params);
    }

    /**
     * 积分兑换/抵扣计算
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function DeductionExchangeCalculate($params = [])
    {
        if(!empty($params['params']) && !empty($params['params']['params']) && !empty($params['params']['params']['is_points']) && $params['params']['params']['is_points'] == 1 && !empty($params['data']))
        {
            PointsService::BuyUserPointsHandle($this->plugins_config, $params['data'], $params['params']['params']);
        }
    }

    /**
     * 订单确认页面基础信息顶部
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BuyBaseConfirmTopContent($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['goods']) && isset($params['params']))
        {
            // 下单页面用户积分信息
            $buy_user_points = PointsService::BuyUserPointsData($this->plugins_config, $params['data']['goods'], $params['params']);
            MyViewAssign('buy_user_points', $buy_user_points);

            return MyView('../../../plugins/view/points/index/public/buy');
        }
    }

    /**
     * 订单确认页面基础信息顶部
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BuyFormInsideContent($params = [])
    {
        if(!empty($params['params']) && isset($params['params']['is_points']) && $params['params']['is_points'] == 1)
        {
            return '<input type="hidden" name="is_points" value="1" />';
        }
        return '';
    }

    /**
     * 商品面板+icon
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsDetailPanelIconHandle($params = [])
    {
        if(!empty($params['goods']) && !empty($params['goods']['id']) && !empty($this->plugins_config['goods_exchange']))
        {
            if(array_key_exists($params['goods']['id'], $this->plugins_config['goods_exchange']) && !empty($this->plugins_config['goods_exchange'][$params['goods']['id']]['integral']))
            {
                // 兑换所需积分
                $integral = $this->plugins_config['goods_exchange'][$params['goods']['id']]['integral'];

                // icon
                if(!empty($this->plugins_config['goods_detail_title_icon']))
                {
                    $params['goods']['plugins_view_icon_data'][] = [
                        'name'      => $this->plugins_config['goods_detail_title_icon'],
                        'br_color'  => '#fb7364',
                        'color'     => '#fb7364',
                    ];
                }

                // 面板
                if(!empty($this->plugins_config['goods_detail_panel']))
                {
                    $params['goods']['plugins_view_panel_data'][] = str_replace('{$integral}', $integral, $this->plugins_config['goods_detail_panel']);
                }
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
                    'url'                   => PluginsHomeUrl('points', 'index', 'index'),
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
            'event_value'   => PluginsHomeUrl('points', 'index', 'index'),
            'name'          => $this->nav_title,
            'images_url'    => MyConfig('shopxo.attachment_host').'/static/plugins/images/points/quick-nav-icon.png',
            'bg_color'      => '#fe3e28',
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
            'event_value'   => '/pages/plugins/points/index/index',
            'name'          => $this->nav_title,
            'images_url'    => MyConfig('shopxo.attachment_host').'/static/plugins/images/points/quick-nav-icon.png',
            'bg_color'      => '#fe3e28',
        ];
        array_push($params['data'], $nav);
    }
}
?>