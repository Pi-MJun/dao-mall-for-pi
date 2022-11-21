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
use app\plugins\shop\service\BaseService;

/**
 * 多商户 - 发送通知服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-02-14
 * @desc    description
 */
class NoticeService
{
    /**
     * 消息通知
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-20
     * @desc    description
     * @param   [int]           $order_id [订单id]
     */
    public static function Send($order_id)
    {
        // 配置信息
        $base = BaseService::BaseConfig();
        if(!empty($base['data']) && !empty($base['data']['is_new_order_notice']))
        {
            // 获取商店id
            $where = [
                ['id', '=', $order_id],
                ['status', '>', 1],
            ];
            $order = Db::name('Order')->where($where)->field('id,order_no,shop_id')->find();
            if(!empty($order) && !empty($order['shop_id']))
            {
                $shop = Db::name('PluginsShop')->where(['id'=>$order['shop_id']])->field('notice_mobile,notice_email')->find();
                if(!empty($shop))
                {
                    $verify_params = [
                        'expire_time'   => MyC('common_verify_expire_time'),
                        'interval_time' => MyC('common_verify_interval_time'),
                    ];

                    // 短信
                    if(!empty($shop['notice_mobile']) && !empty($base['data']['sms_sign']) && !empty($base['data']['sms_new_order_template']))
                    {
                        $obj = new \base\Sms($verify_params);
                        $obj->SendCode($shop['notice_mobile'], ['order_no'=>$order['order_no']], $base['data']['sms_new_order_template'], $base['data']['sms_sign']);
                    }

                    // 邮件
                    if(!empty($shop['notice_email']) && !empty($base['data']['email_new_order_template']))
                    {
                        $obj = new \base\Email($verify_params);
                        $email_params = [
                                'email'     => $shop['notice_email'],
                                'content'   => str_replace(['#order_no#', '#date_y#'], [$order['order_no'], date('Y')], $base['data']['email_new_order_template']),
                                'title'     => MyC('home_site_name').' - 新订单通知',
                            ];
                        $obj->SendHtml($email_params);
                    }
                }
            }
        }
        return DataReturn('success', 0);
    }
}
?>