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
namespace app\plugins\intellectstools;

use app\service\PluginsService;
use app\service\GoodsService;
use app\service\BuyService;
use app\service\UserService;
use app\plugins\intellectstools\service\BaseService;
use app\plugins\intellectstools\service\OrderBaseService;
use app\plugins\intellectstools\service\OrderNoteService;
use app\plugins\intellectstools\service\GoodsNoteService;
use app\plugins\intellectstools\service\CommentsDataService;
use app\plugins\intellectstools\service\GoodsBeautifyService;
use app\plugins\intellectstools\service\GoodsInventoryService;

/**
 * 智能工具箱 - 钩子入口
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class Hook
{
    // 配置信息
    private $plugins_config;

    // 当前模块/控制器/方法
    private $module_name;
    private $controller_name;
    private $action_name;
    private $mca;

    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2021-05-07
     * @param    [array]                    $params [输入参数]
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

            // 页面标识
            $module_name = RequestModule();
            $pluginsname = strtolower(MyInput('pluginsname'));
            $pluginscontrol = strtolower(MyInput('pluginscontrol', 'index'));
            $pluginsaction = strtolower(MyInput('pluginsaction', 'index'));
            $nc = $pluginsname.$pluginscontrol;

            // 是否引入多商户样式
            $is_shop_style = $this->module_name == 'index' && in_array($nc, ['intellectstoolsorder', 'intellectstoolsgoods']);

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            // 商品想提示信息展示
            $is_goods_msg = !empty($this->plugins_config['goods_detail_content_top_tips_msg']) && in_array($this->mca, ['indexgoodsindex']);

            // 地址自动识别
            $is_user_address_discern = (isset($this->plugins_config['is_user_address_discern']) && $this->plugins_config['is_user_address_discern'] == 1 && $this->mca == 'indexuseraddresssaveinfo');

            // 开启商品分类批量移动
            $is_goods_top_category_move = isset($this->plugins_config['is_goods_top_category_move']) && $this->plugins_config['is_goods_top_category_move'] == 1;

            // 开启后台库存预警
            $is_admin_inventory_early_warning = isset($this->plugins_config['is_admin_inventory_early_warning']) && $this->plugins_config['is_admin_inventory_early_warning'] == 1;

            // 商品详情页面是否默认选中第一个有效规格
            $is_goods_detail_selected_first_spec = (isset($this->plugins_config['is_goods_detail_selected_first_spec']) && $this->plugins_config['is_goods_detail_selected_first_spec'] == 1 && $this->mca == 'indexgoodsindex');

            // 搜索右侧购物车入口
            $is_search_right_cart = (isset($this->plugins_config['is_search_right_cart']) && $this->plugins_config['is_search_right_cart'] == 1 && $this->mca != 'indexcartindex');
   
            // 走钩子
            $ret = '';
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    $ret = [];
                    // 商品详情提示信息、地址自动识别、搜索右侧购物车入口
                    if($is_goods_msg || $is_user_address_discern || $is_search_right_cart)
                    {
                        $ret[] = 'static/plugins/css/intellectstools/index/style.css';
                    }
                    // 样式图片处理
                    if(isset($this->plugins_config['is_images_height_fixed']) && $this->plugins_config['is_images_height_fixed'] == 1)
                    {
                        $ret[] = 'static/plugins/css/intellectstools/index/images_style.css';
                    }
                    // 引入多商户样式
                    if($is_shop_style)
                    {
                        $ret[] = 'static/plugins/css/shop/index/public/shop_admin.css';
                    }
                    break;

                // 公共js
                case 'plugins_js' :
                    $ret = [];
                    // 商品详情提示信息和地址自动识别
                    // 搜索右侧展示购物车
                    if($is_user_address_discern || $is_search_right_cart)
                    {
                        $ret[] = 'static/plugins/js/intellectstools/index/style.js';
                    }
                    // 商品详情页面是否默认选中第一个有效规格
                    if($is_goods_detail_selected_first_spec)
                    {
                        $ret[] = 'static/plugins/js/intellectstools/index/goods_spec_selected.js';
                    }
                    // 引入多商户js
                    if($is_shop_style)
                    {
                        $ret[] = 'static/plugins/js/shop/index/common.js';
                    }
                    break;

                // 后台公共css
                case 'plugins_admin_css' :
                    $ret = [];
                    if($this->mca == 'admingoodssaveinfo')
                    {
                        $ret[] = 'static/plugins/css/intellectstools/admin/comments.goodssaveinfo.css';
                    }
                    if($this->mca == 'admingoodsindex' && $is_goods_top_category_move)
                    {
                        $ret[] = 'static/plugins/css/intellectstools/admin/public/goods_category_move.css';
                    }
                    if($is_admin_inventory_early_warning && $this->mca == 'adminindexinit')
                    {
                        $ret[] = 'static/plugins/css/intellectstools/admin/public/inventory_early_warning.css';
                    }
                    break;

                // 后台公共js
                case 'plugins_admin_js' :
                    if($this->mca == 'admingoodsindex' && $is_goods_top_category_move)
                    {
                        $ret = 'static/plugins/js/intellectstools/admin/public/goods_category_move.js';
                    }
                    break;

                // 公共页面底部内容
                case 'plugins_common_page_bottom' :
                    $ret = $this->ViewPageBottomContent($params);
                    break;

                // 商品列表操作
                case 'plugins_view_admin_goods_list_operate' :
                    $ret = $this->AdminGoodsListOperateButton($params);
                    break;

                // 订单列表操作
                case 'plugins_view_admin_order_list_operate' :
                    $ret = $this->AdminOrderListOperateButton($params);
                    break;

                // 后台商品保存页面
                case 'plugins_view_admin_goods_save' :
                    $ret = $this->AdminGoodsSaveView($params);
                    break;

                // 商品保存前处理
                case 'plugins_service_goods_save_handle' :
                    $ret = $this->GoodsSaveBeginHandle($params);
                    break;

                // 商品保存成功处理
                case 'plugins_service_goods_save_end' :
                    $ret = $this->GoodsSaveEndHandle($params);
                    break;

                // 订单列表商品列数据
                case 'plugins_view_admin_order_grid_goods' :
                    $ret = $this->AdminOrderListGoodsInfo($params);
                    break;

                // 商品列表基础信息列数据
                case 'plugins_view_admin_goods_grid_info' :
                    $ret = $this->AdminGoodsListBaseInfo($params);
                    break;

                // 系统初始化
                case 'plugins_service_system_begin' :
                    $this->SystemInitHandle($params);
                    break;

                // 商品详情右侧基础提示信息
                case 'plugins_view_goods_detail_right_content_inside_bottom' :
                    $ret = $this->GoodsDetailRightInsideButtonContent($params);
                    break;

                // 商品详情内容顶部
                case 'plugins_view_goods_detail_base_bottom' :
                    $ret = $this->GoodsDetailContentTopHandle($params);
                    break;

                // 商品接口数据
                case 'plugins_service_base_data_return_api_goods_detail' :
                    $ret = $this->GoodsResultHandle($params);
                    break;

                // 批量设置商品分类按钮
                case 'plugins_view_admin_goods_top_operate' :
                    if($is_goods_top_category_move)
                    {
                        $ret = $this->AdminGoodsViewTopHtml($params);
                    }
                    break;

                // 批量设置商品分类弹窗数据
                case 'plugins_view_admin_goods_content_inside_bottom' :
                    if($is_goods_top_category_move)
                    {
                        $ret = $this->AdminGoodsViewContentBottom($params);
                    }
                    break;

                // 订单列表操作 - 多商户
                case 'plugins_view_index_plugins_shop_order_list_operate' :
                    $ret = $this->ShopOrderListOperateButton($params);
                    break;

                // 订单列表商品列数据 - 多商户
                case 'plugins_view_index_plugins_shop_order_grid_goods' :
                    $ret = $this->ShopOrderListGoodsInfo($params);
                    break;

                // 商品列表操作 - 多商户
                case 'plugins_view_index_plugins_shop_goods_list_operate' :
                    $ret = $this->ShopGoodsListOperateButton($params);
                    break;

                // 商品列表基础信息列数据 - 多商户
                case 'plugins_view_index_plugins_shop_goods_grid_info' :
                    $ret = $this->ShopGoodsListBaseInfo($params);
                    break;

                // 后台首页库存预警 - 后台
                case 'plugins_admin_view_index_init_stats_base_top' :
                    if($is_admin_inventory_early_warning)
                    {
                        $ret = $this->AdminIndexInitInventoryEarlyWarning($params);
                    }
                    break;

                // 搜索右侧
                case 'plugins_view_common_search_right' :
                    // 购物车页面不展示
                    if($is_search_right_cart)
                    {
                        $ret = $this->SearchRight($params);
                    }
                    break;

                // 顶部小导航
                case 'plugins_service_header_navigation_top_right_handle' :
                    $this->NavTopRightHandle($params);
                    break;
            }
            return $ret;
        }
    }

    /**
     * 顶部小导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-13
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function NavTopRightHandle($params = [])
    {
        // 是否去除购物车导航
        if(isset($this->plugins_config['is_del_nav_top_right_cart']) && $this->plugins_config['is_del_nav_top_right_cart'] == 1)
        {
            if(!empty($params['data']) && is_array($params['data']))
            {
                foreach($params['data'] as $k=>$v)
                {
                    if(isset($v['type']) && $v['type'] == 'cart')
                    {
                        unset($params['data'][$k]);
                    }
                }
                $params['data'] = array_values($params['data']);
            }
        }
    }

    /**
     * 搜索右侧
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-13
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function SearchRight($params = [])
    {
        // 列表
        $user = UserService::LoginUserInfo();
        $cart_list = BuyService::CartList(['user'=>$user]);
        MyViewAssign('cart_list', $cart_list['data']);
        // 基础
        $cart_base = [
            'total_price'   => empty($cart_list['data']) ? '0.00' : PriceNumberFormat(array_sum(array_column($cart_list['data'], 'total_price'))),
            'cart_count'    => empty($cart_list['data']) ? 0 : count($cart_list['data']),
            'ids'           => empty($cart_list['data']) ? '' : implode(',', array_column($cart_list['data'], 'id')),
        ];
        MyViewAssign('cart_base', $cart_base);
        return MyView('../../../plugins/view/intellectstools/index/public/search_right_cart');
    }

    /**
     * 后台首页库存预警
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-06-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function AdminIndexInitInventoryEarlyWarning($params = [])
    {
        $warehouse_data = GoodsInventoryService::WarehouseGoodsInventoryEarlyWarning($this->plugins_config);
        MyViewAssign('warehouse_data', $warehouse_data);
        return MyView('../../../plugins/view/intellectstools/admin/public/inventory_early_warning');
    }

    /**
     * 商品列表基础信息列数据 - 多商户
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function ShopGoodsListBaseInfo($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['id']))
        {
            // 备注信息
            $note_data = GoodsNoteService::GoodsNoteData($params['data']['id']);
            if(!empty($note_data) && !empty($note_data['content']))
            {
                $note_data['content'] = explode("\n", $note_data['content']);
            }
            MyViewAssign('plugins_goods_data', $params['data']);
            MyViewAssign('plugins_note_data', $note_data);
            MyViewAssign('plugins_config', $this->plugins_config);
            return MyView('../../../plugins/view/intellectstools/index/goods/goods_info_grid');
        }
    }

    /**
     * 商品管理列表操作 - 多商户
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function ShopGoodsListOperateButton($params = [])
    {
        MyViewAssign('plugins_data', empty($params['data']) ? [] : $params['data']);
        MyViewAssign('plugins_config', $this->plugins_config);
        return MyView('../../../plugins/view/intellectstools/index/goods/button');
    }

    /**
     * 订单列表商品列数据 - 多商户
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function ShopOrderListGoodsInfo($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['id']))
        {
            // 备注信息
            $note_data = OrderNoteService::OrderNoteData($params['data']['id']);
            if(!empty($note_data) && !empty($note_data['content']))
            {
                $note_data['content'] = explode("\n", $note_data['content']);
            }
            MyViewAssign('plugins_order_data', $params['data']);
            MyViewAssign('plugins_note_data', $note_data);
            MyViewAssign('plugins_config', $this->plugins_config);
            return MyView('../../../plugins/view/intellectstools/index/order/order_goods_grid');
        }
    }

    /**
     * 订单管理列表操作 - 多商户
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function ShopOrderListOperateButton($params = [])
    {
        // 订单信息
        $plugins_data = empty($params['data']) ? [] : $params['data'];
        if(!empty($plugins_data['id']) && isset($plugins_data['status']))
        {
            // 订单修改信息
            MyViewAssign('operate_edit_button_info', OrderBaseService::OrderOperateEditInfo($this->plugins_config, $plugins_data));

            MyViewAssign('plugins_data', $plugins_data);
            MyViewAssign('plugins_config', $this->plugins_config);
            return MyView('../../../plugins/view/intellectstools/index/order/button');
        }
    }

    /**
     * 批量设置商品分类弹窗数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-13
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function AdminGoodsViewContentBottom($params = [])
    {
        // 商品分类
        MyViewAssign('goods_category_list', GoodsService::GoodsCategoryAll());
        return MyView('../../../plugins/view/intellectstools/admin/goods/content_bottom');
    }

    /**
     * 批量设置商品分类按钮
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-13
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function AdminGoodsViewTopHtml($params = [])
    {
        return MyView('../../../plugins/view/intellectstools/admin/goods/top_operate');
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
        if(!empty($params['data']) && !empty($params['data']['goods']))
        {
            // 基础提示
            $base_bottom = [];
            if(!empty($this->plugins_config['goods_detail_base_tips_msg']))
            {
                $base_bottom['title'] = empty($this->plugins_config['goods_detail_base_tips_title']) ? null : $this->plugins_config['goods_detail_base_tips_title'];
                $base_bottom['msg'] = $this->plugins_config['goods_detail_base_tips_msg'];
            }

            // 详情顶部内容
            $content_top = [];
            if(!empty($this->plugins_config['goods_detail_content_top_tips_msg']))
            {
                $content_top['title'] = empty($this->plugins_config['goods_detail_content_top_tips_title']) ? null : $this->plugins_config['goods_detail_content_top_tips_title'];
                $content_top['msg'] = $this->plugins_config['goods_detail_content_top_tips_msg'];
            }

            // 存在数据则接口返回
            if(!empty($base_bottom) || !empty($content_top))
            {
                $params['data']['plugins_intellectstools_data'] = [
                    'base_bottom'  => $base_bottom,
                    'content_top'  => $content_top,
                ];
            }
        }
    }

    /**
     * 商品详情内容顶部
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsDetailContentTopHandle($params = [])
    {
        MyViewAssign('plugins_config', $this->plugins_config);
        return MyView('../../../plugins/view/intellectstools/index/public/goods_detail_content_top_tips');
    }

    /**
     * 商品详情右侧基础提示信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-27
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsDetailRightInsideButtonContent($params = [])
    {
        MyViewAssign('plugins_config', $this->plugins_config);
        return MyView('../../../plugins/view/intellectstools/index/public/goods_detail_base_bottom_tips');
    }

    /**
     * 系统初始化处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-05
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function SystemInitHandle($params = [])
    {
        // 必须存在输入参数
        if(!empty($params['params']))
        {
            // web端和接口请求
            $goods_id = ($this->mca == 'indexgoodsindex' && !empty($params['params']['id'])) ? $params['params']['id'] : ($this->mca == 'apigoodsdetail' && !empty($params['params']['goods_id']) ? $params['params']['goods_id'] : 0);
            if(!empty($goods_id))
            {
                // 自动增加销量
                if(!empty($this->plugins_config['auto_inc_sales_number']))
                {
                    $ret = GoodsBeautifyService::GoodsAutoIncSales($goods_id, $this->plugins_config, $params['params']);
                }

                // 处理用户评论数据
                $ret = CommentsDataService::GoodsCommentsHandle($goods_id, $this->plugins_config, $params['params']);
            }
        }
    }

    /**
     * 商品列表基础信息列数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function AdminGoodsListBaseInfo($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['id']))
        {
            // 备注信息
            $note_data = GoodsNoteService::GoodsNoteData($params['data']['id']);
            if(!empty($note_data) && !empty($note_data['content']))
            {
                $note_data['content'] = explode("\n", $note_data['content']);
            }
            MyViewAssign('plugins_goods_data', $params['data']);
            MyViewAssign('plugins_note_data', $note_data);
            MyViewAssign('plugins_config', $this->plugins_config);
            return MyView('../../../plugins/view/intellectstools/admin/goods/goods_info_grid');
        }
    }

    /**
     * 订单列表商品列数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function AdminOrderListGoodsInfo($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['id']))
        {
            // 备注信息
            $note_data = OrderNoteService::OrderNoteData($params['data']['id']);
            if(!empty($note_data) && !empty($note_data['content']))
            {
                $note_data['content'] = explode("\n", $note_data['content']);
            }
            MyViewAssign('plugins_order_data', $params['data']);
            MyViewAssign('plugins_note_data', $note_data);
            MyViewAssign('plugins_config', $this->plugins_config);
            return MyView('../../../plugins/view/intellectstools/admin/order/order_goods_grid');
        }
    }

    /**
     * 商品保存后处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsSaveEndHandle($params = [])
    {
        if(!empty($params['goods_id']) && !empty($params['params']) && isset($this->plugins_config['is_goods_alone_comments_config']) && $this->plugins_config['is_goods_alone_comments_config'] == 1)
        {
            CommentsDataService::CommentsGoodsConfigSave($params['goods_id'], $params['params']);
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
        if(!empty($params['params']) && isset($this->plugins_config['is_goods_beautify']) && $this->plugins_config['is_goods_beautify'] == 1)
        {
            $params['data']['access_count'] = isset($params['params']['access_count']) ? intval($params['params']['access_count']) : 0;
            $params['data']['sales_count'] = isset($params['params']['sales_count']) ? intval($params['params']['sales_count']) : 0;
        }
    }

    /**
     * 商品信息保存页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-23
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function AdminGoodsSaveView($params = [])
    {
        // 商品id
        $goods_id = empty($params['goods_id']) ? 0 : intval($params['goods_id']);

        // 定义空的返回内容
        $ret = '';

        // 商品美化
        if(isset($this->plugins_config['is_goods_beautify']) && $this->plugins_config['is_goods_beautify'] == 1)
        {
            MyViewAssign('plugins_data', empty($params['data']) ? [] : $params['data']);
            $ret .= MyView('../../../plugins/view/intellectstools/admin/goods/goods_edit');
        }

        // 单独为商品配置评价区间值
        if(isset($this->plugins_config['is_goods_alone_comments_config']) && $this->plugins_config['is_goods_alone_comments_config'] == 1)
        {
            MyViewAssign('plugins_data', CommentsDataService::CommentsGoodsConfigData($goods_id));
            $ret .= MyView('../../../plugins/view/intellectstools/admin/comments/goods');
        }
        return $ret;
    }

    /**
     * 订单管理列表操作
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function AdminOrderListOperateButton($params = [])
    {
        // 订单信息
        $plugins_data = empty($params['data']) ? [] : $params['data'];
        if(!empty($plugins_data['id']) && isset($plugins_data['status']))
        {
            // 订单修改信息
            MyViewAssign('operate_edit_button_info', OrderBaseService::OrderOperateEditInfo($this->plugins_config, $plugins_data));

            MyViewAssign('plugins_data', $plugins_data);
            MyViewAssign('plugins_config', $this->plugins_config);
            return MyView('../../../plugins/view/intellectstools/admin/order/button');
        }
    }

    /**
     * 商品管理列表操作
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function AdminGoodsListOperateButton($params = [])
    {
        MyViewAssign('plugins_data', empty($params['data']) ? [] : $params['data']);
        MyViewAssign('plugins_config', $this->plugins_config);
        return MyView('../../../plugins/view/intellectstools/admin/goods/button');
    }

    /**
     * 底部公共内容
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-04-11
     * @desc    description
     * @param   array           $params [description]
     */
    private function ViewPageBottomContent($params = [])
    {
        return MyView('../../../plugins/view/intellectstools/index/public/common');
    }
}
?>