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
namespace app\plugins\activity\index;

use app\service\SeoService;
use app\plugins\activity\index\Common;
use app\plugins\activity\service\BaseService;
use app\plugins\activity\service\SliderService;
use app\plugins\activity\service\ActivityService;
use app\plugins\activity\service\CategoryService;
use app\plugins\activity\service\SearchService;

/**
 * 活动配置
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-28
 * @desc    description
 */
class Index extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 数量
        $page_size = empty($this->plugins_config['home_data_list_number']) ? $this->page_size : intval($this->plugins_config['home_data_list_number']);

        // 条件
        $where = [];
        if(!empty($params['id']))
        {
            $where['activity_category_id'] = intval($params['id']);
        }

        // 总数
        $total = ActivityService::ActivityTotal($where);

        // 分页
        $page_params = [
            'number'    =>  $page_size,
            'total'     =>  $total,
            'where'     =>  $this->data_request,
            'page'      =>  $this->page,
            'url'       =>  PluginsHomeUrl('activity', 'index', 'index'),
        ];
        $page = new \base\Page($page_params);
        MyViewAssign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = [
            'field'         => 'id,title,describe,cover,access_count,add_time',
            'where'         => $where,
            'm'             => $page->GetPageStarNumber(),
            'n'             => $page_size,
        ];
        $ret = ActivityService::ActivityList($data_params);
        MyViewAssign('data_list', $ret['data']);

        // 轮播
        $slider_list = SliderService::ClientSliderList();
        MyViewAssign('slider_list', $slider_list);

        // 分类
        $activity_category = CategoryService::CategoryList(['field'=>'id,name']);
        MyViewAssign('activity_category_list', $activity_category['data']);

        // 存在地图事件则载入
        if(in_array(3, array_column($slider_list, 'event_type')))
        {
            MyViewAssign('is_load_baidu_map_api', 1);
        }

        // 指定分类
        $category = empty($this->data_request['id']) ? [] : CategoryService::CategoryRow(['category_id'=>$this->data_request['id']]);

        // seo
        $seo_title = empty($category['seo_title']) ? (empty($this->plugins_config['seo_title']) ? $this->plugins_application_name : $this->plugins_config['seo_title']) : $category['seo_title'];
        if(empty($category['seo_title']) && !empty($category['name']))
        {
            $seo_title = $category['name'].' - '.$seo_title;
        }
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($seo_title, 2));
        $seo_keywords = empty($category['seo_keywords']) ? (empty($this->plugins_config['seo_keywords']) ? '' : $this->plugins_config['seo_keywords']) : $category['seo_keywords'];
        if(!empty($seo_keywords))
        {
            MyViewAssign('home_seo_site_keywords', $seo_keywords);
        }
        $seo_desc = empty($category['seo_desc']) ? (empty($this->plugins_config['seo_desc']) ? '' : $this->plugins_config['seo_desc']) : $category['seo_desc'];
        if(!empty($seo_desc))
        {
            MyViewAssign('home_seo_site_description', $seo_desc);
        }

        MyViewAssign('params', $this->data_request);
        return MyView('../../../plugins/view/activity/index/index/index');
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
        // 条件
        $where = [
            ['id', '=', intval($this->data_request['id'])],
            ['is_enable', '=', 1],
        ];

        // 获取列表
        $data_params = [
            'm'             => 0,
            'n'             => 1,
            'where'         => $where,
        ];
        $ret = ActivityService::ActivityList($data_params);
        $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
        MyViewAssign('data', $data);

        // 访问数量增加
        ActivityService::ActivityAccessCountInc($data);

        // seo
        $seo_title = empty($data['seo_title']) ? $data['title'] : $data['seo_title'];
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($seo_title, 2));
        if(!empty($data['seo_keywords']))
        {
            MyViewAssign('home_seo_site_keywords', $data['seo_keywords']);
        }
        $seo_desc = empty($data['seo_desc']) ? (empty($data['describe']) ? '' : $data['describe']) : $data['seo_desc'];
        if(!empty($seo_desc))
        {
            MyViewAssign('home_seo_site_description', $seo_desc);
        }

        // 搜索关键字
        if(!empty($this->data_request['bwd']))
        {
            $this->data_request['bwd'] = AsciiToStr($this->data_request['bwd']);
        }

        MyViewAssign('params', $this->data_request);
        return MyView('../../../plugins/view/activity/index/index/detail');
    }
}
?>