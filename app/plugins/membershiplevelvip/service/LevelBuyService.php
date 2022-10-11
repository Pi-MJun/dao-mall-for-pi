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
use app\plugins\membershiplevelvip\service\LevelService;
use app\plugins\membershiplevelvip\service\BusinessService;

/**
 * 会员等级服务层 - 会员购买
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class LevelBuyService
{
    /**
     * 会员购买订单创建
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-18
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function BuyOrderCreate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'opening',
                'error_msg'         => '请选择开通时长',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否开启会员购买
        if(!BusinessService::IsUserPay())
        {
            return DataReturn('暂未开启会员购买', -1);
        }

        // 开通时长
        $opening = explode('-', $params['opening']);
        if(count($opening) != 2)
        {
            return DataReturn('开通时长时间有误', -1);
        }

        // 获取会员等级数据
        $where = ['id' => intval($opening[0])];
        $level = LevelService::DataList(['where'=>$where]);
        if(empty($level['data'][0]))
        {
            return DataReturn('会员等级不存在', -1);
        }
        if(!isset($level['data'][0]['is_enable']) || $level['data'][0]['is_enable'] != 1)
        {
            return DataReturn('会员等级未启用', -1);
        }
        if(empty($level['data'][0]['pay_period_rules']) || !is_array($level['data'][0]['pay_period_rules']))
        {
            return DataReturn('会员购买配置信息有误', -1);
        }

        // 支付金额处理
        $price = 0;
        foreach($level['data'][0]['pay_period_rules'] as $rouls)
        {
            if(isset($rouls['number']) && $rouls['number'] == $opening[1])
            {
                $price = $rouls['price'];
                break;
            }
        }
        if($price <= 0)
        {
            return DataReturn('会员购买配置信息价格有误', -1);
        }

        // 开启事务
        Db::startTrans();

        // 查询用户付费会员信息
        // 不存在则添加
        $pay_user = Db::name('PluginsMembershiplevelvipPaymentUser')->where(['user_id'=>$params['user']['id']])->find();

        // 不存在添加
        if(empty($pay_user))
        {
            // 会员数据
            $data = [
                'user_id'               => $params['user']['id'],
                'level_id'              => $level['data'][0]['id'],
                'level_name'            => $level['data'][0]['name'],
                'add_time'              => time(),
            ];
            $payment_user_id = Db::name('PluginsMembershiplevelvipPaymentUser')->insertGetId($data);
            if($payment_user_id <= 0)
            {
                Db::rollback();
                return DataReturn('会员信息添加失败', -100);
            }
        } else {
            // 当前购买的会员时长是否支持继续购买操作
            if($pay_user['is_supported_renew'] == 1)
            {
                return DataReturn('会员购买时长不支持续费', -1);
            }

            // 会员存在并且是终生则不能购买
            if(isset($pay_user['is_permanent']) && $pay_user['is_permanent'] == 1)
            {
                return DataReturn('当前会员有效期为终生，不可重复购买', -1);
            }

            $payment_user_id = $pay_user['id'];
        }

        // 添加购买会员订单
        $ret = self::BuyOrderInsert($params['user']['id'], $payment_user_id, $opening[1], $price, $level['data'][0], 0);
        if($ret['code'] != 0)
        {
            Db::rollback();
        }

        Db::commit();
        return $ret;
    }

    /**
     * 订单日志添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-18
     * @desc    description
     * @param   [int]          $user_id         [用户id]
     * @param   [int]          $payment_user_id [付费用户数据id]
     * @param   [int]          $number          [购买时长]
     * @param   [float]        $price           [购买价格]
     * @param   [array|json]   $level           [等级数据]
     * @param   [int]          $type            [类型（0正常购买, 1续费）]
     */
    public static function BuyOrderInsert($user_id, $payment_user_id, $number, $price, $level, $type = 0)
    {
        $data = [
            'payment_user_order_no'     => date('YmdHis').GetNumberCode(6),
            'user_id'                   => $user_id,
            'payment_user_id'           => $payment_user_id,
            'number'                    => intval($number),
            'price'                     => PriceNumberFormat($price),
            'level_id'                  => $level['id'],
            'level_data'                => is_array($level) ? json_encode($level) : $level,
            'type'                      => $type,
            'status'                    => 0,
            'add_time'                  => time(),
        ];
        $payment_user_order_id = Db::name('PluginsMembershiplevelvipPaymentUserOrder')->insertGetId($data);
        if($payment_user_order_id > 0)
        {
            if(empty($data['number']))
            {
                $value = '终身';
                $unit = '';
            } else {
                $value_uint = BusinessService::UserExpireTimeValueUnit($data['number']);
                $value = $value_uint['value'];
                $unit = $value_uint['unit'];
            }
            return DataReturn('添加成功', 0, [
                'id'                    => $payment_user_order_id,
                'payment_user_order_no' => $data['payment_user_order_no'],
                'payment_user_id'       => $payment_user_id,
                'value'                 => $value,
                'unit'                  => $unit,
                'price'                 => $data['price'],
            ]);
        }
        return DataReturn('添加失败', -100);
    }

    /**
     * 会员续费
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BuyOrderRenew($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否开启会员付费
        if(!BusinessService::IsSupportedRenewOldOrder())
        {
            return DataReturn('暂未开启会员原订单续费', -1);
        }

        // 获取会员信息
        $user_vip = BusinessService::UserVip($params['user']['id'], false, true);
        if(empty($user_vip) || !isset($user_vip['surplus_time_number']) || $user_vip['surplus_time_number'] <= 0)
        {
            return DataReturn('当前会员已过期，请直接购买', -1);
        }

        // 获取会员最后开通的订单
        $level_order = Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where(['user_id'=>$params['user']['id'], 'status'=>1])->order('id desc')->find();
        if(empty($level_order))
        {
            return DataReturn('没有会员购买订单纪录', -1);
        }

        // 订单等级信息
        if(empty($level_order['level_data']))
        {
            return DataReturn('会员购买订单等级信息有误', -1);
        }
        if(!is_array($level_order['level_data']))
        {
            $level_order['level_data'] = json_decode($level_order['level_data'], true);
        }

        // 当前购买的会员时长是否支持继续购买操作
        if($user_vip['is_supported_renew'] == 1)
        {
            return DataReturn('会员购买时长不支持续费', -1);
        }

        // 会员存在并且是终生则不能购买
        if(isset($user_vip['is_permanent']) && $user_vip['is_permanent'] == 1)
        {
            return DataReturn('当前会员有效期为终生，不可重复购买', -1);
        }

        $pay_user = Db::name('PluginsMembershiplevelvipPaymentUser')->where(['user_id'=>$params['user']['id']])->find();
        if(empty($pay_user))
        {
            return DataReturn('当前会员信息有误', -1);
        }

        // 添加购买会员订单
        return self::BuyOrderInsert($params['user']['id'], $pay_user['id'], $level_order['number'], $level_order['price'], $level_order['level_data'], 1);
    }

    /**
     * 订单纪录删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BuyOrderDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '删除数据id有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'user_type',
                'checked_data'      => ['admin', 'user'],
                'error_msg'         => '用户类型有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 用户校验
        if($params['user_type'] == 'user' && empty($params['user']))
        {
            return DataReturn('用户信息有误', -1);
        }

        // 删除
        $where = [
            ['id', '=', intval($params['id'])],
            ['status', 'in', [2,3]]
        ];
        if(!empty($params['user']['id']))
        {
            $where[] = ['user_id', '=', $params['user']['id']];
        }
        if(Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where($where)->delete())
        {
            return DataReturn('删除成功', 0);
        }
        return DataReturn('删除失败或资源不存在', -100);
    }

    /**
     * 订单纪录取消/关闭
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BuyOrderInvalid($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作数据id有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'value',
                'checked_data'      => [2,3],
                'error_msg'         => '操作值范围有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'user_type',
                'checked_data'      => ['admin', 'user'],
                'error_msg'         => '用户类型有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 用户校验
        if($params['user_type'] == 'user' && empty($params['user']))
        {
            return DataReturn('用户信息有误', -1);
        }

        // 更新
        $where = [
            ['id', '=', intval($params['id'])],
            ['status', '=', 0]
        ];
        if(!empty($params['user']['id']))
        {
            $where[] = ['user_id', '=', $params['user']['id']];
        }
        $upd_data = [
            'status'    => intval($params['value']),
            'upd_time'  => time(),
        ];
        if(Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where($where)->update($upd_data))
        {
            return DataReturn('操作成功', 0);
        }
        return DataReturn('操作失败或资源不存在', -100);
    }
}
?>