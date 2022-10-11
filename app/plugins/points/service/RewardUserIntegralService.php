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
use app\service\IntegralService;

/**
 * 积分商城 - 用户奖励积分服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class RewardUserIntegralService
{
    /**
     * 奖励用户积分
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-03
     * @desc    description
     * @param   [int]          $user_id     [用户id]
     * @param   [array]        $config      [插件配置信息]
     */
    public static function Run($user_id, $config)
    {
        // 用户信息
        $user = Db::name('User')->where(['id'=>$user_id])->field('id,referrer')->find();
        if(empty($user))
        {
            return DataReturn('赠送积分用户不存在', -1);
        }

        // 是否为强制被邀请注册
        if(isset($config['is_force_register']) && $config['is_force_register'] == 1 && empty($user['referrer']))
        {
            return DataReturn('非强制邀请用户、无需处理', 0);
        }

        // 注册人奖励积分
        if(!empty($config['register_reward_integral']))
        {
            $reward_integral = self::IntegralValueHandle($config['register_reward_integral']);
            if(!empty($reward_integral))
            {
                // 用户积分添加
                $user_integral = Db::name('User')->where(['id'=>$user['id']])->value('integral');
                if(!Db::name('User')->where(['id'=>$user['id']])->inc('integral', $reward_integral)->update())
                {
                    return DataReturn('注册奖励积分失败', -1);
                }

                // 积分日志
                IntegralService::UserIntegralLogAdd($user['id'], $user_integral, $reward_integral, '注册奖励', 1);
            }
        }

        // 邀请人奖励积分
        if(!empty($user['referrer']) && !empty($config['invite_reward_integral']))
        {
            $reward_integral = self::IntegralValueHandle($config['invite_reward_integral']);
            if(!empty($reward_integral))
            {
                // 用户积分添加
                $user_integral = Db::name('User')->where(['id'=>$user['referrer']])->value('integral');
                if(!Db::name('User')->where(['id'=>$user['referrer']])->inc('integral', $reward_integral)->update())
                {
                    return DataReturn('邀请用户奖励积分失败', -1);
                }

                // 积分日志
                IntegralService::UserIntegralLogAdd($user['referrer'], $user_integral, $reward_integral, '邀请用户奖励', 1);
            }
        }
    }

    /**
     * 积分值处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-03
     * @desc    description
     * @param   [string]          $value [积分值]
     */
    public static function IntegralValueHandle($value)
    {
        $arr = explode('-', $value);
        if(count($arr) == 2)
        {
            return rand(intval($arr[0]), intval($arr[1]));
        }
        return empty($arr[0]) ? 0 : $arr[0];
    }
}
?>