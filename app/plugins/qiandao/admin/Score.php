<?php
namespace app\plugins\qiandao\admin;

use app\service\PluginsService;
use app\service\ResourcesService;
use app\plugins\qiandao\admin\Common;
use app\plugins\qiandao\service\BaseService;
use app\plugins\qiandao\service\ScoreService;

/**
 * 积分签到 - 积分互赠列表
 */
class Score extends Common
{
    // 列表
    public function Index()
    {
        // 总数
        $total = ScoreService::ScoreTotal($this->form_where);

        // 分页
        $page_params = array(
                'number'    => $this->page_size,
				'total'     => $total,
				'where'     => $this->data_request,
				'page'      => $this->page,
                'url'       => PluginsAdminUrl('qiandao', 'score', 'index'),
            );
        $page = new \base\Page($page_params);
        

        // 获取列表
        $data_params = [
            'm'             => $page->GetPageStarNumber(),
            'n'             => $this->page_size,
            'where'         => $this->form_where,
        ];
        $data = ScoreService::ScoreList($data_params);

		// 参数
        MyViewAssign('params', $this->data_request);
		MyViewAssign('page_html', $page->GetPageHtml());
		MyViewAssign('data_list', $data['data']);
		
        return MyView('../../../plugins/view/qiandao/admin/score/index');
    }
}
?>