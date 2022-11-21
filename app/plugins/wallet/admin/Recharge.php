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
namespace app\plugins\wallet\admin;

use app\plugins\wallet\admin\Common;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\RechargeService;

/**
 * 钱包 - 充值管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Recharge extends Common
{
    /**
     * 充值明细
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 分页
        $number = empty($params['page_size']) ? MyC('admin_page_number', 10, true) : intval($params['page_size']);

        // 条件
        $where = BaseService::RechargeWhere($params);

        // 获取总数
        $total = BaseService::RechargeTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsAdminUrl('wallet', 'recharge', 'index'),
            );
        $page = new \base\Page($page_params);
        MyViewAssign('page_html', $page->GetPageHtml());

        // 获取列表
        if($total > 0)
        {
            $data_params = array(
                'm'         => $page->GetPageStarNumber(),
                'n'         => $number,
                'where'     => $where,
                'user_type' => 'admin',
            );
            $data = BaseService::RechargeList($data_params);
            MyViewAssign('data_list', $data['data']);
        } else {
            MyViewAssign('data_list', []);
        }

        // 静态数据
        MyViewAssign('recharge_status_list', RechargeService::$recharge_status_list);

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/wallet/admin/recharge/index');
    }

    /**
     * 充值纪录删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function delete($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error('非法访问');
        }

        // 开始处理
        return RechargeService::RechargeDelete($params);
    }
}
?>