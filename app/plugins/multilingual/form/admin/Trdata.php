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
namespace app\plugins\multilingual\form\admin;

use app\plugins\multilingual\service\MultilingualService;

/**
 * 翻译数据动态表格
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-11-18
 * @desc    description
 */
class TrData
{
    // 基础条件
    public $condition_base = [];

    // 语言列表
    public $multilingual_list;

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
        $ret = MultilingualService::MultilingualList();
        $this->multilingual_list = empty($ret['data']) ? [] : array_column($ret['data'], 'name', 'code');
    }

    /**
     * 入口
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
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
                'search_url'    => PluginsAdminUrl('multilingual', 'trdata', 'index'),
                'is_delete'     => 1,
                'delete_url'    => PluginsAdminUrl('multilingual', 'trdata', 'delete'),
                'delete_key'    => 'ids',
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
                    'label'         => 'md5key值',
                    'view_type'     => 'field',
                    'view_key'      => 'md5_key',
                    'width'         => 250,
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => '=',
                    ],
                ],
                [
                    'label'         => '原始类型',
                    'view_type'     => 'field',
                    'view_key'      => 'from_type_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'from_type',
                        'where_type'        => '=',
                        'data'              => $this->multilingual_list,
                        'is_seat_select'    => 1,
                        'seat_select_text'  => '请选择原始类型',
                    ],
                ],
                [
                    'label'         => '翻译类型',
                    'view_type'     => 'field',
                    'view_key'      => 'to_type_name',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'select',
                        'form_name'         => 'to_type',
                        'where_type'        => '=',
                        'data'              => $this->multilingual_list,
                        'is_seat_select'    => 1,
                        'seat_select_text'  => '请选择翻译类型',
                    ],
                ],
                [
                    'label'         => '原始的值',
                    'view_type'     => 'field',
                    'view_key'      => 'from_value',
                    'grid_size'     => 'sm',
                    'is_sort'       => 1,
                    'search_config' => [
                        'form_type'         => 'input',
                        'where_type'        => 'like',
                    ],
                ],
                [
                    'label'         => '翻译的值',
                    'view_type'     => 'field',
                    'view_key'      => 'to_value',
                    'grid_size'     => 'sm',
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
                    'view_key'      => '../../../plugins/view/multilingual/admin/trdata/module/operate',
                    'align'         => 'center',
                    'fixed'         => 'right',
                ],
            ],
        ];
    }
}
?>