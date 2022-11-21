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
use app\plugins\wallet\service\WalletService;

/**
 * 钱包 - 账户明细管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Walletlog extends Common
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
        $where = BaseService::WalletLogWhere($params);

        // 获取总数
        $total = BaseService::WalletLogTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsAdminUrl('wallet', 'walletlog', 'index'),
            );
        $page = new \base\Page($page_params);
        MyViewAssign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = array(
            'm'         => $page->GetPageStarNumber(),
            'n'         => $number,
            'where'     => $where,
            'user_type' => 'admin',
        );
        $data = BaseService::WalletLogList($data_params);
        MyViewAssign('data_list', $data['data']);

        // 静态数据
        MyViewAssign('business_type_list', WalletService::$business_type_list);
        MyViewAssign('operation_type_list', WalletService::$operation_type_list);
        MyViewAssign('money_type_list', WalletService::$money_type_list);

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/wallet/admin/walletlog/index');
    }
}
?>