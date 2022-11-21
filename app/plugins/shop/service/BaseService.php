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
namespace app\plugins\shop\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\ResourcesService;
use app\service\SystemBaseService;
use app\service\GoodsService;
use app\plugins\shop\service\ShopService;

/**
 * 多商户 - 基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class BaseService
{
    // 基础私有字段
    public static $base_config_private_field = [
        'sms_sign',
        'sms_new_order_template',
        'email_new_order_template',
        'shop_bond_price',
        'shop_bond_expire_time',
        'shop_second_domain_prohibit',
        'shop_second_domain_can_edit_number',
    ];

    // 基础数据附件字段
    public static $base_config_attachment_field = [
        'shop_auth_company_icon',
        'shop_auth_personal_icon',
        'shop_auth_bond_icon',
    ];

    // 消息类型
    public static $message_business_type = '多商户';

    // 资源路径
    public static $resources_dir = ROOT.'resources'.DS.'plugins_shop';

    // 身份证存储路径
    public static $package_resources_dir = ROOT.'resources'.DS.'plugins_shop'.DS.'idcard';

    // 默认banner
    public static $default_banner = '/static/plugins/images/shop/default-banner.jpg';

    // 店铺icon图标
    public static $shop_icon = '/static/plugins/images/shop/shop-icon.png';

    // 工作日
    public static $plugins_week_list = [
        0 => ['value'=>0, 'name'=>'周一'],
        1 => ['value'=>1, 'name'=>'周二'],
        2 => ['value'=>2, 'name'=>'周三'],
        3 => ['value'=>3, 'name'=>'周四'],
        4 => ['value'=>4, 'name'=>'周五'],
        5 => ['value'=>5, 'name'=>'周六'],
        6 => ['value'=>6, 'name'=>'周日'],
    ];

    // 店铺状态
    public static $plugins_shop_status_list = [
        0 => ['value'=>0, 'name'=>'待提交'],
        1 => ['value'=>1, 'name'=>'待审核'],
        2 => ['value'=>2, 'name'=>'已审核'],
        3 => ['value'=>3, 'name'=>'已拒绝'],
        4 => ['value'=>4, 'name'=>'已关闭'],
    ];

    // 店铺保证金状态
    public static $plugins_shop_bond_status_list = [
        0 => ['value'=>0, 'name'=>'未缴'],
        1 => ['value'=>1, 'name'=>'已缴'],
        2 => ['value'=>2, 'name'=>'已退'],
    ];

    // 店铺首页数据模式
    public static $plugins_shop_data_model_list = [
        0 => ['value'=>0, 'name'=>'自动模式', 'is_checked'=>1],
        1 => ['value'=>1, 'name'=>'拖拽模式'],
    ];

    // 结算类型
    public static $plugins_settle_type = [
        0 => ['value'=>0, 'name'=>'统一比例'],
        1 => ['value'=>1, 'name'=>'商品配置(金额|比例)'],
    ];

    // 收益状态
    public static $plugins_profit_status_list = [
        0 => ['value'=>0, 'name'=>'待生效'],
        1 => ['value'=>1, 'name'=>'生效中'],
        2 => ['value'=>2, 'name'=>'待结算'],
        3 => ['value'=>3, 'name'=>'已结算'],
        4 => ['value'=>4, 'name'=>'已失效'],
    ];

    // 认证类型
    public static $plugins_auth_type_list = [
        0 => ['value'=>0, 'name'=>'个人'],
        1 => ['value'=>1, 'name'=>'企业'],
    ];

    // 导航数据类型
    public static $plugins_data_type_list = [
        'custom' => ['value'=>'custom', 'name'=>'自定义'],
        'design' => ['value'=>'design', 'name'=>'页面设计'],
        'category' => ['value'=>'category', 'name'=>'商品分类'],
    ];

    // 推荐样式类型
    public static $recommend_style_type_list = [
        0 => ['value' => 0, 'name' => '图文列表', 'checked' => true],
        1 => ['value' => 1, 'name' => '九方格'],
        2 => ['value' => 2, 'name' => '一行滚动'],
    ];

    // 数据类型
    public static $recommend_data_type_list = [
        0 => ['value' => 0, 'name' => '自动模式', 'checked' => true],
        1 => ['value' => 1, 'name' => '指定商品'],
    ];

    // 排序类型
    public static $recommend_order_by_type_list = [
        0 => ['value' => 'id', 'name' => '最新', 'checked' => true],
        1 => ['value' => 'access_count', 'name' => '热度'],
        2 => ['value' => 'upd_time', 'name' => '更新'],
    ];

    // 排序规则
    public static $recommend_order_by_rule_list = [
        0 => ['value' => 'desc', 'name' => '降序(desc)', 'checked' => true],
        1 => ['value' => 'asc', 'name' => '升序(asc)'],
    ];

    // 订单确认状态（0待确认，1已确认，2已拒绝）
    public static $plugins_shop_order_confirm_status_list = [
        0 => ['value' => 0, 'name' => '待确认'],
        1 => ['value' => 1, 'name' => '已确认'],
        2 => ['value' => 2, 'name' => '已拒绝'],
    ];

    // 订单扣除状态（0待扣款，1已扣款，2已退回）
    public static $plugins_shop_order_confirm_pay_status_list = [
        0 => ['value' => 0, 'name' => '待扣款'],
        1 => ['value' => 1, 'name' => '已扣款'],
        2 => ['value' => 2, 'name' => '已解冻'],
    ];

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        // 是否开启了认证、并且对应的图标也设置
        $params['is_enable_auth'] = ((isset($params['is_shop_realname_auth']) && $params['is_shop_realname_auth'] == 1 && (!empty($params['shop_auth_company_icon']) || !empty($params['shop_auth_personal_icon']))) || (isset($params['is_shop_bond_auth']) && $params['is_shop_bond_auth'] == 1 && !empty($params['shop_auth_bond_icon']))) ? 1 : 0;

        // 保存配置
        return PluginsService::PluginsDataSave(['plugins'=>'shop', 'data'=>$params], self::$base_config_attachment_field);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        $ret = PluginsService::PluginsData('shop', self::$base_config_attachment_field, $is_cache);

        // 数据为空则赋值空数组
        if(empty($ret['data']))
        {
            $ret['data'] = [];
        }

        // 入驻协议
        $ret['data']['seller_agreement'] = empty($ret['data']['seller_agreement']) ? [] : explode("\n", $ret['data']['seller_agreement']);

        // 卖家中心通知
        $ret['data']['seller_center_notice'] = empty($ret['data']['seller_center_notice']) ? [] : explode("\n", $ret['data']['seller_center_notice']);

        // 未缴纳保证金说明
        $ret['data']['shop_not_pay_bond_msg'] = empty($ret['data']['shop_not_pay_bond_msg']) ? [] : explode("\n", $ret['data']['shop_not_pay_bond_msg']);

        // 已缴纳保证金说明
        $ret['data']['shop_already_pay_bond_msg'] = empty($ret['data']['shop_already_pay_bond_msg']) ? [] : explode("\n", $ret['data']['shop_already_pay_bond_msg']);

        // 邮件通知模板
        if(!empty($ret['data']['email_new_order_template']))
        {
            $ret['data']['email_new_order_template'] = htmlspecialchars_decode($ret['data']['email_new_order_template']);
        }

        return $ret;
    }

    /**
     * 获取商品所属用户id
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-27
     * @desc    description
     */
    public static function GoodsShopUserID()
    {
        $params = input();
        $user_id = 0;
        if(!empty($params['id']))
        {
            $user_id = Db::name('Goods')->where(['id'=>intval($params['id'])])->value('shop_user_id');
        }
        return $user_id;
    }

    /**
     * 用户中心菜单
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-12
     * @desc    description
     */
    public static function UserCenterNavData()
    {
        // 左侧基础菜单
        $base = [
            [
                'name'      => '商家中心',
                'module'    => 'shop',
                'control'   => 'user',
                'action'    => 'index',
                'icon'      => 'am-icon-home',
            ],
            [
                'name'      => '店铺管理',
                'module'    => 'shop',
                'control'   => 'shop',
                'action'    => 'index',
                'icon'      => 'am-icon-cog',
            ],
            [
                'name'      => '导航管理',
                'module'    => 'shop',
                'control'   => 'navigation',
                'action'    => 'index',
                'icon'      => 'am-icon-navicon',
            ],
            [
                'name'      => '轮播管理',
                'module'    => 'shop',
                'control'   => 'slider',
                'action'    => 'index',
                'icon'      => 'am-icon-image',
            ],
            [
                'name'      => '首页推荐',
                'module'    => 'shop',
                'control'   => 'recommend',
                'action'    => 'index',
                'icon'      => 'am-icon-film',
            ],
            [
                'name'      => '页面设计',
                'module'    => 'shop',
                'control'   => 'design',
                'action'    => 'index',
                'icon'      => 'am-icon-object-group',
            ],
            [
                'name'      => '商品分类',
                'module'    => 'shop',
                'control'   => 'goodscategory',
                'action'    => 'index',
                'icon'      => 'am-icon-th-list',
            ],
            [
                'name'      => '商品管理',
                'module'    => 'shop',
                'control'   => 'goods',
                'action'    => 'index',
                'icon'      => 'am-icon-shopping-basket',
            ],
            [
                'name'      => '订单管理',
                'module'    => 'shop',
                'control'   => 'order',
                'action'    => 'index',
                'icon'      => 'am-icon-th-large',
            ],
            [
                'name'      => '扩展模块',
                'module'    => 'shop',
                'control'   => 'extend',
                'action'    => 'index',
                'icon'      => 'am-icon-cube',
            ],
        ];

        // 扩展模块
        $extends  = [
            [
                'name'          => '余额提取',
                'desc'          => '商城钱包余额提现到指定收款账户',
                'url'           => PluginsHomeUrl('wallet', 'cash', 'index'),
                'icon'          => 'cash.png',
                'is_new_win'    => 1,
            ],
            [
                'name'          => '结算明细',
                'desc'          => '店铺订单收益抽成详细记录',
                'url'           => PluginsHomeUrl('shop', 'profit', 'index'),
                'icon'          => 'profit.png',
                'is_popup'      => 1,
                'is_full'       => 1,
            ],
            [
                'name'          => '订单售后',
                'desc'          => '处理用户订单申请的退款和退货',
                'url'           => PluginsHomeUrl('shop', 'orderaftersale', 'index'),
                'icon'          => 'orderaftersale.png',
                'is_popup'      => 1,
                'is_full'       => 1,
            ],
            [
                'name'          => '商品评论',
                'desc'          => '查看及回复用户评论订单相关的商品',
                'url'           => PluginsHomeUrl('shop', 'goodscomments', 'index'),
                'icon'          => 'goodscomments.png',
                'is_popup'      => 1,
                'is_full'       => 1,
            ],
            [
                'name'          => '运费设置',
                'desc'          => '看按照不同地区设置不同运费、及起步价、续加费用',
                'url'           => PluginsHomeUrl('shop', 'freightfee', 'index'),
                'icon'          => 'freightfee.png',
                'is_popup'      => 1,
                'is_full'       => 1,
            ],
        ];

        $data = [
            'base'      => $base,
            'extends'   => $extends,
        ];

        // 商家中心菜单
        $hook_name = 'plugins_shop_service_base_user_center_nav';
        MyEventTrigger($hook_name, [
            'hook_name'     => $hook_name,
            'is_backend'    => true,
            'data'          => &$data,
        ]);

        // 地址处理
        if(!empty($data['extends']) && is_array($data['extends']))
        {
            $attachment_host = SystemBaseService::AttachmentHost();
            foreach($data['extends'] as &$v)
            {
                if(!IsUrl($v['icon']))
                {
                    $v['icon'] = $attachment_host.'/static/plugins/images/shop/extend/'.$v['icon'];
                }
            }
        }

        return $data;
    }

    /**
     * 商品动态表格状态信息列
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-02
     * @desc    description
     */
    public static function GoodsFormStatusData()
    {
        $data = [];
        $base = self::BaseConfig();

        // 是否可以操作上下架
        if(isset($base['data']['is_shop_goods_list_shelves']) && $base['data']['is_shop_goods_list_shelves'] == 1)
        {
            $data[] = [
                'label'         => '上下架',
                'view_type'     => 'status',
                'view_key'      => 'is_shelves',
                'post_url'      => PluginsHomeUrl('shop', 'goods', 'statusupdate'),
                'is_form_su'    => 1,
                'align'         => 'center',
                'is_sort'       => 1,
                'search_config' => [
                    'form_type'         => 'select',
                    'where_type'        => 'in',
                    'data'              => MyConst('common_is_shelves_list'),
                    'data_key'          => 'id',
                    'data_name'         => 'name',
                    'is_multiple'       => 1,
                ],
            ];
        }

        // 是否可以操作库存扣除
        if(isset($base['data']['is_shop_goods_list_deduction_inventory']) && $base['data']['is_shop_goods_list_deduction_inventory'] == 1)
        {
            $data[] = [
                'label'         => '扣减库存',
                'view_type'     => 'status',
                'view_key'      => 'is_deduction_inventory',
                'post_url'      => PluginsHomeUrl('shop', 'goods', 'statusupdate'),
                'align'         => 'center',
                'is_sort'       => 1,
                'search_config' => [
                    'form_type'         => 'select',
                    'where_type'        => 'in',
                    'data'              => MyConst('common_is_text_list'),
                    'data_key'          => 'id',
                    'data_name'         => 'name',
                    'is_multiple'       => 1,
                ],
            ];
        }

        return $data;
    }

    /**
     * 商品动态表格结算信息列
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-02
     * @desc    description
     */
    public static function GoodsFormSettleData()
    {
        return [
            [
                'label'         => '结算固定金额',
                'view_type'     => 'field',
                'view_key'      => 'shop_settle_price',
                'is_sort'       => 1,
                'search_config' => [
                    'form_type'         => 'section',
                    'is_point'          => 1,
                ],
            ],
            [
                'label'         => '结算比例',
                'view_type'     => 'field',
                'view_key'      => 'shop_settle_rate',
                'is_sort'       => 1,
                'search_config' => [
                    'form_type'         => 'section',
                    'is_point'          => 1,
                ],
            ],
        ];
    }

    /**
     * 订单确认页面仓库信息处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-10
     * @desc    description
     * @param   [array]          $warehouse_ids [仓库id]
     */
    public static function WarehouseShopInfoHandle($warehouse_ids)
    {
        $data = Db::name('Warehouse')->where(['id'=>$warehouse_ids])->column('shop_id', 'id');
        if(!empty($data))
        {
            foreach($data as $k=>$v)
            {
                if(!empty($v))
                {
                    $data[$k] = [
                        'url'   => (APPLICATION == 'web') ? PluginsHomeUrl('shop', 'index', 'detail', ['id'=>$v]) : '/pages/plugins/shop/detail/detail?id='.$v,
                        'icon'  => self::ShopIcon(),
                    ];
                } else {
                    unset($data[$k]);
                }
            }
        }
        return $data;
    }

    /**
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-11
     * @desc    description
     * @param   [int]          $warehouse_id [仓库id]
     */
    public static function WarehouseInfo($warehouse_id)
    {
        return Db::name('Warehouse')->field('id,shop_id,shop_user_id')->find($warehouse_id);
    }

    /**
     * 商品店铺信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-05
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsShopList($params = [])
    {
        $shop_ids = Db::name('Goods')->column('shop_id');
        if(!empty($shop_ids))
        {
            $shop_ids = array_unique($shop_ids);
            if(!empty($shop_ids))
            {
                return  Db::name('PluginsShop')->where(['id'=>$shop_ids])->field('id,name')->select()->toArray();
            }
        }
        return [];
    }

    /**
     * 商品是否存在店铺信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function IsGoodsShopData($params = [])
    {
        if(!empty($params['id']))
        {
            $shop_id =  Db::name('Goods')->where(['id'=>intval($params['id'])])->value('shop_id');
            if(!empty($shop_id))
            {
                return ShopService::UserShopInfo($shop_id, 'id', 'id,user_id');
            }
        }
        return [];
    }

    /**
     * 店铺icon图标
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-07-03
     * @desc    description
     */
    public static function ShopIcon()
    {
        return MyConfig('shopxo.attachment_host').self::$shop_icon;
    }

    /**
     * 获取商品虚拟信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-28
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     */
    public static function FictitiousGoodsValue($goods_id)
    {
        $data = Db::name('Goods')->where(['id'=>$goods_id])->value('fictitious_goods_value');
        return empty($data) ? '' : ResourcesService::ContentStaticReplace($data, 'get');
    }

    /**
     * 获取在线客服链接地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-03-23
     * @desc    description
     * @param   [array]           $config   [插件配置信息]
     * @param   [int]             $user_id  [店铺用户id]
     * @param   [int]             $shop_id  [店铺id]
     */
    public static function ChatUrl($config = [], $user_id = 0, $shop_id = 0)
    {
        // 是否传了插件配置
        if(empty($config))
        {
            $base = self::BaseConfig();
            $config = $base['data'];
        }

        // 是否开启在线客服
        if(isset($config['is_plugins_chat_show']) && $config['is_plugins_chat_show'] == 1)
        {
            // 是否已安装客服插件
            $service_class = '\app\plugins\chat\service\BaseService';
            if(class_exists($service_class))
            {
                if(empty($user_id) && !empty($shop_id))
                {
                    $user_id = Db::name('PluginsShop')->where(['id'=>intval($shop_id)])->value('user_id');
                }
                $params = [
                    'chat_user' => $user_id,
                    'chat_type' => 'shop',
                ];
                return $service_class::ChatUrl([], $params);
            }
        }
        return [];
    }

    /**
     * 店铺二级域名可修改次数
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-09
     * @desc    description
     * @param   [array]          $config                  [插件配置]
     * @param   [int]            $shop_domain_edit_number [店铺修改次数]
     */
    public static function ShopSecondDomainCanEditNumber($config, $shop_domain_edit_number)
    {
        $number = empty($config['shop_second_domain_can_edit_number']) ? 3 : intval($config['shop_second_domain_can_edit_number']);
        return $number-$shop_domain_edit_number;
    }

    /**
     * 店铺域名
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-09
     * @desc    description
     * @param   [array]          $config [配置信息]
     * @param   [array]          $shop   [店铺信息]
     */
    public static function ShopDomain($config, $shop)
    {
        $domain = '';
        if(!empty($shop['domain']) && !empty($config['shop_main_domain']))
        {
            $domain = __MY_HTTP__.'://'.$shop['domain'].'.'.$config['shop_main_domain'].'/';
        }
        return $domain;
    }

    /**
     * 店铺首页地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-09
     * @desc    description
     * @param   [array]          $config [配置信息]
     * @param   [array]          $shop   [店铺信息]
     */
    public static function ShopHomeUrl($config, $shop)
    {
        if(APPLICATION_CLIENT_TYPE == 'pc')
        {
            // 默认url地址
            $url = PluginsHomeUrl('shop', 'index', 'detail', ['id'=>$shop['id']]);

            // 店铺是否开启二级域名使用
            if(!empty($shop['domain']) && !empty($config['shop_main_domain']) && isset($config['is_shop_second_domain_use']) && $config['is_shop_second_domain_use'] == 1)
            {
                $url = self::ShopDomain($config, $shop);
            }
        } else {
            $url = '/pages/plugins/shop/detail/detail?id='.$shop['id'];
        }
        return $url;
    }

    /**
     * 保证金
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-11
     * @desc    description
     * @param   [array]          $config [配置信息]
     * @param   [array]          $shop   [店铺信息]
     */
    public static function ShopBondPrice($config, $shop)
    {
        return empty($config['shop_bond_price']) || empty($config['shop_bond_price'][$shop['category_id']]) ? 0 : floatval($config['shop_bond_price'][$shop['category_id']]);
    }

    /**
     * 店铺商品搜索参数
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-25
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ShopMapParams($params = [])
    {
        // 移除多余的字段
        unset($params['pluginsname'], $params['pluginscontrol'], $params['pluginsaction']);

        return $params;
    }

    /**
     * 搜索数据URL处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-06-29
     * @desc    description
     * @param   [string]          $contro [控制器]
     * @param   [string]          $action [方法]
     * @param   [string]          $pid    [参数字段]
     * @param   [string]          $did    [参数值字段]
     * @param   [array]           $data   [数据]
     * @param   [array]           $params [输入参数]
     */
    public static function ShopSearchMapUrlHandle($contro, $action, $pid, $did, $data, $params = [])
    {
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                if(empty($v[$did]) || (isset($params[$pid]) && $params[$pid] == $v[$did]))
                {
                    $temp_params = $params;
                    unset($temp_params[$pid]);
                } else {
                    $temp_params = array_merge($params, [$pid=>$v[$did]]);
                }
                $v['url'] = PluginsHomeUrl('shop', $contro, $action, $temp_params);
            }
        }
        return $data;
    }

    /**
     * 商品搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function GoodsSearchList($params = [])
    {
        // 条件
        $where = [
            ['g.is_delete_time', '=', 0],
            ['g.is_shelves', '=', 1],
        ];

        // 类型
        $data_type = empty($params['data_type']) ? 'shop' : $params['data_type'];
        switch($data_type)
        {
            // 店铺
            case 'shop' :
                $user_id = empty($params['user_id']) ? 0 : intval($params['user_id']);
                $where[] = ['g.shop_user_id', '=', $user_id];
                break;

            // 主系统
            case 'main' :
                $user_id = empty($params['user_id']) ? 0 : intval($params['user_id']);
                $where[] = ['g.shop_id', '=', 0];
                $main_goods_ids = Db::name('PluginsShopGoodsCopyLog')->alias('pg')->join('goods g', 'g.id=pg.shop_goods_id')->where(['pg.user_id'=>$user_id])->column('pg.main_goods_id');
                if(!empty($main_goods_ids))
                {
                    $where[] = ['g.id', 'not in', array_unique($main_goods_ids)];
                }
                break;
        }

        // 关键字
        if(!empty($params['keywords']))
        {
            $where[] = ['g.title', 'like', '%'.$params['keywords'].'%'];
        }

        // 分类id
        if(!empty($params['category_id']))
        {
            $category_ids = GoodsService::GoodsCategoryItemsIds([$params['category_id']], 1);
            $category_ids[] = $params['category_id'];
            $where[] = ['gci.category_id', 'in', $category_ids];
        }

        // 指定字段
        $field = 'g.id,g.title,g.images,g.price';

        // 获取数据
        return GoodsService::CategoryGoodsList(['where'=>$where, 'm'=>0, 'n'=>100, 'field'=>$field, 'is_admin_access'=>1]);
    }
}
?>