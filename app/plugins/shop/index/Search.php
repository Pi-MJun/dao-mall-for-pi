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

use app\service\SeoService;
use app\plugins\shop\index\Common;
use app\plugins\shop\service\BaseService;
use app\plugins\shop\service\ShopService;
use app\plugins\shop\service\ShopFavorService;
use app\plugins\shop\service\ShopSearchService;
use app\plugins\shop\service\ShopGoodsCategoryService;
use app\plugins\shop\service\ShopNavigationService;

/**
 * 多商户 - 搜索
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Search extends Common
{
    // 店铺信息
    private $shop;

    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-30
     * @desc    description
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 店铺信息
        $this->shop = empty($this->data_request['id']) ? [] : ShopService::ShopValidInfo($this->data_request['id'], $this->base_config);
        $this->data_request['id'] = empty($this->shop) ? 0 : $this->shop['id'];        
    }

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
        // 店铺校验
        if(empty($this->shop))
        {
            MyViewAssign('msg', '店铺不存在或已关闭');
            return MyView('public/tips_error');
        }

        // 搜索关键字处理
        $keywords = input('post.wd');
        if(!empty($keywords))
        {
            return MyRedirect(PluginsHomeUrl('shop', 'search', 'index', ['id'=>$this->shop['id'], 'wd'=>StrToAscii($keywords)]));
        }

        // 参数处理
        $params = BaseService::ShopMapParams($this->data_request);

        // 获取商品列表
        $ret = ShopSearchService::GoodsList($params);

        // 分页
        $page_params = [
            'number'    => $ret['data']['page_size'],
            'total'     => $ret['data']['total'],
            'where'     => $params,
            'page'      => $ret['data']['page'],
            'url'       => PluginsHomeUrl('shop', 'search', 'index'),
        ];
        $page = new \base\Page($page_params);
        $page_html = $page->GetPageHtml();

        // 关键字处理
        if(!empty($params['wd']))
        {
            $params['wd'] = AsciiToStr($params['wd']);
        }

        // 基础参数赋值
        MyViewAssign('params', $params);
        MyViewAssign('page_html', $page_html);
        MyViewAssign('data_total', $ret['data']['total']);
        MyViewAssign('data_list', $ret['data']['data']);

        // 导航
        $navigation = ShopNavigationService::Nav($this->base_config, $this->shop);
        MyViewAssign('shop_navigation', $navigation);

        // 店铺商品分类
        $category = ShopGoodsCategoryService::GoodsCategoryAll(['user_id'=>$this->shop['user_id']]);
        MyViewAssign('shop_goods_category', BaseService::ShopSearchMapUrlHandle('search', 'index', 'cid', 'id', $category['data'], $params));

        // 指定数据
        $search_map_info = ShopSearchService::SearchMapInfo($this->data_request);
        MyViewAssign('search_map_info', $search_map_info);

        // 店铺信息
        MyViewAssign('shop', $this->shop);

        // 排序方式
        MyViewAssign('map_order_by_list', ShopSearchService::ShopGoodsMapOrderByList($params));

        // 用户收藏的店铺
        $shop_favor_user = empty($this->user) ? [] : ShopFavorService::UserShopFavorData($this->user['id']);
        MyViewAssign('shop_favor_user', $shop_favor_user);

        // 在线客服地址
        $chat = BaseService::ChatUrl($this->base_config, $this->shop['user_id']);
        MyViewAssign('chat', $chat);

        // seo
        $this->SetSeo($search_map_info, $params);
        return MyView('../../../plugins/view/shop/index/search/index');
    }

    /**
     * seo设置
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-11
     * @desc    description
     * @param   [array]     $data   [条件基础数据]
     * @param   [array]     $params [输入参数]
     */
    private function SetSeo($data = [], $params = [])
    {
        // 店铺seo信息
        $seo_title = empty($this->shop['seo_title']) ? $this->shop['name'] : $this->shop['seo_title'];
        $seo_keywords = empty($this->shop['seo_keywords']) ? '' : $this->shop['seo_keywords'];
        $seo_desc = empty($this->shop['seo_desc']) ? '' : $this->shop['seo_desc'];
        // 搜索关键字
        if(!empty($params['wd']))
        {
            $seo_title = $params['wd'].' - '.$seo_title;
        } elseif(!empty($data) && !empty($data['category'])) {
            // 分类
            // 存在seo标题则直接使用 seo标题
            if(empty($data['category']['seo_title']))
            {
                $seo_title = $data['category']['name'].' - '.$seo_title;
            } else {
                $seo_title = $data['category']['seo_title'];
            }
            if(!empty($data['category']['seo_keywords']))
            {
                $seo_keywords = $data['category']['seo_keywords'];
            }
            if(!empty($data['category']['seo_desc']))
            {
                $seo_desc = $data['category']['seo_desc'];
            }
        } else {
            $seo_title = '商品搜索 - '.$seo_title;
        }
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($seo_title, 1));
        if(!empty($seo_keywords))
        {
            MyViewAssign('home_seo_site_keywords', $seo_keywords);
        }
        if(!empty($seo_desc))
        {
            MyViewAssign('home_seo_site_description', $seo_desc);
        }
    }
}
?>