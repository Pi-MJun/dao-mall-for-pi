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
namespace app\plugins\label\index;

use app\service\SeoService;
use app\plugins\label\index\Common;
use app\plugins\label\service\LabelService;
use app\plugins\label\service\LabelGoodsService;

/**
 * 标签 - 标签展示
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Index extends Common
{
    /**
     * 标签详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        $label_info = [];
        $label_id = empty($params['id']) ? 0 : intval($params['id']);
        if($label_id > 0)
        {
            // 标签数据
            $data_params = [
                'm'         => 0,
                'n'         => 1,
                'where'     => [
                    ['id', '=', $label_id],
                    ['is_enable', '=', 1],
                ],
            ];
            $ret = LabelService::LabelList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                // 标签基础数据
                $label_info = $ret['data'][0];

                // 商品数据
                // 条件
                $map = LabelGoodsService::LabelGoodsMap($params);;

                // 总数
                $page_size = empty($params['page_size']) ? 20 : min(intval($params['page_size']), 100);
                $data_total = LabelGoodsService::LabelGoodsTotal($map['where']);

                // 分页
                $page_params = [
                    'number'    =>  $page_size,
                    'total'     =>  $data_total,
                    'where'     =>  $params,
                    'not_fields'=> ['id','pluginsname','pluginscontrol','pluginsaction'],
                    'page'      =>  $this->page,
                    'url'       =>  PluginsHomeUrl('label', 'index', 'detail', ['id'=>$label_id]),
                ];
                $page = new \base\Page($page_params);
                $page_html = $page->GetPageHtml();

                // 获取列表
                $data_params = [
                    'm'             => $page->GetPageStarNumber(),
                    'n'             => $page_size,
                    'where'         => $map['where'],
                    'order_by'      => $map['order_by'],
                    'field'         => $map['field'],
                ];
                $ret = LabelGoodsService::LabelGoodsList($data_params);
                $data_list = $ret['data'];

                // 基础参数赋值
                MyViewAssign('label_id', $label_id);
                MyViewAssign('params', $params);
                MyViewAssign('page_html', $page_html);
                MyViewAssign('data_total', $data_total);
                MyViewAssign('data_list', $data_list);

                // seo
                if(empty($label_info['seo_title']))
                {
                    MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($label_info['name'], 1));
                } else {
                    MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($label_info['seo_title'], 2));
                }
                if(!empty($label_info['seo_keywords']))
                {
                    MyViewAssign('home_seo_site_keywords', $label_info['seo_keywords']);
                }
                if(!empty($label_info['seo_desc']))
                {
                    MyViewAssign('home_seo_site_description', $label_info['seo_desc']);
                }
            }
        }

        // 排序方式
        MyViewAssign('map_order_by_list', LabelGoodsService::LabelGoodsMapOrderByList($params));

        // 基础参数赋值
        MyViewAssign('label_info', $label_info);
        return MyView('../../../plugins/view/label/index/index/detail');
    }
}
?>