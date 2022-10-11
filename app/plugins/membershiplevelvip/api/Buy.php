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
namespace app\plugins\membershiplevelvip\api;

use app\plugins\membershiplevelvip\api\Common;
use app\plugins\membershiplevelvip\service\LevelService;
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
     * @date    2019-08-12
     * @desc    description
     */
    public function __construct()
    {
        parent::__construct();

        // 是否登录
        $this->IsLogin();
    }

    /**
     * 会员购买
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-18
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 会员等级
        $level = LevelService::DataList(['where'=>['is_enable'=>1, 'is_supported_pay_buy'=>1]]);

        // 返回数据
        $result = [
            'base'  => $this->plugins_base,
            'data'  => $level['data'],
        ];
        return DataReturn('处理成功', 0, $result);
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
    public function Create($params = [])
    {
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
    public function Renew($params = [])
    {
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
    public function Pay($params = [])
    {
        $params['user'] = $this->user;
        return PayService::Pay($params);
    }

    /**
     * 支付状态校验
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function PayCheck($params = [])
    {
        $params['user'] = $this->user;
        return PayService::LevelPayCheck($params);
    }
}
?>