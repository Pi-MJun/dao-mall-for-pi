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
namespace app\plugins\brand\api;

use app\plugins\brand\api\Common;
use app\plugins\brand\service\BaseService;

/**
 * 品牌 - 首页
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
        // 插件配置信息
        $base = BaseService::BaseConfig();

        // 品牌分类
        $brand_category_list = BaseService::BrandCategoryList();

        // 品牌列表
        $brand_list = BaseService::BrandList();

        // 返回数据
        $result = [
            'base'                  => $base['data'],
            'brand_category_list'   => $brand_category_list,
            'brand_list'            => $brand_list,
        ];
        return DataReturn('success', 0, $result);
    }
}
?>