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
namespace app\plugins\label;

use think\facade\Db;
use app\service\UserService;
use app\plugins\label\service\BaseService;
use app\plugins\label\service\LabelService;

/**
 * 标签 - 钩子入口
 * @author   Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2019-04-11
 * @desc    description
 */
class Hook
{
    // 模块、控制器、方法
    private $module_name;
    private $controller_name;
    private $action_name;
    private $mca;

    // 配置信息
    private $plugins_config;

    /**
     * 应用响应入口
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
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
            $this->mca = $this->module_name.$this->controller_name.$this->action_name;

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            // 走钩子
            $ret = '';
            switch($params['hook_name'])
            {
                case 'plugins_css' :
                    if((isset($this->plugins_config['is_user_goods_detail_photo_label']) && $this->plugins_config['is_user_goods_detail_photo_label'] == 1) || (isset($this->plugins_config['is_user_goods_home_label']) && $this->plugins_config['is_user_goods_home_label'] == 1) || (isset($this->plugins_config['is_user_goods_search_label']) && $this->plugins_config['is_user_goods_search_label'] == 1))
                    {
                        $ret = 'static/plugins/css/label/index/public/goods_label.css';
                    }
                    break;

                // 用户数据列表处理结束
                case 'plugins_service_user_list_handle_end' :
                    if(in_array($this->mca, ['adminuserindex', 'adminuserdetail']))
                    {
                        $this->AdminUserListDataHandleEnd($params);
                    }
                    break;

                // 后台用户动态列表
                case 'plugins_module_form_admin_user_index' :
                case 'plugins_module_form_admin_user_detail' :
                    if(in_array($this->mca, ['adminuserindex', 'adminuserdetail']))
                    {
                        $this->AdminFormUserHandle($params);
                    }
                    break;

                // 商品数据列表处理结束
                case 'plugins_service_goods_list_handle_end' :
                    if(in_array($this->mca, ['admingoodsindex', 'admingoodsdetail', 'indexgoodsindex', 'apigoodsdetail']))
                    {
                        $this->GoodsListDataHandleEnd($params);
                    }
                    break;

                // 后台商品动态列表
                case 'plugins_module_form_admin_goods_index' :
                case 'plugins_module_form_admin_goods_detail' :
                    if(in_array($this->mca, ['admingoodsindex', 'admingoodsdetail']))
                    {
                        $this->AdminFormGoodsHandle($params);
                    }
                    break;

                // 后台用户管理关联标签操作
                case 'plugins_view_admin_user_list_operate' :
                    if(in_array($this->mca, ['adminuserindex']) && !empty($params['data']))
                    {
                        if(!empty($this->plugins_config) && isset($this->plugins_config['is_admin_user_label']) && $this->plugins_config['is_admin_user_label'] == 1)
                        {
                            $ret = $this->AdminLabelOperate($params['data']['id'], 'user');
                        }
                    }
                    break;

                // 后台商品管理关联标签操作
                case 'plugins_view_admin_goods_list_operate' :
                    if(in_array($this->mca, ['admingoodsindex']) && !empty($params['data']))
                    {
                        if(!empty($this->plugins_config) && isset($this->plugins_config['is_admin_goods_label']) && $this->plugins_config['is_admin_goods_label'] == 1)
                        {
                            $ret = $this->AdminLabelOperate($params['data']['id'], 'goods');
                        }
                    }
                    break;

                // 订单列表用户信息
                case 'plugins_view_admin_order_grid_user' :
                    if(in_array($this->mca, ['adminorderindex', 'adminorderdetail']) && !empty($params['data']) && !empty($params['data']['user_id']))
                    {
                        $ret = $this->AdminOrderListUserLabel($params['data']['user_id']);
                    }
                    break;

                // 商品标签
                case 'plugins_view_goods_detail_photo_within' :
                case 'plugins_view_home_goods_inside_bottom' :
                case 'plugins_view_search_goods_inside_bottom' :
                    if(in_array($this->mca, ['indexindexindex', 'indexgoodsindex', 'indexsearchindex', 'indexsearchgoodslist']) && !empty($params['goods']) && !empty($params['goods']['id']))
                    {
                        $ret = $this->HomeGoodsLabelHandle($params['goods']['id'], $this->mca);
                    }
                    break;

                // 首页接口数据
                case 'plugins_service_base_data_return_api_index_index' :
                    $ret = $this->IndexResultHandle($params);
                    break;

                // 首页接口数据
                case 'plugins_service_base_data_return_api_search_index' :
                    $ret = $this->SearchResultHandle($params);
                    break;

                // 商品接口数据
                case 'plugins_service_base_data_return_api_goods_detail' :
                    $ret = $this->GoodsResultHandle($params);
                    break;
            }
            return $ret;
        }
    }

    /**
     * 商品接口数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsResultHandle($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['goods']) && isset($this->plugins_config['is_app_user_goods_detail_photo_label']) && $this->plugins_config['is_app_user_goods_detail_photo_label'] == 1)
        {
            $data = LabelService::GoodsLabelData($params['data']['goods']['id']);
            if(!empty($data))
            {
                $params['data']['plugins_label_data'] = [
                    'base'  => $this->plugins_config,
                    'data'  => $data,
                ];
            }
        }
    }

    /**
     * 搜索接口数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function SearchResultHandle($params = [])
    {
        if(isset($this->plugins_config['is_user_goods_search_label']) && $this->plugins_config['is_user_goods_search_label'] == 1)
        {
            $data = LabelService::LabelListGoodsData();
            if(!empty($data))
            {
                $params['data']['plugins_label_data'] = [
                    'base'  => $this->plugins_config,
                    'data'  => $data,
                ];
            }
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
        if(isset($this->plugins_config['is_user_goods_home_label']) && $this->plugins_config['is_user_goods_home_label'] == 1)
        {
            $data = LabelService::LabelListGoodsData();
            if(!empty($data))
            {
                $params['data']['plugins_label_data'] = [
                    'base'  => $this->plugins_config,
                    'data'  => $data,
                ];
            }
        }
    }

    /**
     * 商品标签展示
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-08-09
     * @desc    description
     * @param   [int]           $goods_id   [商品id]
     * @param   [string]        $tag        [页面标记]
     */
    private function HomeGoodsLabelHandle($goods_id, $tag)
    {
        // 配置数据
        $is_success = false;
        if(!empty($this->plugins_config))
        {
            switch($tag)
            {
                // 商品详情页面
                case 'indexgoodsindex' :
                    if(isset($this->plugins_config['is_user_goods_detail_photo_label']) && $this->plugins_config['is_user_goods_detail_photo_label'] == 1)
                    {
                        $is_success = true;
                    }
                    break;

                // 首页显示标签
                case 'indexindexindex' :
                    if(isset($this->plugins_config['is_user_goods_home_label']) && $this->plugins_config['is_user_goods_home_label'] == 1)
                    {
                        $is_success = true;
                    }
                    break;

                // 搜索页显示标签
                case 'indexsearchindex' :
                case 'indexsearchgoodslist' :
                    if(isset($this->plugins_config['is_user_goods_search_label']) && $this->plugins_config['is_user_goods_search_label'] == 1)
                    {
                        $is_success = true;
                    }
                    break;
            }
        }
        if($is_success === true)
        {
            // 缓存获取
            $key = 'plugins_label_home_goods_label_'.$goods_id;
            $label = MyCache($key);
            if($label === null)
            {
                // 获取关联的标签
                $label = BaseService::LabelGoodsData($goods_id);
                MyCache($key, $label, 600);
            }

            // 是否存在标签数据
            if(!empty($label) && !empty($label[$goods_id]))
            {
                // 展示位置
                $user_goods_show_style = empty($this->plugins_config['user_goods_show_style']) ? 'top-left' : $this->plugins_config['user_goods_show_style'];

                // 是否开启 url
                $is_label_url = (isset($this->plugins_config['is_user_goods_label_url']) && $this->plugins_config['is_user_goods_label_url'] == 1) ? 1 : 0;

                // 是否图标展示
                $is_label_icon = (isset($this->plugins_config['is_user_goods_label_icon']) && $this->plugins_config['is_user_goods_label_icon'] == 1) ? 1 : 0;

                return MyView('../../../plugins/view/label/index/public/goods_label', [
                    'user_goods_show_style' => $user_goods_show_style,
                    'is_label_url'          => $is_label_url,
                    'is_label_icon'         => $is_label_icon,
                    'data'                  => $label[$goods_id],
                ]);
            }
        }
    }

    /**
     * 后台订单列表用户列展示用户标签信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    private function AdminOrderListUserLabel($user_id)
    {
        if(!empty($this->plugins_config) && isset($this->plugins_config['is_admin_order_user_label']) && $this->plugins_config['is_admin_order_user_label'] == 1 && !empty($user_id))
        {
            // 缓存获取
            $key = 'plugins_label_admin_order_user_label_'.$user_id;
            $label = MyCache($key);
            if($label === null)
            {
                // 获取关联的标签
                $label = BaseService::LabelUserData($user_id);
                MyCache($key, $label, 600);
            }

            // 是否存在标签数据
            if(!empty($label) && !empty($label[$user_id]))
            {
                return MyView('../../../plugins/view/label/admin/public/admin_label', [
                    'data'  => $label[$user_id],
                ]);
            }
        }
    }

    /**
     * 用户管理标签操作
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-08-06
     * @desc    description
     * @param   [int]           $vid    [数据id]
     * @param   [string]        $type   [类型（user 用户、 goods 商品）]
     */
    public function AdminLabelOperate($vid, $type)
    {
        $url = PluginsAdminUrl('label', 'label', 'join', ['vid'=>$vid, 'type'=>$type]);
        MyViewAssign('url', $url);
        return MyView('../../../plugins/view/label/admin/public/label_button');
    }

    /**
     * 后台商品动态列表标签信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function AdminFormGoodsHandle($params = [])
    {
        if(!empty($this->plugins_config) && isset($this->plugins_config['is_admin_goods_label']) && $this->plugins_config['is_admin_goods_label'] == 1)
        {
            array_splice($params['data']['form'], 3, 0, [
                [
                    'label'         => '标签',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/label/admin/public/label',
                    'search_config' => [
                        'form_type'             => 'select',
                        'form_name'             => 'id',
                        'where_type_custom'     => 'in',
                        'where_value_custom'    => 'ModuleWhereValueGoodsInfo',
                        'where_object_custom'   => $this,
                        'data'                  => BaseService::LabelListGoods(),
                        'data_key'              => 'id',
                        'data_name'             => 'name',
                        'is_multiple'           => 1,
                    ],
                ]
            ]);
        }
    }

    /**
     * 动态数据商品列表条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-26
     * @desc    description
     * @param   [string]          $value    [条件值]
     * @param   [array]           $params   [输入参数]
     */
    public function ModuleWhereValueGoodsInfo($value, $params = [])
    {
        if(!empty($value))
        {
            // 获取id
            $ids = Db::name('PluginsLabelGoods')->where(['label_id'=>$value])->column('goods_id');

            // 避免空条件造成无效的错觉
            return empty($ids) ? [0] : $ids;
        }
        return $value;
    }

    /**
     * 商品数据列表标签信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function GoodsListDataHandleEnd($params = [])
    {
        if(!empty($this->plugins_config) && !empty($params['data']))
        {
            // 是否需展示标签
            $is_admin_show = isset($this->plugins_config['is_admin_goods_label']) && $this->plugins_config['is_admin_goods_label'] == 1;
            $is_web_user_show = isset($this->plugins_config['is_user_goods_detail_title_label']) && $this->plugins_config['is_user_goods_detail_title_label'] == 1;
            $is_app_user_show = isset($this->plugins_config['is_app_user_goods_detail_title_label']) && $this->plugins_config['is_app_user_goods_detail_title_label'] == 1;
            $is_label_url = isset($this->plugins_config['is_user_goods_label_url']) && $this->plugins_config['is_user_goods_label_url'] == 1;

            if($is_admin_show || $is_web_user_show || $is_app_user_show)
            {
                // 获取商品关联的标签
                $ids = array_column($params['data'], 'id');
                $label = BaseService::LabelGoodsData($ids);
                if(!empty($label))
                {
                    foreach($params['data'] as &$v)
                    {
                        // 后台管理列表数据
                        if($is_admin_show && in_array($this->mca, ['admingoodsindex', 'admingoodsdetail']))
                        {
                            $v['label_info'] = array_key_exists($v['id'], $label) ? $label[$v['id']] : [];
                        }

                        // 前端商品icon数据
                        if((($is_web_user_show && $this->mca == 'indexgoodsindex') || ($is_app_user_show && $this->mca == 'apigoodsdetail')) && array_key_exists($v['id'], $label))
                        {
                            foreach($label[$v['id']] as $lv)
                            {
                                $color = empty($lv['bg_color']) ? (empty($lv['text_color']) ? '#666' : $lv['text_color']) : $lv['bg_color'];
                                $v['plugins_view_icon_data'][] = [
                                    'name'      => $lv['name'],
                                    'br_color'  => $color,
                                    'color'     => $color,
                                    'url'       => $is_label_url ? $lv['url'] : '',
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 后台用户动态列表标签信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function AdminFormUserHandle($params = [])
    {
        if(!empty($this->plugins_config) && isset($this->plugins_config['is_admin_user_label']) && $this->plugins_config['is_admin_user_label'] == 1)
        {
            array_splice($params['data']['form'], 3, 0, [
                [
                    'label'         => '标签',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/label/admin/public/label',
                    'search_config' => [
                        'form_type'             => 'select',
                        'form_name'             => 'id',
                        'where_type_custom'     => 'in',
                        'where_value_custom'    => 'ModuleWhereValueUserInfo',
                        'where_object_custom'   => $this,
                        'data'                  => BaseService::LabelListUser(),
                        'data_key'              => 'id',
                        'data_name'             => 'name',
                        'is_multiple'           => 1,
                    ],
                ]
            ]);
        }
    }

    /**
     * 动态数据用户列表条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-26
     * @desc    description
     * @param   [string]          $value    [条件值]
     * @param   [array]           $params   [输入参数]
     */
    public function ModuleWhereValueUserInfo($value, $params = [])
    {
        if(!empty($value))
        {
            // 获取id
            $ids = Db::name('PluginsLabelUser')->where(['label_id'=>$value])->column('user_id');

            // 避免空条件造成无效的错觉
            return empty($ids) ? [0] : $ids;
        }
        return $value;
    }

    /**
     * 后台用户数据列表标签信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function AdminUserListDataHandleEnd($params = [])
    {
        if(!empty($this->plugins_config) && isset($this->plugins_config['is_admin_user_label']) && $this->plugins_config['is_admin_user_label'] == 1 && !empty($params['data']))
        {
            // 获取用户关联的标签
            $ids = array_column($params['data'], 'id');
            $label = BaseService::LabelUserData($ids);
            if(!empty($label))
            {
                foreach($params['data'] as &$v)
                {
                    $v['label_info'] = array_key_exists($v['id'], $label) ? $label[$v['id']] : [];
                }
            }
        }
    }
}
?>