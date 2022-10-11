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
use app\service\ResourcesService;
use app\service\RegionService;
use app\plugins\intellectstools\service\OrderBaseService;

/**
 * 智能工具箱 - 订单地址修改服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class OrderAddressService
{
    /**
     * 订单地址修改保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-15
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderAddressSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'name',
                'error_msg'         => '姓名不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'tel',
                'error_msg'         => '联系电话不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'province',
                'error_msg'         => '省不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'city',
                'error_msg'         => '城市不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'address',
                'error_msg'         => '详细地址不能为空',
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

        // 附件
        $data_fields = ['idcard_front', 'idcard_back'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);
        if($attachment['code'] != 0)
        {
            return $attachment;
        }

        // 操作数据
        $data = [
            'alias'             => empty($params['alias']) ? '' : $params['alias'],
            'name'              => $params['name'],
            'tel'               => $params['tel'],
            'province'          => intval($params['province']),
            'city'              => intval($params['city']),
            'county'            => isset($params['county']) ? intval($params['county']) : 0,
            'address'           => $params['address'],
            'lng'               => isset($params['lng']) ? floatval($params['lng']) : 0,
            'lat'               => isset($params['lat']) ? floatval($params['lat']) : 0 ,
            'idcard_name'       => empty($params['idcard_name']) ? '' : $params['idcard_name'],
            'idcard_number'     => empty($params['idcard_number']) ? '' : $params['idcard_number'],
            'idcard_front'      => $attachment['data']['idcard_front'],
            'idcard_back'       => $attachment['data']['idcard_back'],
            'upd_time'          => time(),
        ];

        // 地区名称
        $region = RegionService::RegionName(array_filter([$data['province'], $data['city'], $data['county']]));
        if(!empty($region))
        {
            $data['province_name'] = (!empty($data['province']) && array_key_exists($data['province'], $region)) ? $region[$data['province']] : '';
            $data['city_name'] = (!empty($data['city']) && array_key_exists($data['city'], $region)) ? $region[$data['city']] : '';
            $data['county_name'] = (!empty($data['county']) && array_key_exists($data['county'], $region)) ? $region[$data['county']] : '';
        }


        // 订单地址更新
        if(Db::name('OrderAddress')->where(['order_id'=>intval($params['id'])])->update($data))
        {
            return DataReturn('更新成功', 0);
        }
        return DataReturn('更新失败', -100);
    }
}
?>