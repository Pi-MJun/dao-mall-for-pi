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

use app\service\ResourcesService;
use app\service\SystemBaseService;
use app\plugins\shop\service\BaseService;

/**
 * 多商户 - 公共
 * @author   Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2018-11-30
 * @desc    description
 */
class Common
{
    // 插件配置信息
    protected $base_config;

    // 是否商家中心
    protected $is_shop_center;

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
        // 参数赋值属性
        foreach($params as $k=>$v)
        {
            $this->$k = $v;
        }

        // 默认非商家中心
        $this->is_shop_center = false;

        // 视图初始化
        $this->ViewInit();

        // 动态表格初始化
        $this->FormTableInit();
    }

    /**
     * 视图初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-02
     * @desc    description
     */
    public function ViewInit()
    {
        // 用户
        MyViewAssign('user', $this->user);

        // 分页信息
        MyViewAssign('page', $this->page);
        MyViewAssign('page_size', $this->page_size);

        // 货币符号
        MyViewAssign('currency_symbol', ResourcesService::CurrencyDataSymbol());

        // 图片host地址
        MyViewAssign('attachment_host', SystemBaseService::AttachmentHost());

        // 价格正则
        MyViewAssign('default_price_regex', MyConst('common_regex_price'));

        // 插件配置信息
        $base = BaseService::BaseConfig();
        $this->base_config = $base['data'];
        MyViewAssign('base_config', $this->base_config);

        // 拖转页面自定义说明地址
        if(!empty($this->base_config['layout_pages_custom_doc_url']))
        {
            MyViewAssign('layout_pages_custom_doc_url', $this->base_config['layout_pages_custom_doc_url']);
        }

        // 站点名称
        MyViewAssign('admin_theme_site_name', MyC('admin_theme_site_name', 'ShopXO', true));

        // 用户中心导航
        $nav = BaseService::UserCenterNavData();
        MyViewAssign('shop_user_center_nav', $nav['base']);
        MyViewAssign('shop_user_center_extends', $nav['extends']);
    }

    /**
     * 动态表格初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-02
     * @desc    description
     */
    public function FormTableInit()
    {
        MyViewAssign('form_table', $this->form_table);
        MyViewAssign('form_params', $this->form_params);
        MyViewAssign('form_md5_key', $this->form_md5_key);
        MyViewAssign('form_user_fields', $this->form_user_fields);
        MyViewAssign('form_order_by', $this->form_order_by);
        MyViewAssign('form_error', $this->form_error);
    }
    
    /**
     * 登录校验
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-16
     * @desc    description
     */
    protected function IsLogin()
    {
        if(empty($this->user))
        {
            if(IS_AJAX)
            {
                exit(json_encode(DataReturn('登录失效，请重新登录', -400)));
            } else {
                $url = $this->is_shop_center ? PluginsHomeUrl('shop', 'login', 'index') : MyUrl('index/user/logininfo');
                MyRedirect($url, true);
            }
        }
    }

    /**
     * 错误提示
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-07-15
     * @desc    description
     * @param   [string]      $msg [提示信息、默认（操作失败）]
     */
    public function error($msg)
    {
        if(IS_AJAX)
        {
            return DataReturn($msg, -1);
        } else {
            MyViewAssign('msg', $msg);
            return MyView('public/jump_error');
        }
    }

    /**
     * 店铺二级菜单
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-26
     * @desc    description
     */
    protected function ShopSecondNav()
    {
        // 二级导航
        $second_nav_list = [
            [
                'name'      => '店铺信息',
                'control'   => 'shop',
                'action'    => 'index',
            ],
        ];
        // 是否开启二级域名设置
        if(isset($this->base_config['is_shop_second_domain_set']) && $this->base_config['is_shop_second_domain_set'] == 1)
        {
            $second_nav_list[] = [
                'name'      => '二级域名',
                'control'   => 'shop',
                'action'      => 'domain',
            ];
        }
        // 是否开启保证金
        if(isset($this->base_config['is_shop_bond']) && $this->base_config['is_shop_bond'] == 1)
        {
            $second_nav_list[] = [
                'name'      => '保证金',
                'control'   => 'shop',
                'action'    => 'bond',
            ];
        }
        MyViewAssign('second_nav_list', $second_nav_list);
    }
}
?>