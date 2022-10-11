<?php
// +----------------------------------------------------------------------
// | 积分签到插件api
namespace app\plugins\qiandao\api;

use app\service\PluginsService;
use app\service\BuyService;
use app\service\GoodsService;
use app\plugins\qiandao\api\Common;
use app\plugins\qiandao\service\BaseService;
use app\plugins\qiandao\service\ScoreService;

/**
 * 签到 by 风车车 
 */
class Index extends Common
{
	/**
	 * [__construct 构造方法]
	 */
	public function __construct()
    {
		// 调用父类前置方法
		parent::__construct();
	}

	/**
	 * [Index 入口]
	 */
	public function Index()
	{
		// 是否登录
        $this->IsLogin(); 
		
		/// 参数
        $params = $this->data_post;
        $params['user'] = $this->user;
        $params['user_type'] = 'user';

        // 分页
        $number = 10;
        $page = max(1, isset($this->data_post['page']) ? intval($this->data_post['page']) : 1);

        // 条件
        $where = BaseService::QianDaoListWhere($params);

        // 获取总数
        $total = BaseService::QianDaoTotal($where);
        $page_total = ceil($total/$number);
        $start = intval(($page-1)*$number);

        // 获取列表
        $data_params = array(
            'm'                 => $start,
            'n'                 => $number,
            'where'             => $where,
        );
        $data = BaseService::QianDaoList($data_params);

        // 返回数据
        $result = [
            'total'             =>  $total,
            'page_total'        =>  $page_total,
            'data'              =>  $data['data']
        ];
        return DataReturn('success', 0, $result);
	}
	
	/**
     * 列表
     * @param   [array]          $params [输入参数]
     */
    public function ScoreList($params = [])
    {
		$this->IsLogin();
		
		$params = $this->data_post;
        $params['user'] = $this->user;
        $params['user_type'] = 'user';
		
		// 得到用户信息
		$params['user_id'] = !empty($this->user) ? $this->user['id'] : 0;
		
		// 是否开启积分互赠
		$base = BaseService::BaseConfig();
		$is_score_flow = (isset($base['data']) && isset($base['data']['is_score_flow']) && intval($base['data']['is_score_flow']) > 0) ? 1 : 0;
		if($is_score_flow == 0){
            return DataReturn('积分互赠功能已关闭', 1);
		}
		
		// 分页
        $number = 10;
        $page = max(1, isset($this->data_post['page']) ? intval($this->data_post['page']) : 1);
		
		// 条件
        $where = ScoreService::ScoreWhere($params);

        // 获取总数
        $total = ScoreService::ScoreTotal($where);
        $page_total = ceil($total/$number);
        $start = intval(($page-1)*$number);
		
        // 获取列表
        $data_params = array(
            'm'                 => $start,
            'n'                 => $number,
            'where'             => $where,
        );
        $data = ScoreService::ScoreList($data_params);
		
		// 返回数据
        $result = [
            'total'             =>  $total,
            'page_total'        =>  $page_total,
            'data'              =>  $data['data'],
			'where'=>$where,
			'par'=>$params
        ];
        return DataReturn('success', 0, $result);
    }
	
	// 搜索会员
    public function souuser($params = [])
    {
		// 是否登录
        $this->IsLogin();
		
		// 开始操作
		$params = $this->data_post;
		
		if(empty($params['keywords']))
        {
			return DataReturn('请输入搜索关键字', 10);
		}

		// 搜索数据
        return ScoreService::UserSearchList($params);
    }
	
	/**
     * [Detail 获取最新的一条详情]
     */
    public function Detail()
    {
        // 参数
        $params = $this->data_post;
		$params['user_id'] = !empty($this->user) ? $this->user['id'] : 0;
		
        // 结果
        return BaseService::LastQianDaoInfo($params);
    }
	
	/**
     * [签到]
     */
    public function QianDao()
    {
		// 是否登录
        $this->IsLogin(); 
		
        // 参数
        $params = $this->data_post;
		$params['user_id'] = $this->user['id'];
		
        // 结果
        return BaseService::QianDao($params);
    }
	
	/**
     * [用户基础数据]
     */
	public function BaseInfo()
    {
		// 是否登录
        $this->IsLogin(); 
		
        // 参数
        $params = $this->data_post;
		$params['user_id'] = !empty($this->user) ? $this->user['id'] : 0;
		
        // 结果
        return BaseService::GetUserInfo($params);
    }
	
	// 赠送积分
    public function Sendscore($params = [])
    {
		// 是否登录
        $this->IsLogin(); 

		// 开始操作
		$params = $this->data_post;
		$params['user'] = $this->user;
        return ScoreService::Sendscore($params);
    }
}
?>