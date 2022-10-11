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
namespace app\plugins\activity\admin;

use app\plugins\activity\admin\Common;
use app\plugins\activity\service\CategoryService;

/**
 * 活动配置 - 分类管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-29
 * @desc    description
 */
class Category extends Common
{
	/**
	 * 分类列表
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2020-09-29
	 * @desc    description
	 * @param   [array]           $params [输入参数]
	 */
	public function Index($params = [])
	{
		return MyView('../../../plugins/view/activity/admin/category/index');
	}

	/**
	 * 获取节点子列表
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2020-09-29
	 * @desc    description
	 * @param   [array]           $params [输入参数]
	 */
	public function GetNodeSon($params = [])
	{
		// 是否ajax请求
		if(!IS_AJAX)
		{
			$this->error('非法访问');
		}

		// 开始操作
		return CategoryService::CategoryNodeSon($this->data_request);
	}

	/**
	 * 分类保存
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2020-09-29
	 * @desc    description
	 * @param   [array]           $params [输入参数]
	 */
	public function Save($params = [])
	{
		// 是否ajax请求
		if(!IS_AJAX)
		{
			$this->error('非法访问');
		}

		// 开始操作
		return CategoryService::CategorySave($this->data_request);
	}

	/**
	 * 分类删除
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2020-09-29
	 * @desc    description
	 * @param   [array]           $params [输入参数]
	 */
	public function Delete($params = [])
	{
		// 是否ajax
		if(!IS_AJAX)
		{
			return $this->error('非法访问');
		}

		// 开始操作
		$params = $this->data_post;
		$params['admin'] = $this->admin;
		return CategoryService::CategoryDelete($params);
	}
}
?>