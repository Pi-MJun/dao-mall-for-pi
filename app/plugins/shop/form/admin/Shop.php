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
namespace app\plugins\shop\form\admin;

use think\facade\Db;
use app\plugins\shop\service\BaseService;
use app\plugins\shop\service\ShopCategoryService;

/**
 * 店铺动态表格-管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-05-16
 * @desc    description
 */
class Shop
{
    // 基础条件
    public $condition_base = [];

    /**
     * 入口
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-05-16
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
                'search_url'    => PluginsAdminUrl('shop', 'shop', 'index'),
                'is_delete'     => 1,
                'delete_url'    => PluginsAdminUrl('shop', 'shop', 'delete'),
                'delete_key'    => 'ids',
                'detail_title'  => '基础信息',
                'is_middle'     => 0,
            ],
            // 表单配置
            'form' => [
                [
                    'view_type'         => 'checkbox',
                    'is_checked'        => 0,
                    'checked_text'      => '反选',
                    'not_checked_text'  => '全选',
                    'align'             => 'center',
                    'width'             => 80,
                ],
                [
                    'label'         => '用户信息',
                    'view_type'     => 'module',
                    'view_key'      => 'lib/module/user',
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
                    'label'         => 'logo/banner',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/admin/shop/module/logo',
                    'width'         => '230',
                ],
                [
                    'label'         => '系统类型',
                    'view_type'     => 'field',
                    'view_key'      => 'system_type',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => $this->SystemTypeList(),
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '名称',
                    'view_type'     => 'field',
                    'view_key'      => 'name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '简介',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/admin/shop/module/describe',
                    'grid_size'     => 'sm',
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'describe',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '店铺分类',
                    'view_type'     => 'field',
                    'view_key'      => 'category_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'category_id',
                        'where_type'        => 'in',
                        'data'              => $this->ShopCategoryList(),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '首页数据模式',
                    'view_type'     => 'field',
                    'view_key'      => 'data_model_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'data_model',
                        'where_type'        => 'in',
                        'data'              => BaseService::$plugins_shop_data_model_list,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '状态',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/admin/shop/module/status',
                    'align'         => 'center',
                    'width'         => 120,
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'status',
                        'where_type'        => 'in',
                        'data'              => BaseService::$plugins_shop_status_list,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '截止时间',
                    'view_type'     => 'field',
                    'view_key'      => 'expire_time_text',
                    'search_config' => [
                        'form_type'         => 'datetime',
                        'form_name'         => 'expire_time',
                    ],
                ],
                [
                    'label'         => '二级域名',
                    'view_type'     => 'field',
                    'view_key'      => 'domain',
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'         => '=',
                    ],
                ],
                [
                    'label'         => '域名修改次数',
                    'view_type'     => 'field',
                    'view_key'      => 'domain_edit_number',
                    'search_config' => [
                        'form_type'         => 'section',
                    ],
                ],
                [
                    'label'         => '店铺信息',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/admin/shop/module/shop_info',
                    'grid_size'     => 'lg',
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'contacts_name|contacts_tel|address',
                        'where_type'        => 'like',
                        'placeholder'       => '请输入联系人姓名/电话/详细地址'
                    ],
                ],
                [
                    'label'         => '认证类型',
                    'view_type'     => 'field',
                    'view_key'      => 'auth_type_name',
                    'align'         => 'center',
                    'width'         => 115,
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'auth_type',
                        'where_type'        => 'in',
                        'data'              => BaseService::$plugins_auth_type_list,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '身份证信息',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/admin/shop/module/idcard_info',
                    'grid_size'     => 'sm',
                    'is_sort'       => 1,
                    'sort_field'    => 'idcard_number',
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'idcard_name|idcard_number',
                        'where_type'        => 'like',
                        'placeholder'       => '请输入身份证姓名/号码'
                    ],
                ],
                [
                    'label'         => '企业信息',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/admin/shop/module/company_info',
                    'grid_size'     => 'sm',
                    'is_sort'       => 1,
                    'sort_field'    => 'company_number',
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'company_name|company_number',
                        'where_type'        => 'like',
                        'placeholder'       => '请输入企业名称/号码'
                    ],
                ],
                [
                    'label'         => '更多材料附件',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/admin/shop/module/more_prove',
                    'grid_size'     => 'sm',
                ],
                [
                    'label'         => '客服服务',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/admin/shop/module/service_info',
                    'grid_size'     => 'sm',
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'service_qq|service_tel',
                        'where_type'        => 'like',
                        'placeholder'       => '请输入客服QQ/电话'
                    ],
                ],
                [
                    'label'         => '接收通知',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/admin/shop/module/notice_info',
                    'grid_size'     => 'sm',
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'notice_mobile|notice_email',
                        'where_type'        => 'like',
                        'placeholder'       => '请输入接收通知手机/邮箱'
                    ],
                ],
                [
                    'label'         => '结算信息',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/shop/admin/shop/module/settle_info',
                    'width'         => 220,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'settle_type',
                        'where_type'        => 'in',
                        'data'              => BaseService::$plugins_settle_type,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                        'placeholder'       => '请选择结算类型'
                    ],
                ],
                [
                    'label'         => '原因',
                    'view_type'     => 'field',
                    'view_key'      => 'fail_reason',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
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
                    'view_key'      => '../../../plugins/view/shop/admin/shop/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                ],
            ],
        ];
    }

    /**
     * 店铺分类
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-05
     * @desc    description
     */
    public function ShopCategoryList()
    {
        $ret = ShopCategoryService::ShopCategoryAll(['field'=>'id,name']);
        return empty($ret['data']) ? [] : $ret['data'];
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
     * 系统类型列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-04-14
     * @desc    description
     */
    public function SystemTypeList()
    {
        return Db::name('PluginsShop')->group('system_type')->column('system_type', 'system_type');
    }
}
?>