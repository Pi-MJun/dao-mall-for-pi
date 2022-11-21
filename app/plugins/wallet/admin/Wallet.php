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
use app\plugins\wallet\service\WalletService;
use app\plugins\wallet\service\BaseService;

/**
 * 钱包插件 - 钱包管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Wallet extends Common
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
        $where = BaseService::WalletWhere($params);

        // 获取总数
        $total = BaseService::WalletTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsAdminUrl('wallet', 'wallet', 'index'),
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
            $data = BaseService::WalletList($data_params);
            MyViewAssign('data_list', $data['data']);
        } else {
            MyViewAssign('data_list', []);
        }

        // 静态数据
        MyViewAssign('wallet_status_list', WalletService::$wallet_status_list);

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/wallet/admin/wallet/index');
    }

    /**
     * 钱包编辑页面
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-05
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public function saveinfo($params = [])
    {
        $data = [];
        if(!empty($params['id']))
        {
            $data_params = array(
                'm'         => 0,
                'n'         => 1,
                'where'     => ['id'=>intval($params['id'])],
            );
            $ret = BaseService::WalletList($data_params);
            if(!empty($ret['data'][0]))
            {
                $data = $ret['data'][0];

                // 静态数据
                MyViewAssign('wallet_status_list', WalletService::$wallet_status_list);
            } else {
                MyViewAssign('msg', '钱包有误');
            }
        } else {
            MyViewAssign('msg', '钱包id有误');
        }

        // 价格正则
        MyViewAssign('default_price_regex', MyConst('common_regex_price'));

        MyViewAssign('data', $data);
        return MyView('../../../plugins/view/wallet/admin/wallet/saveinfo');
    }

    /**
     * 钱包编辑
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-06
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public function save($params = [])
    {
        $params['operate_id'] = $this->admin['id'];
        $params['operate_name'] = $this->admin['username'];
        return WalletService::WalletEdit($params);
    }
}
?>