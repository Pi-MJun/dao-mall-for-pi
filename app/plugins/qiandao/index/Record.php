<?php
namespace app\plugins\qiandao\index;

use app\service\SeoService;
use app\plugins\qiandao\service\BaseService;

/**
 * 签到 - 列表
 */
class Record extends Common
{
    /**
     * 签到列表
     * @param   [array]          $params [输入参数]
     */
    public function index($params = [])
    {
		$this->IsLogin();
		
		// 总数
        $total = BaseService::QianDaoTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    =>  $this->page_size,
            'total'     =>  $total,
            'where'     =>  $this->data_request,
            'page'      =>  $this->page,
            'url'       =>  PluginsHomeUrl('qiandao', 'record', 'index'),
        ];
        $page = new \base\Page($page_params);

        // 获取列表
        $data_params = [
            'm'                 => $page->GetPageStarNumber(),
            'n'                 => $this->page_size,
            'where'             => $this->form_where,
            'order_by'      	=> $this->form_order_by['data'],
            'user_type'         => 'user',
        ];
        $ret = BaseService::QianDaoList($data_params);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('我的签到记录', 1));
		
		// 是否开启积分互赠
		$base = BaseService::BaseConfig();
		$is_score_flow = (isset($base['data']) && isset($base['data']['is_score_flow']) && intval($base['data']['is_score_flow']) > 0) ? 1 : 0;
		MyViewAssign('is_score_flow', $is_score_flow);

        // 基础参数赋值
        MyViewAssign('params', $this->data_request);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);
        return MyView('../../../plugins/view/qiandao/index/record/index');
    }
}
?>