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
namespace app\plugins\orderremind;

/**
 * 新订单语音提醒 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-11T21:51:08+0800
 */
class Hook
{
    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-11T14:25:44+0800
     * @param    [array]          $params [输入参数]
     */
    public function handle($params = [])
    {
        // 钩子名称
        $ret = '';
        if(!empty($params['hook_name']))
        {
            // 当前模块/控制器/方法
            $module_name = RequestModule();
            $controller_name = RequestController();
            $action_name = RequestAction();

            // 仅后端订单管理页面有效
            if($module_name.$controller_name.$action_name == 'adminindexindex')
            {
                switch($params['hook_name'])
                {
                    // js
                    case 'plugins_admin_js' :
                        $ret = 'static/plugins/js/orderremind/public/style.js';
                        break;

                    // 容器
                    case 'plugins_admin_view_common_bottom' :
                        $ret = $this->BottomContainerHtml($params);
                        break;
                }
            }
        }
        return $ret;
    }

    /**
     * 音频容器
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-13
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function BottomContainerHtml($params = [])
    {
        return MyView('../../../plugins/view/orderremind/admin/public/container');
    }
}
?>