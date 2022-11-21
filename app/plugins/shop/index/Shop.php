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
use app\plugins\shop\service\BaseService;
use app\plugins\shop\service\ShopService;
use app\plugins\shop\service\ShopCategoryService;
use app\plugins\wallet\service\WalletService;

/**
 * 多商户 - 店铺信息
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Shop extends Base
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

        // 店铺二级菜单
        $this->ShopSecondNav();
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
        // 获取数据
        $data_params = [
            'where'     => [
                'user_id' => $this->user['id']
            ],
            'm'         => 0,
            'n'         => 1,
            'user_type' => 'shop',
        ];
        $ret = ShopService::ShopList($data_params);
        $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
        MyViewAssign('data', $data);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('店铺信息', 1));
        return MyView('../../../plugins/view/shop/index/shop/index');
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
        // 获取数据
        $data_params = [
            'where'     => [
                'user_id' => $this->user['id']
            ],
            'm'         => 0,
            'n'         => 1,
            'user_type' => 'shop',
        ];
        $ret = ShopService::ShopList($data_params);
        $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
        MyViewAssign('data', $data);

        // 店铺分类
        $category = ShopCategoryService::ShopCategoryAll(['field'=>'id,name']);
        MyViewAssign('shop_category', $category['data']);

        // 静态数据
        MyViewAssign('plugins_week_list', BaseService::$plugins_week_list);
        MyViewAssign('plugins_auth_type_list', BaseService::$plugins_auth_type_list);
        MyViewAssign('plugins_shop_data_model_list', BaseService::$plugins_shop_data_model_list);

        // 加载百度地图api
        MyViewAssign('is_load_baidu_map_api', 1);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('店铺信息', 1));

        // 编辑器文件存放地址定义
        MyViewAssign('editor_path_type', 'plugins_shop-user_shop-'.$this->user['id']);
        return MyView('../../../plugins/view/shop/index/shop/saveinfo');
    }

    /**
     * 二级域名修改
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Domain($params = [])
    {
        // 获取数据
        $data_params = [
            'where'     => [
                'user_id' => $this->user['id']
            ],
            'm'         => 0,
            'n'         => 1,
            'user_type' => 'shop',
        ];
        $ret = ShopService::ShopList($data_params);
        $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
        MyViewAssign('data', $data);

        if(!empty($data))
        {
            // 可最大修改次数
            $shop_domain_can_edit_number = BaseService::ShopSecondDomainCanEditNumber($this->base_config, $data['domain_edit_number']);
            MyViewAssign('shop_domain_can_edit_number', $shop_domain_can_edit_number);

            // 店铺域名
            $shop_domain = BaseService::ShopDomain($this->base_config, $data);
            MyViewAssign('shop_domain', $shop_domain);
        }

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('二级域名', 1));
        return MyView('../../../plugins/view/shop/index/shop/domain');
    }

    /**
     * 保证金
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Bond($params = [])
    {
        // 获取数据
        $data_params = [
            'where'     => [
                'user_id' => $this->user['id']
            ],
            'm'         => 0,
            'n'         => 1,
            'user_type' => 'shop',
        ];
        $ret = ShopService::ShopList($data_params);
        $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
        MyViewAssign('data', $data);

        if(!empty($data))
        {
            // 用户钱包
            $user_wallet = WalletService::UserWallet($this->user['id']);
            MyViewAssign('user_wallet', $user_wallet['data']);

            // 保证金
            $shop_bond_price = BaseService::ShopBondPrice($this->base_config, $data);
            MyViewAssign('shop_bond_price', $shop_bond_price);
        }

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('保证金', 1));
        return MyView('../../../plugins/view/shop/index/shop/bond');
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
        $params['base_config'] = $this->base_config;
        return ShopService::ShopSave($params);
    }

    /**
     * 提交审核
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Submit($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        return ShopService::ShopSubmit($params);
    }

    /**
     * 布局保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function LayoutSave($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        return ShopService::LayoutDesignSave($params);
    }

    /**
     * 二级域名查询
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function DomainQuery($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        $params['base_config'] = $this->base_config;
        return ShopService::DomainQuery($params);
    }

    /**
     * 二级域名绑定
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function DomainBind($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        $params['base_config'] = $this->base_config;
        return ShopService::DomainBind($params);
    }

    /**
     * 保证金缴纳
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BondPay($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        $params['base_config'] = $this->base_config;
        return ShopService::BondPay($params);
    }

    /**
     * 保证金退回
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BondGoback($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        $params['base_config'] = $this->base_config;
        return ShopService::BondGoback($params);
    }
}
?>