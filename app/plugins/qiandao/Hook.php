<?php
namespace app\plugins\qiandao;


use app\plugins\qiandao\service\BaseService;

/**
 * 积分签到 - 钩子入口
 */
class Hook
{
    // 配置信息
    private $plugins_config;

    /**
     * 应用响应入口
     * @param    [array]          $params [输入参数]
     */
    public function handle($params = [])
    {
        // 后端访问不处理
        if(isset($params['params']['is_admin_access']) && $params['params']['is_admin_access'] == 1)
        {
            return DataReturn('无需处理', 0);
        }

        // 钩子名称
        $ret = '';
        if(!empty($params['hook_name']))
        {
            // 配置信息
            $MyConfig = BaseService::BaseConfig();
            $this->plugins_config = empty($MyConfig['data']) ? [] : $MyConfig['data'];

            // 当前模块/控制器/方法
            $module_name = RequestModule();
            $controller_name = RequestController();
            $action_name = RequestAction();

            switch($params['hook_name'])
            {	
                // 在前面添加导航
                case 'plugins_service_navigation_header_handle' :
                    $ret = $this->NavigationHeaderHandle($params);
                    break;

                // 用户中心左侧导航
                case 'plugins_service_users_center_left_menu_handle' :
                    $ret = $this->UserCenterLeftMenuHandle($params);
                    break;

                // 顶部小导航右侧-我的商城
                case 'plugins_service_header_navigation_top_right_handle' :
                    $ret = $this->CommonTopNavRightMenuHandle($params);
                    break;
					
				// 处理用户数据调出来的手机号脱敏
                case 'plugins_service_user_list_handle_end' :
                    $ret = $this->UserDataHandle($params);
                    break;
            }
        }
        return $ret;
    }
	
	/**
     * 处理用户数据调出来的手机号脱敏
     * @param   [array]           $params [输入参数]
     */
    public function UserDataHandle($params = [])
    {
        if(!empty($params['data']))
        {
            foreach($params['data'] as &$v)
            {
                // 生日
                if(array_key_exists('mobile', $v))
                {
                    $v['mobile_security']=   empty($v['mobile']) ? '' : mb_substr($v['mobile'], 0, 3, 'utf-8').'***'.mb_substr($v['mobile'], -3, null, 'utf-8');
                }
            }
        }
    }
	
	/**
     * 中间大导航
     * @param   [array]           $params [输入参数]
     */
    public function NavigationHeaderHandle($params = [])
    {
        if(is_array($params['header']) && !empty($this->plugins_config['application_name']))
        {
            $nav = [
                'id'                    => 0,
                'pid'                   => 0,
                'name'                  => $this->plugins_config['application_name'],
                'url'                   => PluginsHomeUrl('qiandao', 'index', 'index'),
                'data_type'             => 'custom',
                'is_show'               => 1,
                'is_new_window_open'    => 0,
                'items'                 => [],
            ];
            array_unshift($params['header'], $nav);
        }
    }
	
	/**
     * 用户中心左侧菜单处理
     * @param   [array]           $params [输入参数]
     */
    public function UserCenterLeftMenuHandle($params = [])
    {
        $params['data']['business']['item'][] = [
            'name'      =>  '我的签到',
            'url'       =>  PluginsHomeUrl('qiandao', 'record', 'index'),
            'contains'  =>  ['qiandaorecordindex','qiandaoscoreindex'],
            'is_show'   =>  1,
            'icon'      =>  'am-icon-get-pocket',
        ];
    }
	
	/**
     * 顶部小导航右侧-我的商城
     * @param   [array]           $params [输入参数]
     */
    public function CommonTopNavRightMenuHandle($params = [])
    {
        array_push($params['data'][1]['items'], [
            'name'  => '我的签到',
            'url'   => PluginsHomeUrl('qiandao', 'record', 'index'),
        ]);
    }
}
?>