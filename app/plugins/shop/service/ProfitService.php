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
namespace app\plugins\shop\service;

use think\facade\Db;
use app\service\UserService;
use app\service\MessageService;
use app\plugins\shop\service\BaseService;

/**
 * 多商户 - 结算服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-02-14
 * @desc    description
 */
class ProfitService
{
    /**
     * 收益订单添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-14
     * @desc    description
     * @param   [array]           $params [输入参数]
     * @param   [array]           $config [配置信息]
     */
    public static function OrderProfitInsert($params = [], $config = [])
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
            return DataReturn('结算订单创建仅支持状态[待确认/待支付]', -1);
        }

        // 获取订单信息
        $order = Db::name('Order')->find($params['order_id']);
        if(empty($order))
        {
            return DataReturn('订单信息不存在', -1);
        }

        // 必须是店铺订单
        if(!empty($order['shop_id']))
        {
            // 订单商品
            $order_goods = Db::name('OrderDetail')->where(['order_id'=>$params['order_id']])->field('id,order_id,goods_id,price,total_price,buy_number,refund_price,returned_quantity')->select()->toArray();
            if(empty($order_goods))
            {
                return DataReturn('订单商品信息不存在', -1);
            }

            // 获取店铺信息
            $shop = ShopService::UserShopInfo($order['shop_id'], 'id', '*', ['user_type'=>'shop']);
            if(empty($shop))
            {
                return DataReturn('用户店铺信息有误', -1);
            }
            if($shop['settle_type'] == -1)
            {
                return DataReturn('未设置结算类型、不进行返现数据添加', 0);
            }

            // 商品结算信息处理
            $order_goods = self::OrderGoodsSettleHandle($order_goods, $shop['settle_rate']);

            // 收益金额处理
            $profit_price = self::ProfitPriceCalculation($order, $order_goods, $shop['settle_type'], $shop['settle_rate']);

            // 增加结算数据
            if($profit_price > 0)
            {
                return self::ProfitInsert($shop['user_id'], $order, $profit_price, $shop['settle_type'], $shop['settle_rate'], $order_goods);
            }
        }

        return DataReturn('操作成功', 0);
    }

    /**
     * 收益计算
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-14
     * @desc    根据结算类型，两种结算方案[统一比例使用订单支付金额(订单单价减掉优惠金额加上增加金额)、商品配置(根据商品配置的金额或者比例进行结算，订单中产生的优惠金额和增加金额不影响结算金额)]
     * @param   [aray]           $order         [订单信息]
     * @param   [aray]           $order_goods   [订单商品信息]
     * @param   [int]            $settle_type   [店铺结算类型（0统一比例、1商品配置）]
     * @param   [float]          $settle_rate   [店铺结算比例]
     */
    private static function ProfitPriceCalculation($order, $order_goods, $settle_type, $settle_rate)
    {
        // 结算金额
        $profit_price = 0;

        // 统一比例
        if($settle_type == 0)
        {
            // 必须配置比例值
            if($settle_rate > 0)
            {
                // 订单金额
                $order_total_price = self::ActualOrderTotal($order);

                // 结算金额
                $profit_price = $order_total_price*($settle_rate/100);
            }
        } else {
            // 商品配置
            if(!empty($order_goods))
            {
                foreach($order_goods as $v)
                {
                    // 固定金额
                    if(!empty($v['shop_settle_price']) && $v['shop_settle_price'] > 0)
                    {
                        // 是否存在退款金额
                        if(isset($v['returned_quantity'])  && $v['returned_quantity'] > 0)
                        {
                            $v['buy_number'] -= $v['returned_quantity'];
                        }
                        // 必须存在数量
                        if($v['buy_number'] > 0)
                        {
                            $profit_price += $v['shop_settle_price']*$v['buy_number'];
                        }
                    } else {
                        // 比例
                        if(!empty($v['shop_settle_rate']) && $v['shop_settle_rate'] > 0)
                        {
                            // 是否存在退款金额
                            if(isset($v['refund_price'])  && $v['refund_price'] > 0)
                            {
                                $v['total_price'] -= $v['refund_price'];
                            }
                            // 小计必须大于0
                            if($v['total_price'] > 0)
                            {
                                $profit_price += $v['total_price']*($v['shop_settle_rate']/100);
                            }
                        }
                    }
                }
            }
        }
        return PriceNumberFormat($profit_price);
    }

    /**
     * 订单商品结算信息处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-02
     * @desc    description
     * @param   [array]        $order_goods     [订单商品]
     * @param   [float]        $settle_rate     [店铺结算比例]
     */
    public static function OrderGoodsSettleHandle($order_goods, $settle_rate)
    {
        // 商品处理
        $goods = Db::name('Goods')->where(['id'=>array_column($order_goods, 'goods_id')])->column('shop_settle_price,shop_settle_rate', 'id');
        if(!empty($goods))
        {
            foreach($order_goods as &$v)
            {
                if(array_key_exists($v['goods_id'], $goods))
                {
                    $v['shop_settle_price'] = $goods[$v['goods_id']]['shop_settle_price'];
                    $v['shop_settle_rate'] = $goods[$v['goods_id']]['shop_settle_rate'];
                }

                // 未设置结算信息则使用店铺默认
                if((!isset($v['shop_settle_price']) || $v['shop_settle_price'] <= 0) && (!isset($v['shop_settle_rate']) || $v['shop_settle_rate'] <= 0))
                {
                    $v['shop_settle_rate'] = $settle_rate;
                }
            }
        }
        return $order_goods;
    }

    /**
     * 收益添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-14
     * @desc    description
     * @param   [int]               $user_id        [受益人用户id]
     * @param   [array]             $order          [订单数据]
     * @param   [float]             $profit_price   [收益金额]
     * @param   [int]               $settle_type    [结算类型（0统一比例，1商品配置）]
     * @param   [int]               $settle_rate    [收益比例]
     * @param   [array]             $settle_rules   [订单商品、结算规则]
     */
    private static function ProfitInsert($user_id, $order, $profit_price, $settle_type, $settle_rate, $settle_rules)
    {
        // 订单金额
        $order_total_price = self::ActualOrderTotal($order);

        // 日志数据
        $data = [
            'user_id'           => $user_id,
            'order_id'          => $order['id'],
            'shop_id'           => $order['shop_id'],
            'shop_user_id'      => $order['shop_user_id'],
            'order_user_id'     => $order['user_id'],
            'total_price'       => $order_total_price,
            'profit_price'      => $profit_price,
            'settle_type'       => $settle_type,
            'settle_rate'       => $settle_rate,
            'settle_rules'      => empty($settle_rules) ? '' : json_encode($settle_rules, JSON_UNESCAPED_UNICODE),
            'status'            => 0,
            'add_time'          => time(),
        ];
        $data_id = Db::name('PluginsShopOrderProfit')->insertGetId($data);
        if($data_id > 0)
        {
            // 获取订单用户昵称
            $user = UserService::GetUserViewInfo($order['user_id']);

            // 消息通知
            $user_name_view = (empty($user) || empty($user['user_name_view'])) ? '' : $user['user_name_view'];
            $msg = $user_name_view.'用户下单'.$data['total_price'].'π, 预计收益'.$data['profit_price'].'π';
            MessageService::MessageAdd($user_id, '店铺收益新增', $msg, BaseService::$message_business_type, $data_id);
            return DataReturn('收益订单添加成功', 0);
        }
        return DataReturn('收益订单添加失败', -1);
    }

    /**
     * 订单金额
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-14
     * @desc    description
     * @param   [array]          $order [订单数据]
     */
    public static function ActualOrderTotal($order)
    {
        // 存在退款金额则减去退款金额
        if(isset($order['refund_price']) && $order['refund_price'] > 0)
        {
            $order['total_price'] -= $order['refund_price'];
        }

        // 增加的金额
        // 优惠金额
        $increase_price = isset($order['increase_price']) ? $order['increase_price'] : 0;
        $preferential_price = isset($order['preferential_price']) ? $order['preferential_price'] : 0;
        if($increase_price > 0)
        {
            $order['total_price'] += $increase_price;
        }
        if($preferential_price > 0)
        {
            $order['total_price'] -= $preferential_price;
        }

        // 去掉运费金额
        if(!empty($order['extension_data']))
        {
            if(!is_array($order['extension_data']))
            {
                $order['extension_data'] = json_decode($order['extension_data'], true);
            }
            if(!empty($order['extension_data']))
            {
                foreach($order['extension_data'] as $v)
                {
                    if(isset($v['business']) && in_array($v['business'], ['plugins-freightfee']))
                    {
                        $order['total_price'] -= $v['price'];
                    }
                }
            }
        }
        return $order['total_price'];
    }

    /**
     * 订单变更重新计算收益
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-14
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
        $profit = Db::name('PluginsShopOrderProfit')->where($where)->select()->toArray();
        if(!empty($profit))
        {
            // 循环处理
            foreach($profit as $v)
            {
                // 退款金额大于0或者订单金额不一致
                if($order['refund_price'] > 0 || $order['returned_quantity'] > 0)
                {
                    // 计算规则处理
                    $settle_rules = empty($v['settle_rules']) ? '' : json_decode($v['settle_rules'], true);
                    if(!empty($settle_rules))
                    {
                        // 获取新的订单售后数据
                        $order_detail = Db::name('OrderDetail')->where(['id'=>array_column($settle_rules, 'id')])->column('refund_price,returned_quantity', 'id');
                        if(!empty($order_detail))
                        {
                            foreach($settle_rules as &$r)
                            {
                                if(array_key_exists($r['id'], $order_detail))
                                {
                                    $r['refund_price'] = $order_detail[$r['id']]['refund_price'];
                                    $r['returned_quantity'] = $order_detail[$r['id']]['returned_quantity'];
                                }
                            }
                        }
                    }

                    // 计算收益
                    $profit_price = self::ProfitPriceCalculation($order, $settle_rules, $v['settle_type'], $v['settle_rate']);

                    // 订单金额
                    $order_total_price = self::ActualOrderTotal($order);

                    // 重新计算收益
                    $data = [
                        'total_price'   => $order_total_price,
                        'profit_price'  => $profit_price,
                        'settle_rules'  => empty($settle_rules) ? '' : json_encode($settle_rules, JSON_UNESCAPED_UNICODE),
                        'upd_time'      => time(),
                    ];

                    $msg = '用户订单发生变更, 订单金额'.$order['total_price'].'π, 增加金额'.$order['increase_price'].'π, 优惠金额'.$order['preferential_price'].'π, 退款金额'.$order['refund_price'].'π, 原收益'.$v['profit_price'].'π / 变更后收益'.$data['profit_price'].'π';
                    $data['msg'] = $v['msg'].'['.$msg.']';
                    if(Db::name('PluginsShopOrderProfit')->where(['id'=>$v['id']])->update($data))
                    {
                        // 收益金额不一致的时候变更
                        if($v['profit_price'] != $data['profit_price'])
                        {
                            // 描述标题
                            $msg_title = '店铺收益变更';

                            // 消息通知
                            MessageService::MessageAdd($v['user_id'], $msg_title, $msg, BaseService::$message_business_type, $v['id']);
                        }
                    }
                }
            }
            return DataReturn('操作成功', 0);
        }
        return DataReturn('无需处理', 0);
    }

    /**
     * 订单关闭
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-14
     * @desc    description
     * @param   [int]          $order_id [订单id]
     * @param   [array]        $params   [订单日志数据]
     */
    public static function OrderProfitClose($order_id, $params)
    {
        // 原因
        $msg = ($params['new_status'] == 5) ? '订单取消' : '订单关闭';

        // 收益订单关闭
        $upd_data = [
            'status'    => 4,
            'msg'       => $msg,
            'upd_time'  => time(),
        ];
        if(Db::name('PluginsShopOrderProfit')->where(['order_id'=>$order_id])->update($upd_data) === false)
        {
            return DataReturn('收益订单关闭失败', -1);
        }

        return DataReturn('success', 0);
    }

    /**
     * 订单生效
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-14
     * @desc    description
     * @param   [int]          $order_id [订单id]
     * @param   [array]        $params   [订单日志数据]
     */
    public static function OrderProfitValid($order_id, $params)
    {
        // 重新计算订单佣金
        $ret = self::OrderChange(['order_id'=>$order_id]);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 更新收益订单状态
        $upd_data = [
            'status'    => ($params['new_status'] == 2) ? 1 : 2,
            'upd_time'  => time(),
        ];
        if(Db::name('PluginsShopOrderProfit')->where(['order_id'=>$order_id])->update($upd_data) === false)
        {
            return DataReturn('收益订单生效失败', -1);
        }

        return DataReturn('success', 0);
    }
}
?>