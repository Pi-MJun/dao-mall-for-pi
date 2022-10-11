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
namespace app\plugins\multilingual;

use app\plugins\multilingual\service\BaseService;
use app\plugins\multilingual\service\TranslateService;
use app\plugins\multilingual\service\MultilingualService;

/**
 * 多语言 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-11T21:51:08+0800
 */
class Hook
{
    // 基础属性
    public $module_name;
    public $controller_name;
    public $action_name;
    public $mca;

    // 插件配置
    public $plugins_config;

    // 当前选中的id
    private $multilingual_value;

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
        if(!empty($params['hook_name']))
        {
            // 当前模块/控制器/方法
            $this->module_name = RequestModule();
            $this->controller_name = RequestController();
            $this->action_name = RequestAction();
            $this->mca = $this->module_name.$this->controller_name.$this->action_name;

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            // 后台开启选择
            $is_admin_select = isset($this->plugins_config['is_admin_select']) ? intval($this->plugins_config['is_admin_select']) : 0;

            // 是否开启选择
            $is_user_quick_select = isset($this->plugins_config['is_user_quick_select']) ? intval($this->plugins_config['is_user_quick_select']) : 0;
            $is_user_header_top_right_select = isset($this->plugins_config['is_user_header_top_right_select']) ? intval($this->plugins_config['is_user_header_top_right_select']) : 0;
   
            // 用户指定的语言值
            $this->multilingual_value = BaseService::GetUserMultilingualCacheValue();

            // 是否设置默认语言
            if(empty($this->multilingual_value))
            {
                if(!empty($this->plugins_config['default_lang']))
                {
                    $lang_list = MultilingualService::MultilingualColumnList();
                    if(array_key_exists($this->plugins_config['default_lang'], $lang_list))
                    {
                        BaseService::SetUserMultilingualCacheValue($this->plugins_config['default_lang']);
                        $this->multilingual_value = $this->plugins_config['default_lang'];
                    }
                }
            } else {
                BaseService::SetUserMultilingualCacheValue($this->multilingual_value);
            }

            // 走钩子
            $ret = '';
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    if($this->module_name == 'index' && ($is_user_quick_select == 1 || $is_user_header_top_right_select == 1))
                    {
                        $ret = 'static/plugins/css/multilingual/index/style.css';
                    }
                    break;

                // 后台
                // 后台css
                case 'plugins_admin_css' :
                    if($this->module_name == 'admin' && $is_admin_select == 1)
                    {
                        $ret = 'static/plugins/css/multilingual/index/style.css';
                    }
                    break;
                // 后台切换
                case 'plugins_admin_view_common_bottom' :
                    if($this->module_name == 'admin' && $is_admin_select == 1 && in_array($this->mca, ['adminindexindex', 'adminadminlogininfo']))
                    {
                        $ret = $this->AdminViewPageBottomContent();
                    }
                    break;

                // 公共翻译js
                case 'plugins_common_page_bottom' :
                case 'plugins_admin_common_page_bottom' :
                    if(($this->module_name == 'admin' && $is_admin_select == 1) || ($this->module_name == 'index' && ($is_user_quick_select == 1 || $is_user_header_top_right_select == 1)))
                    {
                        $ret = $this->MultilingualViewPageBottomContent();
                    }
                    break;

                // pc端顶部左侧菜单
                case 'plugins_service_header_navigation_top_right_handle' :
                    if($is_user_header_top_right_select == 1)
                    {
                        $this->HeaderNavigationTopRightHandle($params);
                    }
                    break;

                // 加载层、前后端
                case 'plugins_view_common_top' :
                case 'plugins_admin_view_common_top' :
                    if(isset($this->plugins_config['is_loading']) && $this->plugins_config['is_loading'] == 1 && (($this->module_name == 'admin' && $is_admin_select == 1) || ($this->module_name == 'index' && ($is_user_quick_select == 1 || $is_user_header_top_right_select == 1))))
                    {
                        $ret = $this->ViewCommonTopLoadingContent($params);
                    }
                    break;

                // web端快捷导航切换处理视图
                case 'plugins_view_common_bottom' :
                    // 排除用户登录窗口、不展示切换按钮
                    if($this->module_name == 'index' && $is_user_quick_select == 1)
                    {
                        $ret = $this->IndexViewPageBottomContent($params);
                    }
                    break;

                // 商品搜索条件处理
                case 'plugins_service_search_goods_list_where' :
                    if(!empty($this->plugins_config['is_search_auto_switch']) && $this->plugins_config['is_search_auto_switch'] == 1 && $this->module_name.$this->controller_name.$this->action_name == 'indexsearchgoodslist')
                    {
                        $this->GoodsSearchWhereHandle($params);
                    }
                    break;
            }
            return $ret;
        }
    }

    /**
     * 后台语言切换操作
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-12-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function AdminViewPageBottomContent($params = [])
    {
        // 获取语言列表
        $data = BaseService::UserMultilingualData($this->multilingual_value);
        if(!empty($data['data']))
        {
            // 当前url地址
            $my_url = $this->CurrentViewUrl();

            // 语言选择
            $select = [];
            foreach($data['data'] as $v)
            {
                // 选择列表
                $select[] = [
                    'name'  => $v['name'].'-'.$v['code'],
                    'url'   => $my_url.$v['id'],
                ];
            }
            MyViewAssign('language_select', $select);
        }
        return MyView('../../../plugins/view/multilingual/public/admin_select');
    }

    /**
     * 商品搜索条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsSearchWhereHandle($params = [])
    {
        if(!empty($params['params']) && !empty($params['params']['wd']))
        {
            // 获取语言列表
            $data = BaseService::UserMultilingualData($this->multilingual_value);

            // 默认语言
            $multilingual_default_code = (empty($data['default']) || empty($data['default']['code'])) ? 'zh' : $data['default']['code'];
            if($multilingual_default_code != 'zh')
            {
                // 翻译
                $p = [
                    'from'  => $multilingual_default_code,
                    'to'    => 'zh',
                    'q'     => $params['params']['wd'],
                ];
                $ret = TranslateService::Run($p);
                if(!empty($ret['data']) && !empty($ret['data']['trans_result']) && !empty($ret['data']['trans_result'][0]) && !empty($ret['data']['trans_result'][0]['dst']))
                {
                    $params['params']['wd'] = $ret['data']['trans_result'][0]['dst'];
                }
            }
        }
    }

    /**
     * 快捷导航页面选择
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function IndexViewPageBottomContent($params = [])
    {
        // 获取语言列表
        $data = BaseService::UserMultilingualData($this->multilingual_value);
        if(!empty($data['data']))
        {
            // 当前url地址
            $my_url = $this->CurrentViewUrl();

            // 语言选择
            $select = [];
            foreach($data['data'] as $v)
            {
                // 选择列表
                $select[] = [
                    'name'  => $v['name'].'-'.$v['code'],
                    'url'   => $my_url.$v['id'],
                ];
            }
            MyViewAssign('language_select', $select);
        }
        return MyView('../../../plugins/view/multilingual/public/index_select');
    }

    /**
     * 顶部加载层
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function ViewCommonTopLoadingContent($params = [])
    {
        return MyView('../../../plugins/view/multilingual/public/loading');
    }

    /**
     * 翻译js
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function MultilingualViewPageBottomContent($params = [])
    {
        // 获取语言列表
        $data = BaseService::UserMultilingualData($this->multilingual_value);

        // 默认语言
        $multilingual_default_code = (empty($data['default']) || empty($data['default']['code'])) ? 'zh' : $data['default']['code'];

        // 实时监听时间
        $real_time_monitoring = empty($this->plugins_config['real_time_monitoring']) ? 0 : intval($this->plugins_config['real_time_monitoring'])*1000;

        // 停留页面翻译接口最多请求次数
        $stop_page_request_number = empty($this->plugins_config['stop_page_request_number']) ? 0 : intval($this->plugins_config['stop_page_request_number']);

        // 接口地址
        $request_url = $this->module_name == 'admin' ? PluginsAdminUrl("multilingual", "index", "fanyi") : PluginsHomeUrl("multilingual", "index", "fanyi");

        MyViewAssign('request_url', $request_url);
        MyViewAssign('stop_page_request_number', $stop_page_request_number);
        MyViewAssign('real_time_monitoring', $real_time_monitoring);
        MyViewAssign('multilingual_default', $data['default']);
        MyViewAssign('multilingual_default_code', $multilingual_default_code);
        return MyView('../../../plugins/view/multilingual/public/content');
    }

    /**
     * web端顶部右侧小导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function HeaderNavigationTopRightHandle($params = [])
    {
        // 获取语言列表
        $data = BaseService::UserMultilingualData($this->multilingual_value);
        if(!empty($data['default']) && !empty($data['data']))
        {
            // 当前url地址
            $my_url = $this->CurrentViewUrl();

            // 语言选择
            $select = [];
            foreach($data['data'] as $v)
            {
                // 选择列表
                $select[] = [
                    'name'  => $v['name'].'-'.$v['code'],
                    'url'   => $my_url.$v['id'],
                ];
            }

            // 指定语言不为空则存储选择的语言值
            if(!empty($data['default']) && !empty($data['default']['id']))
            {
                BaseService::SetUserMultilingualCacheValue($data['default']['id']);
            }

            // 加入导航尾部
            $nav = [
                'name'      => '语言['.$data['default']['name'].']',
                'is_login'  => 0,
                'badge'     => null,
                'icon'      => 'am-icon-language',
                'url'       => '',
                'items'     => $select,
            ];
            array_push($params['data'], $nav);
        }
    }

    /**
     * 当前页面url地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-11
     * @desc    description
     */
    public function CurrentViewUrl()
    {
        // 去除当前存在的参数
        $url = __MY_VIEW_URL__;
        if(stripos($url, BaseService::$request_multilingual_key) !== false)
        {
            $arr1 = explode('?', $url);
            if(!empty($arr1[1]))
            {
                $arr2 = explode('&', $arr1[1]);
                foreach($arr2 as $k=>$v)
                {
                    if(stripos($v, BaseService::$request_multilingual_key) !== false)
                    {
                        unset($arr2[$k]);
                    }
                }
                $url = '?'.implode('&', $arr2);
            }
        }

        // 当前页面地址
        $join = (stripos($url, '?') === false) ? '?' : '&';
        return $url.$join.BaseService::$request_multilingual_key.'=';
    }
}
?>