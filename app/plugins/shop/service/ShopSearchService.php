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

/**
 * 多商户 - 搜索服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class ShopSearchService
{
    // 筛选排序规则列表
    public static $map_order_by_list = [
        [
            'name'  => '综合',
            'type'  => 'default',
            'value' => 'desc',
        ],
        [
            'name'  => '销量',
            'type'  => 'sales',
            'value' => 'desc',
        ],
        [
            'name'  => '热度',
            'type'  => 'access',
            'value' => 'desc',
        ],
        [
            'name'  => '价格',
            'type'  => 'price',
            'value' => 'desc',
        ],
        [
            'name'  => '最新',
            'type'  => 'new',
            'value' => 'desc',
        ],
    ];

    /**
     * 排序列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ShopGoodsMapOrderByList($params = [])
    {
        $ov = empty($params['ov']) ? ['default'] : explode('-', $params['ov']);
        $data = self::$map_order_by_list;
        foreach($data as &$v)
        {
            // 是否选中
            $v['active'] = ($ov[0] == $v['type']) ? 1 : 0;

            // url
            $temp_ov = '';
            if($v['type'] == 'default')
            {
                $temp_params = $params;
                unset($temp_params['ov']);
            } else {
                // 类型
                if($ov[0] == $v['type'])
                {
                    $v['value'] = ($ov[1] == 'desc') ? 'asc' : 'desc';
                }

                // 参数值
                $temp_ov = $v['type'].'-'.$v['value'];
                $temp_params = array_merge($params, ['ov'=>$temp_ov]);
            }
            $v['url'] = PluginsHomeUrl('shop', 'search', 'index', $temp_params);
        }
        return $data;
    }

    /**
     * 获取商品列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function GoodsList($params = [])
    {        
        // 搜索处理
        $map = self::SearchMapHandle($params);
        $where_base = $map['base'];
        $where_keywords = $map['keywords'];

        // 分页
        $page = max(1, isset($params['page']) ? intval($params['page']) : 1);
        $page_size = empty($params['page_size']) ? 20 : min(intval($params['page_size']), 100);
        $page_start = intval(($page-1)*$page_size);

        // 获取商品
        $goods_params = [
            'where_base'    => $map['base'],
            'where_keywords'=> $map['keywords'],
            'order_by'      => $map['order_by'],
            'field'         => '*',
            'page'          => $page,
            'page_start'    => $page_start,
            'page_size'     => $page_size,
        ];
        $ret = GoodsService::GoodsSearchList($goods_params);
        $ret['data']['page'] = $page;
        $ret['data']['page_size'] = $page_size;
        $ret['data']['page_start'] = $page_start;
        return $ret;
    }

    /**
     * 搜索条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function SearchMapHandle($params = [])
    {
        // 基础条件
        $shop_id = empty($params['id']) ? (empty($params['shop_id']) ? 0 : intval($params['shop_id'])) : intval($params['id']);
        $where_base = [
            ['is_delete_time', '=', 0],
            ['is_shelves', '=', 1],
            ['shop_id', '=', $shop_id],
        ];

        // 关键字
        $where_keywords = [];
        if(!empty($params['wd']))
        {
            // WEB端则处理关键字
            if(APPLICATION_CLIENT_TYPE == 'pc')
            {
                $params['wd'] = AsciiToStr($params['wd']);
            }
            $keywords_fields = 'title|simple_desc|model';
            foreach(explode(' ', $params['wd']) as $kv)
            {
                $where_keywords[] = [$keywords_fields, 'like', '%'.$kv.'%'];
            }
        }

        // 分类id、多选
        if(!empty($params['category_ids']))
        {
            if(!is_array($params['category_ids']))
            {
                $params['category_ids'] = (substr($params['category_ids'], 0, 1) == '{') ? json_decode(htmlspecialchars_decode($params['category_ids']), true) : explode(',', $params['category_ids']);
            }
            if(!empty($params['category_ids']))
            {
                $where_base[] = ['shop_category_id', 'in', $params['category_ids']];
            }
        }
        // 分类id、单选
        if(!empty($params['cid']))
        {
            $where_base[] = ['shop_category_id', '=', intval($params['cid'])];
        }

        // 排序
        $order_by = 'access_count desc, sales_count desc, id desc';
        if(!empty($params['ov']))
        {
            // 数据库字段映射关系
            $fields = [
                'sales'     => 'sales_count',
                'access'    => 'access_count',
                'price'     => 'min_price',
                'new'       => 'id',
            ];

            // 参数判断
            $temp = explode('-', $params['ov']);
            if(count($temp) == 2 && $temp[0] != 'default' && array_key_exists($temp[0], $fields) && in_array($temp[1], ['desc', 'asc']))
            {
                $order_by = $fields[$temp[0]].' '.$temp[1];
            }
        } else {
            if(!empty($params['order_by_type']) && !empty($params['order_by_field']) && $params['order_by_field'] != 'default')
            {
                $order_by = $params['order_by_field'].' '.$params['order_by_type'];
            }
        }

        return [
            'base'      => $where_base,
            'keywords'  => $where_keywords,
            'order_by'  => $order_by,
        ];
    }

    /**
     * 搜索条件基础数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function SearchMapInfo($params = [])
    {
        // 分类
        $category = [];
        if(!empty($params['cid']))
        {
            $category = Db::name('PluginsShopGoodsCategory')->field('id,name,seo_title,seo_keywords,seo_desc')->where(['id'=>intval($params['cid'])])->find();
        }

        return [
            'category'  => empty($category) ? null : $category,
        ];
        
    }
}
?>