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
use app\service\GoodsService;
use app\service\BrandService;
use app\layout\service\BaseLayout;
use app\plugins\shop\index\Base;
use app\plugins\shop\service\BaseService;
use app\plugins\shop\service\ShopService;
use app\plugins\shop\service\ShopFavorService;
use app\plugins\shop\service\ShopDesignService;
use app\plugins\shop\service\ShopNavigationService;
use app\plugins\shop\service\ShopGoodsCategoryService;

/**
 * 多商户 - 页面设计
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Design extends Base
{
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
        // 是否已经登录
        $this->IsLogin();

        // 总数
        $total = ShopDesignService::DesignTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    =>  $this->page_size,
            'total'     =>  $total,
            'where'     =>  $this->data_request,
            'page'      =>  $this->page,
            'url'       =>  PluginsHomeUrl('shop', 'design', 'index'),
        ];
        $page = new \base\Page($page_params);

        // 获取列表
        $data_params = [
            'm'             => $page->GetPageStarNumber(),
            'n'             => $this->page_size,
            'where'         => $this->form_where,
            'order_by'      => $this->form_order_by['data'],
        ];
        $ret = ShopDesignService::DesignList($data_params);

        // 基础参数赋值
        MyViewAssign('params', $this->data_request);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('页面设计', 1));
        return MyView('../../../plugins/view/shop/index/design/index');
    }

    /**
     * 编辑页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        // 是否已经登录
        $this->IsLogin();

        // 是否指定id、不存在则增加数据
        if(empty($params['id']))
        {
            $ret = ShopDesignService::DesignSave(['user_id'=>$this->user['id']]);
            if($ret['code'] == 0)
            {
                return MyRedirect(PluginsHomeUrl('shop', 'design', 'saveinfo', ['id'=>$ret['data']]));
            } else {
                MyViewAssign('msg', $ret['msg']);
                return MyView('public/tips_error');
            }
        }

        // 获取数据
        $data_params = [
            'where' => [
                'id' => intval($params['id']),
            ],
            'm' => 0,
            'n' => 1,
        ];
        $ret = ShopDesignService::DesignList($data_params);
        if(empty($ret['data']) || empty($ret['data'][0]))
        {
            MyViewAssign('to_title', '去添加 >>');
            MyViewAssign('to_url', PluginsHomeUrl('shop', 'design', 'saveinfo'));
            MyViewAssign('msg', '编辑数据为空、请重新添加');
            return MyView('public/tips_error');
        }
        $data = $ret['data'][0];

        // 配置处理
        $layout_data = BaseLayout::ConfigAdminHandle($data['config']);
        MyViewAssign('layout_data', $layout_data);
        MyViewAssign('data', $data);
        unset($data['config']);

        // 页面列表
        $pages_list = BaseLayout::PagesList();
        MyViewAssign('pages_list', $pages_list);

        // 商品搜索分类（商品分类）
        $layout_goods_category = ShopGoodsCategoryService::GoodsCategoryAll(['user_id'=>$this->user['id']]);
        MyViewAssign('layout_goods_category', $layout_goods_category['data']);
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

        // 关闭尾部
        MyViewAssign('is_footer', 0);

        // 加载布局样式+管理
        MyViewAssign('is_load_layout', 1);
        MyViewAssign('is_load_layout_admin', 1);

        // 编辑器文件存放地址定义
        MyViewAssign('editor_path_type', ShopDesignService::AttachmentPathTypeValue($this->user['id'], $data['id']));

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('页面设计', 1));
        return MyView('../../../plugins/view/shop/index/design/saveinfo');
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
        $data = [];
        if(!empty($params['id']))
        {
            $data_params = [
                'where' => [
                    'id' => intval($params['id']),
                ],
                'm' => 0,
                'n' => 1,
            ];
            $ret = ShopDesignService::DesignList($data_params);
            if($ret['code'] == 0 && !empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
            }
        }
        if(!empty($data))
        {
            // 店铺信息
            $shop = ShopService::ShopValidInfo($data['shop_id'], $this->base_config);
            if(empty($shop))
            {
                MyViewAssign('msg', '店铺不存在或已关闭');
                return MyView('public/tips_error');
            }
            MyViewAssign('shop', $shop);

            // 导航
            $navigation = ShopNavigationService::Nav($this->base_config, $shop);
            MyViewAssign('shop_navigation', $navigation);

            // 店铺商品分类
            $category = ShopGoodsCategoryService::GoodsCategoryAll(['user_id'=>$shop['user_id']]);
            MyViewAssign('shop_goods_category', $category['data']);

            // 用户收藏的店铺
            $shop_favor_user = empty($this->user) ? [] : ShopFavorService::UserShopFavorData($this->user['id']);
            MyViewAssign('shop_favor_user', $shop_favor_user);

            // 访问统计
            ShopDesignService::DesignAccessCountInc(['design_id'=>$data['id']]);

            // 在线客服地址
            $chat = BaseService::ChatUrl($this->base_config, $shop['user_id']);
            MyViewAssign('chat', $chat);

            // 配置处理
            $layout_data = BaseLayout::ConfigHandle($data['config']);
            MyViewAssign('layout_data', $layout_data);

            // 加载布局样式
            MyViewAssign('is_load_layout', 1);

            // seo
            $seo_title = empty($data['seo_title']) ? $data['name'] : $data['seo_title'];
            MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($seo_title, 2));
            if(!empty($data['seo_keywords']))
            {
                MyViewAssign('home_seo_site_keywords', $data['seo_keywords']);
            }
            if(!empty($data['seo_desc']))
            {
                MyViewAssign('home_seo_site_description', $data['seo_desc']);
            }

            // 头尾
            MyViewAssign('is_header', $data['is_header']);
            MyViewAssign('is_footer', $data['is_footer']);
            return MyView('../../../plugins/view/shop/index/design/detail');
        }
        MyViewAssign('msg', '页面不存在或已删除');
        return MyView('public/tips_error');
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Save($params = [])
    {
        // 是否已经登录
        $this->IsLogin();

        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        return ShopDesignService::DesignSave($params);
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function StatusUpdate($params = [])
    {
        // 是否已经登录
        $this->IsLogin();

        // 是否ajax
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始操作
        $params['user_id'] = $this->user['id'];
        return ShopDesignService::DesignStatusUpdate($params);
    }
    
    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Delete($params = [])
    {
        // 是否已经登录
        $this->IsLogin();

        // 是否ajax
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始操作
        $params['user_id'] = $this->user['id'];
        return ShopDesignService::DesignDelete($params);
    }

    /**
     * 同步到首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-04-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Sync($params = [])
    {
        // 是否已经登录
        $this->IsLogin();

        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error('非法访问');
        }

        // 开始操作
        $params['user_id'] = $this->user['id'];
        return ShopDesignService::DesignSync($params);
    }
}
?>