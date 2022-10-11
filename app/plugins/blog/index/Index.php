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
namespace app\plugins\blog\index;

use app\service\SeoService;
use app\plugins\blog\index\Common;
use app\plugins\blog\service\BaseService;
use app\plugins\blog\service\SlideService;
use app\plugins\blog\service\BlogService;
use app\plugins\blog\service\CategoryService;
use app\plugins\blog\service\SearchService;

/**
 * 博客
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
        // 轮播
        $slide_list = SlideService::ClientSlideList();
        MyViewAssign('slide_list', $slide_list);

        // 分类
        $blog_category = CategoryService::CategoryList(['field'=>'id,name,seo_title,seo_desc']);
        MyViewAssign('blog_category_list', $blog_category['data']);

        // 分类数据列表
        $data_list = BaseService::HomeBlogDataList($this->plugins_config, $params);
        MyViewAssign('data_list', $data_list);

        // 推荐博文-右上角
        $blog_right_list = BaseService::HomeBlogRightList($this->plugins_config, ['n'=>5]);
        MyViewAssign('blog_right_list', $blog_right_list);

        // 热门多图滚动博文
        $blog_hot_middle_list = BaseService::HomeBlogHotList($this->plugins_config, $params);
        MyViewAssign('blog_hot_middle_list', $blog_hot_middle_list);

        // 推荐商品-首页底部
        $goods_list = BaseService::HomeBlogHomeBottomGoodsList($this->plugins_config, $params);
        MyViewAssign('goods_list', $goods_list);

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

        // 搜索关键字
        if(!empty($this->data_request['bwd']))
        {
            $this->data_request['bwd'] = AsciiToStr($this->data_request['bwd']);
        }
        
        MyViewAssign('params', $this->data_request);
        return MyView('../../../plugins/view/blog/index/index/index');
    }

    /**
     * 分类/搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Search($params = [])
    {
        $keywords = input('post.bwd');
        if(!empty($keywords))
        {
            $search_params = [
                'bwd'   => StrToAscii($keywords),
            ];
            if(!empty($params['id']))
            {
                $search_params['id'] = intval($params['id']);
            }
            return MyRedirect(PluginsHomeUrl('blog', 'index', 'search', $search_params));
        } else {
            // 条件
            $map = SearchService::SearchWhere($this->plugins_config, $this->data_request);

            // 总数
            $total = SearchService::BlogTotal($map['where'], $map['where_keywords']);

            // 分页
            unset($this->data_request['pluginsname'], $this->data_request['pluginscontrol'], $this->data_request['pluginsaction']);
            $page_params = [
                'number'    => $map['page_size'],
                'total'     => $total,
                'where'     => $this->data_request,
                'page'      => $this->page,
                'url'       => PluginsHomeUrl('blog', 'index', 'search'),
            ];
            $page = new \base\Page($page_params);

            // 获取数据列表
            $data_params = [
                'where'         => $map['where'],
                'm'             => $page->GetPageStarNumber(),
                'n'             => $map['page_size'],
                'field'         => 'id,title,title_color,describe,access_count,cover,is_live_play,video_url,add_time',
                'where_keywords'=> $map['where_keywords'],
            ];
            $data = SearchService::BlogList($data_params);

            // 基础参数赋值
            MyViewAssign('page_html', $page->GetPageHtml());
            MyViewAssign('data_list', $data['data']);

            // 搜索关键字
            if(!empty($map['keywords_value']))
            {
                MyViewAssign('blog_keywords', $map['keywords_value']);
            }

            // 分类
            $blog_category = CategoryService::CategoryList(['field'=>'id,name,seo_title,seo_desc']);
            MyViewAssign('blog_category_list', $blog_category['data']);

            // 推荐博文-右上角
            $blog_right_list = BaseService::HomeBlogRightList($this->plugins_config, $params);
            MyViewAssign('blog_right_list', $blog_right_list);

            // 相关商品-右侧
            $data_params = [
                'blog_data' => $data['data'],
            ];
            $goods = BaseService::SearchBlogRightGoodsList($this->plugins_config, $data_params);
            MyViewAssign('goods_list', $goods['data']['goods']);

            // 指定分类
            $category = empty($this->data_request['id']) ? [] : CategoryService::CategoryRow(['category_id'=>$this->data_request['id']]);

            // seo
            $seo_title = empty($category['seo_title']) ? $this->plugins_application_name : $category['seo_title'];
            if(!empty($category))
            {
                if(empty($category['seo_title']) && !empty($category['name']))
                {
                    $seo_title = $category['name'].' - '.$seo_title;
                }
            } else {
                if(empty($this->data_request['bwd']))
                {
                    $seo_title = '分类 - '.$seo_title;
                }
            }
            if(!empty($this->data_request['bwd']))
            {
                $seo_title = $this->data_request['bwd'].' - '.$seo_title;
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
            return MyView('../../../plugins/view/blog/index/index/search');
        }
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
        ];

        // 获取列表
        $data_params = [
            'm'             => 0,
            'n'             => 1,
            'where'         => $where,
        ];
        $ret = BlogService::BlogList($data_params);
        $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
        MyViewAssign('data', $data);
        if(!empty($data))
        {
            // 访问数量增加
            BlogService::BlogAccessCountInc($data);

            // 是否外部链接
            if(!empty($data['jump_url']))
            {
                return MyRedirect($data['jump_url']);
            }

            // 关联商品
            MyViewAssign('goods_list', empty($data['goods_list']) ? [] : $data['goods_list']);

            // 上一篇、下一篇
            MyViewAssign('last_next_data', BlogService::BlogLastNextData($data['id']));

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
        }

        // 推荐博文-右上角
        $blog_right_list = BaseService::HomeBlogRightList($this->plugins_config, $params);
        MyViewAssign('blog_right_list', $blog_right_list);

        // 搜索关键字
        if(!empty($this->data_request['bwd']))
        {
            $this->data_request['bwd'] = AsciiToStr($this->data_request['bwd']);
        }

        unset($this->data_request['id']);
        MyViewAssign('params', $this->data_request);
        return MyView('../../../plugins/view/blog/index/index/detail');
    }
}
?>