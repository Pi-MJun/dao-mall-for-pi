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
use app\service\PluginsService;
use app\service\ResourcesService;
use app\service\UserService;
use app\plugins\membershiplevelvip\service\BaseService;
use app\plugins\membershiplevelvip\service\LevelService;

/**
 * 会员等级服务层 - 业务处理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class BusinessService
{
    /**
     * 优惠价格计算
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [string]          $price            [商品展示金额]
     * @param   [int]             $plugins_discount [折扣系数]
     * @param   [int]             $plugins_price    [减金额]
     */
    public static function PriceCalculate($price, $plugins_discount = 0, $plugins_price = 0)
    {
        if($plugins_discount <= 0 && $plugins_price <= 0)
        {
            return $price;
        }

        // 折扣
        if($plugins_discount > 0)
        {
            if(stripos($price, '-') !== false)
            {
                $text = explode('-', $price);
                $min_price = floatval($text[0])*$plugins_discount;
                $max_price = floatval($text[1])*$plugins_discount;
                $price = ($min_price <= 0) ? '0.00' : PriceNumberFormat($min_price);
                $price .= '-'.(($max_price <= 0) ? '0.00' : PriceNumberFormat($max_price));
            } else {
                $price = (float) $price *$plugins_discount;
                $price = ($price <= 0) ? '0.00' : PriceNumberFormat($price);
            }
        }

        // 减金额
        if($plugins_price > 0)
        {
            if(stripos($price, '-') !== false)
            {
                $text = explode('-', $price);
                $min_price = floatval($text[0])-$plugins_price;
                $max_price = floatval($text[1])-$plugins_price;
                $price = ($min_price <= 0) ? '0.00' : PriceNumberFormat($min_price);
                $price .= '-'.(($max_price <= 0) ? '0.00' : PriceNumberFormat($max_price));
            } else {
                $price = (float) $price-$plugins_price;
                $price = ($price <= 0) ? '0.00' : PriceNumberFormat($price);
            }
        }
        return $price;
    }

    /**
     * 自定义商品金额计算
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-23
     * @desc    description
     * @param   [string]          $extedns [商品扩展数据]
     * @param   [array]           $level   [用户等级信息]
     * @return  [boolean|float]            [商品金额|boolean]
     */
    public static function PriceCalculateManual($extedns, $level)
    {
        if(!empty($extedns))
        {
            if(!is_array($extedns))
            {
                $extedns = json_decode($extedns, true);
            }
            $key = 'plugins_membershiplevelvip_price_'.$level['id'];
            if(!empty($extedns[$key]))
            {
                return PriceNumberFormat($extedns[$key]);
            }
        }
        return false;
    }

    /**
     * 用户等级匹配
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-28
     * @desc    description
     * @param   [int]           $user_id [用户id]
     */
    public static function UserLevelMatching($user_id = 0)
    {
        // 未指定用户信息，则从服务层读取
        if(empty($user_id))
        {
            $user = UserService::LoginUserInfo();
            if(!empty($user['id']))
            {
                $user_id = $user['id'];
            }
        }
        if(!empty($user_id))
        {
            // 缓存key
            $key = md5(BaseService::$user_vip_data_key.$user_id);
            $level = MyCache($key);

            // 获取用户等级
            if(empty($level) || MyEnv('app_debug'))
            {
                // 会员等级
                $level_list = LevelService::DataList(['where'=>['is_enable'=>1]]);
                if(!empty($level_list['data']))
                {
                    // 当前会员等级id数组列
                    $level_id_col = array_column($level_list['data'], 'id');

                    // 是否设定了用户等级
                    $user_level_id = Db::name('User')->where(['id'=>$user_id])->value('plugins_user_level');
                    if(!empty($user_level_id) && in_array($user_level_id, $level_id_col))
                    {
                        $level_id_key = array_search($user_level_id, $level_id_col);
                        if($level_id_key !== false)
                        {
                            $level = $level_list['data'][$level_id_key];
                            $level['user_vip_model'] = 'manual';
                        }
                    }
                }

                // 用户付费购买模式下
                if(empty($level))
                {
                    $vip = Db::name('PluginsMembershiplevelvipPaymentUser')->where(['user_id'=>$user_id])->find();
                    if(!empty($vip) && (isset($vip['is_permanent']) && $vip['is_permanent'] == 1) || (isset($vip['expire_time']) && $vip['expire_time'] > time()))
                    {
                        // 获取用户购买订单
                        $order = Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where(['user_id'=>$user_id, 'status'=>1])->order('id desc')->value('level_data');
                        if(!empty($order))
                        {
                            $order = json_decode($order, true);
                            if(!empty($order))
                            {
                                $level = array_merge($vip, $order);
                                $level['user_vip_model'] = 'pay';
                                // 重新获取等级折扣信息
                                $lv = Db::name('PluginsMembershiplevelvipLevel')->where(['id'=>$level['level_id']])->field('order_price,full_reduction_price,discount_rate')->find();
                                if(!empty($lv))
                                {
                                    $level = array_merge($level, $lv);
                                }
                            }
                        }
                    }
                }

                // 应用配置
                $base = BaseService::BaseConfig();

                // 自动匹配
                if(!empty($base['data']) && !empty($level_list['data']))
                {
                    // 积分和订单总价模式下
                    if(empty($level))
                    {
                        // 等级规则模式
                        $level_rules = isset($base['data']['level_rules']) ? $base['data']['level_rules'] : 0;

                        // 匹配类型
                        $value = 0;
                        switch($level_rules)
                        {
                            // 积分（可用积分）
                            case 0 :
                                $value = Db::name('User')->where(['id'=>$user_id])->value('integral');
                                break;

                            // 消费总额（已完成订单）
                            // 订单状态（0待确认, 1已确认/待支付, 2已支付/待发货, 3已发货/待收货, 4已完成, 5已取消, 6已关闭）
                            case 1 :
                                $where = ['user_id'=>$user_id, 'status'=>4];
                                $value = (float) Db::name('Order')->where($where)->sum('total_price');
                                break;
                        }
                        
                        // 匹配相应的等级
                        foreach($level_list['data'] as $v)
                        {
                            // 0-*
                            if($v['rules_min'] <= 0 && $v['rules_max'] > 0 && $value < $v['rules_max'])
                            {
                                $level = $v;
                                break;
                            }

                            // *-*
                            if($v['rules_min'] > 0 && $v['rules_max'] > 0 && $value >= $v['rules_min'] && $value < $v['rules_max'])
                            {
                                $level = $v;
                                break;
                            }

                            // *-0
                            if($v['rules_max'] <= 0 && $v['rules_min'] > 0 && $value > $v['rules_min'])
                            {
                                $level = $v;
                                break;
                            }
                        }
                        if(!empty($level))
                        {
                            $level['user_vip_model'] = 'auto';
                        }
                    }
                }

                // 数据处理
                if(!empty($level))
                {
                    // 等级icon
                    if(empty($level['icon']))
                    {
                        $level['icon'] = empty($level['images_url']) ? (empty($base['data']['default_level_images']) ? MyConfig('shopxo.attachment_host').'/static/plugins/images/membershiplevelvip/level-default-images.png' : $base['data']['default_level_images']) : ResourcesService::AttachmentPathViewHandle($level['images_url']);
                    }
                    unset($level['images_url']);

                    // 等级名称
                    if(empty($level['level_name']))
                    {
                        $level['level_name'] = empty($level['name']) ? '' : $level['name'];
                    }
                }
                MyCache($key, $level, 10);
            }
            return $level;
        }
        return [];
    }

    /**
     * 用户VIP数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-19
     * @desc    description
     * @param   [int]          $user_id         [用户id]
     * @param   [boolean]      $is_cache        [是否走缓存（默认true）]
     */
    public static function UserVip($user_id, $is_cache = true)
    {
        $vip = self::UserLevelMatching($user_id);
        if(!empty($vip))
        {
            // 有效时间处理
            // user_vip_model 模式
            // auto         自动分配
            // pay          用户购买
            // manual       手动
            if(isset($vip['user_vip_model']) && $vip['user_vip_model'] == 'pay')
            {
                if($vip['is_permanent'] == 1)
                {
                    $vip['permanent_value'] = '终生';
                    $vip['permanent_unit'] = '不限期限';
                } else {
                    $day = (empty($vip['expire_time']) || $vip['expire_time'] < time()) ? 0 : ($vip['expire_time']-time())/60/60/24;
                    $value_unit = self::UserExpireTimeValueUnit($day);
                    $vip['surplus_time_number'] = $value_unit['value'];
                    $vip['surplus_time_unit'] = $value_unit['unit'];

                    // 过期值和过期时间处理
                    $vip['surplus_time_number'] = ($vip['surplus_time_number'] > 0) ? $vip['surplus_time_number'] : 0;
                    $vip['expire_time_date'] = empty($vip['expire_time']) ? '' : date('Y-m-d H:i:s', $vip['expire_time']);
                }
            }

            // 加入用户id
            $vip['user_id'] = $user_id;
            return $vip;
        }
        return null;
    }

    /**
     * 会员过期值和单位计算
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-26
     * @desc    description
     * @param   [int]          $number [天数]
     */
    public static function UserExpireTimeValueUnit($number)
    {
        $value = 0;
        $unit = '';
        if(!empty($number))
        {
            if($number >= 365)
            {
                $value = $number/365;
                $unit = '年';
            } else {
                if($number >= 30)
                {
                    $value = $number/30;
                    $unit = '月';
                } else {
                    if($number >= 1)
                    {
                        $value = $number;
                        $unit = '天';
                    } else {
                        if($number >= 1)
                        {
                            $value = $number*60;
                            $unit = '小时';
                        } else {
                            $value = $number*60*60;
                            $unit = '分钟';
                            if($value < 1)
                            {
                                $value = intval($value*60);
                                $unit = '秒';
                            }
                        }
                    }
                }
            }
        }
        return [
            'value' => PriceBeautify(PriceNumberFormat($value, 2)),
            'unit'  => $unit,
        ];
    }

    /**
     * 是否开启会员购买
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-26
     * @desc    description
     */
    public static function IsUserPay()
    {
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {
            if(!empty($ret['data']) && isset($ret['data']['is_user_buy']) && $ret['data']['is_user_buy'] == 1)
            {
                return true;
            }
        }
        return false;
    }

    /**
     * 是否开启会员付费
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-26
     * @desc    description
     */
    public static function IsSupportedRenewOldOrder()
    {
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {
            if(!empty($ret['data']) && isset($ret['data']['is_supported_renew_old_order']) && $ret['data']['is_supported_renew_old_order'] == 1)
            {
                return true;
            }
        }
        return false;
    }
}
?>