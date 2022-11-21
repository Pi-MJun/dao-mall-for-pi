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
use app\plugins\shop\service\ShopNavigationService;
use app\plugins\shop\service\ShopGoodsCategoryService;
use app\plugins\shop\service\ShopDesignService;

/**
 * 多商户 - 页面设计
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Design extends Common
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
        if(empty($data))
        {
            return DataReturn('页面不存在或已删除', -1);
        }

        // 店铺信息
        $shop = ShopService::ShopValidInfo($data['shop_id'], $this->base_config);
        if(empty($shop))
        {
            return DataReturn('店铺不存在或已关闭', -1);
        }

        // 访问统计
        ShopDesignService::DesignAccessCountInc(['design_id'=>$data['id']]);

        // 配置处理
        $layout_data = BaseLayout::ConfigHandle($data['config']);

        // 用户收藏的店铺
        $shop_favor_user = empty($this->user) ? [] : ShopFavorService::UserShopFavorData($this->user['id']);

        // 导航
        $navigation = ShopNavigationService::Nav($this->base_config, $shop);

        // 店铺商品分类
        $category = ShopGoodsCategoryService::GoodsCategoryAll(['field'=>'id,name', 'user_id'=>$shop['user_id']]);

        // 去除布局配置数据、避免很多配置数据造成带宽浪费
        unset($shop['layout_config']);
        unset($data['config']);

        // 返回数据
        $result = [
            'base'                  => $this->base_config,
            'shop_navigation'       => $navigation,
            'shop_favor_user'       => $shop_favor_user,
            'shop_goods_category'   => $category['data'],
            'shop'                  => $shop,
            'data'                  => $data,
            'layout_data'           => $layout_data,
        ];
        return DataReturn('success', 0, $result);
    }
}
?>