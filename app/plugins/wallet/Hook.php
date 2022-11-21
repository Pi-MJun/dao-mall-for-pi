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
namespace app\plugins\wallet;

use app\plugins\wallet\service\WalletService;

/**
 * 会员等级插件 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-09T14:25:44+0800
     * @param    [array]                    $params [输入参数]
     */
    public function handle($params = [])
    {
        if(!empty($params['hook_name']))
        {
            switch($params['hook_name'])
            {
                // 用户中心左侧导航
                case 'plugins_service_users_center_left_menu_handle' :
                    $ret = $this->UserCenterLeftMenuHandle($params);
                    break;

                // 顶部小导航右侧-我的商城
                case 'plugins_service_header_navigation_top_right_handle' :
                    $ret = $this->CommonTopNavRightMenuHandle($params);
                    break;

                // 用户注册
                case 'plugins_service_user_register_end' :
                    $ret = WalletService::UserWallet($params['user_id']);
                    break;

                default :
                    $ret = '';
            }
            return $ret;
        }
    }

    /**
     * 用户中心左侧菜单处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   array           $params [description]
     */
    public function UserCenterLeftMenuHandle($params = [])
    {
        $params['data']['property']['item'][] = [
            'name'      =>  '我的钱包',
            'url'       =>  PluginsHomeUrl('wallet', 'wallet', 'index'),
            'contains'  =>  ['walletindex', 'rechargeindex', 'cashindex', 'cashauthinfo', 'cashcreateinfo'],
            'is_show'   =>  1,
            'icon'      =>  'am-icon-google-wallet',
        ];
    }

    /**
     * 顶部小导航右侧-我的商城
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   array           $params [description]
     */
    public function CommonTopNavRightMenuHandle($params = [])
    {
        array_push($params['data'][1]['items'], [
            'name'  => '我的钱包',
            'url'   => PluginsHomeUrl('wallet', 'wallet', 'index'),
        ]);
    }
}
?>