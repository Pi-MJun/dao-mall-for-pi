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
namespace app\plugins\membershiplevelvip\index;

use app\plugins\membershiplevelvip\index\Common;
use app\plugins\membershiplevelvip\service\LevelBuyService;
use app\plugins\membershiplevelvip\service\PayService;

/**
 * 会员等级增强版插件 - 会员购买
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Buy extends Common
{
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
        parent::__construct($params);

        // 是否已经登录
        if(!isset($this->plugins_action_name) || $this->plugins_action_name != 'respond')
        {
            $this->IsLogin();
        }
    }

    /**
     * 会员购买订单创建
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-18
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function create($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 用户
        $params['user'] = $this->user;
        return LevelBuyService::BuyOrderCreate($params);
    }

    /**
     * 会员续费
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-18
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function renew($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 用户
        $params['user'] = $this->user;
        return LevelBuyService::BuyOrderRenew($params);
    }

    /**
     * 支付
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function pay($params = [])
    {
        // 用户
        $params['user'] = $this->user;
        $ret = PayService::Pay($params);
        if($ret['code'] == 0)
        {
            return MyRedirect($ret['data']['data']);
        } else {
            MyViewAssign('msg', $ret['msg']);
            return MyView('public/tips_error');
        }
    }

    /**
     * 支付状态校验
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function paycheck($params = [])
    {
        if(input('post.'))
        {
            $params['user'] = $this->user;
            return PayService::LevelPayCheck($params);
        } else {
            MyViewAssign('msg', '非法访问');
            return MyView('public/tips_error');
        }
    }

    /**
     * 支付同步页面
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function respond($params = [])
    {
        // 获取支付回调数据
        $params['user'] = $this->user;
        $ret = PayService::Respond($params);

        // 自定义链接
        MyViewAssign('to_url', PluginsHomeUrl('membershiplevelvip', 'order', 'index'));
        MyViewAssign('to_title', '开通订单');

        // 状态
        if($ret['code'] == 0)
        {
            MyViewAssign('msg', '支付成功');
            return MyView('public/tips_success');
        } else {
            MyViewAssign('msg', $ret['msg']);
            return MyView('public/tips_error');
        }
    }
}
?>