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
namespace app\plugins\distribution\form\index;

use think\facade\Db;
use app\service\UserService;
use app\plugins\distribution\service\BaseService;

/**
 * 商家佣金订单动态表格
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-06-28
 * @desc    description
 */
class ShopProfit
{
    // 基础条件
    public $condition_base = [];

    // 当前用户id
    public $user_id;

    /**
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function __construct($params = [])
    {
        // 用户信息
        $user = UserService::LoginUserInfo();
        $this->user_id = empty($user['id']) ? 0 : $user['id'];
        $this->condition_base[] = ['o.shop_user_id', '=', $this->user_id];
    }

    /**
     * 入口
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Run($params = [])
    {
        return [
            // 基础配置
            'base' => [
                'key_field'     => 'id',
                'is_search'     => 1,
                'search_url'    => PluginsHomeUrl('distribution', 'shopprofit', 'index'),
            ],
            // 表单配置
            'form' => [
                [
                    'label'             => '用户信息',
                    'view_type'         => 'module',
                    'view_key'          => '../../../plugins/view/distribution/index/public/module/user',
                    'width'             => 220,
                    'is_sort'           => 1,
                    'params_where_name' => 'uid',
                    'search_config'     => [
                        'form_type'             => 'input',
                        'form_name'             => 'pdl.user_id',
                        'where_type_custom'     => 'in',
                        'where_value_custom'    => 'WhereValueUserInfo',
                        'placeholder'           => '请输入用户名/昵称/手机/邮箱',
                    ],
                ],
                [
                    'label'         => '订单号',
                    'view_type'     => 'field',
                    'view_key'      => 'order_no',
                    'width'         => 170,
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'o.order_no',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '订单金额(π)',
                    'view_type'     => 'field',
                    'view_key'      => 'total_price',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'form_name'         => 'o.total_price',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '退款金额(π)',
                    'view_type'     => 'field',
                    'view_key'      => 'refund_price',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'form_name'         => 'o.refund_price',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '收益金额(π)',
                    'view_type'     => 'field',
                    'view_key'      => 'profit_price',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'form_name'         => 'pdl.profit_price',
                        'is_point'          => 1,
                    ],
                ],
                [
                    'label'         => '当前级别',
                    'view_type'     => 'field',
                    'view_key'      => 'level_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'pdl.level',
                        'where_type'        => 'in',
                        'data'              => BaseService::$level_name_list,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '结算状态',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/distribution/index/shopprofit/module/status',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'o.status',
                        'where_type'        => 'in',
                        'data'              => BaseService::$profit_status_list,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '订单状态',
                    'view_type'     => 'field',
                    'view_key'      => 'order_status_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'o.status',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_order_status'),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '订单支付状态',
                    'view_type'     => 'field',
                    'view_key'      => 'order_pay_status_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'o.pay_status',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_order_pay_status'),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '来源终端',
                    'view_type'     => 'field',
                    'view_key'      => 'order_client_type',
                    'view_data_key' => 'name',
                    'view_data'     => MyConst('common_platform_type'),
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'o.client_type',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_platform_type'),
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '创建时间',
                    'view_type'     => 'field',
                    'view_key'      => 'add_time_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                        'form_name'         => 'pdl.add_time',
                    ],
                ],
                [
                    'label'         => '更新时间',
                    'view_type'     => 'field',
                    'view_key'      => 'upd_time_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                        'form_name'         => 'pdl.upd_time',
                    ],
                ],
                [
                    'label'         => '操作',
                    'view_type'     => 'operate',
                    'view_key'      => '../../../plugins/view/distribution/index/shopprofit/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                ],
            ],
        ];
    }

    /**
     * 用户信息条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-26
     * @desc    description
     * @param   [string]          $value    [条件值]
     * @param   [array]           $params   [输入参数]
     */
    public function WhereValueUserInfo($value, $params = [])
    {
        if(!empty($value))
        {
            // 获取用户 id
            $ids = Db::name('User')->whereOr('id', '=', $value)->whereOr('username|nickname|mobile|email', 'like', '%'.$value.'%')->column('id');

            // 避免空条件造成无效的错觉
            return empty($ids) ? [0] : $ids;
        }
        return $value;
    }
}
?>