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
use app\service\ResourcesService;
use app\service\OrderService;
use app\service\OrderAftersaleService;

/**
 * 多商户 - 订单售后服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-02-14
 * @desc    description
 */
class ShopOrderAftersaleService
{
    /**
     * 获取订单售后纪录列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-23
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderAftersaleList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? 'oa.*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'oa.id desc' : $params['order_by'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        // 获取数据列表
        $data = Db::name('OrderAftersale')->alias('oa')->join('order o', 'o.id=oa.order_id')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            $type_list = MyConst('common_order_aftersale_type_list');
            $status_list = MyConst('common_order_aftersale_status_list');
            $refundment_list = MyConst('common_order_aftersale_refundment_list');
            foreach($data as &$v)
            {
                // 订单商品
                $order = OrderAftersaleService::OrdferGoodsRow($v['order_id'], $v['order_detail_id'], $v['user_id']);
                $v['order_data'] = $order['data'];

                // 用户信息
                $v['user'] = UserService::GetUserViewInfo($v['user_id']);

                // 类型
                $v['type_text'] = array_key_exists($v['type'], $type_list) ? $type_list[$v['type']]['name'] : '';

                // 状态
                $v['status_text'] = array_key_exists($v['status'], $status_list) ? $status_list[$v['status']]['name'] : '';

                // 退款方式
                $v['refundment_text'] = ($v['status'] == 3 && array_key_exists($v['refundment'], $refundment_list)) ? $refundment_list[$v['refundment']]['name'] : '';

                // 图片
                if(!empty($v['images']))
                {
                    $images = json_decode($v['images'], true);
                    foreach($images as $ik=>$iv)
                    {
                        $images[$ik] = ResourcesService::AttachmentPathViewHandle($iv);
                    }
                    $v['images'] = $images;
                } else {
                    $v['images'] = null;
                }

                // 申请时间
                $v['apply_time_time'] = empty($v['apply_time']) ? null : date('Y-m-d H:i:s', $v['apply_time']);
                $v['apply_time_date'] = empty($v['apply_time']) ? null : date('Y-m-d', $v['apply_time']);

                // 确认时间
                $v['confirm_time_time'] = empty($v['confirm_time']) ? null : date('Y-m-d H:i:s', $v['confirm_time']);
                $v['confirm_time_date'] = empty($v['confirm_time']) ? null : date('Y-m-d', $v['confirm_time']);

                // 退货时间
                $v['delivery_time_time'] = empty($v['delivery_time']) ? null : date('Y-m-d H:i:s', $v['delivery_time']);
                $v['delivery_time_date'] = empty($v['delivery_time']) ? null : date('Y-m-d', $v['delivery_time']);

                // 审核时间
                $v['audit_time_time'] = empty($v['audit_time']) ? null : date('Y-m-d H:i:s', $v['audit_time']);
                $v['audit_time_date'] = empty($v['audit_time']) ? null : date('Y-m-d', $v['audit_time']);

                // 取消时间
                $v['cancel_time_time'] = empty($v['cancel_time']) ? null : date('Y-m-d H:i:s', $v['cancel_time']);
                $v['cancel_time_date'] = empty($v['cancel_time']) ? null : date('Y-m-d', $v['cancel_time']);

                // 添加时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = date('Y-m-d', $v['add_time']);

                // 更新时间
                $v['upd_time_time'] = empty($v['upd_time']) ? null : date('Y-m-d H:i:s', $v['upd_time']);
                $v['upd_time_date'] = empty($v['upd_time']) ? null : date('Y-m-d', $v['upd_time']);
                
            }
        }
        return DataReturn('获取成功', 0, $data);
    }

    /**
     * 订单售后总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function OrderAftersaleTotal($where = [])
    {
        return (int) Db::name('OrderAftersale')->alias('oa')->join('order o', 'o.id=oa.order_id')->where($where)->count();
    }

    /**
     * 获取店铺订单售后数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-03-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ShopAftersaleDataCheck($params)
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否开启订单售后
        if(empty($params['base_config']) || !isset($params['base_config']['is_shop_orderaftersale']) || $params['base_config']['is_shop_orderaftersale'] != 1)
        {
            return DataReturn('商家端未开启订单售后、请联系管理员', -1);
        }

        // 售后订单
        $data_params = [
            'm'     => 0,
            'n'     => 1,
            'where' => [
                ['oa.id', '=', intval($params['id'])],
                ['o.shop_user_id', '=', intval($params['user_id'])]
            ],
        ];
        $ret = self::OrderAftersaleList($data_params);
        if(empty($ret['data']) || empty($ret['data'][0]))
        {
            $ret['code'] = -1;
            $ret['msg'] = '无相关订单售后数据';
        } else {
            $ret['data'] = $ret['data'][0];
        }
        return $ret;
    }

    /**
     * 订单售后取消
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function AftersaleCancel($params = [])
    {
        // 订单售后数据
        $ret = self::ShopAftersaleDataCheck($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 调用系统方法
        return OrderAftersaleService::AftersaleCancel($params);
    }

    /**
     * 确认
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-27
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function AftersaleConfirm($params = [])
    {
        // 订单售后数据
        $ret = self::ShopAftersaleDataCheck($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 调用系统方法
        return OrderAftersaleService::AftersaleConfirm($params);
    }

    /**
     * 审核
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-27
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function AftersaleAudit($params = [])
    {
        // 订单售后数据
        $ret = self::ShopAftersaleDataCheck($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 调用系统方法
        return OrderAftersaleService::AftersaleAudit($params);
    }

    /**
     * 拒绝
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-27
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function AftersaleRefuse($params = [])
    {
        // 订单售后数据
        $ret = self::ShopAftersaleDataCheck($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 调用系统方法
        return OrderAftersaleService::AftersaleRefuse($params);
    }
}
?>