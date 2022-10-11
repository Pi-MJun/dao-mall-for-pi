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
namespace app\plugins\share\api;

use app\plugins\share\api\Common;
use app\plugins\share\service\BaseService;

/**
 * 分享
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * [__construct 构造方法]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-03T12:39:08+0800
     */
    public function __construct()
    {
        // 调用父类前置方法
        parent::__construct();
    }

    /**
     * 获取微信环境签名配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-10-15
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function SignPackage($params = [])
    {
        // 获取配置信息
        $base = BaseService::BaseConfig();
        if(!empty($base['data']) && !empty($base['data']['weixin_appid']) && !empty($base['data']['weixin_secret']))
        {
            // 获取微信配置信息
            $obj = new \base\Wechat($base['data']['weixin_appid'], $base['data']['weixin_secret']);
            $package = $obj->GetSignPackage($params);
        } else {
            $package = [];
        }

        // 返回数据
        $result = [
            'package'  => $package,
        ];
        return DataReturn('处理成功', 0, $result);
    }
}
?>