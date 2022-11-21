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
namespace app\plugins\wallet\admin;

use app\service\PluginsService;
use app\service\AdminService;
use app\plugins\wallet\admin\Common;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\StatisticalService;

/**
 * 钱包插件 - 管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {            
            MyViewAssign('data', $ret['data']);

            // 统计数据
            MyViewAssign('statistical', StatisticalService::StatisticalData());

            return MyView('../../../plugins/view/wallet/admin/admin/index');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function saveinfo($params = [])
    {
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {
            // 是否
            $is_whether_list =  [
                0 => array('value' => 0, 'name' => '否'),
                1 => array('value' => 1, 'name' => '是', 'checked' => true),
            ];
            MyViewAssign('is_whether_list', $is_whether_list);

            // 充值赠送类型
            $recharge_give_type_list =  [
                0 => array('value' => 0, 'name' => '固定金额', 'checked' => true),
                1 => array('value' => 1, 'name' => '比例'),
            ];
            MyViewAssign('recharge_give_type_list', $recharge_give_type_list);

            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/wallet/admin/admin/saveinfo');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function save($params = [])
    {
        // 钱包修改密码校验
        if(!empty($params['wallet_edit_money_password']))
        {
            // 当前登录的管理员是否为超管
            $admin = AdminService::LoginInfo();
            if(!isset($admin['id']) || $admin['id'] != 1)
            {
                return DataReturn('钱包修改密码仅超管有权限修改', -1);
            }

            // 密码加密
            $params['wallet_edit_money_password'] = BaseService::WalletMoneyEditPassword($params['wallet_edit_money_password']);
        } else {
            // 使用原来设置的密码覆盖
            $ret = BaseService::BaseConfig();
            if(!empty($ret['data']['wallet_edit_money_password']))
            {
                $params['wallet_edit_money_password'] = $ret['data']['wallet_edit_money_password'];
            }
        }

        // 数据保存
        return PluginsService::PluginsDataSave(['plugins'=>'wallet', 'data'=>$params]);
    }
}
?>