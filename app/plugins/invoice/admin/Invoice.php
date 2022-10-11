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
namespace app\plugins\invoice\admin;

use app\plugins\invoice\admin\Common;
use app\plugins\invoice\service\BaseService;
use app\plugins\invoice\service\InvoiceService;

/**
 * 发票 - 开票管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Invoice extends Common
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
        // 总数
        $total = InvoiceService::InvoiceTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    =>  $this->page_size,
            'total'     =>  $total,
            'where'     =>  $this->data_request,
            'page'      =>  $this->page,
            'url'       =>  PluginsAdminUrl('invoice', 'invoice', 'index'),
        ];
        $page = new \base\Page($page_params);

        // 获取列表
        $data_params = [
            'm'         => $page->GetPageStarNumber(),
            'n'         => $this->page_size,
            'where'     => $this->form_where,
            'order_by'  => $this->form_order_by['data'],
            'is_public' => 0,
        ];
        $ret = InvoiceService::InvoiceList($data_params);

        // 基础参数赋值
        MyViewAssign('params', $this->data_request);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);
        return MyView('../../../plugins/view/invoice/admin/invoice/index');
    }

    /**
     * 详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        if(!empty($params['id']))
        {
            // 条件
            $where = [
                ['id', '=', intval($params['id'])],
            ];

            // 获取列表
            $data_params = [
                'where'     => $where,
                'is_public' => 0,
            ];
            $ret = InvoiceService::InvoiceList($data_params);
            $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
            MyViewAssign('data', $data);
        }
        return MyView('../../../plugins/view/invoice/admin/invoice/detail');
    }

    /**
     * 审核页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function AuditInfo($params = [])
    {
        // 静态数据
        MyViewAssign('audit_type_list', BaseService::$audit_type_list);
        MyViewAssign('params', $this->data_request);
        return MyView('../../../plugins/view/invoice/admin/invoice/auditinfo');
    }

    /**
     * 审核
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Audit($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['admin'] = $this->admin;
        return InvoiceService::InvoiceAudit($params);
    }

    /**
     * 开具发票页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function IssueInfo($params = [])
    {
        if(!empty($params['id']))
        {
            // 条件
            $where = [
                ['id', '=', intval($params['id'])],
            ];

            // 获取列表
            $data_params = [
                'where'         => $where,
            ];
            $ret = InvoiceService::InvoiceList($data_params);
            $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
            MyViewAssign('data', $data);

            if(!empty($data))
            {
                // 编辑器文件存放地址定义
                MyViewAssign('editor_path_type', 'plugins_invoice-electronic-'.$data['business_type'].'-'.$data['id']);
            }
        }
        MyViewAssign('params', $this->data_request);
        return MyView('../../../plugins/view/invoice/admin/invoice/issueinfo');
    }

    /**
     * 开具发票
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-04
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Issue($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['admin'] = $this->admin;
        return InvoiceService::InvoiceIssue($params);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Delete($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['admin'] = $this->admin;
        return InvoiceService::InvoiceDelete($params);
    }
}
?>