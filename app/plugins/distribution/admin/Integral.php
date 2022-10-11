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

use app\plugins\distribution\admin\Common;
use app\plugins\distribution\service\BusinessService;

/**
 * 分销 - 积分明细
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Integral extends Common
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
        $total = BusinessService::UserIntegralTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    => $this->page_size,
            'total'     => $total,
            'where'     => $params,
            'page'      => $this->page,
            'url'       => PluginsAdminUrl('distribution', 'integral', 'index'),
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
        $data = BusinessService::UserIntegralList($data_params);
        MyViewAssign('data_list', $data['data']);

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/distribution/admin/integral/index');
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
                    ['id', '=', intval($params['id'])],
                ],
            ];
            $ret = BusinessService::UserIntegralList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
            }
        }
        MyViewAssign('data', $data);

        // 关闭头尾
        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        return MyView('../../../plugins/view/distribution/admin/integral/detail');
    }
}
?>