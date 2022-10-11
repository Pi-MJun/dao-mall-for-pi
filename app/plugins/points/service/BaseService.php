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
namespace app\plugins\points\service;

use app\service\PluginsService;
use app\service\GoodsService;
use app\service\ResourcesService;

/**
 * 积分商城 - 基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [];

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'points', 'data'=>$params]);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     * @param   [boolean]          $is_goods [是否处理商品]
     */
    public static function BaseConfig($is_cache = true, $is_goods = false)
    {
        $ret = PluginsService::PluginsData('points', self::$base_config_attachment_field, $is_cache);
        if(empty($ret['data']))
        {
            $ret['data'] = [];
        }

        // 描述
        $ret['data']['points_desc'] = empty($ret['data']['points_desc']) ? [] : explode("\n", $ret['data']['points_desc']);

        // 右侧图片
        // 空则使用默认图片
        if(empty($ret['data']['right_images']))
        {
            $ret['data']['right_images'] = '/static/plugins/images/points/default-right-images.jpg';
        }
        $ret['data']['right_images'] = ResourcesService::AttachmentPathViewHandle($ret['data']['right_images']);

        // 右侧图片链接地址
        // 当前平台的链接地址
        if(!empty($ret['data']['right_images_url_rules']) && is_array($ret['data']['right_images_url_rules']) && array_key_exists(APPLICATION_CLIENT_TYPE, $ret['data']['right_images_url_rules']))
        {
            $ret['data']['right_images_url'] = $ret['data']['right_images_url_rules'][APPLICATION_CLIENT_TYPE];
        } else {
            $ret['data']['right_images_url'] = '';
        }

        $ret['data']['footer_code'] = empty($ret['data']['footer_code']) ? '' : htmlspecialchars_decode($ret['data']['footer_code']);

        // 商品兑换
        if($is_goods === true)
        {
            $ret['data']['goods_exchange_data'] = [];
            if(!empty($ret['data']['goods_exchange']) && is_array($ret['data']['goods_exchange']))
            {
                $res = self::GoodsList(array_column($ret['data']['goods_exchange'], 'gid'));
                if($res['code'] == 0 && !empty($res['data']) && !empty($res['data']['goods']))
                {
                    $goods = array_column($res['data']['goods'], null, 'id');
                    foreach($ret['data']['goods_exchange'] as $v)
                    {
                        if(array_key_exists($v['gid'], $goods) && !empty($v['integral']))
                        {
                            $ret['data']['goods_exchange_data'][] = [
                                'goods'     => $goods[$v['gid']],
                                'integral'  => $v['integral'],
                            ];
                        }
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * 商品列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]         $params [商品id]
     * @param   [int]           $m      [分页起始值]
     * @param   [int]           $n      [分页数量]
     */
    public static function GoodsList($goods_ids = [], $m = 0, $n = 0)
    {
        // 获取推荐商品id
        if(empty($goods_ids))
        {
            return DataReturn('没有商品id', 0, ['goods'=>[], 'goods_ids'=>[]]);
        }
        if(!is_array($goods_ids))
        {
            $goods_ids = json_decode($goods_ids, true);
        }

        // 条件
        $where = [
            ['g.is_delete_time', '=', 0],
            ['g.is_shelves', '=', 1],
            ['g.id', 'in', $goods_ids],
        ];

        // 指定字段
        $field = 'g.id,g.title,g.simple_desc,g.images,g.min_price,g.price,g.sales_count,g.original_price,g.inventory,g.inventory_unit,g.is_exist_many_spec';

        // 获取数据
        $ret = GoodsService::CategoryGoodsList(['where'=>$where, 'm'=>$m, 'n'=>$n, 'field'=>$field]);
        return DataReturn('操作成功', 0, ['goods'=>$ret['data'], 'goods_ids'=>$goods_ids]);
    }

    /**
     * 商品搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-13
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function GoodsSearchList($params = [])
    {
        // 返回数据
        $result = [
            'page_total'    => 0,
            'page_size'     => 20,
            'page'          => max(1, isset($params['page']) ? intval($params['page']) : 1),
            'total'         => 0,
            'data'          => [],
        ];

        // 条件
        $where = [
            ['g.is_delete_time', '=', 0],
            ['g.is_shelves', '=', 1]
        ];

        // 关键字
        if(!empty($params['keywords']))
        {
            $where[] = ['g.title', 'like', '%'.$params['keywords'].'%'];
        }

        // 分类id
        if(!empty($params['category_id']))
        {
            $category_ids = GoodsService::GoodsCategoryItemsIds([$params['category_id']], 1);
            $category_ids[] = $params['category_id'];
            $where[] = ['gci.category_id', 'in', $category_ids];
        }

        // 获取商品总数
        $result['total'] = GoodsService::CategoryGoodsTotal($where);

        // 获取商品列表
        if($result['total'] > 0)
        {
            // 基础参数
            $field = 'g.id,g.title,g.images';
            $order_by = 'g.id desc';

            // 分页计算
            $m = intval(($result['page']-1)*$result['page_size']);
            $goods = GoodsService::CategoryGoodsList(['where'=>$where, 'm'=>$m, 'n'=>$result['page_size'], 'field'=>$field, 'order_by'=>$order_by]);
            $result['data'] = $goods['data'];
            $result['page_total'] = ceil($result['total']/$result['page_size']);
             // 数据处理
            if(!empty($result['data']) && is_array($result['data']) && !empty($params['goods_ids']) && is_array($params['goods_ids']))
            {
                foreach($result['data'] as &$v)
                {
                    // 是否已添加
                    $v['is_exist'] = in_array($v['id'], $params['goods_ids']) ? 1 : 0;
                }
            }
        }
        return DataReturn('处理成功', 0, $result);
    }
}
?>