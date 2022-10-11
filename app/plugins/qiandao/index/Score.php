<?php
namespace app\plugins\qiandao\index;

use app\service\SeoService;
use app\plugins\qiandao\service\BaseService;
use app\plugins\qiandao\service\ScoreService;

/**
 * 积分互赠 - 列表
 */
class Score extends Common
{
    /**
     * 列表
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
		$this->IsLogin();
		
		// 得到用户信息
		$params['user_id'] = !empty($this->user) ? $this->user['id'] : 0;
		
		// 是否开启积分互赠
		$base = BaseService::BaseConfig();
		$is_score_flow = (isset($base['data']) && isset($base['data']['is_score_flow']) && intval($base['data']['is_score_flow']) > 0) ? 1 : 0;
		if($is_score_flow == 0){
			MyViewAssign('msg', '积分互赠功能已关闭');
            return MyView('public/tips_error');
		}
		
		// 总数
        $total = ScoreService::ScoreTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    =>  $this->page_size,
            'total'     =>  $total,
            'where'     =>  $this->data_request,
            'page'      =>  $this->page,
            'url'       =>  PluginsHomeUrl('qiandao', 'score', 'index'),
        ];
        $page = new \base\Page($page_params);

        // 获取列表
        $data_params = [
            'm'                 => $page->GetPageStarNumber(),
            'n'                 => $this->page_size,
            'where'             => $this->form_where,
            'order_by'      	=> $this->form_order_by['data'],
        ];
        $ret = ScoreService::ScoreList($data_params);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('积分互赠记录', 1));
		
		// 用户信息
		$res = BaseService::GetUserInfo($params);
		MyViewAssign('user_base_info', $res['data']);

        // 基础参数赋值
        MyViewAssign('params', $this->data_request);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);
        return MyView('../../../plugins/view/qiandao/index/score/index');
    }
	
	// 搜索会员
    public function souuser($params = [])
    {
		// 是否ajax
		if(!IS_AJAX)
		{
			return $this->error('非法访问');
		}

		// 开始操作
		$params = input('post.');
		
		if(empty($params['keywords']))
        {
			return DataReturn('请输入搜索关键字', 10);
		}

		// 搜索数据
        $ret = ScoreService::UserSearchList($params);
        if($ret['code'] == 0)
        {
            MyViewAssign('data', $ret['data']['data']);
            $ret['data']['data'] = MyView('../../../plugins/view/qiandao/index/score/souuser');
        }
		
        return $ret;
    }
	
	// 赠送积分
    public function Sendscore($params = [])
    {
		// 是否ajax
		if(!IS_AJAX)
		{
			return $this->error('非法访问');
		}

		// 开始操作
		$params = input('post.');
		$params['user'] = $this->user;
        return ScoreService::Sendscore($params);
    }
}
?>