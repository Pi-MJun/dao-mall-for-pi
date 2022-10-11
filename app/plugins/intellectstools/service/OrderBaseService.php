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
namespace app\plugins\intellectstools\service;

use think\facade\Db;
use app\service\OrderService;

/**
 * 智能工具箱 - 订单基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class OrderBaseService
{
    // 订单改价-消息类型
    public static $message_business_type_order_price = '订单改价';

    // 订单修改-消息类型
    public static $message_business_type_order_edit = '订单修改';

    // 订单改价-可操作的订单状态
    public static $operate_price_order_status = [1];

    // 订单地址-可操作的订单状态
    public static $operate_address_order_status = [0,1,2];

    // 订单快递信息-可操作的订单状态
    public static $operate_express_order_status = [3];

    /**
     * 获取订单详情信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-15
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderDetail($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '订单id有误',
            ]
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否自定义条件
        $where = empty($params['where']) ? [] : $params['where'];

        // 条件
        $where = array_merge($where, [
            ['is_delete_time', '=', 0],
            ['id', '=', intval($params['id'])],
        ]);

        // 获取列表
        $data_params = [
            'm'         => 0,
            'n'         => 1,
            'where'     => $where,
            'is_items'  => isset($params['is_items']) ? intval($params['is_items']) : 0,
        ];
        $ret = OrderService::OrderList($data_params);
        if($ret['code'] == 0 && !empty($ret['data']) && !empty($ret['data'][0]))
        {
            return DataReturn('success', 0, $ret['data'][0]);
        }
        return DataReturn('没相关订单数据', -1);
    }
    
    /**
     * 订单修改信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-12
     * @desc    description
     * @param   [array]          $config [插件配置信息]
     * @param   [array]          $params [订单信息]
     */
    public static function OrderOperateEditInfo($config, $params = [])
    {
        // 返回数据
        $data = [];

        // 订单信息、配置信息是否开启订单修改
        if(!empty($params) && is_array($params) && isset($params['status']) && isset($params['order_model']) && !empty($config) && is_array($config) && isset($config['is_admin_order_operate_edit']) && $config['is_admin_order_operate_edit'] == 1)
        {
            // 订单改价
            if(in_array($params['status'], self::$operate_price_order_status))
            {
                $data[] = [
                    'type'  => 'price',
                    'name'  => '订单改价',
                ];
            }

            // 销售、自提
            if(in_array($params['order_model'], [0,2]))
            {
                // 订单地址
                if(in_array($params['status'], self::$operate_address_order_status))
                {
                    $data[] = [
                        'type'  => 'address',
                        'name'  => '订单地址',
                    ];
                }

                // 订单快递信息
                if(in_array($params['status'], self::$operate_express_order_status))
                {
                    $data[] = [
                        'type'  => 'express',
                        'name'  => '快递信息',
                    ];
                }
            }
        }

        return $data;
    }
}
?>