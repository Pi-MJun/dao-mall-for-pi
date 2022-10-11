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
namespace app\plugins\blog;

use app\plugins\blog\service\BaseService;
use app\plugins\blog\service\RecommendService;

/**
 * 博客 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    // 配置信息
    private $plugins_config;

    // 当前模块/控制器/方法
    private $module_name;
    private $controller_name;
    private $action_name;

    /**
     * 应用响应入口
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-03
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function handle($params = [])
    {
        if(!empty($params['hook_name']))
        {
            // 当前模块/控制器/方法
            $this->module_name = RequestModule();
            $this->controller_name = RequestController();
            $this->action_name = RequestAction();

            // 当插件页面
            $pn = input('pluginsname');
            $pc = input('pluginscontrol', 'index');
            $pa = input('pluginsaction', 'index');

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            // 是否引入Aliplayer
            $is_require_aliplayer = (isset($this->plugins_config['is_require_aliplayer']) && $this->plugins_config['is_require_aliplayer'] == 1) ? 1 : 0;
            if($is_require_aliplayer == 1)
            {
                $is_require_aliplayer = ($this->module_name.$this->controller_name.$this->action_name == 'indexpluginsindex' && $pn.$pc.$pa == 'blogindexdetail') ? 1 : 0;
            }

            $ret = [];
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    if($is_require_aliplayer == 1)
                    {
                        //$ret = 'https://g.alicdn.com/de/prismplayer/2.9.17/skins/default/aliplayer-min.css';
                        $ret[] = 'static/plugins/js/blog/public/aliplayer/aliplayer-min.css';
                    }
                    $ret[] = 'static/plugins/css/blog/index/common.css';
                    break;

                // 公共js
                case 'plugins_js' :
                    if($is_require_aliplayer == 1)
                    {
                        //$ret = 'https://g.alicdn.com/de/prismplayer/2.9.17/aliplayer-h5-min.js';
                        $ret = 'static/plugins/js/blog/public/aliplayer/aliplayer-min.js';
                    }
                    break;

                // 在前面添加导航
                case 'plugins_service_navigation_header_handle' :
                    $ret = $this->NavigationHeaderHandle($params);
                    break;

                // 首页楼层顶部新增博客
                case 'plugins_view_home_floor_top' :
                case 'plugins_view_home_floor_bottom' :
                    $ret = $this->BlogFloorHandle($params);
                    break;

                // 首页接口数据
                case 'plugins_service_base_data_return_api_index_index' :
                    $ret = $this->IndexResultHandle($params);
                    break;
            }
            return $ret;
        }
    }

    /**
     * 首页接口数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function IndexResultHandle($params = [])
    {
        $data = RecommendService::RecommendFloorData();
        if(!empty($data))
        {
            $params['data']['plugins_blog_data'] = [
                'base'  => $this->plugins_config,
                'data'  => $data,
            ];
        }
    }

    /**
     * 楼层博客
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function BlogFloorHandle($params = [])
    {
        // 数据位置
        $floor_location_arr = [
            'plugins_view_home_floor_top'       => 0,
            'plugins_view_home_floor_bottom'    => 1,
        ];
        $home_data_location = array_key_exists($params['hook_name'], $floor_location_arr) ? $floor_location_arr[$params['hook_name']] : 0;
        $data = RecommendService::RecommendFloorData(['where'=>[['home_data_location', '=', $home_data_location]]]);
        MyViewAssign('blog_data_list', $data);
        MyViewAssign('plugins_config', $this->plugins_config);
        return MyView('../../../plugins/view/blog/index/public/home');
    }

    /**
     * 中间大导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function NavigationHeaderHandle($params = [])
    {
        if(isset($params['header']) && is_array($params['header']))
        {
            if(!empty($this->plugins_config['application_name']))
            {
                $nav = [
                    'id'                    => 0,
                    'pid'                   => 0,
                    'name'                  => $this->plugins_config['application_name'],
                    'url'                   => PluginsHomeUrl('blog', 'index', 'index'),
                    'data_type'             => 'custom',
                    'is_show'               => 1,
                    'is_new_window_open'    => 0,
                    'items'                 => [],
                ];
                array_unshift($params['header'], $nav);
            }
        }
    }
}
?>