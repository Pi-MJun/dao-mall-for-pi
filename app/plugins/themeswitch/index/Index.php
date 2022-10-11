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
namespace app\plugins\themeswitch\index;

use app\plugins\themeswitch\service\BaseService;

/**
 * 主题切换
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index
{
    /**
     * 跳转
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Jump($params = [])
    {
        $ret = BaseService::UrlOpenData($params);
        if($ret['code'] == 0)
        {
            // 存储cookie
            BaseService::SetCookieData($params);
            return MyRedirect($ret['data']);
        }
        MyViewAssign('msg', $ret['msg']);
        return MyView('public/tips_error');
    }
}
?>