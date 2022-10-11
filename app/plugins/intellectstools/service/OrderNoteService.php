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
 * 智能工具箱 - 订单备注服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class OrderNoteService
{
    // 数据缓存标记
    public static $cache_key = 'plugins_intellectstools_order_note_';

    /**
     * 获取数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-15
     * @desc    description
     * @param   [int]          $order_id [订单id]
     */
    public static function OrderNoteData($order_id)
    {
        $key = self::$cache_key.$order_id;
        $data = MyCache($key);
        if($data === null)
        {
            $data = Db::name('PluginsIntellectstoolsOrderNote')->where(['order_id'=>intval($order_id)])->find();
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
    public static function OrderNoteSave($params = [])
    {
        // 请求参数
        $p = [
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

        // 数据
        $data = [
            'order_id'  => intval($params['id']),
            'content'   => empty($params['content']) ? '' : $params['content'],
        ];

        // 获取数据
        $info = Db::name('PluginsIntellectstoolsOrderNote')->where(['order_id'=>$data['order_id']])->find();
        if(empty($info))
        {
            $data['add_time'] = time();
            if(Db::name('PluginsIntellectstoolsOrderNote')->insertGetId($data) <= 0)
            {
                return DataReturn('操作失败', -1);
            }
        } else {
            $data['upd_time'] = time();
            if(Db::name('PluginsIntellectstoolsOrderNote')->where(['order_id'=>$data['order_id']])->update($data) === false)
            {
                return DataReturn('操作失败', -1);
            }
        }

        // 清除缓存
        MyCache(self::$cache_key.$data['order_id'], null);
        return DataReturn('操作成功', 0);
    }
}
?>