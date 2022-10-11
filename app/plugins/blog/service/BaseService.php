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
namespace app\plugins\blog\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\GoodsService;
use app\plugins\blog\service\BlogService;

/**
 * 博客 - 基础服务层
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

    // 商品关联排序类型
    public static $goods_order_by_type_list = [
        0 => ['value' => 'access_count,sales_count,id', 'name' => '综合', 'checked' => true],
        1 => ['value' => 'sales_count', 'name' => '销量'],
        2 => ['value' => 'access_count', 'name' => '热度'],
        3 => ['value' => 'min_price', 'name' => '价格'],
        4 => ['value' => 'id', 'name' => '最新'],
    ];

    // 商品关联排序规则
    public static $goods_order_by_rule_list = [
        0 => ['value' => 'desc', 'name' => '降序(desc)', 'checked' => true],
        1 => ['value' => 'asc', 'name' => '升序(asc)'],
    ];

    // 首页楼层位置
    public static $home_floor_location_list = [
        0 => ['value' => 0, 'name' => '楼层上面', 'checked' => true],
        1 => ['value' => 1, 'name' => '楼层下面'],
    ];

    // 推荐样式类型
    public static $recommend_style_type_list = [
        0 => ['value' => 0, 'name' => '图文列表', 'checked' => true],
        1 => ['value' => 1, 'name' => '九方格'],
        2 => ['value' => 2, 'name' => '一行滚动'],
    ];

    // 数据类型
    public static $recommend_data_type_list = [
        0 => ['value' => 0, 'name' => '自动模式', 'checked' => true],
        1 => ['value' => 1, 'name' => '指定博文'],
    ];

    // 排序类型
    public static $recommend_order_by_type_list = [
        0 => ['value' => 'id', 'name' => '最新', 'checked' => true],
        1 => ['value' => 'access_count', 'name' => '热度'],
        2 => ['value' => 'upd_time', 'name' => '更新'],
    ];

    // 排序规则
    public static $recommend_order_by_rule_list = [
        0 => ['value' => 'desc', 'name' => '降序(desc)', 'checked' => true],
        1 => ['value' => 'asc', 'name' => '升序(asc)'],
    ];

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
        return PluginsService::PluginsDataSave(['plugins'=>'blog', 'data'=>$params], self::$base_config_attachment_field);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * 
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        return PluginsService::PluginsData('blog', self::$base_config_attachment_field, $is_cache);
    }

    /**
     * 商品搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function GoodsSearchList($params = [])
    {
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

        // 指定字段
        $field = 'g.id,g.title';

        // 获取数据
        return GoodsService::CategoryGoodsList(['where'=>$where, 'm'=>0, 'n'=>100, 'field'=>$field, 'is_admin_access'=>1]);
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
        $field = 'g.id,g.title,g.images,g.min_price,g.price,g.original_price';

        // 获取数据
        $ret = GoodsService::CategoryGoodsList(['where'=>$where, 'm'=>$m, 'n'=>$n, 'field'=>$field]);
        return DataReturn('操作成功', 0, ['goods'=>$ret['data'], 'goods_ids'=>$goods_ids]);
    }

    /**
     * 首页底部商品
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-03
     * @desc    description
     * @param   [array]           $config [插件配置信息]
     * @param   [array]           $params [输入参数]
     */
    public static function HomeBlogHomeBottomGoodsList($config, $params = [])
    {
        // 条数
        $n = empty($config['home_bottom_goods_page_size']) ? 10 : intval($config['home_bottom_goods_page_size']);

        // 排序
        $order_by_type = empty($config['home_bottom_goods_order_by_type']) ? self::$goods_order_by_type_list[0]['value'] : self::$goods_order_by_type_list[$config['home_bottom_goods_order_by_type']]['value'];
        $order_by_rule = empty($config['home_bottom_goods_order_by_rule']) ? self::$goods_order_by_rule_list[0]['value'] : self::$goods_order_by_rule_list[$config['home_bottom_goods_order_by_rule']]['value'];
        $order_by = str_replace('g.', '', $order_by_type).' '.$order_by_rule;

        // 条件
        $where = [
            ['is_delete_time', '=', 0],
            ['is_shelves', '=', 1],
        ];

        // 指定字段
        $field = 'id,title,images,min_price,price,original_price';

        // 获取数据
        $ret = GoodsService::GoodsList(['where'=>$where, 'order_by'=>$order_by, 'm'=>0, 'n'=>$n, 'field'=>$field]);
        return empty($ret['data']) ? [] : $ret['data'];
    }

    /**
     * 搜索右侧推荐商品
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-06
     * @desc    description
     * @param   [array]           $config [插件配置信息]
     * @param   [array]           $params [输入参数]
     */
    public static function SearchBlogRightGoodsList($config, $params = [])
    {
        if(!empty($params['blog_data']))
        {
            $blog_ids = array_column($params['blog_data'], 'id');
            if(!empty($blog_ids))
            {
                $goods_ids = array_unique(Db::name('PluginsBlogGoods')->where(['blog_id'=>$blog_ids])->column('goods_id'));
                if(!empty($goods_ids))
                {
                    $n = empty($config['search_right_goods_number']) ? 10 : intval($config['search_right_goods_number']);
                    return self::GoodsList($goods_ids, 0, $n);
                }
            }
        }
        return DataReturn('操作成功', 0, ['goods'=>[], 'goods_ids'=>[]]);
    }

    /**
     * 热门多图滚动博文
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-03
     * @desc    description
     * @param   [array]           $config [插件配置信息]
     * @param   [array]           $params [输入参数]
     */
    public static function HomeBlogHotList($config, $params = [])
    {
        $data_params = [
            'where'     => [
                ['is_enable', '=', 1],
            ],
            'field'     => 'id,title,title_color,describe,access_count,cover,video_url,add_time',
            'order_by'  => 'access_count desc',
            'n'         => empty($config['home_multigraph_number']) ? 30 : intval($config['home_multigraph_number']),
        ];
        $ret = BlogService::BlogList($data_params);
        return empty($ret['data']) ? [] : $ret['data'];
    }

    /**
     * 右上角推荐博文
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-03
     * @desc    description
     * @param   [array]           $config [插件配置信息]
     * @param   [array]           $params [输入参数]
     */
    public static function HomeBlogRightList($config, $params = [])
    {
        $data_params = [
            'where'     => [
                ['is_enable', '=', 1],
            ],
            'field'     => 'id,title,title_color,describe,access_count,cover,video_url,add_time',
            'where'     => ['is_recommended'=>1],
            'n'         => empty($params['n']) ? 5 : (empty($config['right_recommended_number']) ? 10 : intval($config['right_recommended_number'])),
        ];
        $ret = BlogService::BlogList($data_params);
        return empty($ret['data']) ? [] : $ret['data'];
    }

    /**
     * 分类数据列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-03
     * @desc    description
     * @param   [array]           $config [插件配置信息]
     * @param   [array]           $params [输入参数]
     */
    public static function HomeBlogDataList($config, $params = [])
    {
        $data_params = [
            'where'     => [
                ['is_enable', '=', 1],
            ],
            'field'     => 'id,title,title_color,describe,access_count,cover,video_url,add_time',
            'n'         => empty($config['home_data_list_number']) ? 20 : intval($config['home_data_list_number']),
        ];
        $ret = BlogService::BlogList($data_params);
        return empty($ret['data']) ? [] : $ret['data'];
    }

    /**
     * 博客搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function BlogSearchList($params = [])
    {
        $where = [
            ['is_enable', '=', 1],
        ];
        if(!empty($params['category_id']))
        {
            $where[] = ['blog_category_id', '=', intval($params['category_id'])];
        }
        if(!empty($params['keywords']))
        {
            $where[] = ['title', 'like', '%'.trim($params['keywords']).'%'];
        }

        // 获取列表
        $data_params = [
            'where'         => $where,
            'm'             => 0,
            'n'             => 100,
        ];
        return BlogService::BlogList($data_params);
    }
}
?>