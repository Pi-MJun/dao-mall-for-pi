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
namespace app\plugins\blog\form;

use app\plugins\blog\service\BaseService;
use app\plugins\blog\service\CategoryService;

/**
 * 博客推荐动态表格
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-06-18
 * @desc    description
 */
class Recommend
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
                'search_url'    => PluginsAdminUrl('blog', 'recommend', 'index'),
                'is_delete'     => 1,
                'delete_url'    => PluginsAdminUrl('blog', 'recommend', 'delete'),
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
                    'view_key'      => '../../../plugins/view/blog/admin/recommend/module/info',
                    'grid_size'     => 'lg',
                    'search_config' => [
                        'form_type'         => 'input',
                        'form_name'         => 'title|vice_title|describe',
                        'where_type'        => 'like',
                        'placeholder'       => '请输入标题/副标题/描述'
                    ],
                ],
                [
                    'label'         => '推荐关键字',
                    'view_type'     => 'field',
                    'view_key'      => 'keywords',
                    'grid_size'     => 'sm',
                    'is_list'       => 0,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '更多指向分类',
                    'view_type'     => 'field',
                    'view_key'      => 'more_category_id',
                    'view_data_key' => 'name',
                    'view_data'     => $this->BlogCategoryList(),
                    'align'         => 'center',
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => $this->BlogCategoryList(),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '是否启用',
                    'view_type'     => 'status',
                    'view_key'      => 'is_enable',
                    'post_url'      => PluginsAdminUrl('blog', 'recommend', 'statusupdate'),
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
                    'post_url'      => PluginsAdminUrl('blog', 'recommend', 'statusupdate'),
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
                [
                    'label'         => '数据类型',
                    'view_type'     => 'field',
                    'view_key'      => 'data_type',
                    'view_data_key' => 'name',
                    'view_data'     => BaseService::$recommend_data_type_list,
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => BaseService::$recommend_data_type_list,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '排序类型',
                    'view_type'     => 'field',
                    'view_key'      => 'order_by_type',
                    'view_data_key' => 'name',
                    'view_data'     => BaseService::$recommend_order_by_type_list,
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => BaseService::$recommend_order_by_type_list,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '排序规则',
                    'view_type'     => 'field',
                    'view_key'      => 'order_by_rule',
                    'view_data_key' => 'name',
                    'view_data'     => BaseService::$recommend_order_by_rule_list,
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => BaseService::$recommend_order_by_rule_list,
                        'data_key'          => 'value',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '指定分类',
                    'view_type'     => 'field',
                    'view_key'      => 'data_auto_category_id',
                    'view_data_key' => 'name',
                    'view_data'     => $this->BlogCategoryList(),
                    'align'         => 'center',
                    'search_config' => [
                        'form_type'         => 'select',
                        'where_type'        => 'in',
                        'data'              => $this->BlogCategoryList(),
                        'data_key'          => 'id',
                        'data_name'         => 'name',
                        'is_multiple'       => 1,
                    ],
                ],
                [
                    'label'         => '展示数量',
                    'view_type'     => 'field',
                    'view_key'      => 'data_auto_number',
                    'search_config' => [
                        'form_type'         => 'section',
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
                    'view_key'      => '../../../plugins/view/blog/admin/recommend/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                ],
            ],
        ];
    }

    /**
     * 博文分类
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-20
     * @desc    description
     */
    public function BlogCategoryList()
    {
        $ret = CategoryService::CategoryList(['field'=>'id,name']);
        return empty($ret['data']) ? [] : array_column($ret['data'], null, 'id');
    }
}
?>