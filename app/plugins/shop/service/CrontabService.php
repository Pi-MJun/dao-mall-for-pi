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
use app\plugins\wallet\service\WalletService;
use app\plugins\shop\service\BaseService;

/**
 * 多商户 - 脚本服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-02-14
 * @desc    description
 */
class CrontabService
{
    /**
     * 订单收益脚本，将收益增加到用户钱包
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-14
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ProfitSettlement($params = [])
    {
        // 插件配置信息
        $base = BaseService::BaseConfig();

        // 结算周期时间
        $profit_settlement_limit_time = (empty($base['data']) || empty($base['data']['profit_settlement_limit_time'])) ? 43200 : intval($base['data']['profit_settlement_limit_time']);
        $time = time()-($profit_settlement_limit_time*60);

        // 获取需要结算的订单
        $where = [
            ['o.collect_time', '<', $time],
            ['o.status', '=', 4],
            ['op.status', '=', 2],
        ];
        $data = Db::name('PluginsShopOrderProfit')->alias('op')->join('order o', 'o.id=op.order_id')->where($where)->field('op.id,op.user_id,op.shop_id,op.shop_user_id,op.order_user_id,op.profit_price,op.total_price')->limit(50)->select()->toArray();

        // 状态
        $sucs = 0;
        $fail = 0;
        if(!empty($data))
        {
            // 更新状态
            $upd_data = [
                'status'        => 3,
                'upd_time'      => time(),
            ];
            foreach($data as $v)
            {
                // 开启事务
                Db::startTrans();
                if(Db::name('PluginsShopOrderProfit')->where(['id'=>$v['id'], 'status'=>2])->update($upd_data))
                {
                    // 获取订单用户昵称
                    $user = UserService::GetUserViewInfo($v['order_user_id']);

                    // 消息通知
                    $user_name_view = (empty($user) || empty($user['user_name_view'])) ? '' : $user['user_name_view'];
                    $msg = $user_name_view.'用户订单收益结算'.$v['total_price'].'π, 收益'.$v['profit_price'].'π, 已发放至钱包';
                    MessageService::MessageAdd($v['user_id'], '店铺收益新增', $msg, BaseService::$message_business_type, $v['id']);

                    // 钱包变更
                    WalletService::UserWalletMoneyUpdate($v['user_id'], $v['profit_price'], 1, 'normal_money', 0, '店铺收益新增');

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