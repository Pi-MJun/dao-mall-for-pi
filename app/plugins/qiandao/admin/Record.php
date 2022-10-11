<?php
namespace app\plugins\qiandao\admin;


use app\service\PluginsService;
use app\service\ResourcesService;
use app\plugins\qiandao\service\BaseService;

/**
 * 积分签到 - 列表
 */
class Record
{
    // 列表
    public function index($params = [])
    {
        // 参数
        $params = input();
        $params['user_type'] = 'admin';

        // 分页
        $number = MyC('admin_page_number', 10, true);

        // 条件
        $where = BaseService::QianDaoListWhere($params);

        // 获取总数
        $total = BaseService::QianDaoTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsAdminUrl('qiandao', 'record', 'index'),
            );
        $page = new \base\Page($page_params);
        MyViewAssign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = array(
            'm'         => $page->GetPageStarNumber(),
            'n'         => $number,
            'where'     => $where,
        );
        $data = BaseService::QianDaoList($data_params);
        MyViewAssign('data_list', $data['data']);

		// 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/qiandao/admin/record/index');
    }
}
?>