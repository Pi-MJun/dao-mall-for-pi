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
namespace app\plugins\blog\api;

use app\plugins\blog\api\Common;
use app\plugins\blog\service\CategoryService;
use app\plugins\blog\service\SearchService;

/**
 * 博客 - 搜索
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Search extends Common
{
    /**
     * 初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-03
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 分类
        $category = CategoryService::CategoryList(['field'=>'id,name,seo_title,seo_desc']);

        // 返回数据
        $result = [
            'base'      => $this->plugins_config,
            'category'  => empty($category['data']) ? [] : $category['data'],
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 数据列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function DataList($params = [])
    {
        // 返回格式
        $result = [
            'page_total'    => 0,
            'total'         => 0,
            'data'          => [],
        ];

        // 条件
        $map = SearchService::SearchWhere($this->plugins_config, $params);

        // 总数
        $result['total'] = SearchService::BlogTotal($map['where'], $map['where_keywords']);

        // 分页计算
        $page = max(1, isset($params['page']) ? intval($params['page']) : 1);
        $m = intval(($page-1)*$map['page_size']);

        // 获取列表
        $data_params = [
            'where'         => $map['where'],
            'm'             => $m,
            'n'             => $map['page_size'],
            'field'         => 'id,title,title_color,describe,access_count,cover,is_live_play,video_url,add_time',
            'where_keywords'=> $map['where_keywords'],
        ];
        $ret = SearchService::BlogList($data_params);

        // 返回数据
        $result['data'] = $ret['data'];
        $result['page_total'] = ceil($result['total']/$map['page_size']);
        return DataReturn('success', 0, $result);
    }
}
?>