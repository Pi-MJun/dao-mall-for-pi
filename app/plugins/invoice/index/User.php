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
namespace app\plugins\invoice\index;

use app\plugins\invoice\index\Common;
use app\plugins\invoice\service\InvoiceService;
use app\plugins\invoice\service\BaseService;

/**
 * 发票 - 开票管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class User extends Common
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
            'url'       =>  PluginsHomeUrl('invoice', 'user', 'index'),
        ];
        $page = new \base\Page($page_params);

        // 获取列表
        $data_params = [
            'm'         => $page->GetPageStarNumber(),
            'n'         => $this->page_size,
            'where'     => $this->form_where,
            'order_by'  => $this->form_order_by['data'],
        ];
        $ret = InvoiceService::InvoiceList($data_params);

        // 基础参数赋值
        MyViewAssign('params', $this->data_request);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);
        return MyView('../../../plugins/view/invoice/index/user/index');
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
                ['user_id', '=', intval($this->user['id'])],
            ];

            // 获取列表
            $data_params = [
                'where'         => $where,
            ];
            $ret = InvoiceService::InvoiceList($data_params);
            $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
            MyViewAssign('data', $data);
        }

        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        return MyView('../../../plugins/view/invoice/index/user/detail');
    }

    /**
     * 编辑页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        // 数据
        $data = [];
        if(!empty($params['id']))
        {
            // 条件
            $where = [
                ['id', '=', intval($params['id'])],
                ['user_id', '=', intval($this->user['id'])],
            ];

            // 获取列表
            $data_params = [
                'where'         => $where,
            ];
            $ret = InvoiceService::InvoiceList($data_params);
            if(!empty($ret['data']))
            {
                $data = $ret['data'][0];
            }
        }

        // 基础数据计算
        $save_base_data = InvoiceService::InvoiceSaveBaseDataHandle(empty($data) ? $params : $data);

        // 插件配置信息
        $base = BaseService::BaseConfig();
        MyViewAssign('plugins_base', $base['data']);

        // 发票内容
        MyViewAssign('invoice_content_list', empty($base['data']['invoice_content_type']) ? [] : $base['data']['invoice_content_type']);

        // 可选发票类型
        $can_invoice_type_list = BaseService::CanInvoiceTypeList(empty($base['data']['can_invoice_type']) ? [] : $base['data']['can_invoice_type']);
        MyViewAssign('can_invoice_type_list', $can_invoice_type_list);

        // 静态数据
        MyViewAssign('apply_type_list', BaseService::$apply_type_list);

        // 数据
        MyViewAssign('data', $data);
        MyViewAssign('save_base_data', $save_base_data);
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/invoice/index/user/saveinfo');
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Save($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user'] = $this->user;
        return InvoiceService::InvoiceSave($params);
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
        $params['user'] = $this->user;
        return InvoiceService::InvoiceDelete($params);
    }
}
?>