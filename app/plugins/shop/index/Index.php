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
namespace app\plugins\shop\index;

use app\service\GoodsService;
use app\service\BrandService;
use app\service\SeoService;
use app\layout\service\BaseLayout;
use app\plugins\shop\index\Common;
use app\plugins\shop\service\BaseService;
use app\plugins\shop\service\ShopService;
use app\plugins\shop\service\ShopCategoryService;
use app\plugins\shop\service\ShopFavorService;
use app\plugins\shop\service\ShopGoodsCategoryService;
use app\plugins\shop\service\ShopNavigationService;
use app\plugins\shop\service\ShopRecommendService;
use app\plugins\shop\service\ShopSliderService;

/**
 * 多商户 - 首页
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Index extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        $keywords = input('post.wd');
        if(!empty($keywords))
        {
            return MyRedirect(PluginsHomeUrl('shop', 'index', 'index', ['wd'=>StrToAscii($keywords)]));
        }

        // 参数处理
        $params = BaseService::ShopMapParams($this->data_request);

        // 搜索关键字处理
        $wd_params = $params;
        if(!empty($wd_params['wd']))
        {
            $wd_params['wd'] = AsciiToStr($wd_params['wd']);
        }

        // 获取列表
        $ret = ShopService::SearchList($wd_params);

        // 分页
        $page_params = [
            'number'    => $ret['data']['page_size'],
            'total'     => $ret['data']['total'],
            'where'     => $params,
            'page'      => $ret['data']['page'],
            'url'       => PluginsHomeUrl('shop', 'index', 'index'),
        ];
        $page = new \base\Page($page_params);
        $page_html = $page->GetPageHtml();

        // 基础参数赋值
        MyViewAssign('params', $wd_params);
        MyViewAssign('page_html', $page_html);
        MyViewAssign('data_total', $ret['data']['total']);
        MyViewAssign('data_list', $ret['data']['data']);

        // 用户收藏的店铺
        $shop_favor_user = empty($this->user) ? [] : ShopFavorService::UserShopFavorData($this->user['id']);
        MyViewAssign('shop_favor_user', $shop_favor_user);

        // 店铺分类
        $shop_category = ShopCategoryService::ShopCategoryAll(['field'=>'id,name']);
        array_unshift($shop_category['data'], ['id'=>0, 'name'=>'全部']);
        MyViewAssign('shop_category', BaseService::ShopSearchMapUrlHandle('index', 'index', 'cid', 'id', $shop_category['data'], $params));

        // seo
        $this->SetSeo($shop_category['data'], $wd_params);
        return MyView('../../../plugins/view/shop/index/index/index');
    }

    /**
     * seo设置
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-11
     * @desc    description
     * @param   [array]           $data [条件基础数据]
     */
    private function SetSeo($shop_category = [], $wd_params = [])
    {
        // seo
        $seo_title = empty($this->base_config['seo_title']) ? '店铺' : $this->base_config['seo_title'];
        // 关键字
        if(!empty($wd_params['wd']))
        {
            $seo_title = $wd_params['wd'].' - '.$seo_title;
        }
        // 分类
        if(!empty($wd_params['cid']) && !empty($shop_category))
        {
            $temp = array_column($shop_category, 'name', 'id');
            if(array_key_exists($wd_params['cid'], $temp))
            {
                $seo_title = $temp[$wd_params['cid']].' - '.$seo_title;
            }
        }
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($seo_title, 2));
        if(!empty($this->base_config['seo_keywords']))
        {
            MyViewAssign('home_seo_site_keywords', $this->base_config['seo_keywords']);
        }
        $seo_desc = empty($this->base_config['seo_desc']) ? (empty($this->base_config['describe']) ? '' : $this->base_config['describe']) : $this->base_config['seo_desc'];
        if(!empty($seo_desc))
        {
            MyViewAssign('home_seo_site_description', $seo_desc);
        }
    }

    /**
     * 详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        $shop = [];
        if(!empty($this->data_request['id']))
        {
            $shop = ShopService::ShopValidInfo($this->data_request['id'], $this->base_config);
            if(!empty($shop))
            {
                // 用户收藏的店铺
                $shop_favor_user = empty($this->user) ? [] : ShopFavorService::UserShopFavorData($this->user['id']);
                MyViewAssign('shop_favor_user', $shop_favor_user);

                // 导航
                $navigation = ShopNavigationService::Nav($this->base_config, $shop);
                MyViewAssign('shop_navigation', $navigation);

                // 店铺商品分类
                $category = ShopGoodsCategoryService::GoodsCategoryAll(['user_id'=>$shop['user_id']]);
                MyViewAssign('shop_goods_category', $category['data']);

                // 在线客服地址
                $chat = BaseService::ChatUrl($this->base_config, $shop['user_id']);
                MyViewAssign('chat', $chat);

                // seo
                $seo_title = empty($shop['seo_title']) ? (empty($shop['name']) ? '' : $shop['name']) : $shop['seo_title'];
                if(!empty($seo_title))
                {
                    MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($seo_title, 2));
                }
                if(!empty($shop['seo_keywords']))
                {
                    MyViewAssign('home_seo_site_keywords', $shop['seo_keywords']);
                }
                $seo_desc = empty($shop['seo_desc']) ? (empty($shop['describe']) ? '' : $shop['describe']) : $shop['seo_desc'];
                if(!empty($seo_desc))
                {
                    MyViewAssign('home_seo_site_description', $seo_desc);
                }

                // 数据模式
                $data_model = isset($shop['data_model']) ? $shop['data_model'] : 0;

                // 是否设计模式
                $is_design = (isset($this->data_request['is_design']) && $this->data_request['is_design'] == 1 && !empty($this->user) && $this->user['id'] == $shop['user_id'] && $data_model == 1) ? 1 : 0;
                MyViewAssign('is_design', $is_design);
                if($is_design == 1)
                {
                    // 配置处理
                    $layout_data = BaseLayout::ConfigAdminHandle($shop['layout_config']);
                    MyViewAssign('layout_data', $layout_data);

                    // 页面列表
                    $pages_list = BaseLayout::PagesList();
                    MyViewAssign('pages_list', $pages_list);

                    // 商品搜索分类（商品分类）、使用上面读取的店铺商品分类
                    MyViewAssign('layout_goods_category', $category['data']);
                    MyViewAssign('layout_goods_category_field', 'g.shop_category_id');

                    // 商品分类
                    MyViewAssign('goods_category_list', GoodsService::GoodsCategory(['is_all'=>1]));

                    // 品牌
                    MyViewAssign('brand_list', BrandService::CategoryBrand());

                    // 静态数据
                    MyViewAssign('border_style_type_list', BaseLayout::$border_style_type_list);
                    MyViewAssign('goods_view_list_show_style', BaseLayout::$goods_view_list_show_style);
                    MyViewAssign('many_images_view_list_show_style', BaseLayout::$many_images_view_list_show_style);

                    // 首页商品排序规则
                    MyViewAssign('goods_order_by_type_list', MyConst('goods_order_by_type_list'));
                    MyViewAssign('goods_order_by_rule_list', MyConst('goods_order_by_rule_list'));

                    // 加载布局样式+管理
                    MyViewAssign('is_load_layout', 1);
                    MyViewAssign('is_load_layout_admin', 1);

                    // 编辑器文件存放地址定义
                    MyViewAssign('editor_path_type', 'plugins_shop-user_shop_design-'.$this->user['id'].'-'.$shop['id']);

                    // 浏览器名称
                    MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('店铺设计 - '.$shop['name'], 2));
                } else {
                    // 数据模式
                    if($data_model == 1)
                    {
                        // 配置处理
                        $layout_data = BaseLayout::ConfigHandle($shop['layout_config']);
                        MyViewAssign('layout_data', $layout_data);

                        // 加载布局样式
                        MyViewAssign('is_load_layout', 1);
                    } else {
                        // 轮播
                        $slider_list = ShopSliderService::ClientShopSliderList(['user_id'=>$shop['user_id']]);
                        MyViewAssign('slider_list', $slider_list);

                        // 获取店铺推荐数据
                        $data_params = [
                            'where'     => [
                                ['user_id', '=', $shop['user_id']],
                            ],
                            'user_id'   => $shop['user_id'],
                        ];
                        $data_list = ShopRecommendService::ShopRecommendData($data_params);
                        MyViewAssign('data_list', $data_list);
                    }
                }

                // 去除数据模式字段、避免很多配置数据造成带宽浪费
                unset($shop['layout_config']);
            }
        }
        MyViewAssign('shop', $shop);
        return MyView('../../../plugins/view/shop/index/index/detail');
    }
}
?>