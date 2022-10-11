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
use app\service\ResourcesService;
use app\plugins\blog\service\BlogService;

/**
 * 博客 - 博文服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class SearchService
{
    /**
     * 获取列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-08-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BlogList($params)
    {
        $where_keywords = empty($params['where_keywords']) ? [] : $params['where_keywords'];
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : trim($params['order_by']);
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $data = Db::name('PluginsBlog')->field($field)->where($where)->where(function($query) use($where_keywords) {
                $query->whereOr($where_keywords);
            })->order($order_by)->limit($m, $n)->select()->toArray();

        return DataReturn('处理成功', 0, BlogService::DataHandle($data));
    }

    /**
     * 总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-10T22:16:29+0800
     * @param    [array]          $where            [基础条件]
     * @param    [array]          $where_keywords   [关键字条件]
     */
    public static function BlogTotal($where, $where_keywords = [])
    {
        return (int) Db::name('PluginsBlog')->where($where)->where(function($query) use($where_keywords) {
            $query->whereOr($where_keywords);
        })->count();
    }

    /**
     * 搜索条件
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-11
     * @desc    description
     * @param   [array]           $config [插件配置信息]
     * @param   [array]           $params [输入参数]
     */
    public static function SearchWhere($config, $params = [])
    {
        // 条件
        $where = [
            ['is_enable', '=', 1],
        ];
        $where_keywords = [];

        // 分类id
        if(!empty($params['id']))
        {
            $where[] = ['blog_category_id', '=', intval($params['id'])];
        }

        // 搜索关键字
        $keywords_value = '';
        if(!empty($params['bwd']))
        {
            // 搜索关键字
            $keywords_value = (APPLICATION == 'web') ? AsciiToStr($params['bwd']) : $params['bwd'];
            if(!empty($keywords_value))
            {
                foreach(explode(' ', $keywords_value) as $kv)
                {
                    $where_keywords[] = ['title', 'like', '%'.$kv.'%'];
                }
            }
        }

        return [
            'where'             => $where,
            'where_keywords'    => $where_keywords,
            'keywords_value'    => $keywords_value,
            'page_size'         => empty($config['search_page_size']) ? 10 : intval($config['search_page_size']),
        ];
    }
}
?>