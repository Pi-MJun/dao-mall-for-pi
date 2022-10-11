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

use app\plugins\membershiplevelvip\service\CrontabService;

/**
 * 会员等级增强版插件 - 脚本
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Crontab
{
    /**
     * 订单关闭
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-12-07T14:56:20+0800
     * @desc     description
     * @param    [array]         $params [输入参数]
     */
    public function orderclose($params = [])
    {
        $ret = CrontabService::OrderClose();
        return 'count:'.$ret['data'];
    }

    /**
     * 佣金订单创建
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-12-07T14:56:20+0800
     * @desc     description
     * @param    [array]         $params [输入参数]
     */
    public function profitcreate($params = [])
    {
        $ret = CrontabService::ProfitCreate();
        return 'sucs:'.$ret['data']['sucs'].', fail:'.$ret['data']['fail'];
    }

    /**
     * 佣金订单结算
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-12-07T14:56:20+0800
     * @desc     description
     * @param    [array]         $params [输入参数]
     */
    public function profitsettlement($params = [])
    {
        $ret = CrontabService::ProfitSettlement();
        return 'sucs:'.$ret['data']['sucs'].', fail:'.$ret['data']['fail'];
    }
}
?>