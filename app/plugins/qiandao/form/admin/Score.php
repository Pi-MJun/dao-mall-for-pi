<?php
namespace app\plugins\qiandao\form\admin;

use think\facade\Db;
use app\plugins\qiandao\service\ScoreService;

/**
 * 互赠列表动态表格
 * @author  风车车
 * @date    2022-08-04
 * @desc    description
 */
class Score
{
    // 基础条件
    public $condition_base = [];

    /**
     * @param   [array]           $params [输入参数]
     */
    public function __construct($params = [])
    {
        
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
            'search_url'    => PluginsAdminUrl('qiandao', 'score', 'index'),
            'detail_title'  => '基础信息',
            'is_middle'     => 0,
        ];

        // 表单配置
        $form = [
			[
				'label'         => '赠送者',
				'view_type'     => 'module',
				'view_key'      => '../../../plugins/view/qiandao/index/score/userfrom',
				'grid_size'     => 'sm',
				'is_sort'       => 1,
				'search_config' => [
					'form_type'             => 'input',
					'form_name'             => 'fromwho',
					'where_type_custom'     => 'in',
					'where_value_custom'    => 'WhereValueUserInfo',
					'placeholder'           => '请输入Pi用户名/昵称',
				],
			],
			[
				'label'         => '接受者',
				'view_type'     => 'module',
				'view_key'      => '../../../plugins/view/qiandao/index/score/user',
				'grid_size'     => 'sm',
				'is_sort'       => 1,
				'search_config' => [
					'form_type'             => 'input',
					'form_name'             => 'towho',
					'where_type_custom'     => 'in',
					'where_value_custom'    => 'WhereValueUserInfo',
					'placeholder'           => '请输入Pi用户名/昵称',
				],
			],
			[
				'label'         => '转增数量',
				'view_type'     => 'field',
				'view_key'      => 'num',
				'is_sort'       => 1,
				'search_config' => [
					'form_type'         => 'section',
					'is_point'          => 1,
				],
			],
            [
                'label'         => '转增时间',
                'view_type'     => 'field',
                'view_key'      => 'add_time',
                'is_sort'       => 1,
                'search_config' => [
                    'form_type'         => 'datetime',
                ],
            ]
        ];

        return [
            'base'  => $base,
            'form'  => $form,
        ];
    }
	
	/**
     * 用户信息条件处理
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
}
?>