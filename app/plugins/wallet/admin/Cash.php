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
use app\plugins\wallet\service\CashService;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\WalletService;

/**
 * 钱包插件 - 提现管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Cash extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 分页
        $number = empty($params['page_size']) ? MyC('admin_page_number', 10, true) : intval($params['page_size']);

        // 条件
        $where = BaseService::CashWhere($params);

        // 获取总数
        $total = BaseService::CashTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsAdminUrl('wallet', 'cash', 'index'),
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
            $data = BaseService::CashList($data_params);
            MyViewAssign('data_list', $data['data']);
        } else {
            MyViewAssign('data_list', []);
        }

        // 静态数据
        MyViewAssign('cash_status_list', CashService::$cash_status_list);

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/wallet/admin/cash/index');
    }

    /**
     * 审核页面
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-05
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function auditinfo($params = [])
    {
        $data = [];
        if(!empty($params['id']))
        {
            $data_params = array(
                'm'         => 0,
                'n'         => 1,
                'where'     => ['id'=>intval($params['id'])],
            );
            $ret = BaseService::CashList($data_params);
            if(!empty($ret['data'][0]))
            {
                // 用户钱包
                $user_wallet = WalletService::UserWallet($ret['data'][0]['user_id']);
                if($user_wallet['code'] == 0)
                {
                    $data = $ret['data'][0];
                    MyViewAssign('user_wallet', $user_wallet['data']);
                } else {
                    MyViewAssign('msg', $user_wallet['msg']);
                }
            } else {
                MyViewAssign('msg', '数据不存在或已删除');
            }
        } else {
            MyViewAssign('msg', '参数id有误');
        }

        // 价格正则
        MyViewAssign('default_price_regex', MyConst('common_regex_price'));

        MyViewAssign('data', $data);
        return MyView('../../../plugins/view/wallet/admin/cash/auditinfo');
    }

    /**
     * 审核
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-06
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function audit($params = [])
    {
        return CashService::CashAudit($params);
    }
}
?>