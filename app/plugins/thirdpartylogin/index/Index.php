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
namespace app\plugins\thirdpartylogin\index;

use app\plugins\thirdpartylogin\index\Common;
use app\plugins\thirdpartylogin\service\BaseService;
use app\plugins\thirdpartylogin\service\PlatformService;
use app\plugins\thirdpartylogin\service\PlatformUserService;

/**
 * 第三方登录 - 首页
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Index extends Common
{
    /**
     * 登录
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Login($params = [])
    {
        // 处理登录
        $ret = PlatformService::LoginHandle($this->plugins_config, $params);
        if($ret['code'] == 0)
        {
            return MyRedirect($ret['data']);
        }
        return $this->ErrorHandle($ret);
    }

    /**
     * 回调
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Back($params = [])
    {
        $ret = PlatformService::BackHandle($this->plugins_config, $params);
        if($ret['code'] == 0)
        {
            // 来源终端
            $application_client_type = BaseService::GetApplicationClientType();
            if($application_client_type == 'h5')
            {
                $url = BaseService::H5PageSuccessUrl($this->plugins_config, $ret['data']);
                if(!empty($url))
                {
                    return MyRedirect($url);
                }
            }
            return MyRedirect($ret['data']);
        }
        return $this->ErrorHandle($ret);
    }

    /**
     * 错误处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-12-05
     * @desc    description
     * @param   [array]          $ret [返回数据]
     */
    public function ErrorHandle($ret)
    {
        // 来源终端
        $application_client_type = BaseService::GetApplicationClientType();

        // 手机端则默认
        if($application_client_type == 'h5')
        {
            $url = BaseService::H5PageErrorUrl($this->plugins_config, $ret['msg'], $ret['code']);
            if(!empty($url))
            {
                return MyRedirect($url);
            }
        }

        // 默认web页面
        MyViewAssign('msg', $ret['msg']);
        return MyView('public/tips_error');
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

    /**
     * 更多帐号选择
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-26
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function More($params = [])
    {
        // 平台列表
        $platform_type_list = BaseService::PlatformTypeList($this->plugins_config);
        MyViewAssign('platform_type_list', $platform_type_list);

        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/thirdpartylogin/index/index/more');
    }
}
?>