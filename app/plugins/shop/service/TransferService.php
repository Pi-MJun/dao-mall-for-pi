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
use app\service\GoodsService;

/**
 * 多商户 - 数据转移服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class TransferService
{
    /**
     * 仓库列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function WarehouseList($params = [])
    {
        $where = ['is_enable'=>1, 'is_delete_time'=>0];
        $field = 'id,name,shop_id';
        $order_by = 'level desc, id desc';
        return Db::name('Warehouse')->field($field)->where($where)->order($order_by)->select()->toArray();
    }

    /**
     * 数据转移操作
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function TransferSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'warehouse_id',
                'error_msg'         => '请选择仓库id',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 商品id 和 商品分类 必须填写一项
        if(empty($params['goods_ids']) && empty($params['category_id']) && empty($params['brand_id']))
        {
            return DataReturn('指定商品id/商品分类/品牌、必须选填一项', -1);
        }

        // 获取仓库信息
        $warehouse = Db::name('Warehouse')->where(['id'=>intval($params['warehouse_id']), 'is_enable'=>1, 'is_delete_time'=>0])->field('id,name,shop_id')->find();
        if(empty($warehouse))
        {
            return DataReturn('仓库信息有误', -1);
        }

        // 商品id
        $goods_ids = self::GoodsIdsHandle($params);
        if(empty($goods_ids))
        {
            return DataReturn('商品信息有误', -1);
        }

        // 启动事务
        Db::startTrans();

        // 捕获异常
        try {
            // 更新仓库商品信息
            $where = [
                'goods_id'  => $goods_ids,
            ];
            $data  = [
                'warehouse_id'  => $warehouse['id'],
                'upd_time'      => time(),
            ];
            if(Db::name('WarehouseGoods')->where($where)->update($data) === false)
            {
                throw new \Exception('仓库商品更新失败');
            }

            // 仓库商品规格
            $data  = [
                'warehouse_id'  => $warehouse['id'],
            ];
            if(Db::name('WarehouseGoodsSpec')->where($where)->update($data) === false)
            {
                throw new \Exception('仓库商品规格更新失败');
            }

            // 获取店铺信息
            $shop = empty($warehouse['shop_id']) ? [] : Db::name('PluginsShop')->where(['id'=>$warehouse['shop_id']])->field('id,user_id')->find();

            // 商品所属店铺更新
            $data  = [
                'shop_id'       => empty($shop) ? 0 : $shop['id'],
                'shop_user_id'  => empty($shop) ? 0 : $shop['user_id'],
                'upd_time'      => time(),
            ];
            if(Db::name('Goods')->where(['id'=>$goods_ids])->update($data) === false)
            {
                throw new \Exception('商品更新失败');
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
     * 商品id处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsIdsHandle($params = [])
    {
        $data = [];

        // 商品id
        if(!empty($params['goods_ids']))
        {
            $data = explode(',', $params['goods_ids']);
        }

        // 商品分类
        if(!empty($params['category_id']))
        {
            // 获取所有子级分类id
            $cids = GoodsService::GoodsCategoryItemsIds($params['category_id']);
            if(!empty($cids))
            {
                $gids = Db::name('GoodsCategoryJoin')->where(['category_id'=>$cids])->column('goods_id');
                if(!empty($gids))
                {
                    $data = array_merge($data, $gids);
                }
            }
        }

        // 品牌
        if(!empty($params['brand_id']))
        {
            $gids = Db::name('Goods')->where(['brand_id'=>intval($params['brand_id'])])->column('id');
            if(!empty($gids))
            {
                $data = array_merge($data, $gids);
            }
        }

        // 去重商品id并返回
        return array_unique($data);
    }
}
?>