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
namespace app\plugins\distribution\service;

use think\facade\Db;
use app\service\UserService;
use app\service\MessageService;
use app\service\IntegralService;
use app\plugins\distribution\service\BaseService;
use app\plugins\distribution\service\LevelService;

/**
 * 分销 - 收益服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class ProfitService
{
    /**
     * 订单关闭
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [int]          $order_id [订单id]
     * @param   [array]        $params   [订单日志数据]
     */
    public static function OrderProfitClose($order_id, $params)
    {
        // 原因
        $msg = ($params['new_status'] == 5) ? '订单取消' : '订单关闭';

        // 佣金订单关闭
        $upd_data = [
            'status'    => 3,
            'msg'       => $msg,
            'upd_time'  => time(),
        ];
        if(Db::name('PluginsDistributionProfitLog')->where(['order_id'=>$order_id])->update($upd_data) === false)
        {
            return DataReturn('分销佣金订单关闭失败', -1);
        }

        // 积分退回、获取积分订单
        $data = Db::name('PluginsDistributionIntegralLog')->where(['order_id'=>$order_id, 'status'=>1])->select()->toArray();
        if(!empty($data))
        {
            // 批量关闭订单
            $upd_data = [
                'status'    => 2,
                'msg'       => $msg,
                'upd_time'  => time(),
            ];
            if(Db::name('PluginsDistributionIntegralLog')->where(['id'=>array_column($data, 'id'), 'status'=>1])->update($upd_data) === false)
            {
                return DataReturn('分销积分订单关闭失败', -1);
            }

            // 用户积分
            $user_integral = Db::name('User')->where(['id'=>array_column($data, 'user_id')])->column('integral', 'id');

            // 循环退回积分
            foreach($data as $v)
            {
                if(isset($user_integral[$v['user_id']]))
                {
                    // 积分减少
                    if(!Db::name('User')->where(['id'=>$v['user_id']])->dec('integral', $v['integral'])->update())
                    {
                        return DataReturn('分销积分退回失败', -10);
                    }

                    // 积分日志
                    if(!IntegralService::UserIntegralLogAdd($v['user_id'], $user_integral[$v['user_id']], $v['integral'], '分销推广', 0))
                    {
                        return DataReturn('分销积分日志添加失败', -1);
                    }
                }
            }
        }
        
        return DataReturn('success', 0);
    }

    /**
     * 订单支付成功
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [int]          $order_id [订单id]
     * @param   [array]        $data     [订单日志数据]
     * @param   [array]        $config   [配置信息]
     */
    public static function OrderProfitValid($order_id, $data, $config)
    {
        // 佣金订单处理
        $ret = self::OrderProfitStatusHandle($order_id, $data, $config);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 积分发放
        $data = Db::name('PluginsDistributionIntegralLog')->where(['order_id'=>$order_id, 'status'=>0])->select()->toArray();
        if(!empty($data))
        {
            foreach($data as $v)
            {
                // 发放积分
                $ret = self::UserIntegralSend($v['user_id'], $v['order_id'], $v['integral']);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
            }

            // 更新发放状态
            $upd_data = [
                'status'    => 1,
                'upd_time'  => time(),
            ];
            if(Db::name('PluginsDistributionIntegralLog')->where(['order_id'=>$order_id, 'status'=>0])->update($upd_data) === false)
            {
                return DataReturn('分销积分生效失败', -1);
            }
        }

        // 指定商品阶梯返佣订单添加
        $ret = self::AppointProfitLadderOrderInsert($order_id, $config);
        if(isset($ret['code']) && $ret['code'] != 0)
        {
            return $ret;
        }

        return DataReturn('操作成功', 0);
    }

    /**
     * 返佣订单处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [int]          $order_id [订单id]
     * @param   [array]        $data     [订单日志数据]
     * @param   [array]        $config   [配置信息]
     */
    public static function OrderProfitStatusHandle($order_id, $data, $config)
    {
        // 重新计算订单佣金
        $ret = self::OrderChange(['order_id'=>$order_id]);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 更新佣金订单状态
        $upd_data = [
            'status'    => 1,
            'upd_time'  => time(),
        ];
        if(Db::name('PluginsDistributionProfitLog')->where(['order_id'=>$order_id])->update($upd_data) === false)
        {
            return DataReturn('分销订单生效失败', -1);
        }

        return DataReturn('操作成功', 0);
    }

    /**
     *  用户积分发放
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-10
     * @desc    description
     * @param   [int]          $user_id  [用户id]
     * @param   [int]          $order_id [订单id]
     * @param   [int]          $integral [积分]
     */
    public static function UserIntegralSend($user_id, $order_id, $integral)
    {
        // 获取用户
        $user = Db::name('User')->find($user_id);
        if(empty($user))
        {
            return DataReturn('分销发放积分用户不存在', -1);
        }

        // 积分增加
        if(!Db::name('User')->where(['id'=>$user_id])->inc('integral', $integral)->update())
        {
            return DataReturn('分销积分发放失败', -10);
        }

        // 发放积分
        if(!IntegralService::UserIntegralLogAdd($user['id'], $user['integral'], $integral, '分销推广', 1))
        {
            return DataReturn('分销积分日志添加失败', -1);
        }
        return DataReturn('发放成功', 0);
    }

    /**
     * 佣金订单添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     * @param   [array]           $config [插件配置]
     */
    public static function ProfitOrderInsert($params = [], $config = [])
    {
        // 基础返佣
        $ret = self::BaseProfitOrderInsert($params, $config);
        if(isset($ret['code']) && $ret['code'] != 0)
        {
            return $ret;
        }

        // 指定商品购买返现
        $ret = self::AppointProfitOrderInsert($params, $config);
        if(isset($ret['code']) && $ret['code'] != 0)
        {
            return $ret;
        }

        // 指定商品满足销售数量后返佣
        $ret = self::AppointProfitSaleRateOrderInsert($params, $config);
        if(isset($ret['code']) && $ret['code'] != 0)
        {
            return $ret;
        }

        return DataReturn('success', 0);
    }

    /**
     * 指定商品阶梯返佣订单添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-22-24
     * @desc    description
     * @param   [id]              $order_id [订单id]
     * @param   [array]           $config   [配置信息]
     */
    public static function AppointProfitLadderOrderInsert($order_id = 0, $config = [])
    {
        // 参数判断
        if(!empty($order_id) && !empty($config['is_appoint_goods']) && !empty($config['appoint_ladder_goods_ids']))
        {
            // 订单信息
            $order = Db::name('Order')->find($order_id);
            if(empty($order))
            {
                return DataReturn('订单数据为空[阶梯返佣]', -1);
            }
            $goods = Db::name('OrderDetail')->where(['order_id'=>$order_id])->select()->toArray();
            if(empty($goods))
            {
                return DataReturn('订单商品数据为空[阶梯返佣]', -1);
            }

            // 有效商品id
            $goods_ids = array_intersect(array_column($goods, 'goods_id'), $config['appoint_ladder_goods_ids']);
            if(empty($goods_ids))
            {
                return DataReturn('商品不在配置范围', 0);
            }

            // 获取上级用户
            $referrer = Db::name('User')->where(['id'=>$order['user_id']])->value('referrer');
            if(empty($referrer))
            {
                return DataReturn('非邀请用户', 0);
            }

            // 用户分销等级
            $user_level = BaseService::UserDistributionLevel($referrer);
            if($user_level['code'] == 0)
            {
                // 获取级别信息
                $lv = BaseService::AppointProfitLadderOrderLevel($config, $referrer, $config['appoint_ladder_goods_ids']);

                // 返佣规则
                if(!empty($lv) && isset($lv['level']) && (isset($lv['current']['rate']) || isset($lv['current']['price'])))
                {
                    if($lv['current']['rate'] > 0 || $lv['current']['price'] > 0)
                    {
                        // 购买商品是否包含
                        $profit_price = 0;
                        if($lv['current']['price'] > 0)
                        {
                            $profit_price += $lv['current']['price'];
                        } else {
                            // 获取订单详情商品金额
                            $where = [
                                ['o.id', '=', $order_id],
                                ['od.goods_id', 'in', $goods_ids],
                            ];
                            $profit_price += Db::name('Order')->alias('o')->join('order_detail od', 'o.id=od.order_id')->where($where)->avg('od.price')*($lv['current']['rate']/100);
                        }

                        // 添加佣金记录
                        $ret = self::AppointProfitInsert($order_id, $referrer, $order, $profit_price, implode(',', $goods_ids), 10, $lv['current']['rate']);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                    }

                    // 添加记录，不管是否配置了返佣都需要增加记录、避免记录丢失导致层级计算错误
                    $log_data = [
                        'user_id'   => $referrer,
                        'order_id'  => $order_id,
                        'level'     => $lv['level'],
                        'add_time'  => time(),
                    ];
                    $log_id = Db::name('PluginsDistributionAppointLadderLog')->insertGetId($log_data);
                    if($log_id <= 0)
                    {
                        return DataReturn('阶梯返佣日志添加失败', -1);
                    }
                    $log_goods_data = [];
                    foreach($goods_ids as $gid)
                    {
                        $log_goods_data[] = [
                            'log_id'    => $log_id,
                            'goods_id'  => $gid,
                        ];
                    }
                    $res = Db::name('PluginsDistributionAppointLadderLogGoods')->insertAll($log_goods_data);
                    if($res < count($log_goods_data))
                    {
                        return DataReturn('阶梯返佣日志添加失败', -1);
                    }

                    return DataReturn('处理成功', 0);
                }
            }
        }
        return DataReturn('无需返佣', 0);
    }

    /**
     * 指定商品满足销售数量后返佣订单添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     * @param   [array]           $config [配置信息]
     */
    public static function AppointProfitSaleRateOrderInsert($params = [], $config = [])
    {
        // 参数判断
        if(!empty($params['order_id']) && !empty($params['order']) && !empty($params['order']['user_id']) && !empty($params['goods']) && !empty($config['is_appoint_goods']) && !empty($config['appoint_goods_sale_number']) && !empty($config['appoint_sale_goods_ids']) && (!empty($config['appoint_goods_sale_price']) || !empty($config['appoint_goods_sale_rate'])))
        {
            // 基础参数
            $user_id = $params['order']['user_id'];
            $goods_ids = array_column($params['goods'], 'goods_id');

            // 获取上级用户
            $referrer = Db::name('User')->where(['id'=>$user_id])->value('referrer');
            if(empty($referrer))
            {
                return DataReturn('非邀请用户', 0);
            }

            // 用户分销等级
            $user_level = BaseService::UserDistributionLevel($referrer);
            if($user_level['code'] == 0)
            {
                // 固定金额
                $price = empty($config['appoint_goods_sale_price']) ? 0 : $config['appoint_goods_sale_price'];

                // 比例
                $rate = empty($config['appoint_goods_sale_rate']) ? 0 : $config['appoint_goods_sale_rate']/100;

                // 购买商品是否包含
                foreach($goods_ids as $gid)
                {
                    if(in_array($gid, $config['appoint_sale_goods_ids']))
                    {
                        // 获取推广总数
                        $where = [
                            ['od.goods_id', '=', $gid],
                            ['u.referrer', '=', $referrer],
                            ['o.status', '<=', 4],
                        ];
                        // 是否按照用户纬度计算
                        if(isset($config['appoint_goods_sale_is_user']) && $config['appoint_goods_sale_is_user'] == 1)
                        {
                            $where[] = ['o.user_id', '=', $user_id];
                        }
                        $count = Db::name('User')->alias('u')->join('order o', 'u.id=o.user_id')->join('order_detail od', 'o.id=od.order_id')->where($where)->sum('od.buy_number');
                        if($count > $config['appoint_goods_sale_number'])
                        {
                            if($price > 0)
                            {
                                $profit_price = $price;
                            } else {
                                // 获取订单详情商品金额
                                $where = [
                                    ['o.id', '=', $params['order_id']],
                                    ['od.goods_id', '=', $gid],
                                ];
                                $profit_price = Db::name('Order')->alias('o')->join('order_detail od', 'o.id=od.order_id')->where($where)->avg('od.price')*$rate;
                            }
                            // 添加佣金记录
                            $ret = self::AppointProfitInsert($params['order_id'], $referrer, $params['order'], $profit_price, $gid, 9, $config['appoint_goods_sale_rate']);
                            if($ret['code'] != 0)
                            {
                                return $ret;
                            }
                        }
                    }
                }
                return DataReturn('返佣成功', 0);
            }
        }
        return DataReturn('无需返佣', 0);
    }

    /**
     * 指定商品购买满足数量返现订单添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     * @param   [array]           $config [配置信息]
     */
    public static function AppointProfitOrderInsert($params = [], $config = [])
    {
        // 参数判断
        if(!empty($params['order_id']) && !empty($params['order']) && !empty($params['order']['user_id']) && !empty($params['goods']) && !empty($config['is_appoint_goods']) && !empty($config['appoint_goods_profit_number']) && !empty($config['appoint_profit_goods_ids']))
        {
            // 基础参数
            $user_id = $params['order']['user_id'];
            $goods_ids = array_column($params['goods'], 'goods_id');

            // 获取上级用户
            $referrer = Db::name('User')->where(['id'=>$user_id])->value('referrer');
            if(empty($referrer))
            {
                return DataReturn('非邀请用户', 0);
            }

            // 用户分销等级
            $user_level = BaseService::UserDistributionLevel($referrer);
            if($user_level['code'] == 0)
            {
                // 类型
                $level = 8;

                // 购买商品是否包含
                foreach($goods_ids as $gid)
                {
                    if(in_array($gid, $config['appoint_profit_goods_ids']))
                    {
                        // 收益是否已经存在
                        $temp_count = Db::name('PluginsDistributionProfitLog')->where(['user_id'=>$referrer, 'user_level_id'=>$gid, 'level'=>$level, 'status'=>[0,1,2]])->count();
                        if($temp_count > 0)
                        {
                            continue;
                        }

                        // 计算当前商品被用户购买次数
                        // 订单状态（0待确认, 1已确认/待支付, 2已支付/待发货, 3已发货/待收货, 4已完成, 5已取消, 6已关闭）
                        $where = [
                            ['od.goods_id', '=', $gid],
                            ['u.referrer', '=', $referrer],
                            ['o.status', '<=', 4],
                        ];
                        $count = Db::name('User')->alias('u')->join('order o', 'u.id=o.user_id')->join('order_detail od', 'o.id=od.order_id')->where($where)->count('DISTINCT o.user_id');
                        if($count == $config['appoint_goods_profit_number'])
                        {
                            // 满足条件增加收益订单
                            // 最新一个订单商品价格
                            $profit_price = Db::name('User')->alias('u')->join('order o', 'u.id=o.user_id')->join('order_detail od', 'o.id=od.order_id')->where($where)->order('o.id desc')->value('od.price');
                            $ret = self::AppointProfitInsert($params['order_id'], $referrer, $params['order'], $profit_price, $gid, $level, 100);
                            if($ret['code'] != 0)
                            {
                                return $ret;
                            }
                        }
                    }
                }
            }
        }
        return DataReturn('无需返现', 0);
    }

    /**
     * 收益添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-06-10
     * @desc    description
     * @param   [int]               $order_id       [订单id]
     * @param   [int]               $user_id        [受益人用户id]
     * @param   [array]             $order          [订单数据]
     * @param   [float]             $profit_price   [收益金额]
     * @param   [int]               $goods_id       [商品id]
     * @param   [int]               $level          [等级类型（8指定商品返现、9指定商品销售返佣）]
     * @param   [int]               $level          [返佣比例]
     */
    private static function AppointProfitInsert($order_id, $user_id, $order, $profit_price, $goods_id, $level, $rate = 100)
    {
        // 日志数据
        $data = [
            'user_id'           => $user_id,
            'order_id'          => $order_id,
            'order_user_id'     => $order['user_id'],
            'total_price'       => $order['total_price'],
            'profit_price'      => $profit_price,
            'rate'              => $rate,
            'level'             => $level,
            'user_level_id'     => $goods_id,
            'status'            => 0,
            'spec_extends'      => '',
            'add_time'          => time(),
        ];
        $log_id = Db::name('PluginsDistributionProfitLog')->insertGetId($data);
        if($log_id > 0)
        {
            // 获取订单用户昵称
            $user = UserService::GetUserViewInfo($order['user_id']);

            // 消息通知
            $user_name_view = (empty($user) || empty($user['user_name_view'])) ? '' : $user['user_name_view'];
            $msg = $user_name_view.'用户下单'.$data['total_price'].'π, 预计收益'.$data['profit_price'].'π';
            MessageService::MessageAdd($user_id, '分销收益新增', $msg, BaseService::$message_business_type, $log_id);
            return DataReturn('分销订单添加成功', 0);
        }
        return DataReturn('分销订单添加失败', -1);
    }

    /**
     * 基础佣金订单添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     * @param   [array]           $config [配置信息]
     */
    public static function BaseProfitOrderInsert($params = [], $config = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'order_id',
                'error_msg'         => '订单id为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'order',
                'error_msg'         => '订单信息为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'goods',
                'error_msg'         => '订单相关商品为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 状态校验
        if(!in_array($params['order']['status'], [0,1,2]))
        {
            return DataReturn('佣金订单创建仅支持状态[待确认/待支付/已支付]', -1);
        }

        // 返佣类型
        // 判断是否仅首单返佣
        if(isset($config['profit_type']) && $config['profit_type'] == 1)
        {
            $where = [
                ['user_id', '=', $params['order']['user_id']],
                ['status', '<=', 4],
            ];
            $count = (int) Db::name('Order')->where($where)->count('id');
            if($count > 1)
            {
                return DataReturn('无需返佣[当前条件仅首单返佣、下单用户非首单]', 0);
            }
        }

        // 是否多商户订单未开启返佣
        $shop_id = 0;
        $is_profit_shop = (isset($config['is_profit_shop']) && $config['is_profit_shop'] == 1) ? 1 : 0;
        // 店铺id
        $order = Db::name('Order')->where(['id'=>$params['order_id']])->find();
        if(!empty($order['shop_id']))
        {
            // 是否多商户订单未开启返佣
            if($is_profit_shop != 1)
            {
                return DataReturn('多商户订单未开启返佣、无需处理', 0);
            }
            // 店铺返佣配置
            $shop_level = Db::name('PluginsDistributionLevelShop')->where(['shop_id'=>$order['shop_id']])->find();
            if(empty($shop_level))
            {
                return DataReturn('多商户未配置返佣、无需处理', 0);
            }
            if(!isset($shop_level['is_enable']) || $shop_level['is_enable'] != 1)
            {
                return DataReturn('多商户返佣未开启、无需处理', 0);
            }
            if(empty($shop_level['config']))
            {
                return DataReturn('多商户返佣等级未配置、无需处理', 0);
            }
            $shop_level['config'] = json_decode($shop_level['config'], true);
            $shop_id = $order['shop_id'];
        }

        // 支持向下返佣
        $is_profit_down_return_shop = ($is_profit_shop == 1 && isset($config['is_profit_down_return_shop']) && $config['is_profit_down_return_shop'] == 1) ? 1 : 0;
        // 支持内购返佣
        $is_profit_self_buy_shop = ($is_profit_shop == 1 && isset($config['is_profit_self_buy_shop']) && $config['is_profit_self_buy_shop'] == 1) ? 1 : 0;

        // 订单商品规格扩展数据
        $spec_extends = self::OrderDetailGopodsSpecExtends($params['goods'], $config);

        // 是否开启内购
        if(isset($config['self_buy']) && $config['self_buy'] == 1 && (empty($shop_id) || ($is_profit_self_buy_shop == 1 && !empty($shop_id))))
        {
            // 下单用户分销等级
            $user_level = BaseService::UserDistributionLevel($params['order']['user_id']);
            if($user_level['code'] == 0)
            {
                // 多商户返佣配置
                if($is_profit_self_buy_shop == 1 && !empty($shop_id))
                {
                    $user_level['data'] = LevelService::ShopLevelItemDataHandle($user_level['data'], $shop_level['config']);
                }

                // 添加下单用户收益
                $profit_price = self::SelfBuyProfitPriceCalculation($params['order'], $user_level['data']['id'], $user_level['data']['self_buy_rate'], $spec_extends);
                $ret = self::ProfitInsert($params['order_id'], $params['order']['user_id'], $params['order'], $user_level['data']['id'], $user_level['data']['self_buy_rate'], 4, $profit_price, $spec_extends);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
            }
        }

        // 正常返佣模式
        $is_normal_profit_status = true;

        // 订单模式是否为自提、是否开启自提点返佣
        if($params['order']['order_model'] == 2 && isset($config['is_self_extraction']) && $config['is_self_extraction'] == 1 && empty($shop_id))
        {
            // 获取取货点用户id,无数据则是系统自带的自提地址
            $user_id = Db::name('PluginsDistributionUserSelfExtractionOrder')->where(['order_id'=>$params['order_id']])->value('user_id');
            if(!empty($user_id))
            {
                // 获取到了自提点用户，并且未开启不再向上返佣、则可继续正常向上返佣
                if(isset($config['is_self_extraction_close_upper']) && $config['is_self_extraction_close_upper'] == 1)
                {
                    $is_normal_profit_status = false;
                }

                // 用户的分销等级
                $user_level = BaseService::UserDistributionLevel($user_id);
                if($user_level['code'] == 0)
                {
                    // 添加收益
                    $profit_price = self::SelfExtractionProfitPriceCalculation($params['order'], $user_level['data']['id'], $user_level['data']['force_current_user_rate_one'], $spec_extends, 1);
                    $ret = self::ProfitInsert($params['order_id'], $user_id, $params['order'], $user_level['data']['id'], $user_level['data']['force_current_user_rate_one'], 5, $profit_price, $spec_extends);
                    if($ret['code'] != 0)
                    {
                        return $ret;
                    }
                }

                // 取货点返佣层级
                $extraction_profit_level = isset($config['extraction_profit_level']) ? intval($config['extraction_profit_level']) : 2;

                // 上二级用户id
                if($extraction_profit_level == 1)
                {
                    $user_id = Db::name('User')->where(['id'=>$user_id])->value('referrer');
                    if(!empty($user_id))
                    {
                        // 上二级分销等级
                        $user_level = BaseService::UserDistributionLevel($user_id);
                        if($user_level['code'] == 0)
                        {
                            // 添加上一级收益
                            $profit_price = self::SelfExtractionProfitPriceCalculation($params['order'], $user_level['data']['id'], $user_level['data']['force_current_user_rate_two'], $spec_extends, 2);
                            $ret = self::ProfitInsert($params['order_id'], $user_id, $params['order'], $user_level['data']['id'], $user_level['data']['force_current_user_rate_two'], 6, $profit_price, $spec_extends);
                            if($ret['code'] != 0)
                            {
                                return $ret;
                            }
                        }

                        // 上三级用户id
                        if($extraction_profit_level == 2)
                        {
                            $user_id = Db::name('User')->where(['id'=>$user_id])->value('referrer');
                            if(!empty($user_id))
                            {
                                // 上三级分销等级
                                $user_level = BaseService::UserDistributionLevel($user_id);
                                if($user_level['code'] == 0 && !empty($user_level['data']))
                                {
                                    // 添加上一级收益
                                    $profit_price = self::SelfExtractionProfitPriceCalculation($params['order'], $user_level['data']['id'], $user_level['data']['force_current_user_rate_three'], $spec_extends, 3);
                                    $ret = self::ProfitInsert($params['order_id'], $user_id, $params['order'], $user_level['data']['id'], $user_level['data']['force_current_user_rate_three'], 7, $profit_price, $spec_extends);
                                    if($ret['code'] != 0)
                                    {
                                        return $ret;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // 正常向上分销结算
        // 是否开启向上返佣
        if($is_normal_profit_status && isset($config['is_upper_return']) && $config['is_upper_return'] == 1 && (empty($shop_id) || ($is_profit_shop == 1 && !empty($shop_id))))
        {
            // 上一级用户id
            $user_id = Db::name('User')->where(['id'=>$params['order']['user_id']])->value('referrer');
            if(!empty($user_id))
            {
                // 上一级分销等级
                $user_level = BaseService::UserDistributionLevel($user_id);
                if($user_level['code'] == 0)
                {
                    // 多商户返佣配置
                    if($is_profit_shop == 1 && !empty($shop_id))
                    {
                        $user_level['data'] = LevelService::ShopLevelItemDataHandle($user_level['data'], $shop_level['config']);
                    }

                    // 添加上一级收益
                    $profit_price = self::UpperReturnProfitPriceCalculation($params['order'], $user_level['data']['id'], $user_level['data']['level_rate_one'], $spec_extends, 1);
                    $ret = self::ProfitInsert($params['order_id'], $user_id, $params['order'], $user_level['data']['id'], $user_level['data']['level_rate_one'], 1, $profit_price, $spec_extends);
                    if($ret['code'] != 0)
                    {
                        return $ret;
                    }
                }

                // 分销层级
                $level = isset($config['level']) ? intval($config['level']) : 2;

                // 上二级用户id
                if($level > 0)
                {
                    $user_id = Db::name('User')->where(['id'=>$user_id])->value('referrer');
                    if(!empty($user_id))
                    {
                        // 上二级分销等级
                        $user_level = BaseService::UserDistributionLevel($user_id);
                        if($user_level['code'] == 0)
                        {
                            // 多商户返佣配置
                            if($is_profit_shop == 1 && !empty($shop_id))
                            {
                                $user_level['data'] = LevelService::ShopLevelItemDataHandle($user_level['data'], $shop_level['config']);
                            }

                            // 添加上一级收益
                            $profit_price = self::UpperReturnProfitPriceCalculation($params['order'], $user_level['data']['id'], $user_level['data']['level_rate_two'], $spec_extends, 2);
                            $ret = self::ProfitInsert($params['order_id'], $user_id, $params['order'], $user_level['data']['id'], $user_level['data']['level_rate_two'], 2, $profit_price, $spec_extends);
                            if($ret['code'] != 0)
                            {
                                return $ret;
                            }
                        }

                        // 上三级用户id
                        if($level > 1)
                        {
                            $user_id = Db::name('User')->where(['id'=>$user_id])->value('referrer');
                            if(!empty($user_id))
                            {
                                // 上三级分销等级
                                $user_level = BaseService::UserDistributionLevel($user_id);
                                if($user_level['code'] == 0 && !empty($user_level['data']))
                                {
                                    // 多商户返佣配置
                                    if($is_profit_shop == 1 && !empty($shop_id))
                                    {
                                        $user_level['data'] = LevelService::ShopLevelItemDataHandle($user_level['data'], $shop_level['config']);
                                    }

                                    // 添加上一级收益
                                    $profit_price = self::UpperReturnProfitPriceCalculation($params['order'], $user_level['data']['id'], $user_level['data']['level_rate_three'], $spec_extends, 3);
                                    $ret = self::ProfitInsert($params['order_id'], $user_id, $params['order'], $user_level['data']['id'], $user_level['data']['level_rate_three'], 3, $profit_price, $spec_extends);
                                    if($ret['code'] != 0)
                                    {
                                        return $ret;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // 是否开启向下返佣
        if(isset($config['is_down_return']) && $config['is_down_return'] == 1 && (empty($shop_id) || ($is_profit_down_return_shop == 1 && !empty($shop_id))))
        {
            // 下单用户分销等级
            $user_level = BaseService::UserDistributionLevel($params['order']['user_id']);
            if($user_level['code'] == 0)
            {
                // 多商户返佣配置
                if($is_profit_down_return_shop == 1 && !empty($shop_id))
                {
                    $user_level['data'] = LevelService::ShopLevelItemDataHandle($user_level['data'], $shop_level['config']);
                }

                // 获取向下返佣金额
                $profit_price = self::DownReturnProfitPriceCalculation($params['order'], $user_level['data']['id'], $user_level['data']['down_return_rate'], $spec_extends);

                // 用户佣金分摊发放
                if($profit_price > 0)
                {
                    $user_ids = self::DownReturnUsers($params['order']['user_id'], $profit_price, $config);
                    if(!empty($user_ids))
                    {
                        $user_count = count($user_ids);
                        $user_profit_price = PriceNumberFormat($profit_price/$user_count);
                        $temp_price_total = $user_profit_price*$user_count;
                        foreach($user_ids as$uk=>$uid)
                        {
                            // 用户佣金
                            $temp_user_profit_price = $user_profit_price;

                            // 第一个用户获得溢出金额，也可能少于丢失的金额
                            if($uk == 0)
                            {
                                // 计算的总额大于佣金，则第一个用户减掉多余的溢出金额
                                if($temp_price_total > $profit_price)
                                {
                                    $temp_user_profit_price -= $temp_price_total-$profit_price;

                                // 计算的总额小于佣金，则第一个用户加上丢失的精度金额
                                } elseif($temp_price_total < $profit_price)
                                {
                                    $temp_user_profit_price += $profit_price-$temp_price_total;
                                }
                            }
                            
                            // 添加佣金
                            $ret = self::ProfitInsert($params['order_id'], $uid, $params['order'], $user_level['data']['id'], $user_level['data']['down_return_rate'], 0, $temp_user_profit_price, $spec_extends);
                            if($ret['code'] != 0)
                            {
                                return $ret;
                            }
                        }
                    }
                }
            }
        }

        // 向上发放积分
        if(isset($config['is_upper_return_integral']) && $config['is_upper_return_integral'] == 1 && !empty($config['upper_return_integral']) && empty($shop_id))
        {
            $ret = self::UpperReturnIntegral($params['order']['user_id'], $params['order_id'], $config['upper_return_integral']);
            if($ret['code'] != 0)
            {
                return $ret;
            }
        }

        // 向下级发放积分
        if(isset($config['is_down_return_integral']) && $config['is_down_return_integral'] == 1 && !empty($config['down_return_integral']) && empty($shop_id))
        {
            $down_return_integral_people = max(1, isset($config['down_return_integral_people']) ? intval($config['down_return_integral_people']) : 1);
            $ret = self::DownReturnIntegral($params['order']['user_id'], $params['order_id'], $config['down_return_integral'], $down_return_integral_people);
            if($ret['code'] != 0)
            {
                return $ret;
            }
        }

        return DataReturn('操作成功', 0);
    }

    /**
     * 向下发放积分
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-09
     * @desc    description
     * @param   [int]          $user_id  [用户id]
     * @param   [int]          $order_id [订单id]
     * @param   [int]          $integral [积分]
     * @param   [int]          $people   [分摊人数]
     */
    public static function DownReturnIntegral($user_id, $order_id, $integral, $people)
    {
        // 分摊人数不能大于积分数量
        if($people > $integral)
        {
            $people = $integral;
        }

        // 获取用户
        $user = Db::name('User')->where('referrer', '=', $user_id)->field('id,integral')->limit($people)->order('id desc')->select()->toArray();
        if(!empty($user))
        {
            $count = count($user);
            $value = intval($integral/$count);
            $temp_value = $value*$count;
            foreach($user as $uk=>$uv)
            {
                // 用户所得积分
                $user_integral = $value;

                // 第一个用户获得溢出数量，也可能少于丢失的数量
                if($uk == 0)
                {
                    // 计算的总数大于积分，则第一个用户减掉多余的溢出数量
                    if($temp_value > $integral)
                    {
                        $user_integral -= $temp_value-$integral;

                    // 计算的总数小于积分，则第一个用户加上丢失的精度数量
                    } elseif($temp_value < $integral)
                    {
                        $user_integral += $integral-$temp_value;
                    }
                }

                // 添加积分日志
                $ret = self::UserIntegralLogInsert($uv['id'], $order_id, $user_integral);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
            }
        }
        return DataReturn('操作成功', 0);
    }

    /**
     * 向上发放积分
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-09
     * @desc    description
     * @param   [int]          $user_id  [用户id]
     * @param   [int]          $order_id [订单id]
     * @param   [int]          $integral [积分]
     */
    public static function UpperReturnIntegral($user_id, $order_id, $integral)
    {
        // 上一级用户id
        $parent_user_id = Db::name('User')->where(['id'=>$user_id])->value('referrer');
        if(!empty($parent_user_id))
        {
            // 添加积分日志
            $ret = self::UserIntegralLogInsert($parent_user_id, $order_id, $integral);
            if($ret['code'] != 0)
            {
                return $ret;
            }
        }
        return DataReturn('操作成功', 0);
    }

    /**
     * 添加积分日志
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-10
     * @desc    description
     * @param   [int]          $user_id  [用户id]
     * @param   [int]          $order_id [订单id]
     * @param   [int]          $integral [积分]
     */
    public static function UserIntegralLogInsert($user_id, $order_id, $integral)
    {
        // 添加积分日志
        $data = [
            'order_id'      => $order_id,
            'user_id'       => $user_id,
            'integral'      => $integral,
            'status'       => 0,
            'add_time'      => time(),
        ];
        if(Db::name('PluginsDistributionIntegralLog')->insertGetId($data) <= 0)
        {
            return DataReturn('积分发放日志添加失败', -1);
        }

        return DataReturn('操作成功', 0);
    }

    /**
     * 获取用户
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-10
     * @desc    description
     * @param   [int]            $user_id       [用户 id]
     * @param   [float]          $profit_price  [返佣金额]
     * @param   [array]          $base          [插件基础配置]
     */
    public static function DownReturnUsers($user_id, $profit_price, $base)
    {
        // 配置的返佣数量
        $down_return_number = isset($base['down_return_number']) ? intval($base['down_return_number']) : 0;

        // 金额最大数量计算
        $profit_price_number = ($profit_price > 0) ? intval($profit_price*100) : 0;

        // 配置的数量不能大于系统计算的数量
        if($down_return_number > $profit_price_number)
        {
            $down_return_number = $profit_price_number;
        }

        // 获取用户 id
        return Db::name('User')->where('referrer', '=', $user_id)->limit($down_return_number)->order('id desc')->column('id');
    }

    /**
     * 计算自提点返佣金额
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-10
     * @desc    description
     * @param   [aray]           $order         [订单信息]
     * @param   [int]            $user_level_id [用户等级id]
     * @param   [float]          $level_rate    [当前用户等级默认分佣比例]
     * @param   [array|json]     $spec_extends  [规格扩展信息]
     * @param   [int]            $profit_level  [当前需要计算佣金的等级（1~3）]
     */
    public static function SelfExtractionProfitPriceCalculation($order, $user_level_id, $level_rate, $spec_extends, $profit_level)
    {
        // 增加的金额
        // 优惠金额
        $increase_price = isset($order['increase_price']) ? $order['increase_price'] : 0;
        $preferential_price = isset($order['preferential_price']) ? $order['preferential_price'] : 0;

        // 不参与计算佣金的金额
        $no_participation_total = self::OrderNoParticipationTotal($order['extension_data']);

        // 检查是否存在规格配置分销佣金
        $spec_profit_price = 0;
        if(!empty($spec_extends))
        {
            if(!is_array($spec_extends))
            {
                $spec_extends = json_decode($spec_extends, true);
            }
            if(!empty($spec_extends) && is_array($spec_extends))
            {
                // 是否存在商品自定义返佣配置
                if(array_sum(array_column($spec_extends, 'is_distribution_force_current_user')) > 0)
                {
                    // 不参与返佣的金额
                    $preferential_price += $no_participation_total;

                    // 规格总价
                    $spec_total_price = array_sum(array_column($spec_extends, 'total_price'));
                    foreach($spec_extends as $ev)
                    {

                        // 存在退款金额则减去退款金额后再计算
                        if(isset($ev['refund_price']) && $ev['refund_price'] > 0)
                        {
                            $ev['total_price'] -= $ev['refund_price'];
                        }

                        // 总额大于0则计算
                        if($ev['total_price'] > 0)
                        {
                            // 处理商品规格自定义返佣
                            $is_ext_profit = false;
                            $profit_price = 0;
                            if(isset($ev['is_distribution_force_current_user']) && $ev['is_distribution_force_current_user'] == 1 && !empty($ev['extends_force_current_user']))
                            {
                                if(!is_array($ev['extends_force_current_user']))
                                {
                                    $ev['extends_force_current_user'] = json_decode($ev['extends_force_current_user'], true);
                                }

                                // 根据当前规则计算收益
                                // 商品规格自定义配置从0索引开始计算，所以这里佣金等级需要减一
                                $temp_profit_level = $profit_level-1;
                                if(!empty($ev['extends_force_current_user']) && array_key_exists($user_level_id, $ev['extends_force_current_user']))
                                {
                                    $rules_all = explode("\n", $ev['extends_force_current_user'][$user_level_id]);
                                    if(!empty($rules_all) && array_key_exists($temp_profit_level, $rules_all))
                                    {
                                        $rules = explode("|", $rules_all[$temp_profit_level]);
                                        if(!empty($rules) && is_array($rules) && count($rules) == 2)
                                        {
                                            switch($rules[0])
                                            {
                                                // 比例
                                                case 'r' :
                                                    // 当前商品总价占比当前订单商品所有总价的占比
                                                    $rate = $spec_total_price/$ev['total_price'];
                                                    if($rate > 0)
                                                    {
                                                        // 增加/优惠金额处理
                                                        if($increase_price > 0)
                                                        {
                                                            $ev['total_price'] += $increase_price/$rate;
                                                        }
                                                        if($preferential_price > 0)
                                                        {
                                                            $ev['total_price'] -= $preferential_price/$rate;
                                                        }
                                                    }
                                                    
                                                    // 计算得出当前商品需要返佣的金额
                                                    $profit_price = empty($rules[1]) ? 0.00 : $ev['total_price']*($rules[1]/100);
                                                    $is_ext_profit = true;
                                                    break;

                                                // 增加金额
                                                case 's' :
                                                    if(isset($ev['is_fixed_price_multiple']) && $ev['is_fixed_price_multiple'] == 1 && isset($ev['buy_number']))
                                                    {
                                                        $profit_price = PriceNumberFormat($rules[1]*($ev['buy_number']-$ev['returned_quantity']));
                                                    } else {
                                                        $profit_price = PriceNumberFormat($rules[1]);
                                                    }
                                                    $is_ext_profit = true;
                                                    break;
                                            }
                                        }
                                    }
                                }
                            }

                            // 未自定义返佣则使用等级默认的返佣
                            if($is_ext_profit === false && $level_rate > 0)
                            {
                               // 当前商品总价占比当前订单商品所有总价的占比
                                $rate = $spec_total_price/$ev['total_price'];
                                if($rate > 0)
                                {
                                    // 增加/优惠金额处理
                                    if($increase_price > 0)
                                    {
                                        $ev['total_price'] += $increase_price/$rate;
                                    }
                                    if($preferential_price > 0)
                                    {
                                        $ev['total_price'] -= $preferential_price/$rate;
                                    }
                                    $profit_price = $ev['total_price']*($level_rate/100);
                                }
                            }

                            // 佣金总额增加
                            $spec_profit_price += $profit_price;
                        }
                    }
                } else {
                    // 未自定义返佣则使用等级默认的返佣
                    if($level_rate > 0)
                    {
                        // 存在退款金额则减去退款金额后再计算
                        if(isset($order['refund_price']) && $order['refund_price'] > 0)
                        {
                            $order['total_price'] -= $order['refund_price'];
                        }
                        // 不参与佣金计算的金额
                        if($no_participation_total > 0)
                        {
                            $order['total_price'] -= $no_participation_total;
                        }
                       $spec_profit_price = $order['total_price']*($level_rate/100);
                    }
                }
            }
        }
        return $spec_profit_price;
    }

    /**
     * 计算内购返佣金额
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-10
     * @desc    description
     * @param   [aray]           $order         [订单信息]
     * @param   [int]            $user_level_id [用户等级id]
     * @param   [float]          $level_rate    [当前用户等级默认分佣比例]
     * @param   [array|json]     $spec_extends  [规格扩展信息]
     */
    public static function SelfBuyProfitPriceCalculation($order, $user_level_id, $level_rate, $spec_extends)
    {
        // 增加的金额
        // 优惠金额
        $increase_price = isset($order['increase_price']) ? $order['increase_price'] : 0;
        $preferential_price = isset($order['preferential_price']) ? $order['preferential_price'] : 0;

        // 检查是否存在规格配置分销佣金
        $spec_total_price = 0;
        $spec_profit_price = 0;
        if(!empty($spec_extends))
        {
            if(!is_array($spec_extends))
            {
                $spec_extends = json_decode($spec_extends, true);
            }
            if(!empty($spec_extends) && is_array($spec_extends))
            {
                // 是否存在商品自定义返佣配置
                if(array_sum(array_column($spec_extends, 'is_distribution_self_buy')) > 0)
                {
                    $spec_total_price += array_sum(array_column($spec_extends, 'total_price'));
                    foreach($spec_extends as $ev)
                    {
                        // 存在退款金额则减去退款金额后再计算
                        if(isset($ev['refund_price']) && $ev['refund_price'] > 0)
                        {
                            $ev['total_price'] -= $ev['refund_price'];
                        }

                        // 总额大于0则计算
                        if($ev['total_price'] > 0)
                        {
                            // 处理商品规格自定义返佣
                            $is_ext_profit = false;
                            $profit_price = 0;
                            if(isset($ev['is_distribution_self_buy']) && $ev['is_distribution_self_buy'] == 1 && !empty($ev['extends_self_buy']))
                            {
                                if(!is_array($ev['extends_self_buy']))
                                {
                                    $ev['extends_self_buy'] = json_decode($ev['extends_self_buy'], true);
                                }
                                // 根据当前规则计算收益
                                if(!empty($ev['extends_self_buy']) && array_key_exists($user_level_id, $ev['extends_self_buy']))
                                {
                                    $rules = explode("|", $ev['extends_self_buy'][$user_level_id]);
                                    if(!empty($rules) && is_array($rules) && count($rules) == 2)
                                    {
                                        switch($rules[0])
                                        {
                                            // 比例
                                            case 'r' :
                                                // 当前商品总价占比当前订单商品所有总价的占比
                                                $rate = $spec_total_price/$ev['total_price'];
                                                if($rate > 0)
                                                {
                                                    // 增加/优惠金额处理
                                                    if($increase_price > 0)
                                                    {
                                                        $ev['total_price'] += $increase_price/$rate;
                                                    }
                                                    if($preferential_price > 0)
                                                    {
                                                        $ev['total_price'] -= $preferential_price/$rate;
                                                    }
                                                }
                                                
                                                // 计算得出当前商品需要返佣的金额
                                                $profit_price = empty($rules[1]) ? 0.00 : $ev['total_price']*($rules[1]/100);
                                                $is_ext_profit = true;
                                                break;

                                            // 增加金额
                                            case 's' :
                                                if(isset($ev['is_fixed_price_multiple']) && $ev['is_fixed_price_multiple'] == 1 && isset($ev['buy_number']))
                                                {
                                                    $profit_price = PriceNumberFormat($rules[1]*($ev['buy_number']-$ev['returned_quantity']));
                                                } else {
                                                    $profit_price = PriceNumberFormat($rules[1]);
                                                }                                                
                                                $is_ext_profit = true;
                                                break;
                                        }
                                    }
                                }
                            }

                            // 未自定义返佣则使用等级默认的返佣
                            if($is_ext_profit === false && $level_rate > 0)
                            {
                               // 当前商品总价占比当前订单商品所有总价的占比
                                $rate = $spec_total_price/$ev['total_price'];
                                if($rate > 0)
                                {
                                    // 增加/优惠金额处理
                                    if($increase_price > 0)
                                    {
                                        $ev['total_price'] += $increase_price/$rate;
                                    }
                                    if($preferential_price > 0)
                                    {
                                        $ev['total_price'] -= $preferential_price/$rate;
                                    }
                                    $profit_price = $ev['total_price']*($level_rate/100);
                                }
                            }

                            // 佣金总额增加
                            $spec_profit_price += $profit_price;
                        }
                    }
                } else {
                    // 未自定义返佣则使用等级默认的返佣
                    if($level_rate > 0)
                    {
                        // 存在退款金额则减去退款金额后再计算
                        if(isset($order['refund_price']) && $order['refund_price'] > 0)
                        {
                            $order['total_price'] -= $order['refund_price'];
                        }
                       $spec_profit_price = $order['total_price']*($level_rate/100);
                    }
                }
            }
        }
        return $spec_profit_price;
    }

    /**
     * 计算向下返佣金额
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-10
     * @desc    description
     * @param   [aray]           $order         [订单信息]
     * @param   [int]            $user_level_id [用户等级id]
     * @param   [float]          $level_rate    [当前用户等级默认分佣比例]
     * @param   [array|json]     $spec_extends  [规格扩展信息]
     */
    public static function DownReturnProfitPriceCalculation($order, $user_level_id, $level_rate, $spec_extends)
    {
        // 增加的金额
        // 优惠金额
        $increase_price = isset($order['increase_price']) ? $order['increase_price'] : 0;
        $preferential_price = isset($order['preferential_price']) ? $order['preferential_price'] : 0;

        // 检查是否存在规格配置分销佣金
        $spec_total_price = 0;
        $spec_profit_price = 0;
        if(!empty($spec_extends))
        {
            if(!is_array($spec_extends))
            {
                $spec_extends = json_decode($spec_extends, true);
            }
            if(!empty($spec_extends) && is_array($spec_extends))
            {
                // 是否存在商品自定义返佣配置
                if(array_sum(array_column($spec_extends, 'is_distribution_down')) > 0)
                {
                    $spec_total_price += array_sum(array_column($spec_extends, 'total_price'));
                    foreach($spec_extends as $ev)
                    {
                        // 存在退款金额则减去退款金额后再计算
                        if(isset($ev['refund_price']) && $ev['refund_price'] > 0)
                        {
                            $ev['total_price'] -= $ev['refund_price'];
                        }

                        // 总额大于0则计算
                        if($ev['total_price'] > 0)
                        {
                            // 处理商品规格自定义返佣
                            $is_ext_profit = false;
                            $profit_price = 0;
                            if(isset($ev['is_distribution_down']) && $ev['is_distribution_down'] == 1 && !empty($ev['extends_down']))
                            {
                                if(!is_array($ev['extends_down']))
                                {
                                    $ev['extends_down'] = json_decode($ev['extends_down'], true);
                                }
                                // 根据当前规则计算收益
                                if(!empty($ev['extends_down']) && array_key_exists($user_level_id, $ev['extends_down']))
                                {
                                    $rules = explode("|", $ev['extends_down'][$user_level_id]);
                                    if(!empty($rules) && is_array($rules) && count($rules) == 2)
                                    {
                                        switch($rules[0])
                                        {
                                            // 比例
                                            case 'r' :
                                                // 当前商品总价占比当前订单商品所有总价的占比
                                                $rate = $spec_total_price/$ev['total_price'];
                                                if($rate > 0)
                                                {
                                                    // 增加/优惠金额处理
                                                    if($increase_price > 0)
                                                    {
                                                        $ev['total_price'] += $increase_price/$rate;
                                                    }
                                                    if($preferential_price > 0)
                                                    {
                                                        $ev['total_price'] -= $preferential_price/$rate;
                                                    }
                                                }
                                                
                                                // 计算得出当前商品需要返佣的金额
                                                $profit_price = empty($rules[1]) ? 0.00 : $ev['total_price']*($rules[1]/100);
                                                $is_ext_profit = true;
                                                break;

                                            // 增加金额
                                            case 's' :
                                                if(isset($ev['is_fixed_price_multiple']) && $ev['is_fixed_price_multiple'] == 1 && isset($ev['buy_number']))
                                                {
                                                    $profit_price = PriceNumberFormat($rules[1]*($ev['buy_number']-$ev['returned_quantity']));
                                                } else {
                                                    $profit_price = PriceNumberFormat($rules[1]);
                                                }
                                                $is_ext_profit = true;
                                                break;
                                        }
                                    }
                                }
                            }

                            // 未自定义返佣则使用等级默认的返佣
                            if($is_ext_profit === false && $level_rate > 0)
                            {
                               // 当前商品总价占比当前订单商品所有总价的占比
                                $rate = $spec_total_price/$ev['total_price'];
                                if($rate > 0)
                                {
                                    // 增加/优惠金额处理
                                    if($increase_price > 0)
                                    {
                                        $ev['total_price'] += $increase_price/$rate;
                                    }
                                    if($preferential_price > 0)
                                    {
                                        $ev['total_price'] -= $preferential_price/$rate;
                                    }
                                    $profit_price = $ev['total_price']*($level_rate/100);
                                }
                            }

                            // 佣金总额增加
                            $spec_profit_price += $profit_price;
                        }
                    }
                } else {
                    // 未自定义返佣则使用等级默认的返佣
                    if($level_rate > 0)
                    {
                        // 存在退款金额则减去退款金额后再计算
                        if(isset($order['refund_price']) && $order['refund_price'] > 0)
                        {
                            $order['total_price'] -= $order['refund_price'];
                        }
                       $spec_profit_price = $order['total_price']*($level_rate/100);
                    }
                }
            }
        }
        return $spec_profit_price;
    }

    /**
     * 正常向上收益计算
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [aray]           $order         [订单信息]
     * @param   [string]         $user_level_id [用户等级id]
     * @param   [float]          $level_rate    [当前用户等级默认分佣比例]
     * @param   [array|json]     $spec_extends  [规格扩展信息]
     * @param   [int]            $profit_level  [当前需要计算佣金的等级（1~3）]
     */
    private static function UpperReturnProfitPriceCalculation($order, $user_level_id, $level_rate, $spec_extends, $profit_level)
    {
        // 增加的金额
        // 优惠金额
        $increase_price = isset($order['increase_price']) ? $order['increase_price'] : 0;
        $preferential_price = isset($order['preferential_price']) ? $order['preferential_price'] : 0;

        // 不参与计算佣金的金额
        $no_participation_total = self::OrderNoParticipationTotal($order['extension_data']);

        // 检查是否存在规格配置分销佣金
        $spec_profit_price = 0;
        if(!empty($spec_extends))
        {
            if(!is_array($spec_extends))
            {
                $spec_extends = json_decode($spec_extends, true);
            }
            if(!empty($spec_extends) && is_array($spec_extends))
            {
                // 是否存在商品自定义返佣配置
                if(array_sum(array_column($spec_extends, 'is_distribution')) > 0)
                {
                    // 不参与返佣的金额
                    $preferential_price += $no_participation_total;

                    // 规格总价
                    $spec_total_price = array_sum(array_column($spec_extends, 'total_price'));
                    foreach($spec_extends as $ev)
                    {

                        // 存在退款金额则减去退款金额后再计算
                        if(isset($ev['refund_price']) && $ev['refund_price'] > 0)
                        {
                            $ev['total_price'] -= $ev['refund_price'];
                        }

                        // 总额大于0则计算
                        if($ev['total_price'] > 0)
                        {
                            // 处理商品规格自定义返佣
                            $is_ext_profit = false;
                            $profit_price = 0;
                            if(isset($ev['is_distribution']) && $ev['is_distribution'] == 1 && !empty($ev['extends']))
                            {
                                if(!is_array($ev['extends']))
                                {
                                    $ev['extends'] = json_decode($ev['extends'], true);
                                }

                                // 根据当前规则计算收益
                                // 商品规格自定义配置从0索引开始计算，所以这里佣金等级需要减一
                                $temp_profit_level = $profit_level-1;
                                if(!empty($ev['extends']) && array_key_exists($user_level_id, $ev['extends']))
                                {
                                    $rules_all = explode("\n", $ev['extends'][$user_level_id]);
                                    if(!empty($rules_all) && array_key_exists($temp_profit_level, $rules_all))
                                    {
                                        $rules = explode("|", $rules_all[$temp_profit_level]);
                                        if(!empty($rules) && is_array($rules) && count($rules) == 2)
                                        {
                                            switch($rules[0])
                                            {
                                                // 比例
                                                case 'r' :
                                                    // 当前商品总价占比当前订单商品所有总价的占比
                                                    $rate = $spec_total_price/$ev['total_price'];
                                                    if($rate > 0)
                                                    {
                                                        // 增加/优惠金额处理
                                                        if($increase_price > 0)
                                                        {
                                                            $ev['total_price'] += $increase_price/$rate;
                                                        }
                                                        if($preferential_price > 0)
                                                        {
                                                            $ev['total_price'] -= $preferential_price/$rate;
                                                        }
                                                    }
                                                    
                                                    // 计算得出当前商品需要返佣的金额
                                                    $profit_price = empty($rules[1]) ? 0.00 : $ev['total_price']*($rules[1]/100);
                                                    $is_ext_profit = true;
                                                    break;

                                                // 增加金额
                                                case 's' :
                                                    if(isset($ev['is_fixed_price_multiple']) && $ev['is_fixed_price_multiple'] == 1 && isset($ev['buy_number']))
                                                    {
                                                        $profit_price = PriceNumberFormat($rules[1]*($ev['buy_number']-$ev['returned_quantity']));
                                                    } else {
                                                        $profit_price = PriceNumberFormat($rules[1]);
                                                    }
                                                    $is_ext_profit = true;
                                                    break;
                                            }
                                        }
                                    }
                                }
                            }

                            // 未自定义返佣则使用等级默认的返佣
                            if($is_ext_profit === false && $level_rate > 0)
                            {
                               // 当前商品总价占比当前订单商品所有总价的占比
                                $rate = $spec_total_price/$ev['total_price'];
                                if($rate > 0)
                                {
                                    // 增加/优惠金额处理
                                    if($increase_price > 0)
                                    {
                                        $ev['total_price'] += $increase_price/$rate;
                                    }
                                    if($preferential_price > 0)
                                    {
                                        $ev['total_price'] -= $preferential_price/$rate;
                                    }
                                    $profit_price = $ev['total_price']*($level_rate/100);
                                }
                            }

                            // 佣金总额增加
                            $spec_profit_price += $profit_price;
                        }
                    }
                } else {
                    // 未自定义返佣则使用等级默认的返佣
                    if($level_rate > 0)
                    {
                        // 存在退款金额则减去退款金额后再计算
                        if(isset($order['refund_price']) && $order['refund_price'] > 0)
                        {
                            $order['total_price'] -= $order['refund_price'];
                        }
                        // 不参与佣金计算的金额
                        if($no_participation_total > 0)
                        {
                            $order['total_price'] -= $no_participation_total;
                        }
                       $spec_profit_price = $order['total_price']*($level_rate/100);
                    }
                }
            }
        }
        return $spec_profit_price;
    }

    /**
     * 收益添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-06-10
     * @desc    description
     * @param   [int]               $order_id       [订单id]
     * @param   [int]               $user_id        [受益人用户id]
     * @param   [array]             $order          [订单数据]
     * @param   [array]             $user_level_id  [用户等级id]
     * @param   [int]               $rate           [当前用户所属分销等级默认收益比例]
     * @param   [int]               $level          [当前级别,0向下（1~3）正常向上, 4自购, (5~7)自提点]
     * @param   [float]             $profit_price   [收益金额]
     * @param   [array]             $spec_extends   [当前订单商品规格配置数据]
     */
    private static function ProfitInsert($order_id, $user_id, $order, $user_level_id, $rate = 0, $level = 1, $profit_price = 0, $spec_extends = [])
    {
        // 不参与计算佣金的金额
        $no_participation_total = self::OrderNoParticipationTotal($order['extension_data']);

        // 日志数据
        $data = [
            'user_id'           => $user_id,
            'order_id'          => $order_id,
            'order_user_id'     => $order['user_id'],
            'total_price'       => $order['total_price']-$no_participation_total,
            'profit_price'      => $profit_price,
            'rate'              => $rate,
            'level'             => intval($level),
            'user_level_id'     => $user_level_id,
            'status'            => 0,
            'spec_extends'      => empty($spec_extends) ? '' : json_encode($spec_extends),
            'add_time'          => time(),
        ];
        $log_id = Db::name('PluginsDistributionProfitLog')->insertGetId($data);
        if($log_id > 0)
        {
            // 获取订单用户昵称
            $user = UserService::GetUserViewInfo($order['user_id']);

            // 消息通知
            $user_name_view = (empty($user) || empty($user['user_name_view'])) ? '' : $user['user_name_view'];
            $msg = $user_name_view.'用户下单'.$data['total_price'].'π, 预计收益'.$data['profit_price'].'π';
            MessageService::MessageAdd($user_id, '分销收益新增', $msg, BaseService::$message_business_type, $log_id);
            return DataReturn('分销订单添加成功', 0);
        }
        return DataReturn('分销订单添加失败', -1);
    }

    /**
     * 根据订单扩展数据计算不参与返佣的金额
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-17
     * @desc    description
     * @param   [array|string]          $extension_data [订单扩展数据]
     */
    public static function OrderNoParticipationTotal($extension_data)
    {
        $total = 0;
        if(!empty($extension_data))
        {
            if(!is_array($extension_data))
            {
                $extension_data = json_decode($extension_data, true);
            }
            if(!empty($extension_data))
            {
                foreach($extension_data as $v)
                {
                    if(isset($v['business']) && in_array($v['business'], ['plugins-freightfee']))
                    {
                        $total += $v['price'];
                    }
                }
            }
        }
        return $total;
    }

    /**
     * 订单商品规格扩展数据 - 分销等级配置获取
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]          $goods     [订单商品]
     * @param   [array]          $config    [配置信息]
     */
    private static function OrderDetailGopodsSpecExtends($goods, $config)
    {
        $extends_data = [];
        if(!empty($goods))
        {
            // 需要处理的字段 key
            $rules_key = 'plugins_distribution_rules_';
            $rules_down_key = 'plugins_distribution_down_rules_';
            $rules_self_buy_key = 'plugins_distribution_self_buy_rules_';
            $rules_force_current_user_key = 'plugins_distribution_force_current_user_rules_';

            foreach($goods as $v)
            {
                $temp_ext = [];
                $temp_ext_down = [];
                $temp_ext_self_buy = [];
                $temp_ext_force_current_user = [];
                if(!empty($v['extends']))
                {
                    $extends = is_array($v['extends']) ? $v['extends'] : json_decode($v['extends'], true);
                    if(!empty($extends) && is_array($extends))
                    {
                        foreach($extends as $ek=>$ev)
                        {
                            // 向上返佣
                            if(!empty($ev) && substr($ek, 0, strlen($rules_key)) == $rules_key)
                            {
                                $level = str_replace($rules_key, '', $ek);
                                if(!empty($level))
                                {
                                    $temp_ext[$level] = $ev;
                                }
                            }

                            // 向下返佣
                            if(!empty($ev) && substr($ek, 0, strlen($rules_down_key)) == $rules_down_key)
                            {
                                $level = str_replace($rules_down_key, '', $ek);
                                if(!empty($level))
                                {
                                    $temp_ext_down[$level] = $ev;
                                }
                            }

                            // 内购返佣
                            if(!empty($ev) && substr($ek, 0, strlen($rules_self_buy_key)) == $rules_self_buy_key)
                            {
                                $level = str_replace($rules_self_buy_key, '', $ek);
                                if(!empty($level))
                                {
                                    $temp_ext_self_buy[$level] = $ev;
                                }
                            }

                            // 自提点返佣
                            if(!empty($ev) && substr($ek, 0, strlen($rules_force_current_user_key)) == $rules_force_current_user_key)
                            {
                                $level = str_replace($rules_force_current_user_key, '', $ek);
                                if(!empty($level))
                                {
                                    $temp_ext_force_current_user[$level] = $ev;
                                }
                            }
                        }
                    }
                }

                $extends_data[] = [
                    'id'                                    => $v['id'],
                    'total_price'                           => PriceNumberFormat($v['price']*$v['buy_number']),
                    'price'                                 => $v['price'],
                    'refund_price'                          => 0.00,
                    'returned_quantity'                     => 0,
                    'buy_number'                            => $v['buy_number'],
                    'goods_id'                              => $v['goods_id'],
                    'spec'                                  => $v['spec'],
                    'is_fixed_price_multiple'               => (isset($config['is_fixed_price_multiple']) && $config['is_fixed_price_multiple'] == 1) ? 1 : 0,
                    'extends'                               => empty($temp_ext) ? '' : $temp_ext,
                    'is_distribution'                       => empty($temp_ext) ? 0 : 1,
                    'extends_down'                          => empty($temp_ext_down) ? '' : $temp_ext_down,
                    'is_distribution_down'                  => empty($temp_ext_down) ? 0 : 1,
                    'extends_self_buy'                      => empty($temp_ext_self_buy) ? '' : $temp_ext_self_buy,
                    'is_distribution_self_buy'              => empty($temp_ext_self_buy) ? 0 : 1,
                    'extends_force_current_user'            => empty($temp_ext_force_current_user) ? '' : $temp_ext_force_current_user,
                    'is_distribution_force_current_user'    => empty($temp_ext_force_current_user) ? 0 : 1,
                ];
            }
        }
        return $extends_data;
    }

    /**
     * 订单变更重新计算收益
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-06-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function OrderChange($params = [])
    {
        // 参数
        if(empty($params['order_id']))
        {
            return DataReturn('订单id有误', -1);
        }

        // 获取订单数据
        $order = Db::name('Order')->find(intval($params['order_id']));
        if(empty($order))
        {
            return DataReturn('订单不存在', -1);
        }

        // 获取收益数据
        $where = [
            ['order_id', '=', $order['id']],
            ['status', '<=', 1],
        ];
        $profit = Db::name('PluginsDistributionProfitLog')->where($where)->select()->toArray();
        if(!empty($profit))
        {
            // 订单发生售后，则重新处理规格中的数量和价格
            // 由于佣金订单的扩展数据都一致，这里仅使用第一个订单解析扩展数据进行处理
            $ret_ext = self::OrderGoodsSpecExtendsChange($profit[0]['spec_extends']);
            if($ret_ext['code'] == 0)
            {
                // 循环处理
                foreach($profit as $v)
                {
                    // 退款金额大于0或者订单金额不一致
                    if($order['refund_price'] > 0 || $order['total_price'] != $v['total_price'])
                    {
                        // 计算收益
                        switch($v['level'])
                        {
                            // 向下
                            case 0 :
                                $profit_price = self::DownReturnProfitPriceCalculation($order, $v['user_level_id'], $v['rate'], $ret_ext['data']);
                                break;

                            // 向上
                            case 1 :
                            case 2 :
                            case 3 :
                                $profit_price = self::UpperReturnProfitPriceCalculation($order, $v['user_level_id'], $v['rate'], $ret_ext['data'], $v['level']);
                                break;

                            // 内购
                            case 4 :
                                $profit_price = self::SelfBuyProfitPriceCalculation($order, $v['user_level_id'], $v['rate'], $ret_ext['data']);
                                break;

                            // 向上
                            case 5 :
                            case 6 :
                            case 7 :
                                $level_arr = [5=>1, 6=>2, 7=>3];
                                $profit_price = self::SelfExtractionProfitPriceCalculation($order, $v['user_level_id'], $v['rate'], $ret_ext['data'], $level_arr[$v['level']]);
                                break;

                            default :
                                return DataReturn('返佣订单类型无需处理['.$v['level'].']', 0);
                        }

                        // 重新计算收益
                        $data = [
                            'total_price'   => $order['total_price'],
                            'profit_price'  => $profit_price,
                            'spec_extends'  => json_encode($ret_ext['data']),
                            'upd_time'      => time(),
                        ];

                        $msg = '用户订单发生变更, 订单金额'.$order['total_price'].'π, 增加金额'.$order['increase_price'].'π, 优惠金额'.$order['preferential_price'].'π, 退款金额'.$order['refund_price'].'π, 原收益'.$v['profit_price'].'π / 变更后收益'.$data['profit_price'].'π';
                        $data['msg'] = $v['msg'].'['.$msg.']';
                        if(Db::name('PluginsDistributionProfitLog')->where(['id'=>$v['id']])->update($data))
                        {
                            // 收益金额不一致的时候变更
                            if($v['profit_price'] != $data['profit_price'])
                            {
                                // 描述标题
                                $msg_title = '分销收益变更';

                                // 消息通知
                                MessageService::MessageAdd($v['user_id'], $msg_title, $msg, BaseService::$message_business_type, $v['id']);
                            }
                        }
                    }
                }
            }
            return DataReturn('操作成功', 0);
        }
        return DataReturn('无需处理', 0);
    }

    /**
     * 订单商品规格数据更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-05
     * @desc    description
     * @param   [array]          $data [订单商品数据]
     */
    private static function OrderGoodsSpecExtendsChange($data)
    {
        if(!is_array($data))
        {
            $data = json_decode($data, true);
        }
        if(!empty($data) && is_array($data))
        {
            foreach($data as &$v)
            {
                $detail = Db::name('OrderDetail')->where(['id'=>$v['id']])->field('refund_price,returned_quantity')->find();
                if(!empty($detail))
                {
                    $v['returned_quantity'] = $detail['returned_quantity'];
                    $v['refund_price'] = $detail['refund_price'];
                } else {
                    return DataReturn('分销订单详情对应订单详情不存在', -1);
                }
            }
            return DataReturn('操作成功', 0, $data);
        }
        return DataReturn('分销扩展数据有误', -1);
    }
}
?>