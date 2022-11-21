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
namespace app\plugins\thirdpartylogin\api;

use app\plugins\thirdpartylogin\api\Common;
use app\plugins\thirdpartylogin\service\BaseService;
use app\plugins\thirdpartylogin\service\PlatformService;

/**
 * 第三方登录
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * 回调
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Bind($params = [])
    {
        return PlatformService::BindHandle($this->plugins_config, $params);
    }

    /**
     * 解绑
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Unbind($params = [])
    {
        $params['user'] = $this->user;
        return PlatformUserService::UnbindHandle($params);
    }

    /**
     * 取消
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Cancel($params = [])
    {
        BaseService::PlatformUserCacheRemove();
        return DataReturn('取消成功', 0);
    }
}
?>