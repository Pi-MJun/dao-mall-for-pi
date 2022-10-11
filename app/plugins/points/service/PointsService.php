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
namespace app\plugins\points\service;

use think\facade\Db;
use app\service\UserService;
use app\service\IntegralService;
use app\service\ResourcesService;
use app\plugins\points\service\BaseService;

/**
 * 积分商城 - 积分兑换、抵扣服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class PointsService
{
    /**
     * 下单页面用户积分信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-25
     * @desc    description
     * @param   [array]          $base   [插件配置信息]
     * @param   [array]          $goods  [仓库组商品]
     * @param   [array]          $params [输入参数]
     */
    public static function BuyUserPointsData($base, $goods, $params = [])
    {
        // 获取用户信息
        $user = UserService::LoginUserInfo();

        // 用户可用积分
        $user_integral = (empty($user) || empty($user['id'])) ? 0 : (int) Db::name('User')->where(['id'=>$user['id']])->value('integral');

        // 抵扣类型(0积分抵扣, 1积分兑换)
        $discount_type = 0;
        $discount_price = 0;

        // 优先计算是否满足积分兑换
        // 是否全部商品都存在兑换配中、并且积分满足
        $use_integral = 0;
        $group_integral = [];
        if(!empty($base['goods_exchange']) && is_array($base['goods_exchange']))
        {
            $integral_total = 0;
            $goods_count = 0;
            $goods_exchange_count = 0;
            $goods_exchange_total_price = 0;
            foreach($goods as $v)
            {
                if(!empty($v['goods_items']))
                {
                    if(!array_key_exists($v['id'], $group_integral))
                    {
                        $group_integral[$v['id']] = 0;
                    }
                    foreach($v['goods_items'] as $vs)
                    {
                        if(array_key_exists($vs['goods_id'], $base['goods_exchange']) && !empty($base['goods_exchange'][$vs['goods_id']]['integral']) && $vs['stock'] > 0)
                        {
                            $integral = $vs['stock']*$base['goods_exchange'][$vs['goods_id']]['integral'];
                            $integral_total += $integral;
                            $group_integral[$v['id']] += $integral;
                            $goods_exchange_total_price += $vs['total_price'];
                            $goods_exchange_count++;
                        }
                        $goods_count++;
                    }
                }
            }

            // 必须全部分组下商品都存在商品兑换中、并且用户积分满足即可成为商品兑换
            if($integral_total > 0 && $goods_count > 0 && $goods_exchange_count > 0 && $goods_exchange_count >= $goods_count && $user_integral >= $integral_total)
            {
                $discount_type = 1;
                $use_integral = $integral_total;
                $discount_price = $goods_exchange_total_price;
            }
        }

        // 积分抵扣
        if($discount_type == 0 && isset($base['is_integral_deduction']) && $base['is_integral_deduction'] == 1)
        {
            // 当前订单限制可使用积分数量
            $order_max_integral = empty($base['order_max_integral']) ? 0 : intval($base['order_max_integral']);

            // 当前可用积分
            $use_integral = $user_integral;
            if($order_max_integral > 0 && $user_integral > $order_max_integral)
            {
                $use_integral = $order_max_integral;
            }

            // 抵扣金额
            $deduction_price = empty($base['deduction_price']) ? 0 : PriceNumberFormat($base['deduction_price']);
            $discount_price = ($use_integral > 0 && $deduction_price > 0) ? PriceNumberFormat($use_integral*($deduction_price/100)) : 0;

            // 最大的订单总额
            $order_total_max = (!empty($goods) && is_array($goods)) ? max(array_column(array_column($goods, 'order_base'), 'total_price')) : 0;

            // 金额是否超过了订单金额
            if($discount_price > $order_total_max)
            {
                // 重新计算可使用的积分
                $use_integral =  PriceNumberFormat($order_total_max/($deduction_price/100), 0);

                // 重新计算可使用的金额
                $discount_price = $order_total_max;
            }

            // 订单最低金额条件
            if(!empty($base['order_total_price']) && $base['order_total_price'] > 0)
            {
                if($order_total_max < $base['order_total_price'])
                {
                    $discount_price = 0;
                }
            }
        }

        // 是否已选中
        $is_checked = (isset($params['is_points']) && $params['is_points'] == 1) ? 1 : 0;

        return [
            'user_integral'     => $user_integral,
            'use_integral'      => $use_integral,
            'group_integral'    => $group_integral,
            'discount_type'     => $discount_type,
            'discount_price'    => $discount_price,
            'is_checked'        => $is_checked,
        ];
    }

    /**
     * 下单数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-25
     * @desc    description
     * @param   [array]          $base   [插件配置信息]
     * @param   [array]          $goods  [仓库组商品]
     * @param   [array]          $params [输入参数]
     */
    public static function BuyUserPointsHandle($base, &$goods, $params = [])
    {
        $data = self::BuyUserPointsData($base, $goods, $params);
        if(!empty($data) && !empty($data['discount_price']) && $data['discount_price'] > 0)
        {
            $currency_symbol = ResourcesService::CurrencyDataSymbol();
            foreach($goods as $k=>$v)
            {
                // 积分兑换则所有商品分组都增加优惠数据
                if($data['discount_type'] == 1)
                {
                    $integral = empty($data['group_integral'][$v['id']]) ? 0 : $data['group_integral'][$v['id']];
                    if($integral > 0)
                    {
                        $goods[$k]['order_base']['extension_data'][] = [
                            'name'      => '积分兑换('.$integral.')',
                            'price'     => $v['order_base']['total_price'],
                            'type'      => 0,
                            'business'  => 'plugins-points-exchange',
                            'tips'      => '-'.$currency_symbol.$v['order_base']['total_price'].'π',
                            'ext'       => $integral,
                        ];
                    }
                } else {
                    // 积分抵扣仅第一个满足的订单增加扣减数据
                    if($v['order_base']['total_price'] >= $data['discount_price'])
                    {
                        $goods[$k]['order_base']['extension_data'][] = [
                            'name'      => '积分抵扣('.$data['use_integral'].')',
                            'price'     => $data['discount_price'],
                            'type'      => 0,
                            'business'  => 'plugins-points-deduction',
                            'tips'      => '-'.$currency_symbol.$data['discount_price'].'π',
                            'ext'       => $data['use_integral'],
                        ];
                        break;
                    }
                }
            }
        }
    }

    /**
     * 订单添加成功处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-21
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderInsertSuccessHandle($params)
    {
        $ret = DataReturn('success', 0);
        if(!empty($params['order']) && !empty($params['order']['extension_data']) && !empty($params['order']['user_id']))
        {
            $extension_data = json_decode($params['order']['extension_data'], true);
            if(!empty($extension_data) && is_array($extension_data))
            {
                foreach($extension_data as $v)
                {
                    if(!empty($v) && is_array($v) && isset($v['business']) && !empty($v['ext']))
                    {
                        switch($v['business'])
                        {
                            // 积分兑换
                            case 'plugins-points-exchange' :
                                $ret = self::UserIntegralDec($params['order']['user_id'], $v['ext'], '积分兑换');
                                break;

                            // 积分抵扣
                            case 'plugins-points-deduction' :
                                $ret = self::UserIntegralDec($params['order']['user_id'], $v['ext'], '积分抵扣');
                                break;
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * 用户积分扣除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-21
     * @desc    description
     * @param   [int]          $user_id  [用户id]
     * @param   [int]          $integral [积分]
     * @param   [string]       $title    [名称]
     */
    public static function UserIntegralDec($user_id, $integral, $title)
    {
        // 用户积分增加
        $user_integral = Db::name('User')->where(['id'=>$user_id])->value('integral');
        if(!Db::name('User')->where(['id'=>$user_id])->dec('integral', $integral)->update())
        {
            return DataReturn('用户积分扣除失败', -1);
        }

        // 积分日志
        $res = IntegralService::UserIntegralLogAdd($user_id, $user_integral, $integral, $title, 0);
        if(!$res)
        {
            return DataReturn('积分日志记录失败', -1);
        }

        // 更新用户登录缓存数据
        UserService::UserLoginRecord($user_id);

        return DataReturn('success', 0);
    }

    /**
     * 订单状态改变处理,状态为取消|关闭时释放积分
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-21
     * @desc    description
     * @param   [int]          $order_id [订单id]
     */
    public static function OrderStatusChangeHandle($order_id)
    {
        $order = Db::name('Order')->where(['id'=>intval($order_id)])->field('id,user_id,status,extension_data')->find();
        if(!empty($order))
        {
            $extension_data = json_decode($order['extension_data'], true);
            if(!empty($extension_data) && is_array($extension_data))
            {
                foreach($extension_data as $v)
                {
                    if(!empty($v) && is_array($v) && isset($v['business']) && !empty($v['ext']))
                    {
                        switch($v['business'])
                        {
                            // 积分兑换
                            case 'plugins-points-exchange' :
                                self::UserIntegralInc($order['user_id'], $v['ext'], '积分兑换退回');
                                break;

                            // 积分抵扣
                            case 'plugins-points-deduction' :
                                self::UserIntegralInc($order['user_id'], $v['ext'], '积分抵扣退回');
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * 用户积分增加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-21
     * @desc    description
     * @param   [int]          $user_id  [用户id]
     * @param   [int]          $integral [积分]
     * @param   [string]       $title    [名称]
     */
    public static function UserIntegralInc($user_id, $integral, $title)
    {
        // 用户积分增加
        $user_integral = Db::name('User')->where(['id'=>$user_id])->value('integral');
        Db::name('User')->where(['id'=>$user_id])->inc('integral', $integral)->update();

        // 积分日志
        IntegralService::UserIntegralLogAdd($user_id, $user_integral, $integral, $title, 1);

        // 更新用户登录缓存数据
        UserService::UserLoginRecord($user_id);
    }
}
?>