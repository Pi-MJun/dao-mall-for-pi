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
use app\service\MessageService;
use app\plugins\coupon\service\BaseService;

/**
 * 优惠券 - 用户通知服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-11-18
 * @desc    description
 */
class UserNoticeService
{
    /**
     * 短信发送
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-06-25
     * @desc    description
     * @param   [array]         $config     [插件配置]
     * @param   [array]         $data       [用户优惠券数据]
     * @param   [array]         $coupon     [优惠券信息]
     */
    public static function Send($config, $data, $coupon)
    {
        if(!empty($config) && !empty($data) && !empty($coupon))
        {
            // 是否开启站内信通知
            $is_site_notice = (isset($config['is_user_coupon_send_site_notice']) && $config['is_user_coupon_send_site_notice'] == 1);
            // 是否开启短信通知
            $is_sms_notice = (isset($config['is_user_coupon_send_sms_notice']) && $config['is_user_coupon_send_sms_notice'] == 1 && !empty($config['user_coupon_send_notice_sms_template']));

            // 优惠券基础信息
            $discount_value = PriceBeautify($coupon['discount_value']);
            $coupon_value = ($coupon['type'] == 0) ? $discount_value.'π' : $discount_value.'折';

            // 站内信
            if($is_site_notice)
            {
                foreach($data as $v)
                {
                    $msg = '收到一张'.$coupon_value.'优惠券、'.$coupon['desc'];
                    MessageService::MessageAdd($v['user_id'], '优惠券发放', $msg, BaseService::$business_type_name, $v['coupon_id']);
                }
            }

            // 短信
            if($is_sms_notice)
            {
                // 短信签名
                $sms_sign = empty($config['sms_sign']) ? MyC('common_sms_sign') : $config['sms_sign'];

                // 短信发送获取用户手机
                $user_mobile = array_filter(Db::name('User')->where(['id'=>array_column($data, 'user_id')])->column('mobile'));

                // 短信通知
                if(!empty($user_mobile))
                {
                    // 调用短信驱动发送
                    $verify_params = [
                        'expire_time'   => MyC('common_verify_expire_time'),
                        'interval_time' => MyC('common_verify_interval_time'),
                        'is_frq'        => 0,
                    ];
                    $obj = new \base\Sms($verify_params);
                    $obj->SendTemplate(implode(',', $user_mobile), $config['user_coupon_send_notice_sms_template'], $sms_sign, ['coupon_value'=>$coupon_value]);
                }
            }
        }
        return DataReturn('success', 0);
    }
}
?>