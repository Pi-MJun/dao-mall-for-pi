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
namespace app\plugins\distribution\admin;

use app\service\OrderService;
use app\plugins\distribution\admin\Common;
use app\plugins\distribution\service\BusinessService;

/**
 * 分销 - 取货点订单管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class ExtractionOrder extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 获取总数
        $total = BusinessService::ExtractionOrderListTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    => $this->page_size,
            'total'     => $total,
            'where'     => $params,
            'page'      => $this->page,
            'url'       => PluginsAdminUrl('distribution', 'extractionorder', 'index'),
        ];
        $page = new \base\Page($page_params);
        MyViewAssign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = [
            'm'         => $page->GetPageStarNumber(),
            'n'         => $this->page_size,
            'where'     => $this->form_where,
            'order_by'  => $this->form_order_by['data'],
            'user_type' => 'admin',
        ];
        $data = BusinessService::ExtractionOrderList($data_params);
        MyViewAssign('data_list', $data['data']);

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/distribution/admin/extractionorder/index');
    }

    /**
     * 详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        $data = [];
        if(!empty($params['id']))
        {
            $data_params = [
                'm'         => 0,
                'n'         => 1,
                'where'     => [
                    ['po.id', '=', intval($params['id'])],
                ],
                'user_type' => 'admin',
            ];
            $ret = BusinessService::ExtractionOrderList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
            }
        }
        MyViewAssign('data', $data);

        // 关闭头尾
        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        return MyView('../../../plugins/view/distribution/admin/extractionorder/detail');
    }

    /**
     * 取货
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-03
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Take($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 发货操作
        $params['creator'] = $this->admin['id'];
        $params['creator_name'] = $this->admin['username'];
        $params['user_type'] = 'admin';
        return OrderService::OrderDelivery($params);
    }
}
?>