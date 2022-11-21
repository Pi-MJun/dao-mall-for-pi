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
use app\plugins\shop\service\ShopService;

/**
 * 多商户 - 商品服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-03-31
 * @desc    description
 */
class ShopGoodsService
{
    /**
     * 获取商品列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-08-29
     * @desc    description
     * @param   array           $params [输入参数: where, field, is_photo]
     */
    public static function GoodsList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : trim($params['order_by']);
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $data = Db::name('Goods')->field($field)->where($where)->order($order_by)->limit($m, $n)->select()->toArray();
        return GoodsService::GoodsDataHandle($data, $params);
    }

    /**
     * 获取商品总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-07
     * @desc    description
     * @param   [array]           $where [条件]
     */
    public static function GoodsTotal($where = [])
    {
        return (int) Db::name('Goods')->where($where)->count();
    }

    /**
     * 商品保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsSave($params = [])
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
                'key_name'          => 'shop_user_id',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'shop_category_id',
                'error_msg'         => '请选择店铺商品分类',
            ],
            [
                'checked_type'      => 'max',
                'key_name'          => 'shop_settle_rate',
                'checked_data'      => '100',
                'is_checked'        => 1,
                'error_msg'         => '商品结算比例值不能大于100',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否开启店铺商品信息添加/编辑权限
        if(!isset($params['base_config']['is_shop_goods_save']) || $params['base_config']['is_shop_goods_save'] != 1)
        {
            return DataReturn('未开启店铺修改商品权限、请联系管理员！', -1);
        }

        // 获取店铺信息
        $shop = ShopService::UserShopInfo($params['shop_user_id'], 'user_id', 'id,bond_status,bond_expire_time', ['user_type'=>'shop']);
        if(empty($shop))
        {
            return DataReturn('店铺信息不存在', -1);
        }

        // 是否编辑
        if(empty($params['id']))
        {
            // 商品添加校验
            $ret = self::GoodsAddCheck($shop, $params);
            if($ret['code'] != 0)
            {
                return $ret;
            }
        } else {
            // 获取商品信息
            $goods = Db::name('Goods')->where(['id'=>intval($params['id']), 'shop_user_id'=>$params['shop_user_id'], 'shop_id'=>$shop['id']])->value('id');
            if(empty($goods))
            {
                return DataReturn('商品数据有误', -1);
            }
        }

        // 开始处理
        $params['shop_id'] = $shop['id'];
        return GoodsService::GoodsSave($params);
    }

    /**
     * 商品添加校验
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-06-03
     * @desc    description
     * @param   [array]           $shop     [店铺信息]
     * @param   [array]           $params   [输入参数]
     */
    public static function GoodsAddCheck($shop, $params = [])
    {
        // 是否需要缴纳保证金
        if(isset($params['base_config']['is_shop_bond']) && intval($params['base_config']['is_shop_bond']) == 1)
        {
            // 限制总数
            $goods_max_number = empty($params['base_config']['shop_not_pay_bond_can_release_goods_number']) ? 0 : intval($params['base_config']['shop_not_pay_bond_can_release_goods_number']);
            // 已发布总数
            $where = [
                ['shop_id', '=', $shop['id']],
                ['is_delete_time', '=', 0],
            ];
            $goods_count = self::GoodsTotal($where);
            if($goods_count >= $goods_max_number)
            {
                // 是否已经缴纳过保证金
                if($shop['bond_status'] != 1)
                {
                    return DataReturn('请先缴纳店铺保证金！', -1);
                }
                if($shop['bond_expire_time'] > 0 && $shop['bond_expire_time'] < time())
                {
                    return DataReturn('店铺保证金已过期、请重新缴纳保证金！', -1);
                }
            }
        }
        return DataReturn('success', 0);
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsStatusUpdate($params = [])
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
                'key_name'          => 'field',
                'error_msg'         => '未指定操作字段',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'state',
                'checked_data'      => [0,1],
                'error_msg'         => '状态有误',
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

        // 是否开启店铺商品状态值修改权限
        $key = ($params['field'] == 'is_shelves') ? 'is_shop_goods_list_shelves' : 'is_shop_goods_list_deduction_inventory';
        if(!isset($params['base_config'][$key]) || $params['base_config'][$key] != 1)
        {
            return DataReturn('未开启店铺修改商品权限、请联系管理员！', -1);
        }

        // 获取店铺信息
        $shop_id = self::UserShopInfo($params['shop_user_id']);
        if(empty($shop_id))
        {
            return DataReturn('用户店铺有误', -1);
        }

        // 获取商品信息
        $info = Db::name('Goods')->where(['id'=>intval($params['id']), 'shop_user_id'=>$params['shop_user_id'], 'shop_id'=>$shop_id])->value('id');
        if(empty($info))
        {
            return DataReturn('商品数据有误', -1);
        }

        // 开始处理
        return GoodsService::GoodsStatusUpdate($params);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'ids',
                'error_msg'         => '操作id有误',
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

        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 获取店铺信息
        $shop_id = self::UserShopInfo($params['shop_user_id']);
        if(empty($shop_id))
        {
            return DataReturn('用户店铺有误', -1);
        }

        // 获取店铺信息
        $count = (int) Db::name('Goods')->where(['id'=>$params['ids'], 'shop_user_id'=>$params['shop_user_id'], 'shop_id'=>$shop_id])->count();
        if($count < count($params['ids']))
        {
            return DataReturn('商品数据有误', -1);
        }

        // 开始处理
        return GoodsService::GoodsDelete($params);
    }

    /**
     * 用户店铺信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function UserShopInfo($user_id)
    {
        $shop = ShopService::UserShopInfo($user_id, 'user_id', 'id');
        return empty($shop) ? '' : $shop['id'];
    }

    /**
     * 获取商品分类层级数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [int]          $cid [商品分类id]
     */
    public static function GoodsCategoryLevel($cid)
    {
        $data = [];
        $field = 'id,pid,name';
        $temp = Db::name('GoodsCategory')->where(['id'=>$cid])->field($field)->find();
        if(!empty($temp))
        {
            $data[] = $temp;
            if(!empty($temp['pid']))
            {
                $temp = Db::name('GoodsCategory')->where(['id'=>$temp['pid']])->field($field)->find();
                if(!empty($temp))
                {
                    $data[] = $temp;
                    if(!empty($temp['pid']))
                    {
                        $temp = Db::name('GoodsCategory')->where(['id'=>$temp['pid']])->field($field)->find();
                        if(!empty($temp))
                        {
                            $data[] = $temp;
                        }
                    }
                }
            }
        }

        $text = '';
        if(!empty($data))
        {
            $data = array_reverse($data);
            foreach($data as $k=>$v)
            {
                if($k > 0)
                {
                    $text .= ' > ';
                }
                $text .= $v['name'];
            }
        }
        return ['value'=>is_array($cid) ? $cid[0] : $cid, 'text'=>$text, 'ids'=>implode(',', array_column($data, 'id'))];
    }

    /**
     * 商品主分类移动保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsCategoryMainMoveSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'ids',
                'error_msg'         => '请选择商品',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'cid',
                'error_msg'         => '请选择商品分类',
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

        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 获取店铺信息
        $shop_id = self::UserShopInfo($params['shop_user_id']);
        if(empty($shop_id))
        {
            return DataReturn('用户店铺有误', -1);
        }

        // 启动事务
        Db::startTrans();

        // 捕获异常
        try {
            // 更新商品主分类
            $cid = [intval($params['cid'])];
            foreach($params['ids'] as $gid)
            {
                $ret = GoodsService::GoodsCategoryInsert($cid, $gid);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
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
     * 商品店铺分类移动保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsCategoryShopMoveSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'ids',
                'error_msg'         => '请选择商品',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'cid',
                'error_msg'         => '请选择商品分类',
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

        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 获取店铺信息
        $shop_id = self::UserShopInfo($params['shop_user_id']);
        if(empty($shop_id))
        {
            return DataReturn('用户店铺有误', -1);
        }

        // 更新商品店铺分类
        $data = [
            'shop_category_id'  => intval($params['cid']),
            'upd_time'          => time(),
        ];
        if(Db::name('Goods')->where(['id'=>$params['ids']])->update($data))
        {
            return DataReturn('操作成功', 0);
        }
        return DataReturn('操作失败', -100);
    }
}
?>