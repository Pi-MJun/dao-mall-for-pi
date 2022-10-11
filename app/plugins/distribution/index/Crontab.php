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
namespace app\plugins\distribution\index;

use app\plugins\distribution\service\CrontabService;

/**
 * 分销 - 脚本
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Crontab
{
    /**
     * 佣金结算
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-12-07T14:56:20+0800
     * @desc     description
     * @param    [array]         $params [输入参数]
     */
    public function profit($params = [])
    {
        $ret = CrontabService::ProfitSettlement();
        return 'sucs:'.$ret['data']['sucs'].', fail:'.$ret['data']['fail'];
    }
}
?>