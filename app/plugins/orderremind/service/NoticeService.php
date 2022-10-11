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
namespace app\plugins\orderremind\service;

use think\facade\Db;
use app\plugins\orderremind\service\BaseService;

/**
 * 新订单语音提醒 - 服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2019-09-24
 * @desc    description
 */
class NoticeService
{
    /**
     * 获取新订单
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-03-14
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function NewOrderData($params = [])
    {
        $base = BaseService::BaseConfig();
        if($base['code'] == 0 && !empty($base['data']))
        {
            // 语音通知
            $is_voice_notice = isset($base['data']['is_voice_notice']) && $base['data']['is_voice_notice'] == 1 ? 1 : 0;

            // 获取订单
            if($is_voice_notice == 1)
            {
                // 订单通知类型
                $monitor_action = isset($base['data']['monitor_action']) ? intval($base['data']['monitor_action']) : 0;

                // db
                $db = Db::name('Order')->field('id,order_no,price,total_price,pay_price,add_time,pay_time');

                // 前端30秒请求一次, 这里仅读取1分钟内的订单
                $time = 60*1;

                // 条件
                switch($monitor_action)
                {
                    // 订单支付、包含线下支付、虚拟销售订单
                    case 1 :
                        $db->whereOr([
                            [
                                ['status', '=', 2],
                                ['pay_time','>=', time()-$time],
                            ],
                            [
                                ['status', '=', 1],
                                ['add_time','>=', time()-$time],
                                ['is_under_line', '=', 1],
                            ],
                            [
                                ['status', 'in', [2,3]],
                                ['pay_time','>=', time()-$time],
                                ['order_model', '=', 3],
                            ],
                        ]);
                        break;

                    // 默认订单创建
                    default :
                        $db->where([
                            ['status', 'in', [0,1]],
                            ['add_time','>=', time()-$time],
                        ]);
                        break;
                }
                
                // 查询订单
                $order = $db->order('id desc')->find();
                if(!empty($order))
                {
                    $order['add_time'] = date('Y-m-d H:i:s', $order['add_time']);
                    return DataReturn('success', 0, $order);
                }
            }
            return DataReturn('无新订单', -1);
        }
        return $base;
    }
}
?>