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
namespace app\plugins\membershiplevelvip;

use think\facade\Db;
use app\service\PluginsService;
use app\service\GoodsService;
use app\service\ResourcesService;
use app\service\SystemBaseService;
use app\plugins\membershiplevelvip\service\BaseService;
use app\plugins\membershiplevelvip\service\BusinessService;
use app\plugins\membershiplevelvip\service\LevelService;

/**
 * 会员等级增强版插件 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    // 插件配置信息
    private $base_config;

    // 商品是否实际使用
    public $is_actual_discount_goods = 0;

    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-09T14:25:44+0800
     * @param    [array]                    $params [输入参数]
     */
    public function handle($params = [])
    {
        // 钩子名称
        if(!empty($params['hook_name']))
        {
            // 插件配置信息
            $base = BaseService::BaseConfig();
            $this->base_config = $base['data'];

            // 当前模块/控制器/方法
            $module_name = RequestModule();
            $controller_name = RequestController();
            $action_name = RequestAction();

            // 走钩子
            $ret = '';
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    $ret = 'static/plugins/css/membershiplevelvip/index/style.css';
                    break;

                // 在前面添加导航
                case 'plugins_service_navigation_header_handle' :
                    $ret = $this->NavigationHeaderHandle($params);
                    break;

                // 商品数据处理后
                case 'plugins_service_goods_handle_end' :
                    if($module_name != 'admin')
                    {
                        // 是否支持优惠
                        if(SystemBaseService::IsGoodsDiscount($params))
                        {
                            $this->GoodsHandleEnd($params);
                        }

                        // 使用优惠处理
                        if(!empty($params) && !empty($params['goods']) && !empty($params['goods']['id']))
                        {
                            SystemBaseService::GoodsDiscountRecord($params['goods']['id'], 'membershiplevelvip', $this->is_actual_discount_goods);
                        }
                    }
                    break;

                // 商品规格基础数据
                case 'plugins_service_goods_spec_base' :
                    // 是否支持优惠
                    if(SystemBaseService::IsGoodsDiscount($params))
                    {
                        $this->GoodsSpecBase($params);
                    }
                    break;

                // 满减优惠
                case 'plugins_service_buy_group_goods_handle' :
                    $ret = $this->FullReductionCalculate($params);
                    break;

                // 用户登录成功信息纪录钩子 icon处理
                case 'plugins_service_user_login_success_record' :
                    $ret = $this->UserLoginSuccessIconHandle($params);
                    break;

                // 后台商品编辑规格会员等级
                case 'plugins_service_goods_spec_extends_handle' :
                    $ret = $this->GoodsSpecExtendsHandle($params);
                    break;

                // 后台用户保存页面
                case 'plugins_view_admin_user_save' :
                    $ret = $this->AdminUserSaveHandle($params);
                    break;

                // 后台用户动态列表会员等级
                case 'plugins_module_form_admin_user_index' :
                case 'plugins_module_form_admin_user_detail' :
                    if(in_array($module_name.$controller_name.$action_name, ['adminuserindex', 'adminuserdetail']))
                    {
                        $ret = $this->AdminFormUserHandle($params);
                    }
                    break;

                // 用户保存处理
                case 'plugins_service_user_save_handle' :
                    $ret = $this->UserSaveServiceHandle($params);
                    break;

                // 商品保存处理
                case 'plugins_service_goods_save_handle' :
                    $ver = str_replace('v', '', APPLICATION_VERSION);
                    if(version_compare($ver,'2.2.0','<='))
                    {
                        $ret = $this->GoodsSaveServiceHandle($params);
                    }
                    break;

                // 商品基础数据更新、新版本替代上面商品保存的方案
                case 'plugins_service_goods_base_update' :
                    $ret = $this->GoodsBaseUpdateHandle($params);
                    break;

                // 商品价格上面钩子
                case 'plugins_view_goods_detail_panel_price_top' :
                    if(APPLICATION == 'web' && $module_name.$controller_name.$action_name == 'indexgoodsindex')
                    {
                        // 是否已支持优惠
                        if(!empty($params['goods']) && !empty($params['goods']['id']) && SystemBaseService::IsGoodsDiscountRecord($params['goods']['id'], 'membershiplevelvip'))
                        {
                            $ret = $this->GoodsDetailViewPriceTop($params);
                        }
                    }
                    break;

                // 商品列表弹层价格钩子
                case 'plugins_view_home_goods_inside_bottom' :
                case 'plugins_view_search_goods_inside_bottom' :
                    if(APPLICATION == 'web' && in_array($module_name.$controller_name.$action_name, ['indexindexindex', 'indexsearchgoodslist']))
                    {
                        $ret = $this->GoodsListViewPriceContent($params);
                    }
                    break;

                // 商品详情获取规格类型处理
                case 'plugins_service_goods_spec_type' :
                    $this->GoodsSpecType($params);
                    break;

                // 用户中心左侧导航
                case 'plugins_service_users_center_left_menu_handle' :
                    $ret = $this->UserCenterLeftMenuHandle($params);
                    break;

                // 顶部小导航右侧-我的商城
                case 'plugins_service_header_navigation_top_right_handle' :
                    $ret = $this->CommonTopNavRightMenuHandle($params);
                    break;
            }
            return $ret;
        } else {
            return '';
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
        // 是否开启会员购买
        if(BusinessService::IsUserPay())
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
                        'url'                   => PluginsHomeUrl('membershiplevelvip', 'index', 'index'),
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

    /**
     * 用户中心左侧菜单处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   array           $params [description]
     */
    public function UserCenterLeftMenuHandle($params = [])
    {
        // 开启会员购买或者会员续费
        if(BusinessService::IsUserPay() || BusinessService::IsSupportedRenewOldOrder())
        {
            $params['data']['base']['item'][] = [
                'name'      =>  '我的会员',
                'url'       =>  PluginsHomeUrl('membershiplevelvip', 'vip', 'index'),
                'group'     =>  'membershiplevelvip',
                'contains'  =>  ['membershiplevelvipvipindex', 'membershiplevelvipposterindex', 'membershiplevelvipprofitindex', 'membershiplevelviporderindex', 'membershiplevelvipteamindex'],
                'is_show'   =>  1,
                'icon'      =>  'am-icon-coffee',
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
     * @param   array           $params [description]
     */
    public function CommonTopNavRightMenuHandle($params = [])
    {
        // 开启会员购买或者会员续费
        if(BusinessService::IsUserPay() || BusinessService::IsSupportedRenewOldOrder())
        {
            array_push($params['data'][1]['items'], [
                'name'  => '我的会员',
                'url'   => PluginsHomeUrl('membershiplevelvip', 'vip', 'index'),
            ]);
        }
    }

    /**
     * 商品列表金额内容
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-07-30T22:02:44+0800
     * @param   [array]          $params [输入参数]
     */
    private function GoodsListViewPriceContent($params = [])
    {
        if(!empty($params['goods']))
        {
            $is_membershiplevelvip = false;

            // 是否已支持优惠
            if(!empty($params['goods']['id']) && SystemBaseService::IsGoodsDiscountRecord($params['goods']['id'], 'membershiplevelvip'))
            {
                $level = BusinessService::UserLevelMatching();
                if(!empty($level))
                {
                    // 指定商品售价
                    if(!empty($params['goods']['plugins_membershiplevelvip_price_extends']))
                    {
                        $extends = json_decode($params['goods']['plugins_membershiplevelvip_price_extends'], true);
                        if(!empty($extends[$level['id']]) && !empty($extends[$level['id']]['price']))
                        {
                            $is_membershiplevelvip = true;
                        }
                    }
                    
                    // 自动折扣售价
                    if($is_membershiplevelvip == false && $level['discount_rate'] > 0)
                    {
                        $is_membershiplevelvip = true;
                    }
                }
            }

            MyViewAssign('is_membershiplevelvip', $is_membershiplevelvip);
            MyViewAssign('goods_data', $params['goods']);
            return MyView('../../../plugins/view/membershiplevelvip/index/public/items_goods_price');
        }
    }

    /**
     * 商品详情获取规格类型处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-25
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function GoodsSpecType($params = [])
    {
        if(!empty($params['goods_id']))
        {
            $level = BusinessService::UserLevelMatching();
            if(!empty($level))
            {
                $price = $this->GoodsSalePrice($params['goods_id'], $level['id']);
                if(!empty($price))
                {
                    $params['data']['extends_element'][] = [
                        'element'   => '.plugins-membershiplevelvip-goods-price-top',
                        'content'   => $this->GoodsDetailPrice($price, $price),
                    ];
                }
            }
        }
    }

    /**
     * 根据商品id获取会员等级扩展数据价格
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-25
     * @desc    description
     * @param   [int]          $goods_id            [商品id]
     * @param   [int]          $level_id            [会员等级id]
     * @param   [boolean]      $is_extends_check    [是否需要校验是否存在会员扩展数据]
     */
    private function GoodsSalePrice($goods_id, $level_id, $is_extends_check = true)
    {
        $goods_params = [
            'where' => [
                ['id', '=', $goods_id],
                ['is_shelves', '=', 1],
                ['is_delete_time', '=', 0],
            ],
        ];
        $price = 0.00;
        $ret = GoodsService::GoodsList($goods_params);
        if($ret['code'] == 0 && !empty($ret['data'][0]))
        {
            // 扩展数据是否存在会员等级自定义售价
            if($is_extends_check === true)
            {
                if(!empty($ret['data'][0]['plugins_membershiplevelvip_price_extends']))
                {
                    $extedns = json_decode($ret['data'][0]['plugins_membershiplevelvip_price_extends'], true);
                    if(!empty($extedns[$level_id]))
                    {
                        $price = $ret['data'][0]['price_container']['price'];
                    }
                }
            } else {
                // 商品售价
                $price = $ret['data'][0]['price_container']['price'];
            }
        }
        return $price;
    }

    /**
     * 商品价格上面处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-25
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function GoodsDetailViewPriceTop($params = [])
    {
        $level = BusinessService::UserLevelMatching();
        if(!empty($level))
        {
            // 指定商品售价
            if(!empty($params['goods']['plugins_membershiplevelvip_price_extends']))
            {
                $extends = json_decode($params['goods']['plugins_membershiplevelvip_price_extends'], true);
                if(!empty($extends[$level['id']]) && !empty($extends[$level['id']]['price']))
                {
                    return $this->GoodsDetailPrice($params['goods']['price_container']['price'], $params['goods']['price_container']['price']);
                }
            }
        
            // 自动折扣商品售价
            if($level['discount_rate'] > 0)
            {
                return $this->GoodsDetailPrice($params['goods']['price_container']['price'], $params['goods']['price_container']['price']);
            }
        }
    }

    /**
     * 商品详情价格
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-25
     * @desc    description
     * @param   [float]          $goods_price           [商品销售价格]
     * @param   [float]          $goods_original_price  [商品原价价格]
     */
    private function GoodsDetailPrice($goods_price, $goods_original_price)
    {
        if(APPLICATION == 'web')
        {
            MyViewAssign('goods_original_price', $goods_original_price);
            MyViewAssign('goods_price', $goods_price);
            return MyView('../../../plugins/view/membershiplevelvip/index/public/detail_goods_price');
        }
        return '';
    }

    /**
     * 商品基础数据更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-23
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function GoodsBaseUpdateHandle($params = [])
    {
        $spec_all = [];
        $spec_extends = [];
        if(!empty($params['goods_id']))
        {
            $goods_spec = Db::name('GoodsSpecBase')->where(['goods_id'=>$params['goods_id']])->field('price,extends')->select()->toArray();
            foreach($goods_spec as $k=>$v)
            {
                if(!empty($v['extends']))
                {
                    $temp = json_decode($v['extends'], true);
                    if(!empty($temp))
                    {
                        foreach($temp as $ks=>$vs)
                        {
                            if(!empty($vs) && substr($ks, 0, 33) == 'plugins_membershiplevelvip_price_')
                            {
                                $key = str_replace('plugins_membershiplevelvip_price_', '', $ks);
                                if(!array_key_exists($key, $spec_extends))
                                {
                                    $spec_extends[$key] = [];
                                }
                                $spec_extends[$key][$k] = PriceNumberFormat($vs);
                            }
                        }
                    }
                }
                $spec_all[] = $v['price'];
            }
        }

        // 扩展数据处理
        $result = [];
        if(!empty($spec_extends))
        {
            foreach($spec_extends as $k=>$v)
            {
                // 防止会员价未全部设置，将原始数据未设置的加入列表防止出现价格差异
                foreach($spec_all as $ks=>$vs)
                {
                    if(!array_key_exists($ks, $v))
                    {
                        $v[$ks] = $vs;
                    }
                }

                // 价格处理
                $min_price = min($v);
                $max_price = max($v);

                $data = [
                    'min_price' => $min_price,
                    'max_price' => $max_price,
                ];
                $data['price'] = (!empty($data['max_price']) && $data['min_price'] != $data['max_price']) ? (empty($data['min_price']) ? $data['max_price'] : $data['min_price'].'-'.$data['max_price']) : $data['min_price'];
                $result[$k] = $data;
            }
        }

        // 商品数据更新
        $update_data = [
            'plugins_membershiplevelvip_price_extends'  => empty($result) ? '' : json_encode($result),
            'upd_time'                                  => time(),
        ];
        if(Db::name('Goods')->where(['id'=>$params['goods_id']])->update($update_data) === false)
        {
            return DataReturn('会员等级数据更新失败', -1);
        }

        return DataReturn('处理成功', 0);
    }

    /**
     * 商品信息保存处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-23
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function GoodsSaveServiceHandle($params = [])
    {
        $spec_all = [];
        $spec_extends = [];
        if(!empty($params['spec']['data']))
        {
            $spec_count = 6;
            foreach($params['spec']['data'] as $k=>$v)
            {
                $count = count($v);
                if(!empty($v[$count-1]))
                {
                    $temp = json_decode(htmlspecialchars_decode($v[$count-1]), true);
                    if(!empty($temp))
                    {
                        foreach($temp as $ks=>$vs)
                        {
                            if(!empty($vs) && substr($ks, 0, 33) == 'plugins_membershiplevelvip_price_')
                            {
                                $key = str_replace('plugins_membershiplevelvip_price_', '', $ks);
                                if(!array_key_exists($key, $spec_extends))
                                {
                                    $spec_extends[$key] = [];
                                }
                                $spec_extends[$key][$k] = PriceNumberFormat($vs);
                            }
                        }
                    }
                }
                if(isset($v[$count-$spec_count]))
                {
                    $spec_all[] = $v[$count-$spec_count];
                }
            }
        }

        // 扩展数据处理
        if(!empty($spec_extends))
        {
            $result = [];
            foreach($spec_extends as $k=>$v)
            {
                // 防止会员价未全部设置，将原始数据未设置的加入列表防止出现价格差异
                foreach($spec_all as $ks=>$vs)
                {
                    if(!array_key_exists($ks, $v))
                    {
                        $v[$ks] = $vs;
                    }
                }

                // 价格处理
                $min_price = min($v);
                $max_price = max($v);

                $data = [
                    'min_price' => $min_price,
                    'max_price' => $max_price,
                ];
                $data['price'] = (!empty($data['max_price']) && $data['min_price'] != $data['max_price']) ? (empty($data['min_price']) ? $data['max_price'] : $data['min_price'].'-'.$data['max_price']) : $data['min_price'];
                $result[$k] = $data;
            }
            $params['data']['plugins_membershiplevelvip_price_extends'] = json_encode($result);
        } else {
            $params['data']['plugins_membershiplevelvip_price_extends'] = '';
        }
        return DataReturn('处理成功', 0);
    }

    /**
     * 用户信息保存处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-23
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function UserSaveServiceHandle($params = [])
    {
        $params['data']['plugins_user_level'] = isset($params['params']['plugins_user_level']) ? $params['params']['plugins_user_level'] : '';
        return DataReturn('处理成功', 0);
    }

    /**
     * 用户信息保存页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-23
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function AdminUserSaveHandle($params = [])
    {
        $ret = LevelService::DataList(['where'=>['is_enable'=>1]]);
        if(!empty($ret['data']))
        {
            MyViewAssign('user_data', $params['data']);
            MyViewAssign('level_list', $ret['data']);
            return MyView('../../../plugins/view/membershiplevelvip/admin/public/user');
        }
    }

    /**
     * 后台用户动态列表会员等级
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function AdminFormUserHandle($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['form']) && isset($this->base_config['is_admin_user_level_show']) && $this->base_config['is_admin_user_level_show'] == 1)
        {
            $ret = LevelService::DataList(['where'=>['is_enable'=>1]]);
            if($ret['code'] == 0 && !empty($ret['data']))
            {
                $level_data = array_column($ret['data'], 'name', 'id');
                array_splice($params['data']['form'], -3, 0, [[
                    'label'         => '会员等级',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/membershiplevelvip/admin/public/user_level_module',
                    'view_data'     => $level_data,
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'plugins_user_level',
                        'where_type'        => 'in',
                        'data'              => $level_data,
                        'is_multiple'       => 1,
                    ],
                ]]);
            }
        }
    }

    /**
     * 商品规格扩展数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-07-23
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function GoodsSpecExtendsHandle($params = [])
    {
        $ret = LevelService::DataList(['where'=>['is_enable'=>1]]);
        if($ret['code'] == 0 && !empty($ret['data']))
        {
            $element = [];
            foreach($ret['data'] as $v)
            {
                $element[] = [
                    'element'       => 'input',
                    'type'          => 'text',
                    'name'          => 'plugins_membershiplevelvip_price_'.$v['id'],
                    'placeholder'   => $v['name'].'销售价',
                    'title'         => $v['name'].'销售价',
                    'is_required'   => 0,
                    'message'       => '请填写会员销售价',
                    'desc'          => '会员等级对应销售金额',
                ];
            }

            // 配置信息
            if(count($element) > 0)
            {
                $plugins = [
                    'name'      => '会员等级增强版',
                    'desc'      => '按照会员等级设定不同金额',
                    'element'   => $element,
                ];
                $params['data'][] = $plugins;
            }
        }
    }

    /**
     * 用户icon处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-28
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private function UserLoginSuccessIconHandle($params = [])
    {
        if(!empty($params['user']))
        {
            // 用户等级
            $vip = BusinessService::UserLevelMatching($params['user']['id']);
            if(!empty($vip) && !empty($vip['icon']))
            {
                $params['user']['icon'] = $vip['icon'];
            }
        }
        return DataReturn('处理成功', 0);
    }

    /**
     * 满减计算
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-21
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function FullReductionCalculate($params = [])
    {
        if(!empty($params['data']))
        {
            // 用户等级
            $vip = BusinessService::UserLevelMatching();
            if(!empty($vip))
            {
                $order_price = isset($vip['order_price']) ? $vip['order_price'] : 0;
                $full_reduction_price = isset($vip['full_reduction_price']) ? $vip['full_reduction_price'] : 0;
                if($order_price > 0 && $full_reduction_price)
                {
                    $currency_symbol = ResourcesService::CurrencyDataSymbol();
                    $show_name = $vip['name'].'-满减';
                    foreach($params['data'] as &$v)
                    {
                        if($v['order_base']['total_price'] >= $order_price)
                        {
                            // 扩展展示数据
                            $v['order_base']['extension_data'][] = [
                                'name'      => $show_name,
                                'price'     => $full_reduction_price,
                                'type'      => 0,
                                'business'  => 'plugins-membershiplevelvip',
                                'tips'      => '-'.$currency_symbol.$full_reduction_price.'π',
                            ];
                        }
                    }
                }
            }
        }
        return DataReturn('处理成功', 0);
    }

    /**
     * 商品处理结束钩子
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [array]              $params [输入参数]
     */
    private function GoodsHandleEnd($params = [])
    {
        $key_field = empty($params['params']['data_key_field']) ? 'id' : $params['params']['data_key_field'];
        if(!empty($params['goods']) && isset($params['goods'][$key_field]))
        {
            // 用户等级
            $vip = BusinessService::UserLevelMatching();
            if(!empty($vip))
            {
                $status = false;
                // 自定义商品售价、字段不存在则读取
                if(!array_key_exists('plugins_membershiplevelvip_price_extends', $params['goods']))
                {
                    $price_extends = Db::name('Goods')->where(['id'=>$params['goods'][$key_field]])->value('plugins_membershiplevelvip_price_extends');
                } else {
                    $price_extends = $params['goods']['plugins_membershiplevelvip_price_extends'];
                }
                if(!empty($price_extends))
                {
                    $extends = json_decode($price_extends, true);
                    if(!empty($extends[$vip['id']]) && !empty($extends[$vip['id']]['price']))
                    {
                        $status = true;
                        // 展示销售价格
                        $params['goods']['price'] = $extends[$vip['id']]['price'];

                        // 最低价最高价
                        if(isset($params['goods']['min_price']))
                        {
                            $params['goods']['min_price'] = $extends[$vip['id']]['min_price'];
                        }
                        if(isset($params['goods']['max_price']))
                        {
                            $params['goods']['max_price'] = $extends[$vip['id']]['max_price'];
                        }
                    }
                }

                // 统一折扣
                if($status == false && $vip['discount_rate'] > 0)
                {
                    $status = true;
                    // 展示销售价格
                    if(isset($params['goods']['price']))
                    {
                        $params['goods']['price'] = BusinessService::PriceCalculate($params['goods']['price'], $vip['discount_rate'], 0);
                    }
                    // 最低价最高价
                    if(isset($params['goods']['min_price']))
                    {
                        $params['goods']['min_price'] = BusinessService::PriceCalculate($params['goods']['min_price'], $vip['discount_rate'], 0);
                    }
                    if(isset($params['goods']['max_price']))
                    {
                        $params['goods']['max_price'] = BusinessService::PriceCalculate($params['goods']['max_price'], $vip['discount_rate'], 0);
                    }
                }

                // 价格icon处理
                if($status === true)
                {
                    // icon title
                    $price_title = empty($vip['name']) ? '会员价' : $vip['name'];

                    // 开启会员则点击icon可进入会员首页
                    if(isset($this->base_config['is_user_buy']) && $this->base_config['is_user_buy'] == 1)
                    {
                        $params['goods']['show_field_price_text'] = '<a href="'.PluginsHomeUrl('membershiplevelvip', 'index', 'index').'" class="plugins-membershiplevelvip-goods-price-icon" title="'.$price_title.'">'.$price_title.'</a>';
                    } else {
                        $params['goods']['show_field_price_text'] = '<span class="plugins-membershiplevelvip-goods-price-icon" title="'.$price_title.'">'.$price_title.'</span>';
                    }

                    // 使用优惠标记
                    $this->is_actual_discount_goods = 1;
                }
            }
        }
    }

    /**
     * 商品规格基础数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsSpecBase($params = [])
    {
        // 用户等级
        $vip = BusinessService::UserLevelMatching();
        if(!empty($vip))
        {
            $status = false;
            // 自定义等级售价
            $goods_sale_price = $this->GoodsSalePrice($params['goods_id'], $vip['id']);
            $price = BusinessService::PriceCalculateManual($params['data']['spec_base']['extends'], $vip);
            if(!empty($goods_sale_price) && $price !== false)
            {
                $status = true;
                $params['data']['extends_element'][] = [
                    'element'   => '.plugins-membershiplevelvip-goods-price-top',
                    'content'   => $this->GoodsDetailPrice($params['data']['spec_base']['price'], $goods_sale_price),
                ];
                $params['data']['spec_base']['price'] = $price;
            }

            // 统一折扣
            if($status == false && $vip['discount_rate'] > 0 && isset($params['data']['spec_base']['price']))
            {
                $status = true;
                $params['data']['extends_element'][] = [
                    'element'   => '.plugins-membershiplevelvip-goods-price-top',
                    'content'   => $this->GoodsDetailPrice($params['data']['spec_base']['price'], $this->GoodsSalePrice($params['goods_id'], $vip['id'], false)),
                ];
                $params['data']['spec_base']['price'] = BusinessService::PriceCalculate($params['data']['spec_base']['price'], $vip['discount_rate'], 0);
            }

            // 未匹配到则使用默认价格、避免仅配置了部分价格导致销售价格无法展示正常对应数据
            if($status == false)
            {
                $params['data']['extends_element'][] = [
                    'element'   => '.plugins-membershiplevelvip-goods-price-top',
                    'content'   => $this->GoodsDetailPrice($params['data']['spec_base']['price'], $this->GoodsSalePrice($params['goods_id'], 0, false)),
                ];
            }
        }
    }
}
?>