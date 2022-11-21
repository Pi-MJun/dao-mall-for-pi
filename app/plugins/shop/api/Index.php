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

use app\layout\service\BaseLayout;
use app\plugins\shop\api\Common;
use app\plugins\shop\service\ShopService;
use app\plugins\shop\service\ShopFavorService;
use app\plugins\shop\service\ShopCategoryService;
use app\plugins\shop\service\ShopNavigationService;
use app\plugins\shop\service\ShopGoodsCategoryService;
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
        // 店铺分类
        $shop_category = ShopCategoryService::ShopCategoryAll(['field'=>'id,name']);

        // 返回数据
        $result = [
            'base'          => $this->base_config,
            'shop_category' => $shop_category['data'],
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 店铺列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function ShopList($params = [])
    {
        return ShopService::SearchList($this->data_request);
    }

    /**
     * 店铺详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        if(!empty($this->data_request['id']))
        {
            $shop = ShopService::ShopValidInfo($this->data_request['id'], $this->base_config);
            if(!empty($shop))
            {
                // 用户收藏的店铺
                $shop_favor_user = empty($this->user) ? [] : ShopFavorService::UserShopFavorData($this->user['id']);

                // 导航
                $navigation = ShopNavigationService::Nav($this->base_config, $shop);

                // 店铺商品分类
                $category = ShopGoodsCategoryService::GoodsCategoryAll(['field'=>'id,name', 'user_id'=>$shop['user_id']]);

                // 数据模式
                $data = [];
                $slider = [];
                $data_model = isset($shop['data_model']) ? $shop['data_model'] : 0;
                if($data_model == 1)
                {
                    // 配置处理
                    $data = BaseLayout::ConfigHandle($shop['layout_config']);
                } else {
                    // 获取店铺推荐数据
                    $data_params = [
                        'where'     => [
                            ['user_id', '=', $shop['user_id']],
                        ],
                        'user_id'   => $shop['user_id'],
                    ];
                    $data = ShopRecommendService::ShopRecommendData($data_params);

                    // 轮播
                    $slider = ShopSliderService::ClientShopSliderList(['user_id'=>$shop['user_id']]);
                }

                // 去除数据模式字段、避免很多配置数据造成带宽浪费
                unset($shop['layout_config']);

                // 返回数据
                $result = [
                    'base'                  => $this->base_config,
                    'shop_navigation'       => $navigation,
                    'shop_favor_user'       => $shop_favor_user,
                    'shop_goods_category'   => $category['data'],
                    'shop'                  => $shop,
                    'data'                  => $data,
                    'slider'                => $slider,
                ];
                return DataReturn('success', 0, $result);
            }
        }
        return DataReturn('店铺不存在或已删除', -1);
    }
}
?>