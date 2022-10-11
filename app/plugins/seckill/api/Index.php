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
namespace app\plugins\seckill\api;

use app\plugins\seckill\api\Common;
use app\plugins\seckill\service\BaseService;

/**
 * 限时秒杀
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-03
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 秒杀信息
        $ret = BaseService::SeckillData($this->plugins_config);

        // 幻灯片
        $slider = BaseService::SliderList(['where'=>['is_enable'=>1]]);
        $ret['data']['slider'] = empty($slider['data']) ? [] : $slider['data'];

        return $ret;
    }
}
?>