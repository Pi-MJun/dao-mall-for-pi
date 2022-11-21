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
use app\service\WarehouseGoodsService;
use app\plugins\shop\service\ShopService;
use app\plugins\shop\service\ShopGoodsService;

/**
 * 多商户 - 主商品复制同步
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class MainGoodsSyncService
{
    // 商品移除字段
    public static $unset_goods_fields = [
        'id',
    ];

    /**
     * 商品同步
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-24
     * @desc    description
     * @param   [array]           $params   [输入参数]
     */
    public static function GoodsSync($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'base_config',
                'error_msg'         => '插件配置信息有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'goods',
                'error_msg'         => '请选择商品',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'shop_user_id',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        if(!is_array($params['goods']))
        {
            $params['goods'] = json_decode(htmlspecialchars_decode($params['goods']), true);
        }

        // 获取店铺信息
        $shop = ShopService::UserShopInfo($params['shop_user_id'], 'user_id', 'id,user_id,bond_status,bond_expire_time', ['user_type'=>'shop']);
        if(empty($shop))
        {
            return DataReturn('店铺信息不存在', -1);
        }

        // 商品数据
        $goods_ids = array_column($params['goods'], 'goods_id');
        $where = [
            ['is_delete_time', '=', 0],
            ['shop_id', '=', 0],
            ['id', 'in', $goods_ids]
        ];
        $goods = Db::name('Goods')->where($where)->column('*', 'id');
        if(empty($goods))
        {
            return DataReturn('无可添加的商品', -1);
        }

        // 商品添加校验
        $ret = ShopGoodsService::GoodsAddCheck($shop, $params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 主商品是否存在添加记录
        $where = [
            ['shop_id', '=', $shop['id']],
            ['main_goods_id', 'in', $goods_ids],
        ];
        $shop_goods_copy = Db::name('PluginsShopGoodsCopyLog')->where($where)->column('main_goods_id', 'shop_goods_id');
        if(!empty($shop_goods_copy))
        {
            $where = [
                ['id', 'in', array_keys($shop_goods_copy)],
            ];
            $shop_goods = Db::name('Goods')->where($where)->column('*', 'id');
        }

        // 启动事务
        Db::startTrans();

        // 捕获异常
        try {
            // 商品处理
            foreach($goods as $v)
            {
                // 主商品id
                $main_goods_id = $v['id'];

                // 移除不存在的字段
                foreach(self::$unset_goods_fields as $fv)
                {
                    unset($v[$fv]);
                }

                // 默认值、访问量和销量、库存
                $v['access_count'] = 0;
                $v['sales_count'] = 0;
                $v['inventory'] = 0;

                // 店铺信息字段
                $v['shop_id'] = $shop['id'];
                $v['shop_user_id'] = $shop['user_id'];

                // 是否自定义上下架状态
                if(array_key_exists($main_goods_id, $params['goods']) && array_key_exists('is_shelves', $params['goods'][$main_goods_id]))
                {
                    $v['is_shelves'] = intval($params['goods'][$main_goods_id]['is_shelves']);
                }

                // 已添加则更新操作
                $is_exist = false;
                if(!empty($shop_goods_copy) && in_array($main_goods_id, $shop_goods_copy))
                {
                    // 商品不存在则重新添加
                    $shop_goods_id = array_search($main_goods_id, $shop_goods_copy);
                    if(!empty($shop_goods) && array_key_exists($shop_goods_id, $shop_goods))
                    {
                        // 访问量、销量、库存
                        $v['access_count'] = $shop_goods[$shop_goods_id]['access_count'];
                        $v['sales_count'] = $shop_goods[$shop_goods_id]['sales_count'];
                        $v['inventory'] = $shop_goods[$shop_goods_id]['inventory'];
                        $is_exist = true;
                    }
                }
                if($is_exist)
                {
                    if(Db::name('Goods')->where(['id'=>$shop_goods_id])->update($v) === false)
                    {
                        throw new \Exception('商品主数据同步更新失败');
                    }
                } else {
                    // 更新主数据
                    $shop_goods_id = Db::name('Goods')->insertGetId($v);
                    if($shop_goods_id <= 0)
                    {
                        throw new \Exception('商品主数据同步添加失败');
                    }
                }

                // 相册
                $photo = Db::name('GoodsPhoto')->where(['goods_id'=>$main_goods_id])->order('sort asc')->column('images');
                $ret = GoodsService::GoodsPhotoInsert($photo, $shop_goods_id);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }

                // 参数
                $ret = self::GoodsParamsInsert($shop_goods_id, $main_goods_id);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }

                // 分类
                $category = Db::name('GoodsCategoryJoin')->where(['goods_id'=>$main_goods_id])->column('category_id');
                $ret = GoodsService::GoodsCategoryInsert($category, $shop_goods_id);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }

                // 规格
                $ret = self::GoodsSpecificationsInsert($shop_goods_id, $main_goods_id);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }

                // 商品仓库库存
                $ret = self::WarehouseGoodsInsert($shop_goods_id, $main_goods_id, $shop['id'], $params['base_config']);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }

                // 商品规格库存同步
                $ret = WarehouseGoodsService::GoodsSpecInventorySync($shop_goods_id);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }

                // 更新商品基础信息
                $ret = GoodsService::GoodsSaveBaseUpdate($shop_goods_id);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }

                // 未添加则添加商品关联添加、更新则忽略
                if(empty($shop_goods_copy) || !in_array($main_goods_id, $shop_goods_copy))
                {
                    $shop_goods = [
                        'user_id'       => $shop['user_id'],
                        'shop_id'       => $shop['id'],
                        'main_goods_id' => $main_goods_id,
                        'shop_goods_id' => $shop_goods_id,
                        'add_time'      => time(),
                    ];
                    if(Db::name('PluginsShopGoodsCopyLog')->insertGetId($shop_goods) <= 0)
                    {
                        throw new \Exception('店铺商品关联添加失败');
                    }
                } else {
                    // 商品不存在则更新新的商品id
                    $temp_id = array_search($main_goods_id, $shop_goods_copy);                    
                    if(empty($shop_goods) || !array_key_exists($temp_id, $shop_goods))
                    {
                        $where = [
                            'user_id'       => $shop['user_id'],
                            'shop_id'       => $shop['id'],
                            'main_goods_id' => $main_goods_id,
                        ];
                        $shop_goods = [
                            'user_id'       => $shop['user_id'],
                            'shop_id'       => $shop['id'],
                            'main_goods_id' => $main_goods_id,
                            'shop_goods_id' => $shop_goods_id,
                            'upd_time'      => time(),
                        ];
                        Db::name('PluginsShopGoodsCopyLog')->where($where)->update($shop_goods);
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
     * 商品仓库库存添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-06-05
     * @desc    description
     * @param   [int]          $shop_goods_id [店铺商品id]
     * @param   [int]          $main_goods_id [主商品id]
     * @param   [int]          $shop_id       [店铺id]
     * @param   [array]        $config        [插件配置]
     */
    public static function WarehouseGoodsInsert($shop_goods_id, $main_goods_id, $shop_id, $config)
    {
        if(isset($config['is_edit_goods_inventory_sync_shop_goods']) && $config['is_edit_goods_inventory_sync_shop_goods'] == 1)
        {
            // 获取店铺仓库
            $shop_warehouse = ShopService::ShopWarehouseInfo($shop_id);
            if(empty($shop_warehouse))
            {
                $ret = ShopService::ShopWarehouseSave($shop_id, $config);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
                $warehouse_id = $ret['data'];
            } else {
                $warehouse_id = $shop_warehouse['id'];
            }

            // 删除仓库商品和规格
            $where = ['warehouse_id'=>$warehouse_id, 'goods_id'=>$shop_goods_id];
            Db::name('WarehouseGoods')->where($where)->delete();
            Db::name('WarehouseGoodsSpec')->where($where)->delete();

            // 仅获取非店铺商品的库存
            $warehouse_goods = Db::name('WarehouseGoods')->alias('wg')->join('warehouse w', 'w.id=wg.warehouse_id')->where(['wg.goods_id'=>$main_goods_id, 'w.shop_id'=>0])->field('wg.*')->find();
            if(!empty($warehouse_goods))
            {
                $warehouse_goods['goods_id'] = $shop_goods_id;
                $warehouse_goods['warehouse_id'] = $warehouse_id;
                $main_warehouse_goods_id = $warehouse_goods['id'];
                // 添加仓库商品
                unset($warehouse_goods['id']);
                $warehouse_goods_id = Db::name('WarehouseGoods')->insertGetId($warehouse_goods);
                if($warehouse_goods_id <= 0)
                {
                    return DataReturn('店铺仓库商品添加失败', -1);
                }

                // 商品库存
                $warehouse_goods_spec = Db::name('WarehouseGoodsSpec')->where(['warehouse_goods_id'=>$main_warehouse_goods_id])->select()->toArray();
                if(!empty($warehouse_goods_spec))
                {
                    foreach($warehouse_goods_spec as &$v)
                    {
                        $v['goods_id'] = $shop_goods_id;
                        $v['warehouse_id'] = $warehouse_id;
                        $v['warehouse_goods_id'] = $warehouse_goods_id;
                        unset($v['id']);
                    }
                    if(Db::name('WarehouseGoodsSpec')->insertAll($warehouse_goods_spec) < count($warehouse_goods_spec))
                    {
                        return DataReturn('规格库存添加失败', -1);
                    }
                }
            }
        }
        return DataReturn('success', 0);
    }

    /**
     * 订单商品库存扣除和回滚同步到相关商品
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-06-06
     * @desc    description
     * @param   [int]           $goods_id [商品id]
     */
    public static function OrderGoodsInventorySync($params = [])
    {
        $shop_goods = Db::name('PluginsShopGoodsCopyLog')->where(['main_goods_id|shop_goods_id'=>$params['goods_id']])->field('main_goods_id,shop_goods_id')->select()->toArray();
        if(!empty($shop_goods))
        {
            // 商品id合并处理、获取商品信息
            $main_goods_ids = array_unique(array_column($shop_goods, 'main_goods_id'));
            $shop_goods_ids = array_unique(array_column($shop_goods, 'shop_goods_id'));
            $where = [
                ['id', 'in', array_unique(array_merge($main_goods_ids, $shop_goods_ids))],
                ['id', '<>', $params['goods_id']],
                ['is_delete_time', '=', 0],
            ];
            $goods_ids = Db::name('Goods')->where($where)->column('id');
            if(!empty($goods_ids))
            {
                // 规格key，空则默认default
                $md5_key = 'default';
                if(!empty($params['spec']))
                {
                    if(!is_array($params['spec']))
                    {
                        $params['spec'] = json_decode($params['spec'], true);
                    }
                    $md5_key = implode('', array_column($params['spec'], 'value'));
                }
                $md5_key = md5($md5_key);

                // 仓库商品
                $warehouse_goods = Db::name('WarehouseGoods')->where(['goods_id'=>$params['goods_id']])->field('inventory')->find();
                if(!empty($warehouse_goods))
                {
                    $warehouse_goods['upd_time'] = time();
                    if(Db::name('WarehouseGoods')->where(['goods_id'=>$goods_ids])->update($warehouse_goods) === false)
                    {
                        return DataReturn('仓库商品库存同步失败', -1);
                    }

                    // 仓库商品规格
                    $warehouse_goods_spec = Db::name('WarehouseGoodsSpec')->where(['goods_id'=>$params['goods_id'], 'md5_key'=>$md5_key])->field('inventory')->find();
                    if(!empty($warehouse_goods_spec))
                    {
                        if(Db::name('WarehouseGoodsSpec')->where(['goods_id'=>$goods_ids, 'md5_key'=>$md5_key])->update($warehouse_goods_spec) === false)
                        {
                            return DataReturn('仓库商品规格库存同步失败', -1);
                        }

                        // 同步商品库存数据
                        foreach($goods_ids as $gid)
                        {
                            // 获取商品实际规格
                            $res = GoodsService::GoodsSpecificationsActual($gid);
                            if(empty($res['value']))
                            {
                                // 没有规格则读取默认规格数据
                                $res['value'][] = [
                                    'base_id'   => Db::name('GoodsSpecBase')->where(['goods_id'=>$gid])->value('id'),
                                    'value'     => 'default',
                                ];
                            }
                            $inventory_total = 0;

                            // 商品规格库存
                            foreach($res['value'] as $v)
                            {
                                $inventory = WarehouseGoodsService::WarehouseGoodsSpecInventory($gid, str_replace(GoodsService::$goods_spec_to_string_separator, '', $v['value']));
                                if(Db::name('GoodsSpecBase')->where(['id'=>$v['base_id'], 'goods_id'=>$gid])->update(['inventory'=>$inventory]) === false)
                                {
                                    return DataReturn('商品规格库存同步失败', -20);
                                }
                                $inventory_total += $inventory;
                            }

                            // 商品库存
                            $data = [
                                'inventory' => $inventory_total,
                                'upd_time'  => time(),
                            ];
                            if(Db::name('Goods')->where(['id'=>$gid])->update($data) === false)
                            {
                                return DataReturn('商品库存同步失败', -21);
                            }
                        }
                    }
                }
            }
        }
        return DataReturn('success', 0);
    }

    /**
     * 商品规格添加
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-07-10
     * @desc    description
     * @param   [int]          $shop_goods_id [店铺商品id]
     * @param   [int]          $main_goods_id [主商品id]
     */
    public static function GoodsSpecificationsInsert($shop_goods_id, $main_goods_id)
    {
        // 删除原来的数据
        $where = ['goods_id'=>$shop_goods_id];
        Db::name('GoodsSpecType')->where($where)->delete();
        Db::name('GoodsSpecValue')->where($where)->delete();
        Db::name('GoodsSpecBase')->where($where)->delete();

        // 类型
        // 获取规格数据
        $where = ['goods_id'=>$main_goods_id];
        $base = Db::name('GoodsSpecBase')->where($where)->select()->toArray();
        if(!empty($base))
        {
            // 获取规格值
            $value = Db::name('GoodsSpecValue')->where($where)->select()->toArray();
            $value_group = [];
            if(!empty($value))
            {
                foreach($value as $v)
                {
                    $value_group[$v['goods_spec_base_id']][] = $v;
                }
            }

            // 添加规格基础
            $value_data = [];
            foreach($base as $v)
            {
                $temp_base_id = $v['id'];
                unset($v['id']);
                $v['goods_id'] = $shop_goods_id;
                $base_id = Db::name('GoodsSpecBase')->insertGetId($v);
                if(empty($base_id))
                {
                    return DataReturn('规格基础添加失败', -1);
                }

                // 规格值
                if(!empty($value_group) && array_key_exists($temp_base_id, $value_group))
                {
                    foreach($value_group[$temp_base_id] as $vs)
                    {
                        unset($vs['id']);
                        $vs['goods_id'] = $shop_goods_id;
                        $vs['goods_spec_base_id'] = $base_id;
                        $value_data[] = $vs;
                    }
                }
            }

            // 规格值添加
            if(!empty($value_data))
            {
                if(Db::name('GoodsSpecValue')->insertAll($value_data) < count($value_data))
                {
                    return DataReturn('规格值添加失败', -1);
                }
            }
        }

        // 规格类型
        $type = Db::name('GoodsSpecType')->where($where)->select()->toArray();
        if(!empty($type))
        {
            $type_data = [];
            foreach($type as $v)
            {
                unset($v['id']);
                $v['goods_id'] = $shop_goods_id;
                $type_data[] = $v;
            }
            if(Db::name('GoodsSpecType')->insertAll($type_data) < count($type_data))
            {
                return DataReturn('规格类型添加失败', -1);
            }
        }

        return DataReturn('添加成功', 0);
    }

    /**
     * 商品参数添加
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-07-10
     * @desc    description
     * @param   [int]            $shop_goods_id [店铺商品id]
     * @param   [int]            $main_goods_id [主商品id]
     */
    public static function GoodsParamsInsert($shop_goods_id, $main_goods_id)
    {
        // 删除商品参数
        Db::name('GoodsParams')->where(['goods_id'=>$shop_goods_id])->delete();

        // 获取参数数据
        $params = Db::name('GoodsParams')->where(['goods_id'=>$main_goods_id])->select()->toArray();
        if(!empty($params))
        {
            $params_group = [];
            foreach($params as $v)
            {
                unset($v['id']);
                $v['goods_id'] = $shop_goods_id;
                $params_group[] = $v;
            }
            if(Db::name('GoodsParams')->insertAll($params_group) < count($params_group))
            {
                return DataReturn('规格参数添加失败', -1);
            }
        }
        return DataReturn('添加成功', 0);
    }

    /**
     * 商品保存成功同步到店铺添加的商品
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-06-05
     * @desc    description
     * @param   [int]          $goods_id    [商品id]
     * @param   [array]        $base_config [插件配置]
     */
    public static function GoodsSaveSync($goods_id, $base_config)
    {
        $shop_goods = Db::name('PluginsShopGoodsCopyLog')->where(['main_goods_id'=>$goods_id])->field('main_goods_id,shop_id,user_id')->group('main_goods_id')->select()->toArray();
        if(!empty($shop_goods))
        {
            foreach($shop_goods as $v)
            {
                $params = [
                    'base_config'   => $base_config,
                    'shop_user_id'  => $v['user_id'],
                    'goods'         => [
                        $v['main_goods_id'] => [
                            'goods_id'  => $v['main_goods_id'],
                        ]
                    ]
                ];
                $ret = self::GoodsSync($params);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
            }
        }
        return DataReturn('success', 0);
    }

    /**
     * 商品库存同步到店铺添加的商品
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-06-05
     * @desc    description
     * @param   [int]          $goods_id    [商品id]
     * @param   [array]        $base_config [插件配置]
     */
    public static function GoodsInventorySync($goods_id, $base_config)
    {
        $shop_goods = Db::name('PluginsShopGoodsCopyLog')->where(['main_goods_id'=>$goods_id])->field('main_goods_id,shop_goods_id,shop_id')->group('main_goods_id')->select()->toArray();
        if(!empty($shop_goods))
        {
            foreach($shop_goods as $v)
            {
                // 库存同步
                $ret = self::WarehouseGoodsInsert($v['shop_goods_id'], $v['main_goods_id'], $v['shop_id'], $base_config);
                if($ret['code'] != 0)
                {
                    return $ret;
                }

                // 商品规格库存同步
                $ret = WarehouseGoodsService::GoodsSpecInventorySync($v['shop_goods_id']);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
            }
        }
        return DataReturn('success', 0);
    }

    /**
     * 商品主数据上下架同步
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-25
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsStatusUpdateSync($params = [])
    {
        // 获取商品数据
        if(!empty($params['goods_id']) && isset($params['status']) && !empty($params['field']) && in_array($params['field'], ['is_shelves', 'is_deduction_inventory']))
        {
            $shop_goods_ids = Db::name('PluginsShopGoodsCopyLog')->where(['main_goods_id'=>$params['goods_id']])->column('shop_goods_id');
            if(!empty($shop_goods_ids))
            {
                // 数据更新
                if(Db::name('Goods')->where(['id'=>$shop_goods_ids])->update([$params['field']=>$params['status'], 'upd_time'=>time()]) === false)
                {
                    return DataReturn('店铺商品更新失败', -1);
                }
            }
        }
        return DataReturn('操作成功', 0);
    }

    /**
     * 商品主数据删除同步
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-25
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsDeleteSync($params = [])
    {
        if(!empty($params['goods_ids']))
        {
            $shop_goods_ids = Db::name('PluginsShopGoodsCopyLog')->where(['main_goods_id'=>$params['goods_ids']])->column('shop_goods_id');
            if(!empty($shop_goods_ids))
            {
                GoodsService::GoodsDeleteHandle($shop_goods_ids);
            }
        }
    }
}
?>