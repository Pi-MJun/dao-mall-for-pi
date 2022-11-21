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
use app\service\WarehouseService;
use app\service\WarehouseGoodsService;
use app\plugins\shop\service\ShopService;

/**
 * 多商户 - 商品库存服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class GoodsInventoryService
{
    /**
     * 商品库存数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsInventoryData($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '数据id有误',
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

        // 用户店铺
        $shop = ShopService::UserShopInfo($params['user_id']);
        if(empty($shop))
        {
            return DataReturn('用户店铺有误', -1);
        }

        // 获取商品规格
        $goods_id = intval($params['id']);
        $res = GoodsService::GoodsSpecificationsActual($goods_id);
        $inventory_spec = [];
        if(!empty($res['value']) && is_array($res['value']))
        {
            // 获取当前配置的库存
            $spec = array_column($res['value'], 'value');
            foreach($spec as $v)
            {
                $arr = explode(GoodsService::$goods_spec_to_string_separator, $v);
                $inventory_spec[] = [
                    'name'      => implode(' / ', $arr),
                    'spec'      => json_encode(WarehouseGoodsService::GoodsSpecMuster($v, $res['title']), JSON_UNESCAPED_UNICODE),
                    'md5_key'   => md5(implode('', $arr)),
                    'inventory' => 0,
                ];
            }
        } else {
            // 没有规格则处理默认规格 default
            $str = 'default';
            $inventory_spec[] = [
                'name'      => '默认规格',
                'spec'      => $str,
                'md5_key'   => md5($str),
                'inventory' => 0,
            ];
        }

        // 获取仓库
        $where = [
            'is_delete_time'    => 0,
            'shop_id'           => $shop['id'],
            'shop_user_id'      => $shop['user_id'],
        ];
        $warehouse = WarehouseService::WarehouseList(['field'=>'id,name,alias,is_enable', 'where'=>$where]);
        if(!empty($warehouse['data']))
        {
            // 获取仓库商品
            $warehouse_goods = Db::name('WarehouseGoods')->where(['warehouse_id'=>array_column($warehouse['data'], 'id'), 'goods_id'=>$goods_id])->column('*', 'warehouse_id');
            foreach($warehouse['data'] as &$v)
            {
                // 仓库商品规格库存
                $v['inventory_spec'] = $inventory_spec;

                // 获取库存
                if(!empty($warehouse_goods) && array_key_exists($v['id'], $warehouse_goods))
                {
                    $keys = array_column($inventory_spec, 'md5_key');
                    $where = [
                        'md5_key'               => $keys,
                        'warehouse_goods_id'    => $warehouse_goods[$v['id']]['id'],
                        'warehouse_id'          => $warehouse_goods[$v['id']]['warehouse_id'],
                        'goods_id'              => $warehouse_goods[$v['id']]['goods_id'],
                    ];
                    $inventory_data = Db::name('WarehouseGoodsSpec')->where($where)->column('inventory', 'md5_key');
                    if(!empty($inventory_data))
                    {
                        foreach($v['inventory_spec'] as &$iv)
                        {
                            if(array_key_exists($iv['md5_key'], $inventory_data))
                            {
                                $iv['inventory'] = $inventory_data[$iv['md5_key']];
                                $iv['is_enable'] = $warehouse_goods[$v['id']]['is_enable'];
                            }
                        }
                    }
                }
            }
        }
        return $warehouse;
    }

    /**
     * 商品库存保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsInventorySave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'goods_id',
                'error_msg'         => '数据id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'inventory',
                'error_msg'         => '库存数据有误',
            ],
            [
                'checked_type'      => 'is_array',
                'key_name'          => 'inventory',
                'error_msg'         => '库存数据有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'md5_key',
                'error_msg'         => '库存唯一值有误',
            ],
            [
                'checked_type'      => 'is_array',
                'key_name'          => 'md5_key',
                'error_msg'         => '库存唯一值有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'spec',
                'error_msg'         => '库存规格有误',
            ],
            [
                'checked_type'      => 'is_array',
                'key_name'          => 'spec',
                'error_msg'         => '库存规格有误',
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

        // 用户店铺
        $shop = ShopService::UserShopInfo($params['user_id']);
        if(empty($shop))
        {
            return DataReturn('用户店铺有误', -1);
        }

        // 数据处理
        $data = [];
        $goods_id = intval($params['goods_id']);

        // 获取应用商商品
        $goods = Db::name('Goods')->where(['id'=>$goods_id, 'shop_user_id'=>$params['user_id']])->find();
        if(empty($goods))
        {
            return DataReturn('无商品数据', -1);
        }

        // 根据修改规格处理数据
        foreach($params['spec'] as $wid=>$spec)
        {
            // 仓库id必须存在
            if(!array_key_exists($wid, $params['md5_key']) || !array_key_exists($wid, $params['inventory']))
            {
                continue;
            }

            if(!empty($spec) && is_array($spec))
            {
                foreach($spec as $ks=>$vs)
                {
                    // 规格值,md5key,库存 必须存在
                    if(!array_key_exists($ks, $params['md5_key'][$wid]) || !array_key_exists($ks, $params['inventory'][$wid]))
                    {
                        continue;
                    }

                    $data[$wid][] = [
                        'warehouse_id'          => $wid,
                        'goods_id'              => $goods_id,
                        'md5_key'               => $params['md5_key'][$wid][$ks],
                        'spec'                  => htmlspecialchars_decode($vs),
                        'inventory'             => intval($params['inventory'][$wid][$ks]),
                        'add_time'              => time(),
                    ];
                }
            }
        }

        // 写入数据库
        if(!empty($data))
        {
            // 启动事务
            Db::startTrans();

            // 捕获异常
            try {
                // 获取仓库商品
                $warehouse_goods = Db::name('WarehouseGoods')->where(['warehouse_id'=>array_keys($data), 'goods_id'=>$goods_id])->column('id', 'warehouse_id');
                foreach($data as $wid=>$v)
                {
                    // 库存商品不存在则增加
                    if(!array_key_exists($wid, $warehouse_goods))
                    {
                        $where = [
                            'warehouse_id'  => $wid,
                            'goods_id'      => $goods_id,
                        ];
                        $ret = WarehouseGoodsService::WarehouseGoodsAdd($where);
                        if($ret['code'] != 0)
                        {
                            throw new \Exception('仓库商品添加失败');
                        }
                        $warehouse_goods_id = Db::name('WarehouseGoods')->where($where)->value('id');
                    } else {
                        $warehouse_goods_id = $warehouse_goods[$wid];
                    }

                    // 删除原始数据
                    $where = [
                        'warehouse_id'  => $wid,
                        'goods_id'      => $goods_id,
                    ];
                    Db::name('WarehouseGoodsSpec')->where($where)->delete();

                    // 添加数据
                    array_walk($v, function(&$item, $key, $wgid)
                    {
                        $item['warehouse_goods_id'] = $wgid;
                    }, $warehouse_goods_id);
                    if(Db::name('WarehouseGoodsSpec')->insertAll($v) < count($v))
                    {
                        throw new \Exception('规格库存添加失败');
                    }

                    // 仓库商品更新
                    if(!Db::name('WarehouseGoods')->where(['id'=>$warehouse_goods_id])->update([
                        'inventory' => array_sum(array_column($v, 'inventory')),
                        'upd_time'  => time(),
                    ]))
                    {
                        throw new \Exception('库存商品更新失败');
                    }
                }

                // 同步商品库存
                $ret = WarehouseGoodsService::GoodsSpecInventorySync($goods_id);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }

                // 完成
                Db::commit();
            } catch(\Exception $e) {
                Db::rollback();
                return DataReturn($e->getMessage(), -1);
            }
        }
        return DataReturn('操作成功', 0);
    }
}
?>