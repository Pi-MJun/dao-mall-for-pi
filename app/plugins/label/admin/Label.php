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
namespace app\plugins\label\admin;

use app\plugins\label\admin\Common;
use app\plugins\label\service\LabelService;

/**
 * 标签 - 标签管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Label extends Common
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
        $total = LabelService::LabelTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    =>  $this->page_size,
            'total'     =>  $total,
            'where'     =>  $this->data_request,
            'page'      =>  $this->page,
            'url'       =>  PluginsAdminUrl('label', 'label', 'index'),
        ];
        $page = new \base\Page($page_params);

        // 获取列表
        $data_params = [
            'm'             => $page->GetPageStarNumber(),
            'n'             => $this->page_size,
            'where'         => $this->form_where,
            'order_by'      => $this->form_order_by['data'],
        ];
        $ret = LabelService::LabelList($data_params);

        // 基础参数赋值
        MyViewAssign('params', $this->data_request);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);
        return MyView('../../../plugins/view/label/admin/label/index');
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
        // 获取数据
        $data = [];
        if(!empty($params['id']))
        {
            // 条件
            $where = [
                ['id', '=', intval($params['id'])],
            ];

            // 获取列表
            $data_params = [
                'm'         => 0,
                'n'         => 1,
                'where'     => $where,
                'user_type' => 'admin',
            ];
            $ret = LabelService::LabelList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
            }
        }

        // 数据
        MyViewAssign('data', $data);
        return MyView('../../../plugins/view/label/admin/label/saveinfo');
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
        $data = [];
        if(!empty($params['id']))
        {
            // 条件
            $where = [
                ['id', '=', intval($params['id'])],
            ];

            // 获取列表
            $data_params = [
                'm'     => 0,
                'n'     => 1,
                'where' => $where,
            ];
            $ret = LabelService::LabelList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
            }
        }
        MyViewAssign('data', $data);
        return MyView('../../../plugins/view/label/admin/label/detail');
    }

    /**
     * 标签关联
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Join($params = [])
    {
        $data = [];
        $label_ids = [];
        if(!empty($params['vid']) && !empty($params['type']))
        {
            // 获取列表
            $data_params = [
                'm'             => 0,
                'n'             => 0,
                'where'         => [
                    ['is_enable', '=', 1],
                ],
                'field'         => 'id,name',
            ];
            $ret = LabelService::LabelList($data_params);
            if(!empty($ret['data']))
            {
                $data = $ret['data'];
            }

            // 获取数据已关联的标签id
            $label_ids = LabelService::JoinSelectLabelIds($params['vid'], $params['type']);
        }
        MyViewAssign('data', $data);
        MyViewAssign('label_ids', $label_ids);
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/label/admin/label/join');
    }

    /**
     * 标签关联保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-08-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function JoinSave($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        return LabelService::LabelJoinSave($params);
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Save($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        return LabelService::LabelSave($params);
    }
    
    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function StatusUpdate($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return LabelService::LabelStatusUpdate($params);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-30
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

        return LabelService::LabelDelete($params);
    }
}
?>