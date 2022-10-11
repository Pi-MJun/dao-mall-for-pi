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

/**
 * 智能工具箱 - 商品备注服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class GoodsNoteService
{
    // 数据缓存标记
    public static $cache_key = 'plugins_intellectstools_goods_note_';

    /**
     * 获取数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-15
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     */
    public static function GoodsNoteData($goods_id)
    {
        $key = self::$cache_key.$goods_id;
        $data = MyCache($key);
        if($data === null)
        {
            $data = Db::name('PluginsIntellectstoolsGoodsNote')->where(['goods_id'=>intval($goods_id)])->find();
            if(!empty($data))
            {
                if(array_key_exists('add_time', $data))
                {
                    $data['add_time'] = date('Y-m-d H:i:s', $data['add_time']);
                }
                if(array_key_exists('upd_time', $data))
                {
                    $data['upd_time'] = empty($data['upd_time']) ? '' : date('Y-m-d H:i:s', $data['upd_time']);
                }
            } else {
                $data = [];
            }
            MyCache($key, $data, 1800);
        }
        return $data;
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-15
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function GoodsNoteSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
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
            ['id', '=', intval($params['id'])],
        ]);
        $goods = Db::name('Goods')->where($where)->field('id')->find();
        if(empty($goods))
        {
            return DataReturn('商品信息有误', -1);
        }

        // 数据
        $data = [
            'goods_id'  => intval($params['id']),
            'content'   => empty($params['content']) ? '' : $params['content'],
        ];

        // 获取数据
        $info = Db::name('PluginsIntellectstoolsGoodsNote')->where(['goods_id'=>$data['goods_id']])->find();
        if(empty($info))
        {
            $data['add_time'] = time();
            if(Db::name('PluginsIntellectstoolsGoodsNote')->insertGetId($data) <= 0)
            {
                return DataReturn('操作失败', -1);
            }
        } else {
            $data['upd_time'] = time();
            if(Db::name('PluginsIntellectstoolsGoodsNote')->where(['goods_id'=>$data['goods_id']])->update($data) === false)
            {
                return DataReturn('操作失败', -1);
            }
        }

        // 清除缓存
        MyCache(self::$cache_key.$data['goods_id'], null);
        return DataReturn('操作成功', 0);
    }
}
?>