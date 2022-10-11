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
namespace app\plugins\distribution\form\admin;

use think\facade\Db;
use app\plugins\distribution\service\BaseService;

/**
 * 取货点订单动态表格
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-06-28
 * @desc    description
 */
class ExtractionOrder
{
    // 基础条件
    public $condition_base = [];

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
                'search_url'    => PluginsAdminUrl('distribution', 'extractionorder', 'index'),
            ],
            // 表单配置
            'form' => [
                [
                    'label'             => '用户信息',
                    'view_type'         => 'module',
                    'view_key'          => 'lib/module/user',
                    'grid_size'         => 'sm',
                    'is_sort'           => 1,
                    'params_where_name' => 'uid',
                    'search_config'     => [
                        'form_type'             => 'input',
                        'form_name'             => 'po.user_id',
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
                    'search_config'     => [
                        'form_type'             => 'input',
                        'form_name'             => 'o.id',
                        'where_type_custom'     => 'in',
                        'where_value_custom'    => 'WhereValueOrderInfo',
                        'placeholder'           => '请输入订单号/取货码',
                    ],
                ],
                [
                    'label'         => '支付金额(π)',
                    'view_type'     => 'field',
                    'view_key'      => 'pay_price',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'section',
                        'form_name'         => 'o.pay_price',
                        'is_point'          => 1,
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
                    'label'         => '取货状态',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/distribution/admin/extractionorder/module/status',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'po.status',
                        'where_type'        => 'in',
                        'data'              => BaseService::$order_status_list,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '订单时间',
                    'view_type'     => 'field',
                    'view_key'      => 'add_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                        'form_name'         => 'po.add_time',
                    ],
                ],
                [
                    'label'         => '操作',
                    'view_type'     => 'operate',
                    'view_key'      => '../../../plugins/view/distribution/admin/extractionorder/module/operate',
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
            $ids = Db::name('User')->where('username|nickname|mobile|email', 'like', '%'.$value.'%')->column('id');

            // 避免空条件造成无效的错觉
            return empty($ids) ? [0] : $ids;
        }
        return $value;
    }

    /**
     * 订单信息条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-26
     * @desc    description
     * @param   [string]          $value    [条件值]
     * @param   [array]           $params   [输入参数]
     */
    public function WhereValueOrderInfo($value, $params = [])
    {
        if(!empty($value))
        {
            if(strlen($value) != 4)
            {
                $oid = Db::name('Order')->where(['order_no'=>trim($value)])->value('id');
                $is_keywords = true;
            } else {
                // 取件码
                $oid = Db::name('OrderExtractionCode')->where(['code'=>trim($value)])->value('order_id');
            }

            // 避免空条件造成无效的错觉
            return empty($oid) ? [0] : [$oid];
        }
        return $value;
    }
}
?>