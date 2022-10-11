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
namespace app\plugins\themeswitch\service;

use app\service\PluginsService;
use app\service\UserService;

/**
 * 应用商店 - 基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-03-06
 * @desc    description
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [];

    // 来源页面地址 key
    public static $request_callback_url = 'plugins_themeswitch_request_callback_view_url';

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-06
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'themeswitch', 'data'=>$params]);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-06
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        $ret = PluginsService::PluginsData('themeswitch', self::$base_config_attachment_field, $is_cache);
        return $ret;
    }

    /**
     * url处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-31
     * @desc    description
     * @param   [string]          $url    [url地址]
     * @param   [array]           $config [插件配置信息]
     * @param   [array]           $params [输入参数]
     */
    public static function UrlHandle($url, $config, $params = [])
    {
        // 是否需要带上referrer参数
        if(!empty($params['referrer']) && isset($config['js_jump_referrer']) && $config['js_jump_referrer'] == 1)
        {
            if(stripos($url, 'referrer=') === false)
            {
                $join = (stripos($url, '?') === false) ? '?' : '&';
                return $url.$join.'referrer='.$params['referrer'];
            }
        }
        return $url;
    }

    /**
     * 获取需要跳转的url地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function UrlOpenData($params = [])
    {
        // url地址
        if(empty($params['urlkey']))
        {
            return DataReturn('url地址key为空', -1);
        }

        // url解析
        $url = MyCache($params['urlkey']);
        if(empty($url))
        {
            return DataReturn('url缓存数据已过期、请重新操作', -1);
        }
        // 清除缓存
        MyCache($params['urlkey'], null);
        return DataReturn('success', 0, $url);
    }

    /**
     * 设置cookie数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function SetCookieData($params = [])
    {
        // 是否存在邀请码
        if(!empty($params['referrer']))
        {
            // 是否存在自定义的cookie方法
            MyCookie('share_referrer_id', $params['referrer'], false);
        }
    }

    /**
     * 地址识别处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-04-21
     * @desc    description
     * @param   [string]          $url [url地址]
     * @param   [string]          $mca [当前模块控制器方法]
     */
    public static function UrlDiscernHandle($url, $mca)
    {
        if(!empty($url))
        {
            // 地址匹配
            $pd = [];
            $page = '';
            switch($mca)
            {
                // 分类
                case 'indexcategoryindex' :
                    $page = 'pages/goods-category/goods-category';
                    break;

                // 购物车
                case 'indexcartindex' :
                    $page = 'pages/cart/cart';
                    break;

                // 商品详情
                case 'indexgoodsindex' :
                    $id = MyInput('id');
                    if(!empty($id))
                    {
                        $page = 'pages/goods-detail/goods-detail';
                        $pd['id'] = $id;
                    }
                    break;

                // 文章详情
                case 'indexarticleindex' :
                    $id = MyInput('id');
                    if(!empty($id))
                    {
                        $page = 'pages/article-detail/article-detail';
                        $pd['id'] = $id;
                    }
                    break;

                // 文章分类
                case 'indexarticlecategory' :
                    $id = MyInput('id');
                    if(!empty($id))
                    {
                        $page = 'pages/article-category/article-category';
                        $pd['id'] = $id;
                    }
                    break;

                // 搜索-分类分类、关键字
                case 'indexsearchindex' :
                    $page = 'pages/goods-search/goods-search';
                    $category_id = MyInput('category_id');
                    if(!empty($category_id))
                    {
                        $pd['category_id'] = $category_id;
                    }
                    $wd = AsciiToStr(MyInput('wd'));
                    if(!empty($wd))
                    {
                        $pd['keywords'] = $wd;
                    }
                    break;

                // 页面设计
                case 'indexdesignindex' :
                    $id = MyInput('id');
                    if(!empty($id))
                    {
                        $page = 'pages/design/design';
                        $pd['id'] = $id;
                    }
                    break;

                // 插件
                case 'indexpluginsindex' :
                    $pluginsname = MyInput('pluginsname');
                    $pluginscontrol = MyInput('pluginscontrol', 'index');
                    $pluginsaction = MyInput('pluginsaction', 'index');
                    $nca = $pluginsname.$pluginscontrol.$pluginsaction;
                    switch($nca)
                    {
                        // 店铺首页
                        case 'shopindexindex' :
                            $page = 'pages/plugins/shop/index/index';
                            break;

                        // 店铺搜索
                        case 'shopsearchindex' :
                            $id = MyInput('id');
                            if(!empty($id))
                            {
                                $page = 'pages/plugins/shop/search/search';
                                $pd['shop_id'] = $id;
                                // 是否存在分类id
                                $cid = MyInput('cid');
                                if(!empty($cid))
                                {
                                    $pd['category_id'] = $cid;
                                }
                                // 是否存在关键字
                                $wd = MyInput('wd');
                                if(!empty($wd))
                                {
                                    $pd['keywords'] = AsciiToStr($wd);
                                }
                            }
                            break;

                        // 店铺详情
                        case 'shopindexdetail' :
                            $id = MyInput('id');
                            if(!empty($id))
                            {
                                $page = 'pages/plugins/shop/detail/detail';
                                $pd['id'] = $id;
                            }
                            break;

                        // 店铺页面设计
                        case 'shopdesigndetail' :
                            $id = MyInput('id');
                            if(!empty($id))
                            {
                                $page = 'pages/plugins/shop/design/design';
                                $pd['id'] = $id;
                            }
                            break;

                        // 店铺搜索
                        case 'shopsearchindex' :
                            $shop_id = MyInput('shop_id');
                            if(!empty($shop_id))
                            {
                                $page = 'pages/plugins/shop/search/search';
                                $pd = [
                                    'shop_id'   => $shop_id,
                                ];
                                // 分类
                                $category_id = MyInput('category_id');
                                if(!empty($category_id))
                                {
                                    $pd['category_id'] = $category_id;
                                }
                                // 关键字
                                $wd = AsciiToStr(MyInput('wd'));
                                if(!empty($wd))
                                {
                                    $pd['keywords'] = $wd;
                                }
                            }
                            break;

                        // 博客首页
                        case 'blogindexindex' :
                            $page = 'pages/plugins/blog/index/index';
                            break;

                        // 博客详情
                        case 'blogindexdetail' :
                            $id = MyInput('id');
                            if(!empty($id))
                            {
                                $page = 'pages/plugins/blog/detail/detail';
                                $pd['id'] = $id;
                            }
                            break;

                        // 博客搜索
                        case 'blogindexsearch' :
                            $page = 'pages/plugins/blog/search/search';
                            $id = MyInput('id');
                            if(!empty($id))
                            {
                                $pd['id'] = $id;
                            }
                            $bwd = AsciiToStr(MyInput('bwd'));
                            if(!empty($bwd))
                            {
                                $pd['keywords'] = $bwd;
                            }
                            break;

                        // 活动首页
                        case 'activityindexindex' :
                            $page = 'pages/plugins/activity/index/index';
                            break;

                        // 活动详情
                        case 'activityindexdetail' :
                            $id = MyInput('id');
                            if(!empty($id))
                            {
                                $page = 'pages/plugins/activity/detail/detail';
                                $pd['id'] = $id;
                            }
                            break;

                        // 标签详情
                        case 'labelindexdetail' :
                            $id = MyInput('id');
                            if(!empty($id))
                            {
                                $page = 'pages/plugins/label/detail/detail';
                                $pd['id'] = $id;
                            }
                            break;

                        // 限时秒杀首页
                        case 'seckillindexindex' :
                            $page = 'pages/plugins/seckill/index/index';
                            break;

                        // 积分商城首页
                        case 'pointsindexindex' :
                            $page = 'pages/plugins/points/index/index';
                            break;

                        // 优惠券首页
                        case 'couponindexindex' :
                            $page = 'pages/plugins/coupon/index/index';
                            break;

                        // 品牌首页
                        case 'brandindexindex' :
                            $page = 'pages/plugins/brand/index/index';
                            break;

                        // 会员等级增强版首页
                        case 'membershiplevelvipindexindex' :
                            $page = 'pages/plugins/membershiplevelvip/index/index';
                            break;

                        // 直播首页
                        case 'weixinliveplayerindexindex' :
                            $page = 'pages/plugins/weixinliveplayer/index/index';
                            break;
                    }
                    break;
            }
            if(!empty($page))
            {
                $url .= $page.(empty($pd) ? '' : '?'.http_build_query($pd));
            }
        }
        return $url;
    }
}
?>