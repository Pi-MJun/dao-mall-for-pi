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
use app\plugins\shop\index\Base;
use app\plugins\shop\service\ShopDesignService;
use app\plugins\shop\service\ShopNavigationService;
use app\plugins\shop\service\ShopGoodsCategoryService;

/**
 * 多商户 - 导航管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Navigation extends Base
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
     * [Index 导航列表]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     */
    public function Index()
    {
        // 获取列表
        $data_params = [
            'where'         => $this->form_where,
            'order_by'      => $this->form_order_by['data'],
        ];
        $ret = ShopNavigationService::NavList($data_params);
        MyViewAssign('data_list', $ret['data']);

        // 页面设计列表
        $data_params = [
            'm'             => 0,
            'n'             => 0,
            'field'         => 'id.name',
            'where'         => [
                ['is_enable', '=', 1],
            ],
        ];
        $ret = ShopDesignService::DesignList($data_params);
        MyViewAssign('design_list', $ret['data']);

        // 店铺商品分类
        $category = ShopGoodsCategoryService::GoodsCategoryAll(['user_id'=>$this->user['id']]);
        MyViewAssign('shop_goods_category', $category['data']);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('导航管理', 1));
        return MyView('../../../plugins/view/shop/index/navigation/index');
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
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        return ShopNavigationService::NavSave($params);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Delete($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        return ShopNavigationService::NavDelete($params);
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
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

        // 开始处理
        $params['user_id'] = $this->user['id'];
        return ShopNavigationService::NavStatusUpdate($params);
    }
}
?>