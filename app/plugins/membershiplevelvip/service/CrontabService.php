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
use app\plugins\wallet\service\WalletService;
use app\plugins\membershiplevelvip\service\BaseService;
use app\plugins\membershiplevelvip\service\ProfitService;

/**
 * 会员等级服务层 - 脚本
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class CrontabService
{
    /**
     * 订单超时未支付关闭
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     */
    public static function OrderClose()
    {
        // 插件配置信息
        $base = BaseService::BaseConfig();

        // 配置时间
        $order_close_time = (empty($base['data']) || empty($base['data']['order_close_time'])) ? 30 : intval($base['data']['order_close_time']);
        $time = time()-($order_close_time*60);

        // 获取需要关闭的订单
        $where = [
            ['add_time', '<', $time],
            ['status', '=', 0],
        ];
        $data = [
            'status'    => 3,
            'upd_time'  => time(),
        ];
        $count = (int) Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where($where)->update($data);
        return DataReturn('操作成功', 0, $count);
    }

    /**
     * 佣金订单创建
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     */
    public static function ProfitCreate()
    {
        // 状态
        $sucs = 0;
        $fail = 0;

        // 插件配置信息
        $base = BaseService::BaseConfig();

        // 是否开启返佣
        if(!empty($base['data']) && isset($base['data']['is_commission']) && $base['data']['is_commission'] == 1)
        {
            // 配置时间
            $profit_order_create_time = (empty($base['data']) || empty($base['data']['profit_order_create_time'])) ? 5 : intval($base['data']['profit_order_create_time']);
            $time = time()-($profit_order_create_time*60);

            // 获取付费订单
            $where = [
                ['add_time', '<', $time],
                ['status', '=', 1],
                ['settlement_status', '=', 0],
                ['pay_price', '>', 0],
            ];
            $data = Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where($where)->select()->toArray();

            // 处理数据
            if(!empty($data))
            {
                foreach($data as $v)
                {
                    if(!empty($v['level_data']))
                    {
                        $level_data = json_decode($v['level_data'], true);
                        if(!empty($level_data) && is_array($level_data) && !empty($level_data['pay_period_rules']) && is_array($level_data['pay_period_rules']))
                        {
                            foreach($level_data['pay_period_rules'] as $rules)
                            {
                                // 已配置返佣规则
                                // 第一种情况 都为空则终生
                                // 第二种情况 时长相等
                                if(!empty($rules['commission']) && ((empty($v['number']) && empty($rules['number'])) || ($v['number'] == $rules['number'])))
                                {
                                    // 开启事务
                                    Db::startTrans();

                                    // 添加佣金订单
                                    // 返回 -1000 则未处理相关数据
                                    $ret = ProfitService::ProfitHandle($v, $level_data, $rules['commission']);
                                    if($ret['code'] == 0)
                                    {
                                        // 更新订单结算状态
                                        $upd_data = [
                                            'settlement_status' => 1,
                                            'upd_time'          => time(),
                                        ];
                                        if(Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where(['id'=>$v['id'], 'settlement_status'=>0])->update($upd_data))
                                        {
                                            // 提交事务
                                            Db::commit();
                                            $sucs++;
                                            continue;
                                        }
                                    }

                                    // 事务回滚
                                     Db::rollback();
                                    $fail++;
                                }
                            }
                        }
                    }
                }
            }
        }
        return DataReturn('操作成功', 0, ['sucs'=>$sucs, 'fail'=>$fail]);
    }

    /**
     * 佣金订单结算
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-12-29T00:01:08+0800
     * @desc     description
     */
    public static function ProfitSettlement()
    {
        // 插件配置信息
        $base = BaseService::BaseConfig();

        // 配置时间
        $profit_order_settlement_time = (empty($base['data']) || empty($base['data']['profit_order_settlement_time'])) ? 10800 : intval($base['data']['profit_order_settlement_time']);
        $time = time()-($profit_order_settlement_time*60);

        // 获取付费订单
        $where = [
            ['add_time', '<', $time],
            ['status', '=', 0],
        ];
        $data = Db::name('PluginsMembershiplevelvipUserProfit')->where($where)->select()->toArray();

        // 状态
        $sucs = 0;
        $fail = 0;
        if(!empty($data))
        {
            // 更新状态
            $profit_upd_data = [
                'status'        => 1,
                'upd_time'      => time(),
            ];
            $order_upd_data = [
                'settlement_status' => 2,
                'upd_time'          => time(),
            ];
            foreach($data as $v)
            {
                // 开启事务
                Db::startTrans();

                // 收益明细更新
                $profit_res = Db::name('PluginsMembershiplevelvipUserProfit')->where(['id'=>$v['id'], 'status'=>0])->update($profit_upd_data);

                // 付费订单结算更新
                $order_res = Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where(['id'=>$v['payment_user_order_id'], 'settlement_status'=>1])->update($order_upd_data);                
                // 处理消息和钱包
                if($profit_res && $order_res !== false)
                {
                    // 获取订单用户昵称
                    $user = UserService::GetUserViewInfo($v['payment_user_order_user_id'], '', true);
                    $user_name_view = (empty($user) || empty($user['user_name_view'])) ? '' : $user['user_name_view'];

                    // 消息通知
                    $msg = $user_name_view.'用户会员购买佣金结算'.$v['total_price'].'π, 收益'.$v['profit_price'].'π, 已发放至钱包';
                    MessageService::MessageAdd($v['user_id'], '会员返佣收益结算', $msg, BaseService::$business_type_name, $v['id']);

                    // 钱包变更
                    WalletService::UserWalletMoneyUpdate($v['user_id'], $v['profit_price'], 1, 'normal_money', 0, '会员返佣收益结算');

                    // 提交事务
                    Db::commit();
                    $sucs++;
                    continue;
                }

                // 事务回滚
                Db::rollback();
                $fail++;
            }
        }
        return DataReturn('操作成功', 0, ['sucs'=>$sucs, 'fail'=>$fail]);
    }
}
?>