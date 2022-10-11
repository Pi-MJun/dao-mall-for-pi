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
use app\service\UserService;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\CouponService;

/**
 * 用户优惠券服务层-用户
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-05T21:51:08+0800
 */
class UserCouponService
{
    /**
     * 用户优惠券列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date    2019-08-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CouponUserList($params = [])
    {
        // 用户优惠券过期处理
        self::CouponUserExpireHandle();

        // 参数
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $result = [];
        $data = Db::name('PluginsCouponUser')->field($field)->where($where)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            $common_is_whether_list = BaseService::$common_is_whether_list;
            $coupons = [];
            foreach($data as $v)
            {
                // 优惠券信息
                if(!isset($coupons[$v['coupon_id']]))
                {
                    $coupons[$v['coupon_id']] = self::CouponData($v['coupon_id']);
                }
                $v['coupon'] = $coupons[$v['coupon_id']];

                // 是否已使用
                $v['is_use_name'] = (isset($v['is_use']) && isset($common_is_whether_list[$v['is_use']])) ? $common_is_whether_list[$v['is_use']]['name'] : '未知';

                // 是否已过期
                $v['is_expire_name'] = (isset($v['is_expire']) && isset($common_is_whether_list[$v['is_expire']])) ? $common_is_whether_list[$v['is_expire']]['name'] : '未知';

                // 是否有效
                $v['is_valid_name'] = (isset($v['is_valid']) && isset($common_is_whether_list[$v['is_valid']])) ? $common_is_whether_list[$v['is_valid']]['name'] : '未知';

                // 使用时间
                $v['use_time_time'] = empty($v['use_time']) ? '' : date('Y-m-d H:i', $v['use_time']);

                // 有效时间
                $v['time_start_text'] = date('Y-m-d H:i', $v['time_start']);
                $v['time_end_text'] = date('Y-m-d H:i', $v['time_end']);

                // 时间
                $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                $v['upd_time_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);

                // 按照类型分组
                $result[self::CouponTabGroup($v)][] = $v;
            }
        }
        return DataReturn('处理成功', 0, $result);
    }

    /**
     * 优惠券分组
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]          $data [优惠券信息]
     */
    private static function CouponTabGroup($data)
    {
        // not_use 未使用, already_use 已使用, already_expire 已过期
        $value = 'not_use';
        if($data['is_use'] == 1)
        {
            $value = 'already_use';
        } else {
            if($data['is_expire'] == 1)
            {
                $value = 'already_expire';
            }
        }
        return $value;
    }

    /**
     * 获取优惠券信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [int]          $coupon_id [优惠券id]
     */
    private static function CouponData($coupon_id)
    {
        $data = Db::name('PluginsCoupon')->field('name,desc,type,bg_color,expire_type,discount_value,use_limit_type,use_value_ids,where_order_price')->find($coupon_id);
        if(!empty($data))
        {
            // 静态资源
            $coupon_type_list = BaseService::$coupon_type_list;
            $coupon_bg_color_list = BaseService::$coupon_bg_color_list;
            $common_expire_type_list = BaseService::$common_expire_type_list;
            $common_use_limit_type_list = BaseService::$common_use_limit_type_list;

            // 优惠券类型
            $data['type_name'] = (isset($data['type']) && isset($coupon_type_list[$data['type']])) ? $coupon_type_list[$data['type']]['name'] : '未知';
            $data['type_unit'] = (!isset($data['type']) || $data['type'] == 0) ? 'π' : '折';

            // 背景色
            if((isset($data['bg_color']) && isset($coupon_bg_color_list[$data['bg_color']])))
            {
                $data['bg_color_name'] = $coupon_bg_color_list[$data['bg_color']]['name'];
                $data['bg_color_value'] = $coupon_bg_color_list[$data['bg_color']]['color'];
            } else {
                $data['bg_color_name'] = '未知';
                $data['bg_color_value'] = '#D2364C';
            }

            // 过期类型
            $data['expire_type_name'] = (isset($data['expire_type']) && isset($common_expire_type_list[$data['expire_type']])) ? $common_expire_type_list[$data['expire_type']]['name'] : '未知';

            // 使用限制类型
            $data['use_limit_type_name'] = (isset($data['use_limit_type']) && isset($common_use_limit_type_list[$data['use_limit_type']])) ? $common_use_limit_type_list[$data['use_limit_type']]['name'] : '未知';

            // 限制条件值
            $data['use_value_ids_all'] = empty($data['use_value_ids']) ? [] : json_decode($data['use_value_ids'], true);

            // 优惠金额/折扣美化
            $data['discount_value'] = PriceBeautify($data['discount_value']);
        }
        return $data;
    }

    /**
     * 用户优惠券过期处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     */
    public static function CouponUserExpireHandle()
    {
        $where = [
            ['is_use', '=', 0],
            ['is_expire', '=', 0],
            ['time_end', '<', time()],
        ];
        $count = Db::name('PluginsCouponUser')->where($where)->update(['is_expire'=>1, 'upd_time'=>time()]);
        return DataReturn('处理成功', 0, $count);
    }

    /**
     * 用户优惠券使用状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-15
     * @desc    description
     * @param   [string|array]     $extension_data [订单扩展数据]
     * @param   [int]              $status         [状态值 0|1]
     */
    public static function UserCouponUseStatusUpdate($extension_data, $status, $use_order_id = 0)
    {
        $fail = 0;
        $success = 0;
        if(!empty($extension_data))
        {
            if(is_string($extension_data))
            {
                $extension_data = json_decode($extension_data, true);
            }
            if(!empty($extension_data) && is_array($extension_data))
            {
                foreach($extension_data as $ext)
                {
                    if(isset($ext['business']) && $ext['business'] == 'plugins-coupon' && !empty($ext['ext']) && !empty($ext['ext']['id']))
                    {
                        $data = [
                            'is_use'    => $status,
                            'upd_time'  => time(),
                        ];
                        if($status == 1)
                        {
                            $data['use_time'] = time();
                            $data['use_order_id'] = $use_order_id;
                        } else {
                            $data['use_time'] = 0;
                            $data['use_order_id'] = 0;
                        }
                        if(Db::name('PluginsCouponUser')->where(['id'=>intval($ext['ext']['id'])])->update($data))
                        {
                            $success++;
                        }
                        $fail++;
                    }
                }
            }
        }
        return DataReturn('更新成功', 0, ['success'=>$success, 'fail'=>$fail]);
    }

    /**
     * 注册赠送优惠券
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-15
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function UserRegisterGive($user_id)
    {
        // 获取已启用/可注册领取优惠券列表
        $where = [
            'is_enable'         => 1,
            'is_regster_send'   => 1,
        ];
        $coupons_ids = Db::name('PluginsCoupon')->where($where)->column('id');
        if(!empty($coupons_ids))
        {
            // 是否允许重复领取
            $base = BaseService::BaseConfig();
            $is_repeat_receive = (isset($base['data']['is_repeat_receive']) && $base['data']['is_repeat_receive'] == 1) ? 1 : 0;

            // 循环发放优惠券
            $fail = 0;
            $success = 0;
            foreach($coupons_ids as $coupon_id)
            {
                // 是否已领取过
                if($is_repeat_receive != 1)
                {
                    $temp = Db::name('PluginsCouponUser')->where(['coupon_id'=>$coupon_id, 'user_id'=>$user_id])->find();
                    if(!empty($temp))
                    {
                        continue;
                    }
                }

                // 用户优惠券发放
                $coupon_params = [
                    'user_ids'          => [$user_id],
                    'coupon_id'         => $coupon_id,
                    'is_regster_send'   => 1,
                    'config'            => $base['data'],
                ];
                $ret = CouponService::CouponSend($coupon_params);
                if($ret['code'] == 0)
                {
                    $success++;
                } else {
                    $fail++;
                }
            }
            return DataReturn('发放成功', 0, ['success'=>$success, 'fail'=>$fail]);
        }
        return DataReturn('没有可用优惠券', -1);
    }
}
?>