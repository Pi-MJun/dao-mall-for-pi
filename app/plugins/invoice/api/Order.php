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
namespace app\plugins\invoice\api;

use app\module\FormHandleModule;
use app\plugins\invoice\api\Common;
use app\plugins\invoice\service\InvoiceOrderService;

/**
 * 发票 - 订单开票
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Order extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 获取总数
        $total = InvoiceOrderService::InvoiceOrderTotal($this->form_where);
        $page_total = ceil($total/$this->page_size);
        $start = intval(($this->page-1)*$this->page_size);

        // 获取列表
        $data_params = [
            'm'             => $start,
            'n'             => $this->page_size,
            'where'         => $this->form_where,
            'order_by'      => $this->form_order_by['data'],
        ];
        $ret = InvoiceOrderService::InvoiceOrderList($data_params);

        // 表格数据列表处理
        $ret['data'] = (new FormHandleModule())->FormTableDataListHandle($ret['data'], $params);

        // 返回数据
        $result = [
            'total'             => $total,
            'page_total'        => $page_total,
            'data'              => $ret['data'],
        ];
        return DataReturn('success', 0, $result);
    }
}
?>