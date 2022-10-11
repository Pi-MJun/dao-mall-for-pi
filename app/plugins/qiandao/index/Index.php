<?php
namespace app\plugins\qiandao\index;

use app\service\UserService;
use app\plugins\qiandao\service\BaseService;

/**
 * 积分签到 - 前端独立页面入口
 */
class Index extends Common
{
    // 前端页面入口
    public function index($params = [])
    {
		// 获取签到信息
        $params = $this->data_post;
		
		// 得到用户信息
		$params['user_id'] = !empty($this->user) ? $this->user['id'] : 0;

        // 结果
        $data = BaseService::LastQianDaoInfo($params);
		
        // 数组组装
        MyViewAssign('data', $data['data']);
		
		$ret = BaseService::GetUserInfo($params);
		MyViewAssign('user_base_info', $ret['data']);
        return MyView('../../../plugins/view/qiandao/index/index/index');
    }
	
	/**
     * [签到]
     */
    public function QianDao()
    {
		// 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }
		
		// 是否登录
        $this->IsLogin();
		
        // 参数
        $params = $this->data_post;
		$params['user_id'] = $this->user['id'];
		
        // 结果
        return BaseService::QianDao($params);
    }
}
?>