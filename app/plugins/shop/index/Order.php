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
namespace app\plugins\shop\index;

use app\service\SeoService;
use app\service\OrderService;
use app\service\ExpressService;
use app\plugins\shop\index\Base;
use app\plugins\shop\service\ShopOrderService;

/**
 * 订单管理 - 卖家中心
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Order extends Base
{
    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-30
     * @desc    description
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        $this->IsLogin();
    }

    /**
     * 订单列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 总数
        $total = OrderService::OrderTotal($this->form_where);

        // 提示信息
        $tips_msg = OrderService::OrderTipsMsg($this->form_where);

        // 分页
        $page_params = [
            'number'    => $this->page_size,
            'total'     => $total,
            'where'     => $params,
            'page'      => $this->page,
            'url'       => PluginsHomeUrl('shop', 'order', 'index'),
            'tips_msg'  => $tips_msg,
        ];
        $page = new \base\Page($page_params);

        // 获取列表
        $data_params = [
            'm'         => $page->GetPageStarNumber(),
            'n'         => $this->page_size,
            'where'     => $this->form_where,
            'order_by'  => $this->form_order_by['data'],
            'is_public' => 0,
            'is_operate'=> 1,
            'user_type' => 'admin',
        ];
        $ret = OrderService::OrderList($data_params);

        // 快递公司
        MyViewAssign('express_list', ExpressService::ExpressList());

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('订单管理', 1));

        // 基础参数赋值
        MyViewAssign('params', $params);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);
        return MyView('../../../plugins/view/shop/index/order/index');
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
            // 条件
            $where = [
                ['is_delete_time', '=', 0],
                ['shop_is_delete_time', '=', 0],
                ['id', '=', intval($params['id'])],
                ['shop_user_id', '=', $this->user['id']],
            ];

            // 获取列表
            $data_params = [
                'm'         => 0,
                'n'         => 1,
                'where'     => $where,
                'is_public' => 0,
                'is_operate'=> 1,
                'user_type' => 'admin',
            ];
            $ret = OrderService::OrderList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
            }
        }
        MyViewAssign('data', $data);

        // 关闭头尾
        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        return MyView('../../../plugins/view/shop/index/order/detail');
    }

    /**
     * 订单确认
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Confirm($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 订单确认
        $params['user'] = $this->user;
        return ShopOrderService::OrderConfirm($params);
    }

    /**
     * 订单取消
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Cancel($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 取消操作
        $params['user'] = $this->user;
        return ShopOrderService::OrderCancel($params);
    }

    /**
     * 订单发货/取货
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Delivery($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 发货操作
        $params['user'] = $this->user;
        return ShopOrderService::OrderDelivery($params);
    }

    /**
     * 订单删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Delete($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 删除操作
        $params['user'] = $this->user;
        return ShopOrderService::OrderDelete($params);
    }

    /**
     * 订单接收
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Receive($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始操作
        $params['user'] = $this->user;
        $params['base_config'] = $this->base_config;
        return ShopOrderService::OrderReceive($params);
    }
}
?>