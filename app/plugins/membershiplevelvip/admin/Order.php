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
namespace app\plugins\membershiplevelvip\admin;

use app\plugins\membershiplevelvip\service\BaseService;
use app\plugins\membershiplevelvip\service\LevelBuyService;

/**
 * 会员等级增强版插件 - 会员订单
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Order
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 分页
        $number = MyC('admin_page_number', 10, true);

        // 条件
        $where = BaseService::UserPayOrderWhere($params);

        // 获取总数
        $total = BaseService::UserPayOrderTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsAdminUrl('membershiplevelvip', 'order', 'index'),
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
            );
            $data = BaseService::UserPayOrderList($data_params);
            MyViewAssign('data_list', $data['data']);
        } else {
            MyViewAssign('data_list', []);
        }

        // 静态数据
        MyViewAssign('payment_user_order_status_list', BaseService::$payment_user_order_status_list);
        MyViewAssign('payment_user_order_settlement_status_list', BaseService::$payment_user_order_settlement_status_list);

        // 参数
        MyViewAssign('form_params', empty($params['uid']) ? [] : ['uid'=>$params['uid']]);
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/membershiplevelvip/admin/order/index');
    }

    /**
     * 订单纪录取消
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function cancel($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error('非法访问');
        }

        // 开始处理
        $params['user_type'] = 'admin';
        $params['value'] = 2;
        return LevelBuyService::BuyOrderInvalid($params);
    }

    /**
     * 订单纪录删除
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
        $params['user_type'] = 'admin';
        return LevelBuyService::BuyOrderDelete($params);
    }
}
?>