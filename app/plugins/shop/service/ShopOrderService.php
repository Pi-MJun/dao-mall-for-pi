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
use app\service\OrderService;
use app\service\MessageService;
use app\plugins\wallet\service\WalletService;

/**
 * 多商户 - 店铺订单服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class ShopOrderService
{
    /**
     * 根据订单id和店铺用户id获取订单信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-11
     * @desc    description
     * @param   [int]          $order_id        [订单id]
     * @param   [int]          $shop_user_id    [订单店铺用户id]
     */
    public static function ShopOrderInfo($order_id, $shop_user_id)
    {
        $where = [
            'id'                    => intval($order_id),
            'shop_user_id'          => intval($shop_user_id),
            'user_is_delete_time'   => 0,
        ];
        return Db::name('Order')->where($where)->field('id,user_id,shop_id,shop_user_id,price,total_price,pay_price')->find();
    }

    /**
     * 订单确认
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-30
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderConfirm($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '订单id有误',
            ],
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

        // 获取订单信息
        $order = self::ShopOrderInfo($params['id'], $params['user']['id']);
        if(empty($order))
        {
            return DataReturn('订单信息不存在', -1);
        }

        // 调用服务层操作
        $update_params = [
            'user_type'     => 'admin',
            'id'            => $order['id'],
            'user_id'       => $order['user_id'],
            'creator'       => $params['user']['id'],
            'creator_name'  => $params['user']['user_name_view'],
        ];
        return OrderService::OrderConfirm($update_params);
    }

    /**
     * 订单取消
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-30
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderCancel($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '订单id有误',
            ],
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

        // 获取订单信息
        $order = self::ShopOrderInfo($params['id'], $params['user']['id']);
        if(empty($order))
        {
            return DataReturn('订单信息不存在', -1);
        }

        // 调用服务层操作
        $update_params = [
            'user_type'     => 'admin',
            'id'            => $order['id'],
            'user_id'       => $order['user_id'],
            'creator'       => $params['user']['id'],
            'creator_name'  => $params['user']['user_name_view'],
        ];
        return OrderService::OrderCancel($update_params);
    }

    /**
     * 订单发货/取货
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-30
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderDelivery($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '订单id有误',
            ],
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

        // 获取订单信息
        $order = self::ShopOrderInfo($params['id'], $params['user']['id']);
        if(empty($order))
        {
            return DataReturn('订单信息不存在', -1);
        }

        // 调用服务层操作
        $update_params = [
            'user_type'         => 'admin',
            'id'                => $order['id'],
            'user_id'           => $order['user_id'],
            'creator'           => $params['user']['id'],
            'creator_name'      => $params['user']['user_name_view'],
            'express_id'        => isset($params['express_id']) ? intval($params['express_id']) : 0,
            'express_number'    => isset($params['express_number']) ? $params['express_number'] : '',
            'extraction_code'   => isset($params['extraction_code']) ? $params['extraction_code'] : '',
        ];
        return OrderService::OrderDelivery($update_params);
    }

    /**
     * 订单删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-30
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '订单id有误',
            ],
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

        // 获取订单信息
        $order = self::ShopOrderInfo($params['id'], $params['user']['id']);
        if(empty($order))
        {
            return DataReturn('订单信息不存在', -1);
        }
        if(!in_array($order['status'], [4,5,6]))
        {
            $status_text = MyConst('common_order_status')[$order['status']]['name'];
            return DataReturn('状态不可操作['.$status_text.']', -1);
        }

        // 更新操作
        $data = [
            'shop_is_delete_time'   => time(),
            'upd_time'              => time(),
        ];
        if(Db::name('Order')->where($where)->update($data))
        {
            // 用户消息
            MessageService::MessageAdd($order['shop_user_id'], '订单删除', '订单删除成功', OrderService::$business_type_name, $order['id']);

            return DataReturn('删除成功', 0);
        }
        return DataReturn('删除失败或资源不存在', -1);
    }

    /**
     * 订单接收
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-30
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderReceive($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'order_id',
                'error_msg'         => '订单id有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'opt_type',
                'checked_data'      => [1,2],
                'error_msg'         => '操作范围值有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'base_config',
                'error_msg'         => '插件配置信息有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        if($params['opt_type'] == 2 && empty($params['msg']))
        {
            return DataReturn('拒绝原因不能为空', -1);
        }

        // 获取订单信息
        $order = self::ShopOrderInfo($params['order_id'], $params['user']['id']);
        if(empty($order))
        {
            return DataReturn('订单信息不存在', -1);
        }

        // 是否需要冻结用户钱包余额
        $is_frozen_order_price = (isset($params['base_config']['is_shop_order_confirm_frozen_order_price']) && $params['base_config']['is_shop_order_confirm_frozen_order_price'] == 1) ? 1 : 0;
        if($is_frozen_order_price == 1 && $params['opt_type'] == 1)
        {
            // 用户钱包
            $user_wallet = WalletService::UserWallet($order['shop_user_id']);
            if($user_wallet['code'] != 0)
            {
                return $$user_wallet;
            }
            if($user_wallet['data']['normal_money'] < $order['pay_price'])
            {
                return DataReturn('钱包余额不足、请先充值['.$user_wallet['data']['normal_money'].'<'.$order['pay_price'].']', -1);
            }
        }

        // 启动事务
        Db::startTrans();

        // 捕获异常
        try {
            // 数据
            $data = [
                'order_id'      => $order['id'],
                'shop_id'       => $order['shop_id'],
                'user_id'       => $order['shop_user_id'],
                'status'        => intval($params['opt_type']),
                'reason'        => ($params['opt_type'] == 1) ? '' : $params['msg'],
                'order_price'   => $order['pay_price'],
            ];
            $info = Db::name('PluginsShopOrderConfirm')->where(['order_id'=>$data['order_id']])->find();
            if(empty($info))
            {
                $data['add_time'] = time();
                if(Db::name('PluginsShopOrderConfirm')->insertGetId($data) <= 0)
                {
                    throw new \Exception('数据添加失败');
                }
            } else {
                $data['upd_time'] = time();
                if(!Db::name('PluginsShopOrderConfirm')->where(['id'=>$info['id']])->update($data))
                {
                    throw new \Exception('数据更新失败');
                }
            }

            // 是否冻结用户钱包余额
            if($is_frozen_order_price == 1)
            {
                // 无订单信息或待缴状态
                if($data['status'] == 1 && (empty($info) || $info['pay_status'] == 0))
                {
                    // 是否余额充足
                    if($user_wallet['data']['normal_money'] < $data['order_price'])
                    {
                        throw new \Exception('钱包余额不足、请先充值['.$user_wallet['data']['normal_money'].'<'.$data['order_price'].']');
                    }

                    // 钱包变更、有效金额减少
                    $ret = WalletService::UserWalletMoneyUpdate($order['shop_user_id'], $data['order_price'], 0, 'normal_money', 0, '商家订单确认接收');
                    if($ret['code'] != 0)
                    {
                        throw new \Exception($ret['msg']);
                    }

                    // 钱包变更、冻结金额增加
                    $ret = WalletService::UserWalletMoneyUpdate($order['shop_user_id'], $data['order_price'], 1, 'frozen_money', 0, '商家订单确认接收');
                    if($ret['code'] != 0)
                    {
                        throw new \Exception($ret['msg']);
                    }

                    // 缴纳状态
                    if(!Db::name('PluginsShopOrderConfirm')->where(['order_id'=>$data['order_id']])->update(['pay_status'=>1, 'frozen_price'=>$data['order_price'], 'upd_time'=>time()]))
                    {
                        throw new \Exception('扣除状态更新失败');
                    }
                }
                // 拒绝已缴则退回
                if($data['status'] == 2 && !empty($info) && $info['pay_status'] == 1 && $info['frozen_price'] > 0)
                {
                    // 钱包变更、冻结金额减少
                    $ret = WalletService::UserWalletMoneyUpdate($order['shop_user_id'], $info['frozen_price'], 0, 'frozen_money', 0, '商家订单确认拒绝退回');
                    if($ret['code'] != 0)
                    {
                        throw new \Exception($ret['msg']);
                    }

                    // 钱包变更、有效金额增加
                    $ret = WalletService::UserWalletMoneyUpdate($order['shop_user_id'], $info['frozen_price'], 1, 'normal_money', 0, '商家订单确认拒绝退回');
                    if($ret['code'] != 0)
                    {
                        throw new \Exception($ret['msg']);
                    }

                    // 缴纳状态及扣款状态恢复到初始
                    if(!Db::name('PluginsShopOrderConfirm')->where(['order_id'=>$data['order_id']])->update(['pay_status'=>0, 'frozen_price'=>0, 'upd_time'=>time()]))
                    {
                        throw new \Exception('扣除状态更新失败');
                    }
                }
            }

            // 完成
            Db::commit();
            return DataReturn('操作成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 商家订单确认接收扣款退回
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-06-21
     * @desc    description
     * @param   [array]        $config   [插件配置]
     * @param   [int]          $order_id [订单id]
     * @param   [array]        $params   [输入参数]
     */
    public static function OrderReceiveGoBack($config, $order_id, $params = [])
    {
        if(!empty($config) && isset($config['is_shop_order_confirm_success_thaw_order_price']) && $config['is_shop_order_confirm_success_thaw_order_price'] == 1)
        {
            $info = Db::name('PluginsShopOrderConfirm')->where(['order_id'=>$order_id])->find();
            if(!empty($info) && $info['pay_status'] == 1 && $info['frozen_price'] > 0)
            {
                // 启动事务
                Db::startTrans();

                // 捕获异常
                try {
                    // 钱包变更、冻结金额减少
                    $ret = WalletService::UserWalletMoneyUpdate($info['user_id'], $info['frozen_price'], 0, 'frozen_money', 0, '商家订单确认完成解冻');
                    if($ret['code'] != 0)
                    {
                        throw new \Exception($ret['msg']);
                    }

                    // 钱包变更、有效金额增加
                    $ret = WalletService::UserWalletMoneyUpdate($info['user_id'], $info['frozen_price'], 1, 'normal_money', 0, '商家订单确认完成解冻');
                    if($ret['code'] != 0)
                    {
                        throw new \Exception($ret['msg']);
                    }

                    // 缴纳状态
                    if(!Db::name('PluginsShopOrderConfirm')->where(['id'=>$info['id']])->update(['pay_status'=>2, 'upd_time'=>time()]))
                    {
                        throw new \Exception('扣除状态更新失败');
                    }

                    // 完成
                    Db::commit();
                    return DataReturn('处理成功', 0);
                } catch(\Exception $e) {
                    Db::rollback();
                    return DataReturn($e->getMessage(), -1);
                }
            }
        }
        return DataReturn('无需处理', 0);
    }

    /**
     * 订单确认数据列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-06-21
     * @desc    description
     * @param   [array|int]          $order_ids [订单id]
     */
    public static function OrderReceiveDataList($order_ids)
    {
        $data = Db::name('PluginsShopOrderConfirm')->where(['order_id'=>$order_ids])->column('*', 'order_id');
        if(!empty($data))
        {
            $status_list = array_column(BaseService::$plugins_shop_order_confirm_status_list, 'name', 'value');
            $pay_status_list = array_column(BaseService::$plugins_shop_order_confirm_pay_status_list, 'name', 'value');
            foreach($data as &$v)
            {
                $v['status_name'] = array_key_exists($v['status'], $status_list) ? $status_list[$v['status']] : '';
                $v['pay_status_name'] = array_key_exists($v['pay_status'], $pay_status_list) ? $pay_status_list[$v['pay_status']] : '';
            }
        }
        return $data;
    }
}
?>