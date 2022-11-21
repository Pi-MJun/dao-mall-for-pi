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

use app\plugins\shop\index\Common;
use app\plugins\shop\service\CrontabService;

/**
 * 多商户 - 定时脚本
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-02-14
 * @desc    description
 */
class Crontab extends Common
{
    /**
     * 收益结算
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-14
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Profit($params = [])
    {
        $ret = CrontabService::ProfitSettlement($params);
        return 'sucs:'.$ret['data']['sucs'].', fail:'.$ret['data']['fail'];
    }
}
?>