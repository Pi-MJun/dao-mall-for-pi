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
namespace app\plugins\coupon;

use think\facade\Db;
use app\service\UserService;
use app\service\ResourcesService;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\CouponService;
use app\plugins\coupon\service\UserCouponService;

/**
 * 优惠券 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-11T21:51:08+0800
 */
class Hook
{
    // 配置信息
    private $plugins_config;

    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-11T14:25:44+0800
     * @param    [array]          $params [输入参数]
     */
    public function handle($params = [])
    {
        // 后端访问不处理
        if(isset($params['params']['is_admin_access']) && $params['params']['is_admin_access'] == 1)
        {
            return DataReturn('无需处理', 0);
        }

        // 钩子名称
        $ret = '';
        if(!empty($params['hook_name']))
        {
            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            // 当前模块/控制器/方法
            $module_name = RequestModule();
            $controller_name = RequestController();
            $action_name = RequestAction();

            $coupon_style = ['indexgoodsindex', 'indexbuyindex'];
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    if(in_array($module_name.$controller_name.$action_name, $coupon_style))
                    {
                        $ret = 'static/plugins/css/coupon/index/common.css';
                    }
                    break;

                // 公共js
                case 'plugins_js' :
                    if(in_array($module_name.$controller_name.$action_name, $coupon_style))
                    {
                        $ret = 'static/plugins/js/coupon/index/common.js';
                    }
                    break;

                // 在前面添加导航
                case 'plugins_service_navigation_header_handle' :
                    $ret = $this->NavigationHeaderHandle($params);
                    break;

                // 用户中心左侧导航
                case 'plugins_service_users_center_left_menu_handle' :
                    $ret = $this->UserCenterLeftMenuHandle($params);
                    break;

                // 顶部小导航右侧-我的商城
                case 'plugins_service_header_navigation_top_right_handle' :
                    $ret = $this->CommonTopNavRightMenuHandle($params);
                    break;

                // 商品详情
                case 'plugins_view_goods_detail_panel_bottom' :
                    $ret = $this->GoodsDetailCoupinView($params);
                    break;

                // 购买确认页面优惠券选择
                case 'plugins_view_buy_group_goods_inside_bottom' :
                    $ret = $this->BuyCoupinView($params);
                    break;

                // 购买订单优惠处理
                case 'plugins_service_buy_group_goods_handle' :
                    $ret = $this->BuyDiscountCalculate($params);
                    break;

                // 购买提交订单页面隐藏域html
                case 'plugins_view_buy_form_inside' :
                    $ret = '';
                    $data = $this->BuyCouponSelectIds($params);
                    if(!empty($data))
                    {
                        foreach($data as $k=>$v)
                        {
                            $ret .= '<input type="hidden" name="coupon_id_'.$k.'" value="'.$v.'" />';
                        }
                    }
                    break;

                // 订单添加成功处理
                case 'plugins_service_buy_order_insert_success' :
                    $ret = $this->OrderInsertSuccessHandle($params);
                    break;

                // 订单状态改变处理
                case 'plugins_service_order_status_change_history_success_handle' :
                    $ret = $this->OrderInvalidHandle($params);
                    break;

                // 注册送优惠券
                case 'plugins_service_user_register_end' :
                    $ret = $this->UserRegisterGiveHandle($params);
                    break;

                // 商品接口数据
                case 'plugins_service_base_data_return_api_goods_detail' :
                    $ret = $this->GoodsResultHandle($params);
                    break;

                // 下单接口数据
                case 'plugins_service_base_data_return_api_buy_index' :
                    $ret = $this->BuyResultHandle($params);
                    break;
            }
        }
        return $ret;
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
        if(!empty($params['data']) && !empty($params['data']['goods_list']))
        {
            $result = [];
            foreach($params['data']['goods_list'] as $v)
            {
                $ret = BaseService::BuyUserCouponDataHandle($v['id'], $v['goods_items'], $params['params']);
                $result[] = [
                    'warehouse_id'      => $v['id'],
                    'warehouse_name'    => $v['name'],
                    'coupon_data'       => $ret,
                ];
            }
            $params['data']['plugins_coupon_data'] = $result;
        }
    }

    /**
     * 商品接口数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsResultHandle($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['goods']))
        {
            $data = $this->GoodsDetailCoupinData($params['data']['goods']['id']);
            if(!empty($data))
            {
                $params['data']['plugins_coupon_data'] = [
                    'base'  => $this->plugins_config,
                    'data'  => $data,
                ];
            }
        }
    }

    /**
     * 注册送劵
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function UserRegisterGiveHandle($params = [])
    {
        if(!empty($params['user_id']))
        {
            UserCouponService::UserRegisterGive($params['user_id']);
        }
    }

    /**
     * 订单状态改变处理,状态为取消|关闭时释放优惠券
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function OrderInvalidHandle($params = [])
    {
        if(!empty($params['data']) && isset($params['data']['new_status']) && in_array($params['data']['new_status'], [5,6]) && !empty($params['order_id']))
        {
            // 释放用户优惠券
            UserCouponService::UserCouponUseStatusUpdate(Db::name('Order')->where(['id'=>intval($params['order_id'])])->value('extension_data'), 0, 0);
        }
    }

    /**
     * 订单添加成功处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-14
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function OrderInsertSuccessHandle($params = [])
    {
        if(!empty($params['order_ids']))
        {
            $order = Db::name('Order')->where(['id'=>$params['order_ids']])->field('id,extension_data')->select()->toArray();
            if(!empty($order))
            {
                // 更新优惠券使用状态
                foreach($order as $v)
                {
                    UserCouponService::UserCouponUseStatusUpdate($v['extension_data'], 1, $v['id']);
                }
            }
        }
    }

    /**
     * 满减计算
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-14
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BuyDiscountCalculate($params = [])
    {
        $currency_symbol = ResourcesService::CurrencyDataSymbol();
        foreach($params['data'] as &$v)
        {
            $ret = BaseService::BuyUserCouponDataHandle($v['id'], $v['goods_items'], $params['params']['params']);
            if(!empty($ret['coupon_choice']) && !empty($ret['coupon_choice']['buy_goods_ids']))
            {
                // 优惠券是否限定, 则读取优惠券可用商品id重新计算
                $order_price = 0.00;
                if($ret['coupon_choice']['coupon']['use_limit_type'] > 0)
                {
                    foreach($v['goods_items'] as $goods)
                    {
                        if(in_array($goods['goods_id'], $ret['coupon_choice']['buy_goods_ids']))
                        {
                            $order_price += $goods['total_price'];
                        }
                    }
                } else {
                    $order_price = $v['order_base']['total_price'];
                }
                if($order_price > 0)
                {
                    $discount_price = BaseService::PriceCalculate($order_price, $ret['coupon_choice']['coupon']['type'], $ret['coupon_choice']['coupon']['where_order_price'], $ret['coupon_choice']['coupon']['discount_value']);

                    if($discount_price > 0)
                    {
                        // 扩展展示数据
                        $title = ($ret['coupon_choice']['coupon']['type'] == 0) ? '优惠券' : '折扣劵';
                        $v['order_base']['extension_data'][] = [
                            'name'      => $title.'-'.$ret['coupon_choice']['coupon']['desc'],
                            'price'     => $discount_price,
                            'type'      => 0,
                            'tips'      => '-'.$currency_symbol.$discount_price.'π',
                            'business'  => 'plugins-coupon',
                            'ext'       => $ret['coupon_choice'],
                        ];
                    }
                }
            }
        }
        return DataReturn('处理成功', 0);
    }

    /**
     * 购买确认页面优惠券选择
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function BuyCoupinView($params = [])
    {
        // 当前仓库id
        $warehouse_id = $params['data']['id'];

        // 获取用户优惠券
        $ret = BaseService::BuyUserCouponDataHandle($warehouse_id, $params['data']['goods_items'], $params['params']);

        // 数据赋值
        MyViewAssign('coupon_choice', $ret['coupon_choice']);
        MyViewAssign('coupon_list', $ret['coupon_list']);
        MyViewAssign('warehouse_id', $warehouse_id);
        MyViewAssign('coupon_ids', $this->BuyCouponSelectIds($params));
        MyViewAssign('params', $params['params']);
        return MyView('../../../plugins/view/coupon/index/public/buy');
    }

    /**
     * 获取优惠券选中的id
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-08-02
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function BuyCouponSelectIds($params)
    {
        $ids = [];
        if(!empty($params) && isset($params['params']) && is_array($params['params']))
        {
            $key_field_first = 'coupon_id_';
            foreach($params['params'] as $k=>$v)
            {
                if(substr($k, 0, 10) == $key_field_first)
                {
                    $key = str_replace($key_field_first, '', $k);
                    $ids[$key] = $v;
                }
            }
        }
        return $ids;
    }

    /**
     * 商品详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsDetailCoupinView($params = [])
    {
        if(!empty($params['goods_id']))
        {
            MyViewAssign('coupon_list', $this->GoodsDetailCoupinData($params['goods_id']));
            return MyView('../../../plugins/view/coupon/index/public/goods_detail_panel');
        }        
    }

    /**
     * 商品页面优惠券
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-07
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     */
    private function GoodsDetailCoupinData($goods_id)
    {
        $coupon_params = [
            'where'             => [
                'is_enable'         => 1,
                'is_user_receive'   => 1,
            ],
            'm'                 => 0,
            'n'                 => 0,
            'is_sure_receive'   => 1,
            'user'              => UserService::LoginUserInfo(),
        ];
        $ret = CouponService::CouponList($coupon_params);
        // 排除商品不支持的活动
        if(!empty($ret['data']))
        {
            $ret['data'] = BaseService::CouponListGoodsExclude(['data'=>$ret['data'], 'goods_id'=>intval($goods_id)]);
        }
        return empty($ret['data']) ? [] : $ret['data'];
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
        if(is_array($params['header']) && !empty($this->plugins_config['application_name']))
        {
            $nav = [
                'id'                    => 0,
                'pid'                   => 0,
                'name'                  => $this->plugins_config['application_name'],
                'url'                   => PluginsHomeUrl('coupon', 'index', 'index'),
                'data_type'             => 'custom',
                'is_show'               => 1,
                'is_new_window_open'    => 0,
                'items'                 => [],
            ];
            array_unshift($params['header'], $nav);
        }
    }

    /**
     * 用户中心左侧菜单处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function UserCenterLeftMenuHandle($params = [])
    {
        $params['data']['property']['item'][] = [
            'name'      =>  '我的卡劵',
            'url'       =>  PluginsHomeUrl('coupon', 'coupon', 'index'),
            'contains'  =>  ['couponindex'],
            'is_show'   =>  1,
            'icon'      =>  'am-icon-gift',
        ];
    }

    /**
     * 顶部小导航右侧-我的商城
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function CommonTopNavRightMenuHandle($params = [])
    {
        array_push($params['data'][1]['items'], [
            'name'  => '我的卡劵',
            'url'   => PluginsHomeUrl('coupon', 'coupon', 'index'),
        ]);
    }
}
?>