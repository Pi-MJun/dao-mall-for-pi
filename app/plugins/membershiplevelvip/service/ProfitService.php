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
namespace app\plugins\membershiplevelvip\service;

use think\facade\Db;
use app\service\UserService;
use app\service\MessageService;
use app\plugins\membershiplevelvip\service\BaseService;

/**
 * 会员等级服务层 - 收益
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class ProfitService
{
    /**
     * 返佣订单添加
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-12-29T00:44:47+0800
     * @desc     description
     * @param    [array]                   $order      [付费订单数据]
     * @param    [array]                   $level_data [description]
     * @param    [string]                  $commission [description]
     */
    public static function ProfitHandle($order, $level_data, $commission)
    {
        // 返佣规则
        $commission = explode(';', $commission);
        if(!empty($commission) && is_array($commission) && count($commission) > 0)
        {
            // 一级返佣
            if(!empty($commission[0]))
            {
                $referrer = Db::name('User')->where(['id'=>$order['user_id']])->value('referrer');
                if(!empty($referrer))
                {
                    $ret = self::ProfitInsert($referrer, $order, $commission[0], $level_data['id'], 1);
                    if($ret['code'] != 0)
                    {
                        return $ret;
                    }
                }
            }

            // 二级返佣
            if(!empty($referrer))
            {
                $referrer = Db::name('User')->where(['id'=>$referrer])->value('referrer');
                if(!empty($referrer) && !empty($commission[1]))
                {
                    $ret = self::ProfitInsert($referrer, $order, $commission[1], $level_data['id'], 2);
                    if($ret['code'] != 0)
                    {
                        return $ret;
                    }
                }
            }

            // 三级返佣
            if(!empty($referrer) && !empty($commission[2]))
            {
                $referrer = Db::name('User')->where(['id'=>$referrer])->value('referrer');
                if(!empty($referrer))
                {
                    $ret = self::ProfitInsert($referrer, $order, $commission[2], $level_data['id'], 3);
                    if($ret['code'] != 0)
                    {
                        return $ret;
                    }
                }
            }
        }
        return DataReturn('处理成功', 0);
    }

    /**
     * 佣金订单添加
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-12-29T01:09:15+0800
     * @desc     description
     * @param    [int]                   $user_id          [用户id]
     * @param    [array]                 $order            [订单数据]
     * @param    [string]                $commission_rules [返佣规则]
     * @param    [int]                   $user_level_id    [会员等级id]
     * @param    [int]                   $level            [返佣级别（1~3）]
     */
    private static function ProfitInsert($user_id, $order, $commission_rules, $user_level_id, $level)
    {
        $commission_rules = explode('|', $commission_rules);
        if(!empty($commission_rules) && is_array($commission_rules) && count($commission_rules) == 2)
        {
            // 佣金计算
            // 0 r 代表按照比例, s 代表固定金额
            // 1 比例 或 固定金额
            $profit_price = 0;
            if(!empty($commission_rules[1]))
            {
                if($commission_rules[0] == 'r')
                {
                    $profit_price = $order['pay_price']*($commission_rules[1]/100);
                } else {
                    $profit_price = PriceNumberFormat($commission_rules[1]);
                }
            }

            // 数据组装
            $data = [
                'user_id'                       => $user_id,
                'payment_user_order_id'         => $order['id'],
                'payment_user_order_user_id'    => $order['user_id'],
                'total_price'                   => $order['pay_price'],
                'profit_price'                  => $profit_price,
                'commission_rules'              => is_array($commission_rules) ? implode('|', $commission_rules) : $commission_rules,
                'status'                        => 0,
                'level'                         => $level,
                'user_level_id'                 => $user_level_id,
                'add_time'                      => time(),
            ];
            $profit_id = Db::name('PluginsMembershiplevelvipUserProfit')->insertGetId($data);
            if($profit_id > 0)
            {
                // 获取订单用户昵称
                $user = UserService::GetUserViewInfo($order['user_id'], '', true);

                // 消息通知
                $msg = $user['user_name_view'].'用户购买会员'.$data['total_price'].'π, 预计收益'.$data['profit_price'].'π';
                MessageService::MessageAdd($user_id, '会员返佣收益新增', $msg, BaseService::$business_type_name, $profit_id);

                return DataReturn('会员等级返佣订单添加成功', 0);
            }
            return DataReturn('会员等级返佣订单添加失败', -1);
        }
        return DataReturn('无需处理', 0);
    }
}
?>