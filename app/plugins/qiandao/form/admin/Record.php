<?php
namespace app\plugins\qiandao\form\admin;

use app\plugins\qiandao\service\BaseService;
/**
 * 签到列表动态表格
 * @author  风车车
 * @date    2021-05-01
 * @desc    description
 */
class Record
{
    // 基础条件
    public $condition_base = [];

    /**
     * @param   [array]           $params [输入参数]
     */
    public function __construct($params = [])
    {
        // 用户信息
        if(!empty($params['system_user']))
        {
            $this->condition_base[] = ['user_id', '=', $params['system_user']['id']];
        }
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
        // 基础配置
        $base = [
            'key_field'     => 'id',
            'is_search'     => 1,
            'search_url'    => PluginsHomeUrl('qiandao', 'record', 'index'),
            'detail_title'  => '基础信息',
            'is_middle'     => 0,
        ];

        // 表单配置
        $form = [
            [
                'label'         => '签到时间',
                'view_type'     => 'field',
                'view_key'      => 'add_time',
                'is_sort'       => 1,
                'search_config' => [
                    'form_type'         => 'datetime',
                ],
            ],
			[
				'label'         => '奖励类型',
				'view_type'     => 'field',
				'view_key'      => 'type',
				'view_data_key' => 'name',
				'view_data'     => BaseService::$get_type,
				'is_sort'       => 1,
				'search_config' => [
					'form_type'         => 'select',
					'where_type'        => 'in',
					'data'              => BaseService::$get_type,
					'data_key'          => 'id',
					'data_name'         => 'name',
					'is_multiple'       => 1,
				],
			],
            [
                'label'         => '奖励描述',
                'view_type'     => 'field',
                'view_key'      => 'type_name',
                'search_config' => [
                   
                ],
            ]
        ];

        return [
            'base'  => $base,
            'form'  => $form,
        ];
    }
}
?>