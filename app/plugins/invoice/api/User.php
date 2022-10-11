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
     * 用户中心
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Center($params = [])
    {
        // 插件配置信息
        $base = BaseService::BaseConfig();

        // 返回数据
        $result = [
            'base'      => $base['data'],
            'nav_list'  => BaseService::UserCenterNav($base['data']),
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 数据列表
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
        $total = InvoiceService::InvoiceTotal($this->form_where);
        $page_total = ceil($total/$this->page_size);
        $start = intval(($this->page-1)*$this->page_size);

        // 获取列表
        $data_params = [
            'm'             => $start,
            'n'             => $this->page_size,
            'where'         => $this->form_where,
            'order_by'      => $this->form_order_by['data'],
        ];
        $ret = InvoiceService::InvoiceList($data_params);

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
        // 数据读取
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

            // 表格数据列表处理
            $ret['data'] = (new FormHandleModule())->FormTableDataListHandle($ret['data'], $params);
            $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
        }

        // 返回数据
        $result = [
            'data'  => $data,
        ];
        return DataReturn('success', 0, $result);
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
        // 插件配置信息
        $base = BaseService::BaseConfig();

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

        // 发票内容
        $invoice_content_list = empty($base['data']['invoice_content_type']) ? [] : $base['data']['invoice_content_type'];

        // 可选发票类型
        $can_invoice_type_list = BaseService::CanInvoiceTypeList(empty($base['data']['can_invoice_type']) ? [] : $base['data']['can_invoice_type']);

        // 返回数据
        $result = [
            'base'                  => $base['data'],
            'data'                  => $data,
            'save_base_data'        => $save_base_data,
            'apply_type_list'       => BaseService::$apply_type_list,
            'invoice_content_list'  => $invoice_content_list,
            'can_invoice_type_list' => $can_invoice_type_list,
        ];
        return DataReturn('success', 0, $result);
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