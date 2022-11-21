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
use app\service\UserService;
use app\plugins\shop\index\Base;

/**
 * 多商户 - 用户登录
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Login extends Base
{
    /**
     * 用户登录页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 是否已经登录
        if(!empty($this->user))
        {
            MyRedirect(PluginsHomeUrl('shop', 'user', 'index'), true);
        }

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('商家登录', 1));
        return MyView('../../../plugins/view/shop/index/login/index');
    }

    /**
     * 退出
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-04
     * @desc    description
     */
    public function Logout()
    {
        // 调用服务层
        $ret = UserService::Logout();

        // 登录返回
        $body_html = (!empty($ret['data']['body_html']) && is_array($ret['data']['body_html'])) ? implode(' ', $ret['data']['body_html']) : $ret['data']['body_html'];
        MyViewAssign('body_html', $body_html);
        MyViewAssign('msg', $ret['msg']);
        return MyView('../../../plugins/view/shop/index/login/logout');
    }
}
?>