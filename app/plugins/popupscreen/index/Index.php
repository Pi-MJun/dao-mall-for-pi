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
namespace app\plugins\popupscreen\index;

use app\plugins\popupscreen\service\BaseService;

/**
 * 弹屏广告 - 前端
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @date     2019-06-23
 */
class Index
{
    /**
     * 关闭
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2019-06-23
     * @param    [array]          $params [输入参数]
     */
    public function Close($params = [])
    {
        // 记录session当前时间
        MySession('plugins_popupscreen_key', time());
        return DataReturn('关闭成功', 0);
    }
}
?>