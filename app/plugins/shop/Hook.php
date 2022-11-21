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
namespace app\plugins\shop;

use think\facade\Db;
use app\service\UserService;
use app\service\ResourcesService;
use app\service\UserAddressService;
use app\service\PluginsService;
use app\plugins\shop\service\BaseService;
use app\plugins\shop\service\ProfitService;
use app\plugins\shop\service\NoticeService;
use app\plugins\shop\service\ShopService;
use app\plugins\shop\service\ShopFavorService;
use app\plugins\shop\service\MainGoodsSyncService;
use app\plugins\shop\service\ShopOrderService;

/**
 * 多商户 - 钩子入口
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

    // 插件、控制器、方法
    private $pluginsname;
    private $pluginscontrol;
    private $pluginsaction;
    private $pca;

    // 配置信息
    private $base_config;

    // 导航名称
    // 卖家
    private $nav_seller_title = '商家中心';
    private $is_seller_user_menu = 0;

    // 店铺收藏
    private $nav_favor_title = '店铺收藏';
    private $is_shop_favor_menu = 0;

    // 店铺自提地址信息缓存key
    private $cache_shop_ext_ads_key = 'cache_shop_extraction_address_key';

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

            $this->pluginsname = strtolower(MyInput('pluginsname'));
            $this->pluginscontrol = strtolower(MyInput('pluginscontrol', 'index'));
            $this->pluginsaction = strtolower(MyInput('pluginsaction', 'index'));
            $this->pca = $this->pluginsname.$this->pluginscontrol.$this->pluginsaction;

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->base_config = empty($config['data']) ? [] : $config['data'];

            // 用户中心导航入口
            // 商家中心
            $this->is_seller_user_menu = isset($this->base_config['is_seller_user_menu']) ? intval($this->base_config['is_seller_user_menu']) : 0;
            // 店铺收藏
            $this->is_shop_favor_menu = isset($this->base_config['is_shop_favor_menu']) ? intval($this->base_config['is_shop_favor_menu']) : 0;

            // 商品详情展示店铺信息
            $is_goods_detail_shop_show = isset($this->base_config['is_goods_detail_shop_show']) ? intval($this->base_config['is_goods_detail_shop_show']) : 0;

            // 是否商家中心
            $is_shop_center = ($this->module_name == 'index' && $this->pluginsname == 'shop') ? 1 : 0;
            if($is_shop_center == 1)
            {
                // 多商户公共页面
                if(in_array($this->pluginscontrol, ['search', 'index', 'shopfavor']))
                {
                    $is_shop_center = 0;
                }
                // 多商户公共页面-页面设计详情
                if(in_array($this->pluginscontrol.$this->pluginsaction, ['designdetail']))
                {
                    $is_shop_center = 0;
                }
            }

            // 系统主搜索是否支持店铺
            $is_main_search_shop = isset($this->base_config['is_main_search_shop']) ? intval($this->base_config['is_main_search_shop']) : 0;
   
            // 走钩子
            $ret = '';
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                case 'plugins_admin_css' :
                    $ret = [
                        'static/plugins/css/shop/public/iconfont.css',
                    ];
                    switch($this->mca)
                    {
                        // 商品详情页面
                        case 'indexgoodsindex' :
                            $shop = BaseService::IsGoodsShopData(input());
                            if($is_goods_detail_shop_show == 1 && !empty($shop))
                            {
                                $ret[] = 'static/plugins/css/shop/index/public/goods_detail.css';

                                // 是否开启客服
                                $chat = BaseService::ChatUrl($this->base_config, $shop['user_id']);
                                if(!empty($chat))
                                {
                                    $ret[] = 'static/plugins/css/shop/index/public/goods_detail_chat_hide.css';
                                }
                            }
                            break;
                    }

                    // 用户端
                    if($params['hook_name'] == 'plugins_css')
                    {
                        // 首页店铺推荐
                        $is_home_shop = $this->mca == 'indexindexindex' && isset($this->base_config['is_home_main']) && $this->base_config['is_home_main'] == 1;
                        if($is_home_shop)
                        {
                            $ret[] = 'static/plugins/css/shop/index/public/style.css';
                        }

                        // 商家中心css
                        if($is_shop_center == 1)
                        {
                            $ret[] = 'static/plugins/css/shop/index/public/shop_admin.css';
                        }

                        // 系统搜索支持店铺
                        if($is_main_search_shop)
                        {
                            $ret[] = 'static/plugins/css/shop/index/public/search_shop.css';
                        }
                    }
                    break;

                // 公共js
                case 'plugins_js' :
                    $ret = [];
                    switch($this->mca)
                    {
                        // 商品详情
                        case 'indexgoodsindex' :
                            $shop = BaseService::IsGoodsShopData(input());
                            if($is_goods_detail_shop_show == 1 && !empty($shop))
                            {
                                $ret[] = 'static/plugins/js/shop/index/common.js';
                            }
                            break;
                    }

                    // 加载公共的js方法
                    // 系统搜索支持店铺
                    $is_shop_favor = isset($this->base_config['is_home_main']) && $this->base_config['is_home_main'] == 1 && ($this->mca == 'indexindexindex' || in_array($this->pluginsname.$this->pluginscontrol, ['shopindex', 'shopsearch', 'shopdesign']));
                    if($is_shop_favor || $is_main_search_shop)
                    {
                        $ret[] = 'static/plugins/js/shop/index/public/style.js';
                    }
                    break;

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

                // 主商品保存前处理
                case 'plugins_service_goods_save_handle' :
                    $ret = $this->GoodsSaveBeginHandle($params);
                    break;

                // 商品编辑器位置
                case 'plugins_service_editor_path_type_admin_goods_saveinfo' :
                    $this->EditorPathTypeAdminGoodsSaveinfoHandle($params);
                    break;

                // 商品详情店铺信息-右侧
                case 'plugins_view_goods_detail_right_content_bottom' :
                    if($is_goods_detail_shop_show == 1)
                    {
                        $ret = $this->GoodsDetailRightContentHandle($params);
                    }
                    break;

                // 商品详情店铺信息-底部
                case 'plugins_view_goods_detail_base_bottom' :
                    if($is_goods_detail_shop_show == 1)
                    {
                        $ret = $this->GoodsDetailBaseBottomContentHandle($params);
                    }
                    break;

                // 商品详情店铺信息-小导航
                case 'plugins_view_goods_detail_base_buy_nav_min_inside_begin' :
                    if($is_goods_detail_shop_show == 1)
                    {
                        $ret = $this->GoodsDetailBuyNavMinContentHandle($params);
                    }
                    break;

                // 仓库列表信息处理
                case 'plugins_service_warehouse_handle_end' :
                    $ret = $this->WarehouseListHandle($params);
                    break;

                // 生成订单数据处理
                case 'plugins_service_buy_handle' :
                    $ret = $this->BuyGoodsHandle($params);
                    break;

                // 订单添加前处理
                case 'plugins_service_buy_order_insert_begin' :
                    $ret = $this->BuyOrderInsertBeginHandle($params);
                    break;

                // 订单添加成功处理
                case 'plugins_service_buy_order_insert_end' :
                    $ret = ProfitService::OrderProfitInsert($params, $this->base_config);
                    break;

                // 订单状态
                case 'plugins_service_order_status_change_history_success_handle' :
                    if(!empty($params['data']) && !empty($params['order_id']) && isset($params['data']['new_status']))
                    {
                        switch($params['data']['new_status'])
                        {
                            // 支付订单状态更新
                            case 2 :
                            case 4 :
                                $ret = ProfitService::OrderProfitValid($params['order_id'], $params['data']);
                                if($ret['code'] != 0)
                                {
                                    return $ret;
                                }

                                // 订单完成商户确认接收订单冻结的钱退回
                                if($params['data']['new_status'] == 4)
                                {
                                    $ret = ShopOrderService::OrderReceiveGoBack($this->base_config, $params['order_id'], $params['data']);
                                    if($ret['code'] != 0)
                                    {
                                        return $ret;
                                    }
                                }

                                // 发送通知
                                if($params['data']['new_status'] == 2)
                                {
                                    NoticeService::Send($params['order_id']);
                                }
                                break;

                            // 订单取消/关闭
                            case 5 :
                            case 6 :
                                $ret = ProfitService::OrderProfitClose($params['order_id'], $params['data']);
                                break;
                        }
                    }
                    break;

                // 订单售后审核成功
                case 'plugins_service_order_aftersale_audit_handle_end' :
                    $ret = ProfitService::OrderChange($params);
                    break;

                // 管理后台商品编辑页面
                case 'plugins_view_admin_goods_save' :
                    $ret = $this->AdminGoodsEditViewHandle($params);
                    break;

                // 商品处理结束
                case 'plugins_service_goods_handle_end' :
                    if(in_array($this->mca, ['admingoodsindex', 'admingoodsdetail']))
                    {
                        $this->GoodsDataHandleEnd($params);
                    }
                    break;

                // 后台商品动态列表展示店铺信息和结算信息
                case 'plugins_module_form_admin_goods_index' :
                case 'plugins_module_form_admin_goods_detail' :
                    if(in_array($this->mca, ['admingoodsindex', 'admingoodsdetail']))
                    {
                        $this->AdminFormGoodsHandle($params);
                    }
                    break;

                // 商品详情页面导航购买按钮处理
                case 'plugins_service_goods_buy_nav_button_handle' :
                    $this->GoodsDetailBuyNavButtonContent($params);
                    break;

                // 页面设计搜索商品
                case 'plugins_layout_service_search_goods_begin' :
                    if(!empty($params['params']) && !empty($params['params']['category_field']) && $params['params']['category_field'] == 'g.shop_category_id')
                    {
                        $this->LayoutSearchGoodsList($params);
                    }
                    break;

                // 页面设计商品数据
                case 'plugins_layout_service_goods_data_begin' :
                    if($this->mca == 'indexpluginsindex')
                    {
                        $this->LayoutGoodsDataList($params);
                    }
                    break;

                // 商品接口数据
                case 'plugins_service_base_data_return_api_goods_detail' :
                    if($is_goods_detail_shop_show == 1)
                    {
                        $this->GoodsResultHandle($params);
                    }
                    break;

                // api自提地址选择页面
                case 'plugins_service_site_extraction_address_list' :
                    $this->SiteExtractionAddressChoice($params);
                    break;

                // 手机端首页右上角icon
                case 'plugins_service_app_home_right_icon_list' :
                    if(isset($this->base_config['is_app_home_right_icon']) && $this->base_config['is_app_home_right_icon'] == 1)
                    {
                        $this->AppHomeRightIcon($params);
                    }
                    break;

                // web端左上角展示商家入驻入口
                case 'plugins_view_header_navigation_top_left_end' :
                    if(!empty($this->base_config['nav_top_left_name']))
                    {
                        $user = UserService::LoginUserInfo();
                        if(empty($user))
                        {
                            $url = empty($this->base_config['nav_top_left_name_url']) ? PluginsHomeUrl('shop', 'user', 'index') : $this->base_config['nav_top_left_name_url'];
                            $style = empty($this->base_config['nav_top_left_name_color']) ? '' : 'style="color:'.$this->base_config['nav_top_left_name_color'].';"';
                            $ret = '<a href="'.$url.'" '.$style.' class="am-margin-left-sm">'.$this->base_config['nav_top_left_name'].'</a>';
                        }
                    }
                    break;

                // 系统类型处理
                case 'plugins_service_system_system_type_value' :
                    if($is_shop_center == 1)
                    {
                        $ret = $this->SystemTypeHandle($params);
                    }
                    break;

                // 首页接口数据
                case 'plugins_service_base_data_return_api_index_index' :
                    $this->IndexResultHandle($params);
                    break;

                // 楼层数据上面
                case 'plugins_view_home_floor_top' :
                    $ret = $this->HomeFloorTopAdv($params);
                    break;

                // 搜索框左侧
                case 'plugins_view_common_search_inside_left' :
                case 'plugins_view_common_header_nav_search_inside' :
                    if($is_main_search_shop)
                    {
                        $ret = $this->CommonSearchInsideLeft($params);
                    }
                    break;

                // 商品上下架、是否扣除库存状态操作同步
                case 'plugins_service_goods_field_status_update' :
                    if(isset($this->base_config['is_edit_goods_base_sync_shop_goods']) && $this->base_config['is_edit_goods_base_sync_shop_goods'] == 1)
                    {
                        $ret = MainGoodsSyncService::GoodsStatusUpdateSync($params);
                    }
                    break;

                // 商品保存成功处理、主商品保存同步
                case 'plugins_service_goods_save_end' :
                    if(isset($this->base_config['is_edit_goods_base_sync_shop_goods']) && $this->base_config['is_edit_goods_base_sync_shop_goods'] == 1 && !empty($params['goods_id']))
                    {
                        $ret = MainGoodsSyncService::GoodsSaveSync($params['goods_id'], $this->base_config);
                    }
                    break;

                // 商品库存更新
                case 'plugins_service_warehouse_goods_inventory_sync' :
                    if(isset($this->base_config['is_edit_goods_inventory_sync_shop_goods']) && $this->base_config['is_edit_goods_inventory_sync_shop_goods'] == 1 && !empty($params['goods_id']))
                    {
                        $ret = MainGoodsSyncService::GoodsInventorySync($params['goods_id'], $this->base_config);
                    }
                    break;

                // 商品删除
                case 'plugins_service_goods_delete' :
                    if(isset($this->base_config['is_goods_delete_sync_shop_goods']) && $this->base_config['is_goods_delete_sync_shop_goods'] == 1 && !empty($params['goods_ids']))
                    {
                        $ret = MainGoodsSyncService::GoodsDeleteSync($params);
                    }
                    break;

                // 库存扣减、库存回滚（这里直接使用当前商品库存数据复制到相关商品即可）
                case 'plugins_service_warehouse_goods_inventory_deduct' :
                case 'plugins_service_warehouse_goods_inventory_rollback' :
                    if(isset($this->base_config['is_shop_main_goods_shared_inventory']) && $this->base_config['is_shop_main_goods_shared_inventory'] == 1 && !empty($params['goods_id']))
                    {
                        $ret = MainGoodsSyncService::OrderGoodsInventorySync($params);
                    }
                    break;

                // 商品搜索条件前处理
                // 商品分类列表条件前处理
                // 商品列表条件前处理
                // 商品搜索列表读取前钩子
                case 'plugins_service_search_goods_list_begin' :
                case 'plugins_service_category_goods_list_begin' :
                case 'plugins_service_goods_list_begin' :
                case 'plugins_service_goods_search_list_begin' :
                    if(isset($this->base_config['is_shop_main_goods_add_hide']) && $this->base_config['is_shop_main_goods_add_hide'] == 1)
                    {
                        $this->GoodsQueryWhereBegin($params);
                    }
                    break;

                // 后台管理
                case 'plugins_module_form_admin_goods_index_end' :
                    if(isset($this->base_config['is_shop_main_goods_add_hide']) && $this->base_config['is_shop_main_goods_add_hide'] == 1 && in_array($this->mca, ['admingoodsindex']))
                    {
                        $params['data']['where'][] = ['shop_id', '=', 0];
                    }
                    break;

                // 后台订单动态列表邀请用户
                case 'plugins_module_form_admin_order_index' :
                case 'plugins_module_form_admin_order_detail' :
                    if(in_array($this->module_name.$this->controller_name.$this->action_name, ['adminorderindex', 'adminorderdetail']) && isset($this->base_config['is_shop_order_confirm']) && $this->base_config['is_shop_order_confirm'] == 1)
                    {
                        $ret = $this->AdminFormOrderHandle($params);
                    }
                    break;

                // 订单数据处理后
                case 'plugins_service_order_handle_end' :
                    if((in_array($this->module_name.$this->controller_name.$this->action_name, ['adminorderindex', 'adminorderdetail']) || in_array($this->pca, ['shoporderindex', 'shoporderdetail'])) && isset($this->base_config['is_shop_order_confirm']) && $this->base_config['is_shop_order_confirm'] == 1)
                    {
                        $this->OrderDataHandleEnd($params);
                    }
                    break;
            }
            return $ret;
        }
    }

    /**
     * 订单数据处理结束
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function OrderDataHandleEnd($params = [])
    {
        // 必须是多商户订单
        if(!empty($params['order']) && !empty($params['order']['shop_id']))
        {
            // 审核信息
            $data = ShopOrderService::OrderReceiveDataList($params['order']['id']);
            if(!empty($data) && is_array($data) && array_key_exists($params['order']['id'], $data))
            {
                $params['order']['shop_receive_info'] = $data[$params['order']['id']];
            }

            // 未审核成功则不可发货、仅限制后台管理
            if($this->module_name == 'admin' && isset($this->base_config['is_shop_order_no_confirm_not_delivery']) && $this->base_config['is_shop_order_no_confirm_not_delivery'] == 1)
            {
                if(empty($params['order']['shop_receive_info']) || $params['order']['shop_receive_info']['status'] != 1)
                {
                    $params['order']['operate_data']['is_delivery'] = 0;
                }
            }
        }
    }

    /**
     * 后台订单动态列表邀请用户
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function AdminFormOrderHandle($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['form']))
        {
            array_splice($params['data']['form'], 2, 0, [[
                'label'         => '商户审核信息',
                'view_type'     => 'module',
                'view_key'      => '../../../plugins/view/shop/public/order_receive_module',
                'grid_size'     => 'sm',
                'search_config' => [
                    'form_type'             => 'select',
                    'form_name'             => 'id',
                    'where_type_custom'     => 'in',
                    'where_value_custom'    => 'ModuleWhereValueReceiveInfo',
                    'where_object_custom'   => $this,
                    'data'                  => BaseService::$plugins_shop_order_confirm_status_list,
                    'data_key'              => 'value',
                    'data_name'             => 'name',
                    'is_multiple'           => 1,
                ],
            ]]);
        }
    }

    /**
     * 动态数据订单列表条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-26
     * @desc    description
     * @param   [string]          $value    [条件值]
     * @param   [array]           $params   [输入参数]
     */
    public function ModuleWhereValueReceiveInfo($value, $params = [])
    {
        if(!empty($value))
        {
            // 获取id
            $ids = Db::name('PluginsShopOrderConfirm')->where(['status'=>$value])->column('order_id');
            // 避免空条件造成无效的错觉
            return empty($ids) ? [0] : $ids;
        }
        return $value;
    }

    /**
     * 商品查询条件前处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsQueryWhereBegin($params = [])
    {
        // 仅首页、搜索页、商品详情（不排除当前商品访问）限制不展示店铺商品
        if(in_array($this->mca, ['indexindexindex', 'indexsearchindex', 'indexsearchgoodslist', 'apiindexindex', 'apisearchdatalist']) || (in_array($this->mca, ['indexgoodsindex', 'apigoodsindex']) && isset($params['params']) && isset($params['params']['where']) && isset($params['params']['where'][0]) && isset($params['params']['where'][0][2]) && $params['params']['where'][0][2] != MyInput('id')))
        {
            // 字段名称
            $field_first = in_array($params['hook_name'], ['plugins_service_search_goods_list_begin', 'plugins_service_category_goods_list_begin']) ? 'g.' : '';
            // 条件字段名称
            $where_field = in_array($params['hook_name'], ['plugins_service_goods_search_list_begin', 'plugins_service_search_goods_list_begin']) ? 'where_base' : 'where';

            // 商品id
            $params[$where_field][] = [$field_first.'shop_id', '=', 0];
        }
    }

    /**
     * 搜索框左侧
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function CommonSearchInsideLeft($params = [])
    {
        // 是否当前多商户首页
        $is_shop_active = $this->pluginsname.$this->pluginscontrol == 'shopindex' ? 1 : 0;
        MyViewAssign('is_shop_active', $is_shop_active);

        // 搜索可选数据
        $search_choice_list = [
            [
                'name'  => '商品',
                'url'   => MyUrl('index/search/index'),
            ]
        ];
        $shop_search = [
            'name'  => '店铺',
            'url'   => PluginsHomeUrl('shop', 'index', 'index'),
        ];
        if($is_shop_active == 1)
        {
            array_unshift($search_choice_list, $shop_search);
        } else {
            $search_choice_list[] = $shop_search;
        }
        MyViewAssign('search_choice_list', $search_choice_list);
        return MyView('../../../plugins/view/shop/index/public/search_shop');
    }

    /**
     * 首页楼层顶部店铺数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function HomeFloorTopAdv($params = [])
    {
        if(isset($this->base_config['is_home_main']) && $this->base_config['is_home_main'] == 1)
        {
            // 获取首页店铺数据
            $data = ShopService::HomeShopList($this->base_config);
            MyViewAssign('plugins_shop_data', $data);

            // 用户收藏的店铺
            if(!empty($data))
            {
                $user = UserService::LoginUserInfo();
                $shop_favor_user = empty($user) ? [] : ShopFavorService::UserShopFavorData($user['id']);
                MyViewAssign('shop_favor_user', $shop_favor_user);
            }

            MyViewAssign('base_config', $this->base_config);
            return MyView('../../../plugins/view/shop/index/public/home');
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
        if(isset($this->base_config['is_home_main']) && $this->base_config['is_home_main'] == 1)
        {
            // 获取首页店铺数据
            $data = ShopService::HomeShopList($this->base_config);
            if(!empty($data))
            {
                $params['data']['plugins_shop_data'] = [
                    'base'  => PluginsService::ConfigPrivateFieldsHandle($this->base_config, BaseService::$base_config_private_field),
                    'data'  => $data,
                ];
            }
        }
    }

    /**
     * 系统类型处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function SystemTypeHandle($params = [])
    {
        $user = UserService::LoginUserInfo();
        if(!empty($user))
        {
            $shop = ShopService::UserShopInfo($user['id'], 'user_id', 'id,system_type');
            if(!empty($shop) && !empty($shop['system_type']))
            {
                $params['value'] = $shop['system_type'];
            }
        }
    }

    /**
     * 手机端首页右上角icon
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-12-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function AppHomeRightIcon($params = [])
    {
        if(isset($params['data']) && is_array($params['data']))
        {
            array_unshift($params['data'], [
                'name'  => '所有店铺',
                'icon'  => 'shop',
                'url'   => '/pages/plugins/shop/index/index',
            ]);
        }
    }

    /**
     * 自提地址列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-08-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function SiteExtractionAddressChoice($params = [])
    {
        $user = UserService::LoginUserInfo();
        if(!empty($user))
        {
            $data = MyCache($this->cache_shop_ext_ads_key.$user['id']);
            if(!empty($data))
            {
                $params['data'] = $data;
            }
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
        if(!empty($params['data']) && !empty($params['data']['goods']) && !empty($params['data']['goods']['shop_id']))
        {
            $shop = ShopService::UserShopInfo($params['data']['goods']['shop_id'], 'id', 'id,name,logo,logo_long,describe,auth_type,bond_status,bond_price,bond_expire_time');
            if(!empty($shop))
            {
                $shop['shop_icon'] = BaseService::ShopIcon();
                $params['data']['plugins_shop_data'] = $shop;
                $params['data']['nav_home_button_info'] = [
                    'text'  => '店铺',
                    'icon'  => $shop['shop_icon'],
                    'value' => '/pages/plugins/shop/detail/detail?id='.$shop['id'],
                ];
            }
        }
    }

    /**
     * 页面设计商品数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-21
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function LayoutGoodsDataList($params = [])
    {
        // 用户信息
        $user = UserService::LoginUserInfo();
        if(!empty($user) && !empty($params['data_type']))
        {
            // 用户店铺信息
            $shop = ShopService::UserShopInfo($user['id'], 'user_id', 'id');
            if(!empty($shop))
            {
                switch($params['data_type'])
                {
                    // 商品
                    case 'goods' :
                        $params['where'][] = ['shop_id', '=', $shop['id']];
                        $params['where'][] = ['shop_user_id', '=', $user['id']];
                        break;

                    // 分类
                    case 'category' :
                        $params['where'][] = ['g.shop_id', '=', $shop['id']];
                        $params['where'][] = ['g.shop_user_id', '=', $user['id']];
                        break;
                }
            }
        }
    }

    /**
     * 页面设计搜索商品
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-21
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function LayoutSearchGoodsList($params = [])
    {
        // 用户信息
        $user = UserService::LoginUserInfo();
        if(!empty($user))
        {
            // 用户店铺信息
            $shop = ShopService::UserShopInfo($user['id'], 'user_id', 'id');
            if(!empty($shop))
            {
                $params['where'][] = ['g.shop_id', '=', $shop['id']];
                $params['where'][] = ['g.shop_user_id', '=', $user['id']];
            }
        }
    }

    /**
     * 商品详情页面导航购买按钮处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsDetailBuyNavButtonContent($params = [])
    {
        if(!empty($params) && !empty($params['goods']) && !empty($params['goods']['shop_id']) && !empty($params['data']) && is_array($params['data']) && count($params['data']) == 1 && isset($params['data'][0]) && isset($params['data'][0]['type']) && $params['data'][0]['type'] == 'show')
        {
            // 获取店铺信息
            $shop = ShopService::UserShopInfo($params['goods']['shop_id'], 'id', 'service_tel');
            // 存在客服电话则覆盖咨询客服电话
            if(!empty($shop) && !empty($shop['service_tel']))
            {
                $params['data'][0]['value'] = $shop['service_tel'];
            }
        }
    }

    /**
     * 订单添加处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BuyGoodsHandle($params = [])
    {
        // 用户信息
        $user = UserService::LoginUserInfo();
        if(!empty($user))
        {
            // 取消小程序端独立选择自提地址的缓存
            MyCache($this->cache_shop_ext_ads_key.$user['id'], null);

            // 是否为自提模式
            if(!empty($params) && !empty($params['data']) && !empty($params['data']['goods']) && !empty($params['data']['base']) && ((isset($params['data']['base']['common_site_type']) && in_array($params['data']['base']['common_site_type'], [2,4])) || (isset($params['data']['base']['site_model']) && $params['data']['base']['site_model'] == 2)))
            {
                // 是否存在多个店铺订单
                if(count($params['data']['goods']) > 1)
                {
                    // 重置站点类型为快递、置空取货点地址
                    $params['data']['base']['site_model'] = 0;
                    $params['data']['base']['common_site_type'] = 0;
                    $params['data']['base']['extraction_address'] = [];
                    foreach($params['data']['goods'] as $k=>$v)
                    {
                        $params['data']['goods'][$k]['order_base']['site_model'] = 0;
                        $params['data']['goods'][$k]['order_base']['common_site_type'] = 0;
                        $params['data']['goods'][$k]['order_base']['extraction_address'] = [];
                    }
                } else {
                    // 当前店铺是否支持自提
                    // 获取仓库所属店铺id
                    $warehouse = BaseService::WarehouseInfo($params['data']['goods'][0]['id']);
                    if(!empty($warehouse) && !empty($warehouse['shop_id']))
                    {
                        // 店铺信息
                        // 开启自提则处理自提地址及选中
                        // 未开启则设置站点模式
                        $shop = ShopService::UserShopInfo($warehouse['shop_id'], 'id', '*', ['user_type'=>'shop']);
                        if(!empty($shop) && isset($shop['is_extraction']) && $shop['is_extraction'] == 1)
                        {
                            // 1. 站点类型为自提
                            // 2. 站点类型为销售+自提（用户选择了自提）
                            if(($params['data']['base']['common_site_type'] == 2) || ($params['data']['base']['common_site_type'] == 4 && isset($params['data']['base']['site_model']) && $params['data']['base']['site_model'] == 2))
                            {
                                $params['data']['base']['extraction_address'] = [
                                    [
                                        'id'            => 0,
                                        'logo'          => $shop['logo'],
                                        'alias'         => $shop['name'],
                                        'name'          => $shop['contacts_name'],
                                        'tel'           => $shop['contacts_tel'],
                                        'lng'           => $shop['lng'],
                                        'lat'           => $shop['lat'],
                                        'address'       => $shop['address'],
                                        'province'      => $shop['province'],
                                        'city'          => $shop['city'],
                                        'county'        => $shop['county'],
                                        'province_name' => $shop['province_name'],
                                        'city_name'     => $shop['city_name'],
                                        'county_name'   => $shop['county_name'],
                                    ]
                                ];

                                // 仅一个店铺的自提地址、默认选中
                                // 基础里面的地址
                                $params['data']['base']['address'] = $params['data']['base']['extraction_address'][0];
                                // 订单里面的地址
                                $params['data']['goods'][0]['order_base']['address'] = $params['data']['base']['extraction_address'][0];

                                // 设置缓存，小程序独立页面选择自提地址使用
                                MyCache($this->cache_shop_ext_ads_key.$user['id'], $params['data']['base']['extraction_address'], 3600);
                            }
                        } else {
                            // 重置站点类型为快递、置空取货点地址
                            $params['data']['base']['site_model'] = 0;
                            $params['data']['base']['common_site_type'] = 0;
                            $params['data']['base']['extraction_address'] = [];
                            $params['data']['goods'][0]['order_base']['site_model'] = 0;
                            $params['data']['goods'][0]['order_base']['common_site_type'] = 0;
                            $params['data']['goods'][0]['order_base']['extraction_address'] = [];
                        }
                    }
                }

                // 快递、设置默认地址（以免是自提类型导致快递地址为设置默认）
                if($params['data']['base']['site_model'] == 0 && empty($params['data']['base']['address']))
                {
                    $ads = UserAddressService::UserDefaultAddress(['user'=>$user]);
                    if(!empty($ads['data']))
                    {
                        $params['data']['base']['address'] = $ads['data'];
                        foreach($params['data']['goods'] as $k=>$v)
                        {
                            $params['data']['goods'][$k]['order_base']['address'] = $ads['data'];
                        }
                    }
                }
            }
        }
    }

    /**
     * 后台商品动态列表店铺信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function AdminFormGoodsHandle($params = [])
    {
        if(!empty($this->base_config))
        {
            // 店铺信息
            if(isset($this->base_config['is_admin_goods_shop_show']) && $this->base_config['is_admin_goods_shop_show'] == 1)
            {
                array_splice($params['data']['form'], 2, 0, [
                    [
                        'label'         => '店铺信息',
                        'view_type'     => 'module',
                        'view_key'      => '../../../plugins/view/shop/admin/public/shop_info',
                        'grid_size'     => 'sm',
                        'is_sort'       => 1,
                        'search_config' => [
                            'form_type'         => 'select',
                            'form_name'         => 'shop_id',
                            'where_type'        => 'in',
                            'data'              => BaseService::GoodsShopList(),
                            'data_key'          => 'id',
                            'data_name'         => 'name',
                            'is_multiple'       => 1,
                        ],
                    ]
                ]);
            }

            // 结算信息
            if(isset($this->base_config['is_admin_goods_settle_show']) && $this->base_config['is_admin_goods_settle_show'] == 1)
            {
                array_splice($params['data']['form'], -3, 0, BaseService::GoodsFormSettleData());
            }
        }
    }

    /**
     * 商品处理结束
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-02
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsDataHandleEnd($params = [])
    {
        if(!empty($params) && !empty($params['goods']) && !empty($params['goods']['shop_id']))
        {
            // 是否开启商品列表展示店铺信息
            if(isset($this->base_config['is_admin_goods_shop_show']) && $this->base_config['is_admin_goods_shop_show'] == 1)
            {
                // 获取店铺信息
                $shop = ShopService::UserShopInfo($params['goods']['shop_id'], 'id');
                if(!empty($shop))
                {
                    // 追加店铺信息进去、动态表格列表使用
                    $params['goods']['shop_info'] = [
                        'id'        => $shop['id'],
                        'name'      => $shop['name'],
                        'describe'  => $shop['describe'],
                        'logo'      => ResourcesService::AttachmentPathViewHandle($shop['logo']),
                        'logo_long' => ResourcesService::AttachmentPathViewHandle($shop['logo_long']),
                        'url'       => PluginsHomeUrl('shop', 'index', 'detail', ['id'=>$shop['id']]),
                    ];
                }
            }
        }
    }

    /**
     * 管理后台商品编辑页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-02
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function AdminGoodsEditViewHandle($params = [])
    {
        // 是否存在店铺id
        if(!empty($params) && !empty($params['data']) && !empty($params['data']['shop_id']))
        {
            // 获取店铺信息
            $shop = ShopService::UserShopInfo($params['data']['shop_id'], 'id', '*', ['user_type'=>'shop']);
            if(!empty($shop) && isset($shop['settle_type']) && $shop['settle_type'] == 1)
            {
                // 用户店铺数据
                MyViewAssign('user_shop', $shop);
                MyViewAssign('data', $params['data']);

                // 价格正则
                MyViewAssign('default_price_regex', MyConst('common_regex_price'));
                return MyView('../../../plugins/view/shop/admin/public/goods_edit_view_settle');
            }
        }
    }

    /**
     * 商品保存前处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsSaveBeginHandle($params = [])
    {
        if(!empty($params['params']))
        {
            // 操作来源
            $is_admin = ($this->mca == 'admingoodssave');
            $is_home = ($this->mca == 'indexpluginsindex');

            // 店铺信息
            if(!empty($params['params']['shop_id']))
            {
                $shop = ShopService::UserShopInfo($params['params']['shop_id'], 'id', '*', ['user_type'=>'shop']);
                if(!empty($shop) && $is_home)
                {
                    // 基础信息
                    $params['data']['shop_id'] = $params['params']['shop_id'];
                    $params['data']['shop_user_id'] = $params['params']['shop_user_id'];
                    $params['data']['shop_category_id'] = $params['params']['shop_category_id'];
                }
            }

            // 结算信息、用户店铺必须开启用户设置
            if($is_admin || ($is_home && !empty($shop) && isset($shop['is_user_settle']) && $shop['is_user_settle'] == 1))
            {
                $params['data']['shop_settle_price'] = isset($params['params']['shop_settle_price']) ? floatval($params['params']['shop_settle_price']) : 0;
                $params['data']['shop_settle_rate'] = isset($params['params']['shop_settle_rate']) ? min($params['params']['shop_settle_rate'], 100) : 0;
            }
        }
    }

    /**
     * 订单添加前处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BuyOrderInsertBeginHandle($params = [])
    {
        // 店铺id处理
        if(!empty($params['order']) && !empty($params['order']['warehouse_id']))
        {
            $res = BaseService::WarehouseInfo($params['order']['warehouse_id']);
            if(!empty($res['shop_id']) && !empty($res['shop_user_id']))
            {
                $params['order']['shop_id'] = $res['shop_id'];
                $params['order']['shop_user_id'] = $res['shop_user_id'];
            }
        }
    }

    /**
     * 仓库列表信息处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function WarehouseListHandle($params = [])
    {
        if(!empty($params['data']))
        {
            $res = BaseService::WarehouseShopInfoHandle(array_column($params['data'], 'id'));
            if(!empty($res))
            {
                foreach($params['data'] as &$v)
                {
                    if(array_key_exists($v['id'], $res) && !empty($res[$v['id']]))
                    {
                        $v['icon'] = $res[$v['id']]['icon'];
                        $v['url'] = $res[$v['id']]['url'];
                    }
                }
            }
        }
    }

    /**
     * 商品详情店铺信息-小导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsDetailBuyNavMinContentHandle($params = [])
    {
        // 存在商店id则读取数据
        if(!empty($params['goods']) && !empty($params['goods']['shop_id']))
        {
            $shop = ShopService::UserShopInfo($params['goods']['shop_id'], 'id', 'id,name,describe,logo,logo_long,user_id');
            if(!empty($shop))
            {
                // 在线客服地址
                $chat = BaseService::ChatUrl($this->base_config, $shop['user_id']);
                MyViewAssign('chat', $chat);
                if(!empty($chat))
                {
                    MyViewAssign('buy_nav_opt_number', 3);
                }

                // 店铺信息
                MyViewAssign('shop_info', $shop);
                return MyView('../../../plugins/view/shop/index/public/goods_detail_nav_min');
            }
        }
    }

    /**
     * 商品详情店铺信息-底部
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsDetailBaseBottomContentHandle($params = [])
    {
        // 存在商店id则读取数据
        if(!empty($params['goods']) && !empty($params['goods']['shop_id']))
        {
            $data = $this->UserShopBaseInfo($params['goods']['shop_id']);
            if(!empty($data))
            {
                MyViewAssign('shop_info', $data);
                return MyView('../../../plugins/view/shop/index/public/goods_detail_base_bottom');
            }
        }
    }

    /**
     * 商品详情店铺信息-右侧
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsDetailRightContentHandle($params = [])
    {
        // 存在商店id则读取数据
        if(!empty($params['goods']) && !empty($params['goods']['shop_id']))
        {
            $data = $this->UserShopBaseInfo($params['goods']['shop_id']);
            if(!empty($data))
            {
                // 在线客服地址
                $chat = BaseService::ChatUrl($this->base_config, 0, $params['goods']['shop_id']);
                MyViewAssign('chat', $chat);

                // 店铺信息
                MyViewAssign('shop_info', $data);
                MyViewAssign('base_config', $this->base_config);
                return MyView('../../../plugins/view/shop/index/public/goods_detail_right');
            }
        }
    }

    /**
     * 店铺基础信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-07
     * @desc    description
     * @param   [int]          $shop_id [店铺id]
     */
    public function UserShopBaseInfo($shop_id)
    {
        return ShopService::UserShopInfo($shop_id, 'id', 'id,name,describe,logo,logo_long,domain,service_weixin_qrcode,service_qq,service_tel,open_week,close_week,open_time,close_time,auth_type,bond_status,bond_price,bond_expire_time');
    }

    /**
     * 后台商品编辑编辑器位置
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-27
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function EditorPathTypeAdminGoodsSaveinfoHandle($params = [])
    {
        // 获取商品所属用户id
        $user_id = BaseService::GoodsShopUserID();
        if(!empty($user_id))
        {
            $params['value'] = 'plugins_shop-user_goods-'.$user_id;
        }
    }

    /**
     * 用户中心左侧菜单处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function UserCenterLeftMenuHandle($params = [])
    {
        // 店铺收藏
        if($this->is_shop_favor_menu == 1)
        {
            $params['data']['business']['item'][] = [
                'name'      => $this->nav_favor_title,
                'url'       => PluginsHomeUrl('shop', 'shopfavor', 'index'),
                'contains'  => ['shopshopfavorindex', 'shopshopfavordetail'],
                'is_show'   => 1,
                'icon'      => 'am-icon-star-o',
            ];
        }

        // 商家中心
        if($this->is_seller_user_menu == 1)
        {
            $params['data']['business']['item'][] = [
                'name'      => $this->nav_seller_title,
                'url'       => PluginsHomeUrl('shop', 'user', 'index'),
                'contains'  => ['shopuserindex', 'shopshopindex', 'shopshopsaveinfo', 'shopgoodscategoryindex', 'shopgoodsindex', 'shopgoodssaveinfo', 'shoporderindex', 'shopprofitindex', 'shopdesignindex', 'shopnavigationindex'],
                'is_show'   => 1,
                'icon'      => 'am-icon-get-pocket',
            ];
        }
    }

    /**
     * 顶部小导航右侧-我的商城
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function CommonTopNavRightMenuHandle($params = [])
    {
        // 店铺收藏
        if($this->is_shop_favor_menu == 1)
        {
            array_push($params['data'][2]['items'], [
                'name'  => $this->nav_favor_title,
                'url'   => PluginsHomeUrl('shop', 'shopfavor', 'index'),
            ]);
        }

        // 商家中心
        if($this->is_seller_user_menu == 1)
        {
            array_push($params['data'][1]['items'], [
                'name'  => $this->nav_seller_title,
                'url'   => PluginsHomeUrl('shop', 'user', 'index'),
            ]);
        }
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
        if(is_array($params['header']))
        {
            // 获取应用数据
            if(!empty($this->base_config['application_name']))
            {
                $nav = [
                    'id'                    => 0,
                    'pid'                   => 0,
                    'name'                  => $this->base_config['application_name'],
                    'url'                   => PluginsHomeUrl('shop', 'index', 'index'),
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