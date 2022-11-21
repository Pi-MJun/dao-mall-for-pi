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
namespace app\plugins\shop\form\index;

use think\facade\Db;
use app\service\UserService;
use app\service\PaymentService;
use app\service\ExpressService;
use app\plugins\shop\service\BaseService;

/**
 * 店铺订单动态表格-用户
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-06-08
 * @desc    description
 */
class Order
{
    // 基础条件
    public $condition_base = [
        ['is_delete_time', '=', 0],
        ['shop_is_delete_time', '=', 0],
    ];

    // 当前用户id
    public $user_id;

    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function __construct($params = [])
    {
        // 当前用户
        $user = UserService::LoginUserInfo();
        $this->user_id = empty($user['id']) ? 0 : $user['id'];
        $this->condition_base[] = ['shop_user_id', '=', $this->user_id];
    }

    /**
     * 入口
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Run($params = [])
    {
        $data = [
            // 基础配置
            'base' => [
                'key_field'     => 'id',
                'is_search'     => 1,
                'search_url'    => PluginsHomeUrl('shop', 'order', 'index'),
                'detail_title'  => '基础信息',
                'is_middle'     => 0,
            ],
            // 表单配置
            'form' => [
                [
                    'label'         => '基础信息',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/index/order/module/goods',
                    'grid_size'     => 'xl',
                    'is_detail'     => 0,
                    'search_config' => [
                        'form_type'             => 'input',
                        'form_name'             => 'id',
                        'where_type_custom'     => 'in',
                        'where_value_custom'    => 'WhereBaseGoodsInfo',
                        'placeholder'           => '请输入订单ID/订单号/商品名称/型号',
                    ],
                ],
                [
                    'label'         => '用户信息',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/index/public/module/user',
                    'grid_size'     => 'sm',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'             => 'input',
                        'form_name'             => 'user_id',
                        'where_type_custom'     => 'in',
                        'where_value_custom'    => 'WhereValueUserInfo',
                        'placeholder'           => '请输入用户名/昵称/手机/邮箱',
                    ],
                ],
                [
                    'label'         => '订单状态',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/index/order/module/status',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'status',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_order_status'),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '支付状态',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/index/order/module/pay_status',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'pay_status',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_order_pay_status'),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '总价(π)',
                    'view_type'     => 'field',
                    'view_key'      => 'total_price',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '支付金额(π)',
                    'view_type'     => 'field',
                    'view_key'      => 'pay_price',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '单价(π)',
                    'view_type'     => 'field',
                    'view_key'      => 'price',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '订单模式',
                    'view_type'     => 'field',
                    'view_key'      => 'order_model',
                    'view_data_key' => 'name',
                    'view_data'     => MyConst('common_site_type_list'),
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_site_type_list'),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '来源',
                    'view_type'     => 'field',
                    'view_key'      => 'client_type',
                    'view_data_key' => 'name',
                    'view_data'     => MyConst('common_platform_type'),
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_platform_type'),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '地址信息',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/index/order/module/address',
                    'grid_size'     => 'sm',
                    'is_detail'     => 0,
                    'search_config' => [
                        'form_type'             => 'input',
                        'form_name'             => 'id',
                        'where_type_custom'     => 'in',
                        'where_value_custom'    => 'WhereValueAddressInfo',
                    ],
                ],
                [
                    'label'         => '取货信息',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/index/order/module/take',
                    'width'         => 125,
                    'is_detail'     => 0,
                    'search_config' => [
                        'form_type'             => 'input',
                        'form_name'             => 'id',
                        'where_type_custom'     => 'in',
                        'where_value_custom'    => 'WhereValueTakeInfo',
                    ],
                ],
                [
                    'label'         => '退款金额(π)',
                    'view_type'     => 'field',
                    'view_key'      => 'refund_price',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '退货数量',
                    'view_type'     => 'field',
                    'view_key'      => 'returned_quantity',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                    ],
                ],
                [
                    'label'         => '购买总数',
                    'view_type'     => 'field',
                    'view_key'      => 'buy_number_count',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                    ],
                ],
                [
                    'label'         => '增加金额(π)',
                    'view_type'     => 'field',
                    'view_key'      => 'increase_price',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '优惠金额(π)',
                    'view_type'     => 'field',
                    'view_key'      => 'preferential_price',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '支付方式',
                    'view_type'     => 'field',
                    'view_key'      => 'payment_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'payment_id',
                        'where_type'        => 'in',
                        'data'              => PaymentService::PaymentList(),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '用户备注',
                    'view_type'     => 'field',
                    'view_key'      => 'user_note',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '扩展信息',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/index/order/module/extension',
                    'grid_size'     => 'sm',
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'extension_data',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '快递公司',
                    'view_type'     => 'field',
                    'view_key'      => 'express_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'express_id',
                        'data'              => ExpressService::ExpressList(),
                        'where_type'        => 'in',
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '快递单号',
                    'view_type'     => 'field',
                    'view_key'      => 'express_number',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '最新售后',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/index/order/module/aftersale',
                    'grid_size'     => 'sm',
                ],
                [
                    'label'         => '用户是否评论',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/index/order/module/is_comments',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'             => 'select',
                        'where_type'            => 'in',
                        'form_name'             => 'user_is_comments',
                        'data'                  => MyConst('common_is_text_list'),
                        'data_key'              => 'id',
                        'data_name'             => 'name',
                        'where_type_custom'     => 'WhereTypyUserIsComments',
                        'where_value_custom'    => 'WhereValueUserIsComments',
                        'is_multiple'           => 1,
                    ],
                ],
                [
                    'label'         => '确认时间',
                    'view_type'     => 'field',
                    'view_key'      => 'confirm_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '支付时间',
                    'view_type'     => 'field',
                    'view_key'      => 'pay_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '发货时间',
                    'view_type'     => 'field',
                    'view_key'      => 'delivery_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '完成时间',
                    'view_type'     => 'field',
                    'view_key'      => 'collect_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '取消时间',
                    'view_type'     => 'field',
                    'view_key'      => 'cancel_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '关闭时间',
                    'view_type'     => 'field',
                    'view_key'      => 'close_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '创建时间',
                    'view_type'     => 'field',
                    'view_key'      => 'add_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '更新时间',
                    'view_type'     => 'field',
                    'view_key'      => 'upd_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '操作',
                    'view_type'     => 'operate',
                    'view_key'      => '../../../plugins/view/shop/index/order/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                ],
            ],
        ];

        // 是否开启商家审核接收
        $base = BaseService::BaseConfig();
        if(!empty($base['data']) && isset($base['data']['is_shop_order_confirm']) && $base['data']['is_shop_order_confirm'] == 1)
        {
            $item = [
                [
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
                ]
            ];
            array_splice($data['form'], 2, 0, $item);
        }

        return $data;
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
     * 评论条件符号处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-08
     * @desc    description
     * @param   [string]          $form_key     [表单数据key]
     * @param   [array]           $params       [输入参数]
     */
    public function WhereTypyUserIsComments($form_key, $params = [])
    {
        if(isset($params[$form_key]))
        {
            // 条件值是 0,1
            // 解析成数组，都存在则返回null，则1 >， 0 =
            $value = explode(',', urldecode($params[$form_key]));
            if(count($value) == 1)
            {
                return in_array(1, $value) ? '>' : '=';
            }
        }
        return null;
    }

    /**
     * 评论条件值处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-08
     * @desc    description
     * @param   [string]          $form_key     [表单数据key]
     * @param   [array]           $params       [输入参数]
     */
    public function WhereValueUserIsComments($value, $params = [])
    {
        return (count($value) == 2) ? null : 0;
    }

    /**
     * 取货码条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-08
     * @desc    description
     * @param   [string]          $value    [条件值]
     * @param   [array]           $params   [输入参数]
     */
    public function WhereValueTakeInfo($value, $params = [])
    {
        if(!empty($value))
        {
            // 获取订单 id
            $ids = Db::name('OrderExtractionCode')->where('code', '=', $value)->column('order_id');

            // 避免空条件造成无效的错觉
            return empty($ids) ? [0] : $ids;
        }
        return $value;
    }

    /**
     * 收件地址条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-08
     * @desc    description
     * @param   [string]          $value    [条件值]
     * @param   [array]           $params   [输入参数]
     */
    public function WhereValueAddressInfo($value, $params = [])
    {
        if(!empty($value))
        {
            // 获取订单 id
            $ids = Db::name('OrderAddress')->where('name|tel|address', 'like', '%'.$value.'%')->column('order_id');

            // 避免空条件造成无效的错觉
            return empty($ids) ? [0] : $ids;
        }
        return $value;
    }

    /**
     * 用户信息条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-08
     * @desc    description
     * @param   [string]          $value    [条件值]
     * @param   [array]           $params   [输入参数]
     */
    public function WhereValueUserInfo($value, $params = [])
    {
        if(!empty($value))
        {
            // 获取用户 id
            $ids = Db::name('User')->where('username|nickname|mobile|email', 'like', '%'.$value.'%')->column('id');

            // 避免空条件造成无效的错觉
            return empty($ids) ? [0] : $ids;
        }
        return $value;
    }

    /**
     * 基础条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-08
     * @desc    description
     * @param   [string]          $value    [条件值]
     * @param   [array]           $params   [输入参数]
     */
    public function WhereBaseGoodsInfo($value, $params = [])
    {
        if(!empty($value))
        {
            // 订单ID、订单号
            $where = [
                ['shop_user_id', '=', $this->user_id],
                ['id|order_no', '=', $value],
            ];
            $ids = Db::name('Order')->where($where)->column('id');

            // 获取订单详情搜索的订单 id
            if(empty($ids))
            {
                $temp_ids = Db::name('OrderDetail')->where('title|model', 'like', '%'.$value.'%')->column('order_id');
                if(!empty($temp_ids))
                {
                    // 订单ID
                    $where = [
                        ['shop_user_id', '=', $this->user_id],
                        ['id', 'in', $temp_ids],
                    ];
                    $ids = Db::name('Order')->where($where)->column('id');
                }
            }

            // 避免空条件造成无效的错觉
            return empty($ids) ? [0] : $ids;
        }
        return $value;
    }
}
?>