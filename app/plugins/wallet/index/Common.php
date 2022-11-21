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
namespace app\plugins\wallet\index;

use app\service\PaymentService;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\WalletService;

/**
 * 钱包 - 公共
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Common
{
    protected $user_wallet;
    protected $plugins_base;

    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-07-17
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function __construct($params = [])
    {
        // 参数赋值属性
        foreach($params as $k=>$v)
        {
            $this->$k = $v;
        }

        // 发起支付 - 支付方式
        MyViewAssign('buy_payment_list', PaymentService::BuyPaymentList(['is_enable'=>1, 'is_open_user'=>1]));

        // 用户钱包
        $user_wallet = empty($this->user) ? [] : WalletService::UserWallet($this->user['id']);

        // 用户钱包错误信息
        $wallet_error = empty($user_wallet) ? '请先登录' : (($user_wallet['code'] == 0) ? '' : $user_wallet['msg']);
        MyViewAssign('wallet_error', $wallet_error);

        // 所有ajax请求校验用户钱包状态
        if(IS_AJAX && !empty($wallet_error))
        {
            exit(json_encode(DataReturn($wallet_error, -50)));
        }

        // 用户钱包信息
        $this->user_wallet = empty($user_wallet) ? [] : $user_wallet['data'];
        MyViewAssign('user_wallet', $this->user_wallet);

        // 应用配置
        $plugins_base = BaseService::BaseConfig();
        $this->plugins_base = $plugins_base['data'];
        MyViewAssign('plugins_base', $this->plugins_base);
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
                MyRedirect('index/user/logininfo', true);
            }
        }
    }
}
?>