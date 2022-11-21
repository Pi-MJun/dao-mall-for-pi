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
namespace app\plugins\shop\admin;

use app\service\GoodsService;
use app\service\BrandService;
use app\plugins\shop\admin\Common;
use app\plugins\shop\service\TransferService;

/**
 * 多商户 - 数据转移
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Transfer extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 商品分类
        MyViewAssign('goods_category_list', GoodsService::GoodsCategoryAll());

        // 仓库列表
        MyViewAssign('warehouse_list', TransferService::WarehouseList());

        // 品牌
        MyViewAssign('brand_list', BrandService::CategoryBrand());

        return MyView('../../../plugins/view/shop/admin/transfer/index');
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Save($params = [])
    {
        return TransferService::TransferSave($params);
    }
}
?>