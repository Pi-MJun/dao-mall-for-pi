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

/**
 * 分销等级动态表格-管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-05-16
 * @desc    description
 */
class Level
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
                'status_field'  => 'is_enable',
                'is_search'     => 1,
                'search_url'    => PluginsAdminUrl('distribution', 'level', 'index'),
            ],
            // 表单配置
            'form' => [
                [
                    'label'         => '图标/名称',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/distribution/admin/level/module/info',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'name',
                        'where_type'        => 'like',
                        'placeholder'       => '请输入名称',
                    ],
                ],
                [
                    'label'         => '等级规则',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/distribution/admin/level/module/rules',
                ],
                [
                    'label'         => '分销佣金',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/distribution/admin/level/module/rate',
                ],
                [
                    'label'         => '向下返佣',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/distribution/admin/level/module/down_rate',
                ],
                [
                    'label'         => '自购返佣',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/distribution/admin/level/module/self_buy_rate',
                ],
                [
                    'label'         => '强制返佣至取货点',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/distribution/admin/level/module/force_current_rate',
                ],
                [
                    'label'         => '是否启用',
                    'view_type'     => 'status',
                    'view_key'      => 'is_enable',
                    'post_url'      => PluginsAdminUrl('distribution', 'level', 'statusupdate'),
                    'is_form_su'    => 1,
                    'align'         => 'center',
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_is_text_list'),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '自动分配等级',
                    'view_type'     => 'status',
                    'view_key'      => 'is_level_auto',
                    'post_url'      => PluginsAdminUrl('distribution', 'level', 'statusupdate'),
                    'align'         => 'center',
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_is_text_list'),
                        'data_key'          => 'id',
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
                        'form_name'         => 'add_time',
                    ],
                ],
                [
                    'label'         => '更新时间',
                    'view_type'     => 'field',
                    'view_key'      => 'upd_time_time',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'datetime',
                        'form_name'         => 'upd_time',
                    ],
                ],
                [
                    'label'         => '操作',
                    'view_type'     => 'operate',
                    'view_key'      => '../../../plugins/view/distribution/admin/level/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                ],
            ],
        ];
    }
}
?>