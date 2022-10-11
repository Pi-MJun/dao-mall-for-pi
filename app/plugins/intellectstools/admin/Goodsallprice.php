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
namespace app\plugins\intellectstools\admin;

use app\service\GoodsService;
use app\service\BrandService;
use app\plugins\intellectstools\admin\Common;
use app\plugins\intellectstools\service\BaseService;
use app\plugins\intellectstools\service\GoodsAllPriceService;

/**
 * 智能工具箱 - 商品批量调价
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class GoodsAllPrice extends Common
{
    /**
     * 批量调价页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 商品分类
        MyViewAssign('goods_category_list', GoodsService::GoodsCategoryAll());

        // 品牌
        MyViewAssign('brand_list', BrandService::CategoryBrand());

        // 静态数据
        MyViewAssign('price_type_list', BaseService::$price_type_list);
        MyViewAssign('modify_price_rules_list', BaseService::$modify_price_rules_list);

        return MyView('../../../plugins/view/intellectstools/admin/goodsallprice/index');
    }

    /**
     * 调价
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Edit($params = [])
    {
        return GoodsAllPriceService::GoodsAllPriceEdit($params);
    }
}
?>