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
use app\plugins\shop\index\Base;
use app\plugins\shop\service\ShopOrderAftersaleService;

/**
 * 多商户 - 订单售后
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Orderaftersale extends Base
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
     * 列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 总数
        $total = ShopOrderAftersaleService::OrderAftersaleTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    =>  $this->page_size,
            'total'     =>  $total,
            'where'     =>  $this->data_request,
            'page'      =>  $this->page,
            'url'       =>  PluginsHomeUrl('shop', 'orderaftersale', 'index'),
        ];
        $page = new \base\Page($page_params);

        // 获取数据列表
        $data_params = [
            'where'         => $this->form_where,
            'm'             => $page->GetPageStarNumber(),
            'n'             => $this->page_size,
            'order_by'      => $this->form_order_by['data'],
        ];
        $ret = ShopOrderAftersaleService::OrderAftersaleList($data_params);

        // 静态数据
        MyViewAssign('common_order_aftersale_refundment_list', MyConst('common_order_aftersale_refundment_list'));

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('订单售后', 1));

        // 基础参数赋值
        MyViewAssign('params', $this->data_request);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);
        return MyView('../../../plugins/view/shop/index/orderaftersale/index');
    }

    /**
     * 详情
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-05T08:21:54+0800
     */
    public function Detail($params = [])
    {
        if(!empty($this->data_request['id']))
        {
            // 条件
            $where = [
                ['oa.id', '=', intval($this->data_request['id'])],
                ['o.shop_user_id', '=', intval($this->user['id'])],
            ];

            // 获取列表
            $data_params = [
                'm'         => 0,
                'n'         => 1,
                'where'     => $where,
            ];
            $ret = ShopOrderAftersaleService::OrderAftersaleList($data_params);
            $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
            MyViewAssign('data', $data);
        }
        return MyView('../../../plugins/view/shop/index/orderaftersale/detail');
    }

    /**
     * 确认
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-23
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

        $params['base_config'] = $this->base_config;
        $params['user_id'] = $this->user['id'];
        return ShopOrderAftersaleService::AftersaleConfirm($params);
    }

    /**
     * 审核
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Audit($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        $params['base_config'] = $this->base_config;
        $params['user_id'] = $this->user['id'];
        $params['creator'] = $this->user['id'];
        $params['creator_name'] = $this->user['user_name_view'];
        return ShopOrderAftersaleService::AftersaleAudit($params);
    }

    /**
     * 拒绝
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Refuse($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        $params['base_config'] = $this->base_config;
        $params['user_id'] = $this->user['id'];
        $params['creator'] = $this->user['id'];
        $params['creator_name'] = $this->user['user_name_view'];
        return ShopOrderAftersaleService::AftersaleRefuse($params);
    }

    /**
     * 订单取消
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-30
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

        $params['base_config'] = $this->base_config;
        $params['user_id'] = $this->user['id'];
        $params['creator'] = $this->user['id'];
        $params['creator_name'] = $this->user['user_name_view'];
        return ShopOrderAftersaleService::AftersaleCancel($params);
    }
}
?>