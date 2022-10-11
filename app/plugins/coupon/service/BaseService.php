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
namespace app\plugins\coupon\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\GoodsService;
use app\service\UserService;

/**
 * 基础服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-05T21:51:08+0800
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [
        'banner_images'
    ];

    // 类型名称
    public static $business_type_name = '优惠券';

    // 是否
    public static $common_is_whether_list =  [
        0 => ['value' => 0, 'name' => '否'],
        1 => ['value' => 1, 'name' => '是', 'checked' => true],
    ];

    // 优惠券类型
    public static $coupon_type_list =  [
        0 => ['value' => 0, 'name' => '满减劵', 'checked' => true],
        1 => ['value' => 1, 'name' => '折扣劵'],
    ];

    // 优惠券背景色
    public static $coupon_bg_color_list =  [
        0 => ['value' => 0, 'name' => '红色', 'color' => '#D2364C', 'checked' => true],
        1 => ['value' => 1, 'name' => '紫色', 'color' => '#9C27B0',],
        2 => ['value' => 2, 'name' => '黄色', 'color' => '#FFC107',],
        3 => ['value' => 3, 'name' => '蓝色', 'color' => '#03A9F4',],
        4 => ['value' => 4, 'name' => '橙色', 'color' => '#F44336',],
        5 => ['value' => 5, 'name' => '绿色', 'color' => '#4CAF50',],
        6 => ['value' => 6, 'name' => '咖啡色', 'color' => '#795548',],
    ];

    // 到期类型
    public static $common_expire_type_list =  [
        0 => ['value' => 0, 'name' => '领取生效', 'checked' => true],
        1 => ['value' => 1, 'name' => '固定日期'],
    ];

    // 使用限制类型
    public static $common_use_limit_type_list =  [
        0 => ['value' => 0, 'name' => '全场适用', 'checked' => true],
        1 => ['value' => 1, 'name' => '限定商品分类可用'],
        2 => ['value' => 2, 'name' => '限定商品可用'],
    ];

    /**
     * 优惠列表排除商品未在享受范围的优惠
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-13
     * @desc    description
     * @param   [array]             $params['data']       [优惠列表]
     * @param   [int]               $params['goods_id']   [商品id]
     * @param   [string]            $params['type']       [类型（coupon优惠券, user用户优惠券）]
     */
    public static function CouponListGoodsExclude($params = [])
    {
        if(!empty($params['data']) && !empty($params['goods_id']))
        {
            $type = empty($params['type']) ? 'coupon' : $params['type'];
            $data = $params['data'];
            foreach($data as $k=>$v)
            {
                // 使用限制条件
                $use_limit_type = 0;
                $use_value_ids = [];
                switch($type)
                {
                    // 优惠券
                    case 'coupon' :
                        $use_limit_type = isset($v['use_limit_type']) ? $v['use_limit_type'] : 0;
                        $use_value_ids = isset($v['use_value_ids_all']) ? $v['use_value_ids_all'] : [];
                        break;

                    // 用户优惠券
                    case 'user' :
                        if(isset($v['coupon']))
                        {
                            $use_limit_type = isset($v['coupon']['use_limit_type']) ? $v['coupon']['use_limit_type'] : 0;
                            $use_value_ids = isset($v['coupon']['use_value_ids_all']) ? $v['coupon']['use_value_ids_all'] : [];
                        }
                        break;
                }
                if($use_limit_type > 0 && !empty($use_value_ids))
                {
                    switch($use_limit_type)
                    {
                        // 限定商品分类
                        case 1 :
                            $goods_categosy_ids = Db::name('GoodsCategoryJoin')->where(['goods_id'=>$params['goods_id']])->column('category_id');
                            if(!empty($goods_categosy_ids))
                            {
                                $category_status = false;
                                foreach($use_value_ids as $value)
                                {
                                    $category_ids = GoodsService::GoodsCategoryItemsIds([$value], 1);
                                    if(!empty($category_ids))
                                    {
                                        foreach($goods_categosy_ids as $category_id)
                                        {
                                            if(in_array($category_id, $category_ids))
                                            {
                                                $category_status = true;
                                                break 2;
                                            }
                                        }
                                    }
                                }
                                if($category_status == false)
                                {
                                    unset($data[$k]);
                                    break;
                                }
                            }
                            break;

                        // 限定商品
                        case 2 :
                            if(!in_array($params['goods_id'], $use_value_ids))
                            {
                                unset($data[$k]);
                            }
                            break;
                    }
                }
            }
            sort($data);
        }
        return $data;
    }

    /**
     * 提交订单页面优惠券排除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-14
     * @desc    description
     * @param   [array]             $data       [优惠列表]
     * @param   [array]             $goods_ids  [商品id]
     * @param   [array]             $goods      [购买的商品信息]
     */
    public static function BuyCouponExclude($data, $goods_ids, $goods)
    {
        $coupon = [];
        if(!empty($data) && !empty($goods_ids))
        {
            foreach($goods_ids as $goods_id)
            {
                $temp_coupon = self::CouponListGoodsExclude(['data'=>$data, 'goods_id'=>$goods_id, 'type'=>'user']);
                if(!empty($temp_coupon))
                {
                    // 合并优惠券
                    foreach($temp_coupon as $v)
                    {
                        if(!isset($coupon[$v['id']]))
                        {
                            $coupon[$v['id']] = $v;
                        }
                        $coupon[$v['id']]['buy_goods_ids'][] = $goods_id;
                    }
                }
            }

            // 是否有优惠
            // 根据当前订单商品排除不满足的优惠券
            if(!empty($coupon))
            {
                $order_total_price = array_sum(array_column($goods, 'total_price'));
                foreach($coupon as $k=>$v)
                {
                    // 整个订单总额是否满足当前优惠券条件
                    if($order_total_price >= $v['coupon']['where_order_price'])
                    {
                        // 是否有使用限制，根据使用限制关联的商品总额重新计算满足条件
                        if($v['coupon']['use_limit_type'] > 0)
                        {
                            if(!empty($v['buy_goods_ids']))
                            {
                                $inside_goods_price_total = 0.00;
                                foreach($goods as $g)
                                {
                                    if(in_array($g['goods_id'], $v['buy_goods_ids']))
                                    {
                                        $inside_goods_price_total += $g['total_price'];
                                    }
                                }
                                if($inside_goods_price_total < $v['coupon']['where_order_price'])
                                {
                                    unset($coupon[$k]);
                                }
                            } else {
                                unset($coupon[$k]);
                            }
                        }
                    } else {
                        unset($coupon[$k]);
                    }

                    // 排除过期
                    if(time() < $v['time_start'] || time() > $v['time_end'])
                    {
                        unset($coupon[$k]);
                    }
                }
                sort($coupon);
            }
        }
        return $coupon;
    }

    /**
     * 优惠价格计算
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-14
     * @desc    description
     * @param   [float]           $order_price          [订单金额]
     * @param   [int]             $type                 [类型（0满减, 1折扣）]
     * @param   [float]           $where_order_price    [订单满优惠条件]
     * @param   [float]           $discount_value       [满减金额|折扣系数]
     */
    public static function PriceCalculate($order_price, $type = 0, $where_order_price = 0, $discount_value = 0)
    {
        if($order_price <= 0 || $discount_value <= 0 || $order_price < $where_order_price)
        {
            return 0;
        }

        // 默认 减金额
        $discount_price = $discount_value;
        switch($type)
        {
            // 折扣
            case 1 :
                $discount_price = $order_price-($order_price*($discount_value/10));
                break;
        }
        return PriceNumberFormat($discount_price);
    }

    /**
     * 下订单用户优惠券信息获取处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [int]             $warehouse_id     [输入参当前仓库id数]
     * @param   [array]           $order_goods      [订单商品]
     * @param   [array]           $params           [输入参数]
     */
    public static function BuyUserCouponDataHandle($warehouse_id, $order_goods, $params = [])
    {
        // 当前选中的优惠券id
        $coupon_key = 'coupon_id_'.$warehouse_id;
        $coupon_id = (!empty($params) && isset($params[$coupon_key])) ? intval($params[$coupon_key]) : 0;

        // 获取当前购买可选择的优惠券数据
        return self::BuyUserCouponData(['order_goods'=>$order_goods, 'coupon_id'=>$coupon_id]);
    }

    /**
     * 下订单用户优惠券信息获取
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]             $params['order_goods']    [订单商品参数]
     * @param   [int]               $params['coupon_id']      [当前选中的优惠券id]
     */
    public static function BuyUserCouponData($params = [])
    {
        $coupon_list = [];
        $coupon_choice = null;
        if(!empty($params['order_goods']))
        {
            // 当前登录用户
            $user = UserService::LoginUserInfo();
            if(!empty($user['id']))
            {
                // 优惠券列表
                $coupon_params = [
                    'user'  => $user,
                    'where' => [
                        'user_id'   => $user['id'],
                        'is_valid'  => 1,
                        'is_use'    => 0,
                        'is_expire' => 0,
                    ],
                ];
                $ret = UserCouponService::CouponUserList($coupon_params);
                if(!empty($ret['data']['not_use']))
                {
                    // 排除商品不支持的活动
                    $coupon_list = self::BuyCouponExclude($ret['data']['not_use'], array_column($params['order_goods'], 'goods_id'), $params['order_goods']);

                    // 当前选中优惠券
                    if(!empty($params['coupon_id']) && !empty($coupon_list))
                    {
                        foreach($coupon_list as $v)
                        {
                            if($v['id'] == $params['coupon_id'])
                            {
                                $coupon_choice = $v;
                                break;
                            }
                        }
                    }
                }
            }
        }
        return ['coupon_list'=>$coupon_list, 'coupon_choice'=>$coupon_choice];
    }

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'coupon', 'data'=>$params], self::$base_config_attachment_field);
    }

    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        $ret = PluginsService::PluginsData('coupon', self::$base_config_attachment_field, $is_cache);
        return $ret;
    }
}
?>