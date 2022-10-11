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
use app\plugins\intellectstools\service\OrderBaseService;

/**
 * 智能工具箱 - 订单地址修改服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class OrderExpressService
{
    /**
     * 订单快递修改保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-15
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderExpressSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'express_id',
                'error_msg'         => '请选择快递公司',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'express_number',
                'error_msg'         => '请填写快递单号',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '订单id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取订单信息
        $ret = OrderBaseService::OrderDetail($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 操作数据
        $data = [
            'express_id'        => intval($params['express_id']),
            'express_number'    => trim($params['express_number']),
            'upd_time'          => time(),
        ];

        // 订单地址更新
        if(Db::name('Order')->where(['id'=>intval($params['id'])])->update($data))
        {
            return DataReturn('更新成功', 0);
        }
        return DataReturn('更新失败', -100);
    }
}
?>