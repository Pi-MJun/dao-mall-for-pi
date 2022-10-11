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
namespace app\plugins\activity\form;

use app\plugins\activity\service\BaseService;
use app\plugins\activity\service\CategoryService;

/**
 * 活动管理动态表格
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-06-18
 * @desc    description
 */
class Activity
{
    // 基础条件
    public $condition_base = [];

    /**
     * 入口
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-16
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
                'search_url'    => PluginsAdminUrl('activity', 'activity', 'index'),
                'is_delete'     => 1,
                'delete_url'    => PluginsAdminUrl('activity', 'activity', 'delete'),
                'delete_key'    => 'ids',
                'detail_title'  => '基础信息',
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
                    'label'         => '基础信息',
                    'view_type'     => 'module',
                    'view_key'      => '../../../plugins/view/activity/admin/activity/module/info',
                    'grid_size'     => 'lg',
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'title|vice_title',
                        'where_type'        => 'like',
                        'placeholder'       => '请输入标题/副标题'
                    ],
                ],
                [
                    'label'         => '描述',
                    'view_type'     => 'field',
                    'view_key'      => 'describe',
                    'grid_size'     => 'lg',
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '分类',
                    'view_type'     => 'field',
                    'view_key'      => 'activity_category_name',
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'activity_category_id',
                        'where_type'        => 'in',
                        'data'              => $this->ActivityCategoryList(),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '是否启用',
                    'view_type'     => 'status',
                    'view_key'      => 'is_enable',
                    'post_url'      => PluginsAdminUrl('activity', 'activity', 'statusupdate'),
                    'is_form_su'    => 1,
                    'align'         => 'center',
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_is_enable_list'),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '首页展示',
                    'view_type'     => 'status',
                    'view_key'      => 'is_home',
                    'post_url'      => PluginsAdminUrl('activity', 'activity', 'statusupdate'),
                    'align'         => 'center',
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => MyConst('common_is_enable_list'),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '首页数据位置',
                    'view_type'     => 'field',
                    'view_key'      => 'home_data_location',
                    'view_data_key' => 'name',
                    'view_data'     => BaseService::$home_floor_location_list,
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => BaseService::$home_floor_location_list,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '样式类型',
                    'view_type'     => 'field',
                    'view_key'      => 'style_type',
                    'view_data_key' => 'name',
                    'view_data'     => BaseService::$recommend_style_type_list,
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => BaseService::$recommend_style_type_list,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                // [
                //     'label'         => '商品详情页展示',
                //     'view_type'     => 'status',
                //     'view_key'      => 'is_goods_detail',
                //     'post_url'      => PluginsAdminUrl('activity', 'activity', 'statusupdate'),
                //     'align'         => 'center',
                //     'search_config' => [
                //         'form_type'         => 'select',
                //         'where_type'        => 'in',
                //         'data'              => MyConst('common_is_enable_list'),
                //         'data_key'          => 'id',
                //         'data_name'         => 'name',
                //         'is_multiple'       => 1,
                //     ],
                // ],
                [
                    'label'         => '起始时间',
                    'view_type'     => 'field',
                    'view_key'      => 'time_start',
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '结束时间',
                    'view_type'     => 'field',
                    'view_key'      => 'time_end',
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '推荐关键字',
                    'view_type'     => 'field',
                    'view_key'      => 'keywords',
                    'grid_size'     => 'sm',
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '商品数量',
                    'view_type'     => 'field',
                    'view_key'      => 'goods_count',
                    'search_config' => [
                        'form_type'         => 'section',
                    ],
                ],
                [
                    'label'         => '访问次数',
                    'view_type'     => 'field',
                    'view_key'      => 'access_count',
                    'search_config' => [
                        'form_type'         => 'section',
                    ],
                ],
                [
                    'label'         => '排序',
                    'view_type'     => 'field',
                    'view_key'      => 'sort',
                ],
                [
                    'label'         => '创建时间',
                    'view_type'     => 'field',
                    'view_key'      => 'add_time',
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '更新时间',
                    'view_type'     => 'field',
                    'view_key'      => 'upd_time',
                    'search_config' => [
                        'form_type'         => 'datetime',
                    ],
                ],
                [
                    'label'         => '操作',
                    'view_type'     => 'operate',
                    'view_key'      => '../../../plugins/view/activity/admin/activity/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                ],
            ],
        ];
    }

    /**
     * 获取分类列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-11
     * @desc    description
     */
    public function ActivityCategoryList()
    {
        $res = CategoryService::CategoryList(['field'=>'id,name']);
        return $res['data'];
    }
}
?>