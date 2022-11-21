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

use app\service\PaymentService;
use app\service\GoodsService;
use app\service\SeoService;
use app\plugins\shop\index\Base;
use app\plugins\shop\service\ShopFreightfeeService;
use app\plugins\freightfee\service\BaseService as FreightfeeBaseService;

/**
 * 多商户 - 运费设置
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Freightfee extends Base
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

        // 是否已经登录
        $this->IsLogin();
    }

    /**
     * 首页
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 获取运费数据
        $data = ShopFreightfeeService::ShopFreightfeeData($this->user['id']);
        MyViewAssign('data', $data);

        // 静态数据
        MyViewAssign('is_whether_list', FreightfeeBaseService::$is_whether_list);
        MyViewAssign('is_continue_type_list', FreightfeeBaseService::$is_continue_type_list);
        return MyView('../../../plugins/view/shop/index/freightfee/index');
    }
    
    /**
     * 编辑
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        // 获取运费数据
        $data = ShopFreightfeeService::ShopFreightfeeData($this->user['id']);
        MyViewAssign('data', $data);

        // 支付方式
        MyViewAssign('payment_list', PaymentService::PaymentList(['is_enable'=>1, 'is_open_user'=>1]));

        // 商品分类
        MyViewAssign('goods_category_list', GoodsService::GoodsCategory(['is_all'=>1]));

        // 地区
        MyViewAssign('region_list', FreightfeeBaseService::RegionList());

        // 静态数据
        MyViewAssign('is_whether_list', FreightfeeBaseService::$is_whether_list);
        MyViewAssign('is_continue_type_list', FreightfeeBaseService::$is_continue_type_list);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('运费设置', 1));
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/shop/index/freightfee/saveinfo');
    }

    /**
     * 保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Save($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 调用运费插件方法
        return FreightfeeBaseService::WarehouseFeeSave($params);
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function StatusUpdate($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 调用运费插件方法
        return FreightfeeBaseService::WarehouseFeeStatusUpdate($params);
    }

    /**
     * 商品搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-22
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsSearch($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 调用运费插件方法
        return ShopFreightfeeService::GoodsSearchList($this->user['id'], $params);
    }
}
?>