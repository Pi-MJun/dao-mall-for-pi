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
namespace app\plugins\distribution\admin;

use app\plugins\distribution\admin\Common;
use app\plugins\distribution\service\BusinessService;
use app\plugins\distribution\service\ExtractionService;

/**
 * 分销 - 取货点管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Extraction extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 获取总数
        $total = BusinessService::ExtractionTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    => $this->page_size,
            'total'     => $total,
            'where'     => $params,
            'page'      => $this->page,
            'url'       => PluginsAdminUrl('distribution', 'extraction', 'index'),
        ];
        $page = new \base\Page($page_params);
        MyViewAssign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = [
            'm'         => $page->GetPageStarNumber(),
            'n'         => $this->page_size,
            'where'     => $this->form_where,
            'order_by'  => $this->form_order_by['data'],
            'user_type' => 'admin',
        ];
        $data = BusinessService::ExtractionList($data_params);
        MyViewAssign('data_list', $data['data']);

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/distribution/admin/extraction/index');
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
            $data_params = [
                'm'         => 0,
                'n'         => 1,
                'where'     => [
                    ['id', '=', intval($params['id'])],
                ],
                'user_type' => 'admin',
            ];
            $ret = BusinessService::ExtractionList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
            }
        }
        MyViewAssign('data', $data);

        // 关闭头尾
        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        return MyView('../../../plugins/view/distribution/admin/extraction/detail');
    }

    /**
     * 取货点订单
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function order($params = [])
    {
        // 分页
        $number = MyC('admin_page_number', 10, true);

        // 条件
        $where = BusinessService::ExtractionOrderListWhere($params);

        // 获取总数
        $total = BusinessService::ExtractionOrderListTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsAdminUrl('distribution', 'extraction', 'order'),
            );
        $page = new \base\Page($page_params);
        MyViewAssign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = array(
            'm'                 => $page->GetPageStarNumber(),
            'n'                 => $number,
            'where'             => $where,
            'is_orderaftersale' => 1,
        );
        $data = BusinessService::ExtractionOrderList($data_params);
        MyViewAssign('data_list', $data['data']);

        // 必要参数
        $form_params = [];
        $form_params['status'] = isset($params['status']) ? intval($params['status']) : 0;
        if(isset($params['user_id']))
        {
            $form_params['user_id'] = intval($params['user_id']);
        }
        MyViewAssign('form_params', $form_params);

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/distribution/admin/extraction/order');
    }

    /**
     * 审核通过
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-06
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

        // 开始处理
        $params['type'] = 1;
        return ExtractionService::ExtractionAudit($params);
    }

    /**
     * 审核拒绝
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Fail($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['type'] = 2;
        return ExtractionService::ExtractionAudit($params);
    }

    /**
     * 解约
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Relieve($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return ExtractionService::ExtractionRelieve($params);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-06
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

        // 开始处理
        return ExtractionService::ExtractionDelete($params);
    }
}
?>