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

use app\service\ResourcesService;
use app\plugins\blog\api\Common;
use app\plugins\blog\service\BaseService;
use app\plugins\blog\service\SlideService;
use app\plugins\blog\service\CategoryService;
use app\plugins\blog\service\BlogService;

/**
 * 博客 - 首页
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-03
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 轮播
        $slide_list = SlideService::ClientSlideList();

        // 分类
        $category = CategoryService::CategoryList(['field'=>'id,name,seo_title,seo_desc']);

        // 分类数据列表
        $data_list = BaseService::HomeBlogDataList($this->plugins_config, $params);

        // 推荐博文-右上角
        $right_list = BaseService::HomeBlogRightList($this->plugins_config, $params);

        // 热门多图滚动博文
        $hot_list = BaseService::HomeBlogHotList($this->plugins_config, $params);

        // 推荐商品-首页底部
        $goods_list = BaseService::HomeBlogHomeBottomGoodsList($this->plugins_config, $params);

        // 返回数据
        $result = [
            'base'          => $this->plugins_config,
            'slide_list'    => $slide_list,
            'category'      => empty($category['data']) ? [] : $category['data'],
            'data_list'     => $data_list,
            'right_list'    => $right_list,
            'hot_list'      => $hot_list,
            'goods_list'    => $goods_list,
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        if(!empty($this->data_request['id']))
        {
            // 条件
            $where = [
                ['id', '=', intval($this->data_request['id'])],
            ];

            // 获取列表
            $data_params = [
                'm'             => 0,
                'n'             => 1,
                'where'         => $where,
            ];
            $ret = BlogService::BlogList($data_params);
            if(!empty($ret['data'][0]))
            {
                $data = $ret['data'][0];

                // 标签处理，兼容小程序rich-text
                $data['content'] = ResourcesService::ApMiniRichTextContentHandle($data['content']);

                // 访问数量增加
                BlogService::BlogAccessCountInc($data);

                // 推荐博文-右上角
                $right_list = BaseService::HomeBlogRightList($this->plugins_config, $params);

                // 上一篇、下一篇
                $last_next_data = BlogService::BlogLastNextData($data['id']);

                // 返回数据
                $result = [
                    'base'          => $this->plugins_config,
                    'data'          => $data,
                    'right_list'    => $right_list,
                    'last_next'     => $last_next_data,
                ];
                return DataReturn('success', 0, $result);
            } else {
                $ret = DataReturn('博文不存在或已删除', -1);
            }
        } else {
            $ret = DataReturn('博文ID有误', -1);
        }
    }
}
?>