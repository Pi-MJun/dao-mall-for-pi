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
namespace app\plugins\activity;

use app\service\SystemBaseService;
use app\plugins\activity\service\BaseService;
use app\plugins\activity\service\ActivityService;

/**
 * 活动配置 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    // 模块、控制器、方法
    private $module_name;
    private $controller_name;
    private $action_name;

    // 配置信息
    private $plugins_config;

    // 商品是否实际使用
    public $is_actual_discount_goods = 0;

    /**
     * 应用响应入口
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-03
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function handle($params = [])
    {
        if(!empty($params['hook_name']))
        {
            // 当前模块/控制器/方法
            $this->module_name = RequestModule();
            $this->controller_name = RequestController();
            $this->action_name = RequestAction();
            $mca = $this->module_name.$this->controller_name.$this->action_name;

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            $ret = '';
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    $style_arr = ['indexindexindex', 'indexgoodsindex'];
                    if(in_array($mca, $style_arr))
                    {
                        $ret = 'static/plugins/css/activity/index/style.css';
                    }
                    break;

                // 在前面添加导航
                case 'plugins_service_navigation_header_handle' :
                    $ret = $this->NavigationHeaderHandle($params);
                    break;

                // 首页楼层顶部新增活动
                case 'plugins_view_home_floor_top' :
                case 'plugins_view_home_floor_bottom' :
                    $ret = $this->ActivityFloorHandle($params);
                    break;

                // 商品数据处理后
                case 'plugins_service_goods_handle_end' :
                    if($this->module_name != 'admin')
                    {
                        // 是否支持优惠
                        if(SystemBaseService::IsGoodsDiscount($params))
                        {
                            $this->GoodsHandleEnd($params);
                        }

                        // 使用优惠处理
                        if(!empty($params) && !empty($params['goods']) && !empty($params['goods']['id']))
                        {
                            SystemBaseService::GoodsDiscountRecord($params['goods']['id'], 'activity', $this->is_actual_discount_goods);
                        }
                    }
                    break;

                // 商品规格基础数据
                case 'plugins_service_goods_spec_base' :
                    // 是否支持优惠
                    if(SystemBaseService::IsGoodsDiscount($params))
                    {
                        $this->GoodsSpecBase($params);
                    }
                    break;

                // 首页接口数据
                case 'plugins_service_base_data_return_api_index_index' :
                    $this->IndexResultHandle($params);
                    break;
            }
            return $ret;;
        }
    }

    /**
     * 首页接口数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function IndexResultHandle($params = [])
    {
        $data = ActivityService::ActivityFloorData();
        if(!empty($data))
        {
            $params['data']['plugins_activity_data'] = [
                'base'  => $this->plugins_config,
                'data'  => $data,
            ];
        }
    }

    /**
     * 商品规格基础数据
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsSpecBase($params = [])
    {
        if(!empty($params['data']['spec_base']))
        {
            $ag = ActivityService::ActivityGoodsData($params['data']['spec_base']['goods_id']);
            if(!empty($ag))
            {
                if(isset($params['data']['spec_base']['price']))
                {
                    // 使用销售价作为原价
                    if(isset($params['data']['spec_base']['original_price']) && isset($this->plugins_config['is_actas_price_original']) && $this->plugins_config['is_actas_price_original'] == 1)
                    {
                        $params['data']['spec_base']['original_price'] = $params['data']['spec_base']['price'];
                    }

                    // 价格处理
                    $params['data']['spec_base']['price'] = BaseService::PriceCalculate($params['data']['spec_base']['price'], $ag['discount_rate'], $ag['dec_price']);
                }
            }
        }
    }

    /**
     * 商品处理结束钩子
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsHandleEnd($params = [])
    {
        // key字段
        $key_field = empty($params['params']['data_key_field']) ? 'id' : $params['params']['data_key_field'];
        if(!empty($params['goods']) && isset($params['goods'][$key_field]))
        {
            // 活动信息
            $ag = ActivityService::ActivityGoodsData($params['goods'][$key_field]);
            if(!empty($ag))
            {
                // 使用销售价作为原价
                $is_actas_price_original = isset($this->plugins_config['is_actas_price_original']) && $this->plugins_config['is_actas_price_original'] == 1;

                // 无价格字段则不处理
                if(isset($params['goods']['price']))
                {
                    // 使用销售价作为原价
                    if(isset($params['goods']['original_price']) && $is_actas_price_original)
                    {
                        $params['goods']['original_price'] = $params['goods']['price'];
                    }

                    // 价格处理
                    $params['goods']['price'] = BaseService::PriceCalculate($params['goods']['price'], $ag['discount_rate'], $ag['dec_price']);
                    if(!empty($this->plugins_config['goods_detail_icon']))
                    {
                        $params['goods']['show_field_price_text'] = '<a href="'.PluginsHomeUrl('activity', 'index', 'index').'" class="plugins-activity-goods-price-icon" title="'.$this->plugins_config['goods_detail_icon'].'">'.$this->plugins_config['goods_detail_icon'].'</a>';
                    }
                }

                // 最低价最高价
                if(isset($params['goods']['min_price']))
                {
                    // 使用销售价作为原价
                    if(isset($params['goods']['min_original_price']) && $is_actas_price_original)
                    {
                        $params['goods']['min_original_price'] = $params['goods']['min_price'];
                    }

                    // 价格处理
                    $params['goods']['min_price'] = BaseService::PriceCalculate($params['goods']['min_price'], $ag['discount_rate'], $ag['dec_price']);
                }
                if(isset($params['goods']['max_price']))
                {
                    // 使用销售价作为原价
                    if(isset($params['goods']['max_original_price']) && $is_actas_price_original)
                    {
                        $params['goods']['max_original_price'] = $params['goods']['max_price'];
                    }

                    // 价格处理
                    $params['goods']['max_price'] = BaseService::PriceCalculate($params['goods']['max_price'], $ag['discount_rate'], $ag['dec_price']);
                }

                // 使用优惠标记
                $this->is_actual_discount_goods = 1;
            }
        }
    }

    /**
     * 楼层活动
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function ActivityFloorHandle($params = [])
    {
        // 数据位置
        $floor_location_arr = [
            'plugins_view_home_floor_top'       => 0,
            'plugins_view_home_floor_bottom'    => 1,
        ];
        $home_data_location = array_key_exists($params['hook_name'], $floor_location_arr) ? $floor_location_arr[$params['hook_name']] : 0;
        $data = ActivityService::ActivityFloorData(['where'=>[['home_data_location', '=', $home_data_location]]]);
        MyViewAssign('activity_data_list', $data);
        MyViewAssign('plugins_config', $this->plugins_config);
        return MyView('../../../plugins/view/activity/index/public/home');
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
    private function NavigationHeaderHandle($params = [])
    {
        if(isset($params['header']) && is_array($params['header']))
        {
            // 获取应用数据
            if(!empty($this->plugins_config['application_name']))
            {
                $nav = [
                    'id'                    => 0,
                    'pid'                   => 0,
                    'name'                  => $this->plugins_config['application_name'],
                    'url'                   => PluginsHomeUrl('activity', 'index', 'index'),
                    'data_type'             => 'custom',
                    'is_show'               => 1,
                    'is_new_window_open'    => 0,
                    'items'                 => [],
                ];
                array_unshift($params['header'], $nav);
            }
        }
    }
}
?>