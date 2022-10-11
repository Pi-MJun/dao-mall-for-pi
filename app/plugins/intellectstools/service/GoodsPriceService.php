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

/**
 * 智能工具箱 - 商品价格服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class GoodsPriceService
{
    /**
     * 商品价格保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsPriceSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'goods_id',
                'error_msg'         => '商品id有误',
            ],
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
            ['id', '=', intval($params['goods_id'])],
        ]);
        $goods = Db::name('Goods')->where($where)->field('id')->find();
        if(empty($goods))
        {
            return DataReturn('商品信息有误', -1);
        }

        // 规格基础
        $specifications_base = GoodsService::GetFormGoodsSpecificationsBaseParams($params);
        if($specifications_base['code'] != 0)
        {
            return $specifications_base;
        }

        // 规格值
        $specifications = GoodsService::GetFormGoodsSpecificationsParams($params);
        if($specifications['code'] != 0)
        {
            return $specifications;
        }

        // 启动事务
        Db::startTrans();

        // 捕获异常
        try {
            // 商品数据
            $goods_id = intval($params['goods_id']);
            $goods_data = [
                'is_exist_many_spec'    => empty($specifications['data']['title']) ? 0 : 1,
                'spec_base'             => empty($specifications_base['data']) ? '' : json_encode($specifications_base['data'], JSON_UNESCAPED_UNICODE),
                'upd_time'              => time(),
            ];
            if(!Db::name('Goods')->where(['id'=>$goods_id])->update($goods_data))
            {
                throw new \Exception('商品更新失败');
            }

            // 规格
            $ret = GoodsService::GoodsSpecificationsInsert($specifications['data'], $goods_id);
            if($ret['code'] != 0)
            {
                throw new \Exception($ret['msg']);
            }

            // 更新商品基础信息
            $ret = GoodsService::GoodsSaveBaseUpdate($goods_id);
            if($ret['code'] != 0)
            {
                throw new \Exception($ret['msg']);
            }

            // 完成
            Db::commit();
            return DataReturn('操作成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }
}
?>