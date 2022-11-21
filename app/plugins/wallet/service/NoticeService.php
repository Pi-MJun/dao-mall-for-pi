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
namespace app\plugins\wallet\service;

use think\facade\Db;
use app\plugins\wallet\service\BaseService;

/**
 * 钱包 - 发送通知服务层
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
     * @param   [string]        $params    [输入参数]
     */
    public static function Send($params)
    {
        if(!empty($params['user_id']) && !empty($params['msg_title']) && !empty($params['money_type']) && !empty($params['opt_type']) && isset($params['opt_money']) && !empty($params['content']))
        {
            $base = BaseService::BaseConfig();
            if(!empty($base['data']) && isset($base['data']['is_money_change_notice']) && $base['data']['is_money_change_notice'] == 1)
            {
                // 获取用户信息
                $user = Db::name('User')->where(['id'=>$params['user_id']])->field('mobile,email')->find();
                if(!empty($user))
                {
                    $verify_params = [
                        'expire_time'   => MyC('common_verify_expire_time'),
                        'interval_time' => MyC('common_verify_interval_time'),
                    ];

                    // 短信
                    if(!empty($user['mobile']) && !empty($base['data']['sms_sign']) && !empty($base['data']['sms_money_change_template']))
                    {
                        $obj = new \base\Sms($verify_params);
                        $obj->SendCode($user['mobile'], [
                            'money_type'    => $params['money_type'],
                            'opt_type'      => $params['opt_type'],
                            'money'         => $params['opt_money']
                        ],
                        $base['data']['sms_money_change_template'], $base['data']['sms_sign']);
                    }

                    // 邮件
                    if(!empty($user['email']) && !empty($base['data']['email_money_change_template']))
                    {
                        $obj = new \base\Email($verify_params);
                        $email_params = [
                            'email'     => $user['email'],
                            'content'   => str_replace('#content#', $params['content'], $base['data']['email_money_change_template']),
                            'title'     => MyC('home_site_name').' - 钱包余额变更提醒',
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