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
use app\plugins\intellectstools\service\BaseService;

/**
 * 智能工具箱 - 商品数据美化服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class GoodsBeautifyService
{
    /**
     * 数据美化保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsBeautifySave($params = [])
    {
        // 条件
        $data = BaseService::GoodsWhere($params);
        $where = $data['where'];
        $where[] = ['is_shelves', '=', 1];

        // 返回值
        $access_count = 0;
        $sales_count = 0;

        // 访问量
        $access_min = empty($params['access_min']) ? 0 : intval($params['access_min']);
        $access_max = empty($params['access_max']) ? 0 : intval($params['access_max']);
        if($access_max > 0)
        {
            $access_max -= $access_min;
            if($access_max <= 0)
            {
                return DataReturn('访问量最大值不能<=最小值', -1);
            }
            $res = Db::name('Goods')->where($where)->exp('access_count', 'FLOOR('.$access_min.'+RAND()*'.$access_max.')')->update();
            if($res)
            {
                $access_count += $res;
            }
        }

        // 销量
        $sales_min = empty($params['sales_min']) ? 0 : intval($params['sales_min']);
        $sales_max = empty($params['sales_max']) ? 0 : intval($params['sales_max']);
        if($sales_max > 0)
        {
            $sales_max -= $sales_min;
            if($sales_max <= 0)
            {
                return DataReturn('销量最大值不能<=最小值', -1);
            }
            $res = Db::name('Goods')->where($where)->exp('sales_count', 'FLOOR('.$sales_min.'+RAND()*'.$sales_max.')')->update();
            if($res)
            {
                $sales_count += $res;
            }
        }

        // 操作返回
        if($access_count <= 0 && $sales_count <= 0)
        {
            return DataReturn('操作失败、没更新任何数据', -100);
        }
        return DataReturn('操作成功[访问量:'.$access_count.', 销量:'.$sales_count.']', 0);
    }

    /**
     * 商品自动增加销量
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-08
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     * @param   [array]        $config   [插件配置]
     * @param   [array]        $params   [输入参数]
     */
    public static function GoodsAutoIncSales($goods_id, $config, $params = [])
    {
        // 获取商品浏览量
        $goods = Db::name('Goods')->where(['id'=>$goods_id])->field('access_count,sales_count')->find();
        if(empty($goods))
        {
            return DataReturn('商品不存在', -1);
        }
        if($goods['access_count'] <= 0)
        {
            return DataReturn('商品浏览量为0、自动增加取消操作', -1);
        }

        // 基础处理
        $value = intval($goods['access_count']*($config['auto_inc_sales_number']/100));
        if($goods['sales_count'] >= $value)
        {
            return DataReturn('商品已有销量满足基准值['.$goods['sales_count'].'>='.$value.']', -1);
        }

        // 更新销量
        if(Db::name('Goods')->where(['id'=>$goods_id])->update(['sales_count'=>$value, 'upd_time'=>time()]))
        {
            return DataReturn('商品销量自动增加成功['.($value-$goods['sales_count']).']', 0);
        }
        return DataReturn('商品销量自动增加失败', -1);
    }
}
?>