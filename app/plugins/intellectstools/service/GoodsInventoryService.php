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
use app\service\GoodsService;
use app\service\WarehouseService;
use app\service\WarehouseGoodsService;

/**
 * 智能工具箱 - 商品库存服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class GoodsInventoryService
{
    /**
     * 商品库存数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
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
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
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
        $warehouse = WarehouseService::WarehouseList(['field'=>'id,name,alias,is_enable', 'where'=>['is_delete_time'=>0]]);
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
     * @date    2021-05-07
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
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据处理
        $data = [];
        $goods_id = intval($params['goods_id']);
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

                    // 库存数据
                    $inventory = intval($params['inventory'][$wid][$ks]);
                    if($inventory > 0)
                    {
                        $data[$wid][] = [
                            'warehouse_id'          => $wid,
                            'goods_id'              => $goods_id,
                            'md5_key'               => $params['md5_key'][$wid][$ks],
                            'spec'                  => htmlspecialchars_decode($vs),
                            'inventory'             => $inventory,
                            'add_time'              => time(),
                        ];
                    }
                }
            }
        }

        // 需要删除的数据
        $del = [];
        foreach($params['inventory'] as $wid=>$iv)
        {
            if(array_sum($iv) <= 0)
            {
                $del[] = $wid;
            }
        }

        // 添加或删除
        if(!empty($data) || !empty($del))
        {
            // 启动事务
            Db::startTrans();

            // 捕获异常
            try {
                // 写入数据库
                if(!empty($data))
                {
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
                }

                // 删除数据
                if(!empty($del))
                {
                    // 仓库商品、仓库规格
                    $where = [
                        ['warehouse_id', 'in', $del],
                        ['goods_id', '=', $goods_id],
                    ];
                    Db::name('WarehouseGoods')->where($where)->delete();
                    Db::name('WarehouseGoodsSpec')->where($where)->delete();
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

    /**
     * 仓库商品库存预警
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-06-28
     * @desc    description
     * @param   [array]           $config [插件配置]
     */
    public static function WarehouseGoodsInventoryEarlyWarning($config)
    {
        // 预警值、默认5
        $early_warning_number = empty($config['admin_warehouse_inventory_early_warning_number']) ? 5 : intval($config['admin_warehouse_inventory_early_warning_number']);
        // 库存数据读取最大数量、默认8
        $limit_number = empty($config['admin_warehouse_max_number']) ? 8 : intval($config['admin_warehouse_max_number']);
        // 获取预警值内的仓库商品总数
        $where = [
            ['w.is_enable', '=', 1],
            ['w.is_delete_time', '=', 0],
            ['wgs.inventory', '<=', $early_warning_number],
            ['wg.is_enable', '=', 1],
        ];
        $data = Db::name('Warehouse')->alias('w')->join('warehouse_goods wg', 'w.id=wg.warehouse_id')->join('warehouse_goods_spec wgs', 'wg.id=wgs.warehouse_goods_id')->where($where)->group('wg.warehouse_id')->field('w.name as warehouse_name,wgs.warehouse_id,wgs.goods_id,count(distinct wg.goods_id) as goods_count')->limit($limit_number)->select()->toArray();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // 仓库地址
                $v['warehouse_url'] = MyUrl('admin/warehouse/detail', ['id'=>$v['warehouse_id']]);

                // 商品查看
                $v['warehouse_goods_url'] = MyUrl('admin/warehousegoods/index', ['warehouse_id'=>$v['warehouse_id'], 'spec_inventory_max'=>$early_warning_number]);

                // 商品数量是否大于100
                if($v['goods_count'] > 99)
                {
                    $v['goods_count'] = '99+';
                }
            }
        }
        return $data;
    }
}
?>