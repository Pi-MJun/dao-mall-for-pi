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
namespace app\plugins\blog\admin;

use app\plugins\blog\admin\Common;
use app\plugins\blog\service\SlideService;

/**
 * 博客 - 轮播图片管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-29
 * @desc    description
 */
class Slide extends Common
{
    /**
     * 列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 总数
        $total = SlideService::SlideTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    =>  $this->page_size,
            'total'     =>  $total,
            'where'     =>  $this->data_request,
            'page'      =>  $this->page,
            'url'       =>  PluginsAdminUrl('blog', 'slide', 'index'),
        ];
        $page = new \base\Page($page_params);

        // 获取列表
        $data_params = [
            'where'         => $this->form_where,
            'm'             => $page->GetPageStarNumber(),
            'n'             => $this->page_size,
        ];
        $ret = SlideService::SlideList($data_params);

        // 基础参数赋值
        MyViewAssign('params', $this->data_request);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);
        return MyView('../../../plugins/view/blog/admin/slide/index');
    }

    /**
     * 详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        if(!empty($this->data_request['id']))
        {
            // 条件
            $where = [
                ['id', '=', intval($this->data_request['id'])],
            ];

            // 获取列表
            $data_params = [
                'm'             => 0,
                'n'             => 1,
                'where'         => $where,
            ];
            $ret = SlideService::SlideList($data_params);
            $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
            MyViewAssign('data', $data);
        }
        return MyView('../../../plugins/view/blog/admin/slide/detail');
    }

    /**
     * 添加/编辑页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        // 参数
        $params = $this->data_request;

        // 数据
        $data = [];
        if(!empty($params['id']))
        {
            // 获取列表
            $data_params = [
                'm'         => 0,
                'n'         => 1,
                'where'     => ['id'=>intval($params['id'])],
                'field'     => '*',
            ];
            $ret = SlideService::SlideList($data_params);
            $data = empty($ret['data'][0]) ? [] : $ret['data'][0];
        }

        // 静态资源
        MyViewAssign('common_is_enable_list', MyConst('common_is_enable_list'));
        MyViewAssign('common_platform_type', MyConst('common_platform_type'));
        MyViewAssign('common_app_event_type', MyConst('common_app_event_type'));

        // 数据
        MyViewAssign('params', $params);
        MyViewAssign('data', $data);
        return MyView('../../../plugins/view/blog/admin/slide/saveinfo');
    }

    /**
     * 添加/编辑
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-15
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

        // 开始处理
        $params = $this->data_request;
        return SlideService::SlideSave($params);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-15
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
        $params = $this->data_request;
        return SlideService::SlideDelete($params);
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-15
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
        $params = $this->data_request;
        return SlideService::SlideStatusUpdate($params);
    }
}
?>