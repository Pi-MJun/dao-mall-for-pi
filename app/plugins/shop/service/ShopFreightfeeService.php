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
use app\plugins\shop\service\ShopService;
use app\plugins\freightfee\service\BaseService as FreightfeeBaseService;

/**
 * 多商户 - 运费设置服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class ShopFreightfeeService
{
    /**
     * 店铺运费数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-06
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function ShopFreightfeeData($user_id)
    {
        // 获取店铺
        $data_params = [
            'where'     => [
                ['user_id', '=', $user_id]
            ],
            'm'         => 0,
            'n'         => 1,
            'user_type' => 'shop',
        ];
        $ret = ShopService::ShopList($data_params);
        $data = [];
        if(!empty($ret['data']) && !empty($ret['data'][0]))
        {
            // 获取当前店铺仓库
            $warehouse = ShopService::ShopWarehouseInfo($ret['data'][0]['id']);
            if(!empty($warehouse))
            {
                // 获取运费数据
                $data_params = [
                    'm'             => 0,
                    'n'             => 1,
                    'where'         => [
                        ['w.id', '=', $warehouse['id']],
                    ],
                ];
                $ret = FreightfeeBaseService::WarehouseFeeList($data_params);
                $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
            }
        }
        return $data;
    }

    /**
     * 商品搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-12
     * @desc    description
     * @param   [int]           $user_id    [用户id]
     * @param   [array]         $params     [输入参数]
     */
    public static function GoodsSearchList($user_id, $params = [])
    {
        // 增加仅可以搜索当前店铺商品的条件
        $params['where'] = [
            ['shop_user_id', '=', $user_id]
        ];
        return FreightfeeBaseService::GoodsSearchList($params);
    }
}
?>