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
namespace app\plugins\themeswitch;

use app\service\UserService;
use app\plugins\themeswitch\service\BaseService;

/**
 * 主题切换 - 钩子入口
 * @author   Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-03-06
 * @desc    description
 */
class Hook
{
    // 模块、控制器、方法
    private $module_name;
    private $controller_name;
    private $action_name;
    private $mc;
    private $mca;

    // 配置信息
    private $plugins_config;

    /**
     * 应用响应入口
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function handle($params = [])
    {
        // 钩子名称
        if(!empty($params['hook_name']))
        {
            // 当前模块/控制器/方法
            $this->module_name = RequestModule();
            $this->controller_name = RequestController();
            $this->action_name = RequestAction();
            $this->mc = $this->module_name.$this->controller_name;
            $this->mca = $this->module_name.$this->controller_name.$this->action_name;

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            // 走钩子
            $ret = '';
            switch($params['hook_name'])
            {
                // 系统初始化
                case 'plugins_service_system_begin' :
                    if($this->module_name == 'index' && IS_GET && !IS_AJAX && !IS_POST)
                    {
                        // 是否指定跳转地址
                        if(!empty($params['params']) && !empty($params['params']['request_url']))
                        {
                            $url = base64_decode(urldecode($params['params']['request_url']));
                            MySession(BaseService::$request_callback_url, $url);
                        }

                        // 跳转处理
                        // 在当前插件跳转控制器的时候不跳转、存在指定跳转地址也不跳转
                        $m = MyInput('pluginsname');
                        $c = MyInput('pluginscontrol', 'index');
                        // 整个插件
                        $pl_arr = [
                            'chat',
                            'realstore',
                        ];
                        // 插件+控制器
                        $mc = $m.$c;
                        $plc_arr = [
                            'themeswitchindex',
                            'thirdpartyloginindex',
                            'weixinwebauthorizationauth',
                            'weixinwebauthorizationpay',
                            'weixinwebauthorizationindex',
                            'shoplogin',
                            'shopuser',
                            'shopshop',
                            'shopnavigation',
                            'shopdesign',
                            'shopgoodscategory',
                            'shopgoods',
                            'shoporder',
                            'shoporderaftersale',
                            'shopprofit',
                        ];
                        // 主控制器
                        $main_arr = [
                            'agreement',
                            'qrcode',
                        ];
                        // 主控制器+方法
                        $main_ca_arr = [
                            'useruserverifyentry',
                        ];
                        if(empty($params['params']['themeswitchurl']) && !in_array($m, $pl_arr) && !in_array($mc, $plc_arr) && !in_array($this->controller_name, $main_arr) && !in_array($this->controller_name.$this->action_name, $main_ca_arr))
                        {
                            $this->JumpHandle($params);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * url跳转处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function JumpHandle($params = [])
    {
        // 指定地址
        $request_url = MySession(BaseService::$request_callback_url);

        // url
        if(empty($request_url))
        {
            // h5地址
            $url = empty($this->plugins_config['h5_url']) ? MyC('common_app_h5_url') : $this->plugins_config['h5_url'];

            // h5地址识别处理
            $url = BaseService::UrlDiscernHandle($url, $this->mca);
            
        } else {
            $url = $request_url;
        }

        // 必须设置了主题
        if(!empty($url) && __MY_URL__ != $url)
        {
            // 记录状态
            $status = true;

            // 是否手机模式下
            $is_mobile = isset($this->plugins_config['is_mobile']) ? intval($this->plugins_config['is_mobile']) : 0;
            if($is_mobile == 1 && !IsMobile())
            {
                $status = false;
            }

            // 是否微信环境中
            $is_wechat_env = isset($this->plugins_config['is_wechat_env']) ? intval($this->plugins_config['is_wechat_env']) : 0;
            if($is_wechat_env == 1 && !IsWeixinEnv())
            {
                $status = false;
            }

            // 微信环境中-是否需要登录
            $is_wechat_env_login = isset($this->plugins_config['is_wechat_env_login']) ? intval($this->plugins_config['is_wechat_env_login']) : 0;
            if($is_wechat_env_login == 1 && IsWeixinEnv())
            {
                $user = UserService::LoginUserInfo();
                if(empty($user))
                {
                    $status = false;
                }
            }

            // 是否指定不跳转
            if(isset($params['params']['is_jump']) && $params['params']['is_jump'] == 0)
            {
                $status = false;
            }

            // 需要跳转
            if($status)
            {
                // 回调地址信息
                MySession(BaseService::$request_callback_url, null);

                // 存储跳转链接
                $url = BaseService::UrlHandle($url, $this->plugins_config, $params['params']);
                $key = time().GetNumberCode(10).RandomString(10);
                MyCache($key, $url, 30);
                $params['params']['urlkey'] = $key;
                die(header('location:'.PluginsHomeUrl('themeswitch', 'index', 'jump', $params['params'])));
            }
        }
    }
}
?>