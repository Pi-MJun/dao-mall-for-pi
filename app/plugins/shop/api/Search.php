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
namespace app\plugins\shop\api;

use app\plugins\shop\api\Common;
use app\plugins\shop\service\ShopService;
use app\plugins\shop\service\ShopSearchService;
use app\plugins\shop\service\ShopFavorService;
use app\plugins\shop\service\ShopNavigationService;
use app\plugins\shop\service\ShopGoodsCategoryService;

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

        // 搜索关键字
        $this->data_post['wd'] = empty($this->data_post['wd']) ? '' : $this->data_post['wd'];

        // 店铺信息
        $this->shop = empty($this->data_post['shop_id']) ? [] : ShopService::ShopValidInfo($this->data_post['shop_id'], $this->base_config);
        $this->data_post['shop_id'] = empty($this->shop) ? 0 : $this->shop['id'];        
    }

    /**
     * 初始化
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
            return DataReturn('店铺不存在或已关闭', -1);
        }

        // 店铺商品分类
        $shop_goods_category = ShopGoodsCategoryService::GoodsCategoryAll(['user_id'=>$this->shop['user_id']]);

        // 返回数据
        $result = [
            // 插件配置信息
            'base'                  => $this->base_config,
            // 店铺信息
            'shop'                  => $this->shop,
            // 导航
            'shop_navigation'       => ShopNavigationService::Nav($this->base_config, $this->shop),
            // 店铺商品分类
            'shop_goods_category'   => $shop_goods_category['data'],
            // 指定数据
            'search_map_info'       => ShopSearchService::SearchMapInfo($this->data_post),
            // 用户收藏的店铺
            'shop_favor_user'       => empty($this->user) ? [] : ShopFavorService::UserShopFavorData($this->user['id']),
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
        // 店铺校验
        if(empty($this->shop))
        {
            return DataReturn('店铺不存在或已关闭', -1);
        }

        // 获取数据
        $ret = ShopSearchService::GoodsList($this->data_post);

        // 兼容小程序端未去掉代码导致赋空值问题，下一个版本去掉这段代码
        // 店铺商品分类
        $shop_goods_category = ShopGoodsCategoryService::GoodsCategoryAll(['user_id'=>$this->shop['user_id']]);
        $ret['data']['shop'] = $this->shop;
        $ret['data']['shop_goods_category'] = $shop_goods_category['data'];
        $ret['data']['search_map_info'] = ShopSearchService::SearchMapInfo($this->data_post);

        return $ret;
    }
}
?>