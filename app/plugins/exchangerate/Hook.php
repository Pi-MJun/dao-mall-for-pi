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
namespace app\plugins\exchangerate;

use app\service\PluginsService;
use app\plugins\exchangerate\service\BaseService;

/**
 * 汇率 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    // 配置信息
    private $plugins_config;

    // 当前模块/控制器/方法
    private $module_name;
    private $controller_name;
    private $action_name;

    // 插件
    private $pluginsname;
    private $pluginscontrol;
    private $pluginsaction;

    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-09T14:25:44+0800
     * @param    [array]                    $params [输入参数]
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

            // 插件
            $this->pluginsname = strtolower(MyInput('pluginsname'));
            $this->pluginscontrol = strtolower(MyInput('pluginscontrol', 'index'));
            $this->pluginsaction = strtolower(MyInput('pluginsaction', 'index'));

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            // 是否开启选择
            $is_user_quick_select = isset($this->plugins_config['is_user_quick_select']) ? intval($this->plugins_config['is_user_quick_select']) : 0;
            $is_user_header_top_right_select = isset($this->plugins_config['is_user_header_top_right_select']) ? intval($this->plugins_config['is_user_header_top_right_select']) : 0;
   
            // 走钩子
            $ret = '';
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    if($this->module_name == 'index' && $is_user_quick_select == 1)
                    {
                        $ret = 'static/plugins/css/exchangerate/index/style.css';
                    }
                    break;

                // 公共js
                case 'plugins_js' :
                    if($this->module_name == 'index' && $is_user_quick_select == 1)
                    {
                        $ret = 'static/plugins/js/exchangerate/index/style.js';
                    }
                    break;

                // web端快捷导航操作按钮
                case 'plugins_service_quick_navigation_pc' :
                    if($is_user_quick_select == 1)
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
                    if($is_user_quick_select == 1)
                    {
                        $this->MiniQuickNavigationHandle($params);
                    }
                    break;

                // web端快捷导航切换处理视图
                case 'plugins_view_common_bottom' :
                    if($is_user_quick_select == 1)
                    {
                        $ret = $this->QuickNavigationView($params);
                    }
                    break;

                // pc端顶部左侧菜单
                case 'plugins_service_header_navigation_top_right_handle' :
                    if($is_user_header_top_right_select == 1)
                    {
                        $this->HeaderNavigationTopRightHandle($params);
                    }
                    break;

                // 货币信息处理
                case 'plugins_service_currency_data' :
                    if($this->module_name != 'admin')
                    {
                        $this->CurrencyDataHandle($params);
                    }
                    break;

                // 商品数据处理后
                case 'plugins_service_goods_handle_end' :
                    if($this->module_name != 'admin')
                    {
                        $this->GoodsHandleEnd($params['goods']);
                    }
                    break;

                // 商品规格基础数据
                case 'plugins_service_goods_spec_base' :
                    $this->GoodsSpecBase($params);
                    break;
            }
            return $ret;
        }
    }

    /**
     * 获取当前选中的货币
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-25
     * @desc    description
     */
    private function GetUserCurrencyCacheValue()
    {
        // 是否仅使用默认货币下单
        if(isset($this->plugins_config['is_use_default_currency_buy']) && $this->plugins_config['is_use_default_currency_buy'] == 1)
        {
            // 当前访问模块
            $act = $this->module_name.$this->controller_name;
            if(in_array($act, ['indexbuy', 'apibuy']))
            {
                return '';
            }
        }

        // 指定的货币值
        return BaseService::GetUserCurrencyCacheValue();
    }

    /**
     * 货币信息处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function CurrencyDataHandle($params = [])
    {
        // 指定的货币值
        $currency_value = $this->GetUserCurrencyCacheValue();

        // 获取货币列表
        $data = BaseService::UserCurrencyData($currency_value);
        if(!empty($data['default']))
        {
            $params['data']['currency_symbol']  = $data['default']['symbol'];
            $params['data']['currency_code']    = $data['default']['code'];
            $params['data']['currency_rate']    = $data['default']['rate'];
            $params['data']['currency_name']    = $data['default']['name'];
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
        // 获取货币列表
        $data = BaseService::UserCurrencyData();
        if(!empty($data['data']))
        {
            // 加入导航尾部
            $nav = [
                'event_type'    => 0,
                'event_value'   => 0,
                'name'          => '货币切换',
                'images_url'    => MyConfig('shopxo.attachment_host').'/static/plugins/images/exchangerate/quick-nav-icon.png',
                'bg_color'      => '#18277f',
                'class_name'    => 'plugins-exchangerate-currency-select-event',
            ];
            array_push($params['data'], $nav);
        }
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
        // 获取货币列表
        $data = BaseService::UserCurrencyData();
        if(!empty($data['data']))
        {
            // 加入导航尾部
            $nav = [
                'event_type'    => 1,
                'event_value'   => '/pages/plugins/exchangerate/currency/currency',
                'name'          => '货币切换',
                'images_url'    => MyConfig('shopxo.attachment_host').'/static/plugins/images/exchangerate/quick-nav-icon.png',
                'bg_color'      => '#18277f',
            ];
            array_push($params['data'], $nav);
        }
    }

    /**
     * 快捷导航页面选择
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function QuickNavigationView($params = [])
    {
        // 获取货币列表
        $data = BaseService::UserCurrencyData();
        if(!empty($data['data']))
        {
            // 当前url地址
            $my_url = $this->CurrentViewUrl();

            // 货币选择
            $select = [];
            foreach($data['data'] as $v)
            {
                // 选择列表
                $select[] = [
                    'name'  => $v['name'].'-'.$v['code'],
                    'url'   => $my_url.$v['id'],
                ];
            }
            MyViewAssign('currency_select', $select);
        }

        return MyView('../../../plugins/view/exchangerate/index/public/select');
    }

    /**
     * web端顶部右侧小导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function HeaderNavigationTopRightHandle($params = [])
    {
        // 指定的货币值
        $currency_value = $this->GetUserCurrencyCacheValue();

        // 获取货币列表
        $data = BaseService::UserCurrencyData($currency_value);
        if(!empty($data['default']) && !empty($data['data']))
        {
            // 当前url地址
            $my_url = $this->CurrentViewUrl();

            // 货币选择
            $select = [];
            foreach($data['data'] as $v)
            {
                // 选择列表
                $select[] = [
                    'name'  => $v['name'].'-'.$v['code'],
                    'url'   => $my_url.$v['id'],
                ];
            }

            // 指定货币不为空则存储选择的货币值
            if(!empty($currency_value))
            {
                BaseService::SetUserCurrencyCacheValue($data['default']['id']);
            }

            // 加入导航尾部
            $nav = [
                'name'      => '货币['.$data['default']['name'].'-'.$data['default']['code'].']',
                'is_login'  => 0,
                'badge'     => null,
                'icon'      => 'am-icon-shield',
                'url'       => '',
                'items'     => $select,
            ];
            array_push($params['data'], $nav);
        }
    }

    /**
     * 当前页面url地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-11
     * @desc    description
     */
    public function CurrentViewUrl()
    {
        // 去除当前存在的参数
        $url = __MY_VIEW_URL__;
        if(stripos($url, BaseService::$request_currency_key) !== false)
        {
            $arr1 = explode('?', $url);
            $arr2 = explode('&', $arr1[1]);
            foreach($arr2 as $k=>$v)
            {
                if(stripos($v, BaseService::$request_currency_key) !== false)
                {
                    unset($arr2[$k]);
                }
            }
            $url = '?'.implode('&', $arr2);
        }

        // 当前页面地址
        $join = (stripos($url, '?') === false) ? '?' : '&';
        return $url.$join.BaseService::$request_currency_key.'=';
    }

    /**
     * 商品处理结束钩子
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [array]              &$goods [商品数据]
     */
    private function GoodsHandleEnd(&$goods = [])
    {
        // 开启转换
        if($this->IsGoodsToRate())
        {
            // 指定的货币值
            $currency_value = $this->GetUserCurrencyCacheValue();

            // 获取货币列表
            $data = BaseService::UserCurrencyData($currency_value);
            if(!empty($data['default']) && !empty($data['default']['rate']) && $data['default']['rate'] > 0)
            {
                // 汇率
                $rate = $data['default']['rate'];

                // 原始价格处理、正常是不需要改变系统保留的原始价格，这里汇率插件需要将商品所有价格进行转换，避免有某些插件使用了原始价格而造成页面价格不一致的情况
                if(isset($this->plugins_config['is_goods_od_to_rate']) && $this->plugins_config['is_goods_od_to_rate'] == 1 && !empty($goods['price_container']))
                {
                    $goods['price_container'] = $this->PriceRateHandle($goods['price_container'], $rate);
                }

                // 使用价格处理
                $goods = $this->PriceRateHandle($goods, $rate);
            }
        }
    }

    /**
     * 是否转换商品
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-09-13
     * @desc    description
     */
    public function IsGoodsToRate()
    {
        // 避开订单管理页面
        if(isset($this->plugins_config['is_goods_to_rate']) && $this->plugins_config['is_goods_to_rate'] == 1 && !in_array($this->module_name.$this->controller_name, [
            'adminorder',
            'adminorderaftersale',
            'apiorder',
            'apiorderaftersale',
            'indexorder',
            'indexorderaftersale',
        ]) && !in_array($this->pluginsname.$this->pluginscontrol, [
            'shoporder',
            'shoporderaftersale',
            'realstoreorder',
            'realstoreorderaftersale',
        ]))
        {
            return true;
        }
        return false;
    }

    /**
     * 商品价格转换处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-27
     * @desc    description
     * @param   [array]          $price_data [商品价格数据]
     * @param   [float]          $rate       [汇率]
     */
    public function PriceRateHandle($price_data, $rate)
    {
        // 展示销售价格,原价
        if(isset($price_data['price']))
        {
            if(stripos($price_data['price'], '-') !== false)
            {
                $temp = explode('-', $price_data['price']);
                if(is_array($temp) && count($temp) == 2)
                {
                    $temp[0] = PriceNumberFormat($temp[0]*$rate);
                    $temp[1] = PriceNumberFormat($temp[1]*$rate);
                    $price_data['price'] = implode('-', $temp);
                }
            } else {
                $price_data['price'] = PriceNumberFormat($price_data['price']*$rate);
            }
        }
        if(isset($price_data['original_price']))
        {
            if(stripos($price_data['original_price'], '-') !== false)
            {
                $temp = explode('-', $price_data['original_price']);
                if(is_array($temp) && count($temp) == 2)
                {
                    $temp[0] = PriceNumberFormat($temp[0]*$rate);
                    $temp[1] = PriceNumberFormat($temp[1]*$rate);
                    $price_data['original_price'] = implode('-', $temp);
                }
            } else {
                $price_data['original_price'] = PriceNumberFormat($price_data['original_price']*$rate);
            }
        }

        // 最低价,最高价
        if(isset($price_data['min_price']))
        {
            $price_data['min_price'] = PriceNumberFormat($price_data['min_price']*$rate);
        }
        if(isset($price_data['max_price']))
        {
            $price_data['max_price'] = PriceNumberFormat($price_data['max_price']*$rate);
        }

        return $price_data;
    }

    /**
     * 商品规格基础数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsSpecBase($params = [])
    {
        if($this->IsGoodsToRate())
        {
            // 指定的货币值
            $currency_value = $this->GetUserCurrencyCacheValue();

            // 获取货币列表
            $data = BaseService::UserCurrencyData($currency_value);
            if(!empty($data['default']) && !empty($data['default']['rate']) && $data['default']['rate'] > 0)
            {
                // 汇率
                $rate = $data['default']['rate'];

                // 商品规格
                $params['data']['spec_base']['price'] = PriceNumberFormat($params['data']['spec_base']['price']*$rate);
                if(isset($params['data']['spec_base']['original_price']) && $params['data']['spec_base']['original_price'] > 0)
                {
                    $params['data']['spec_base']['original_price'] = PriceNumberFormat($params['data']['spec_base']['original_price']*$rate);
                }
            }
        }
    }
}
?>