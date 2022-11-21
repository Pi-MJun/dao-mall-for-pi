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
use app\service\GoodsCommentsService;

/**
 * 多商户 - 商品评论服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-02-14
 * @desc    description
 */
class ShopGoodsCommentsService
{
    /**
     * 获取商品评论纪录列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-23
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function GoodsCommentsList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? 'gc.*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'gc.id desc' : $params['order_by'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        // 获取数据列表
        $data = Db::name('GoodsComments')->alias('gc')->join('order o', 'o.id=gc.order_id')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        $data = GoodsCommentsService::DataHandle($data, ['is_goods'=>1]);
        return DataReturn('获取成功', 0, $data);
    }

    /**
     * 商品评论总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function GoodsCommentsTotal($where = [])
    {
        return (int) Db::name('GoodsComments')->alias('gc')->join('order o', 'o.id=gc.order_id')->where($where)->count();
    }

    /**
     * 回复
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function GoodsCommentsReply($params = [])
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
                'key_name'          => 'user_id',
                'error_msg'         => '用户id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'base_config',
                'error_msg'         => '插件配置有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取数据
        $where = [
            ['o.shop_user_id', '=', $params['user_id']],
            ['gc.id', '=', intval($params['id'])],
        ];
        $count = self::GoodsCommentsTotal($where);
        if($count <= 0)
        {
            return DataReturn('店铺没该商品评论数据', -1);
        }

        // 调用系统服务层回复
        return GoodsCommentsService::GoodsCommentsReply($params);
    }
}
?>