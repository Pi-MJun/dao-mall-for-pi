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
namespace app\plugins\distribution\index;

use app\service\SeoService;
use app\service\OrderService;
use app\service\ConfigService;
use app\plugins\distribution\index\Common;
use app\plugins\distribution\service\BusinessService;
use app\plugins\distribution\service\ExtractionService;

/**
 * 分销 - 取货点
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
        // 获取取货点信息
        $extraction = ExtractionService::ExtractionData($this->user['id']);
        MyViewAssign('data', $extraction['data']);
        if(isset($extraction['data']['status']) && $extraction['data']['status'] == 1)
        {
            // 获取总数
            $total = BusinessService::ExtractionOrderListTotal($this->form_where);

            // 分页
            $page_params = [
                'number'    => $this->page_size,
                'total'     => $total,
                'where'     => $params,
                'page'      => $this->page,
                'url'       => PluginsHomeUrl('distribution', 'extraction', 'index'),
            ];
            $page = new \base\Page($page_params);
            MyViewAssign('page_html', $page->GetPageHtml());

            // 获取列表
            $data_params = [
                'm'         => $page->GetPageStarNumber(),
                'n'         => $this->page_size,
                'where'     => $this->form_where,
                'order_by'  => $this->form_order_by['data'],
                'user_type' => 'user',
            ];
            $data = BusinessService::ExtractionOrderList($data_params);
            MyViewAssign('data_list', $data['data']);
        } else {
            MyViewAssign('data_list', []);
        }

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('取货点 - 我的分销', 1));

        // 参数
        MyViewAssign('params_status', isset($params['status']) ? intval($params['status']) : 0);
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/distribution/index/extraction/index');
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
                    ['po.id', '=', intval($params['id'])],
                ],
                'user_type' => 'user',
            ];
            $ret = BusinessService::ExtractionOrderList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
            }
        }
        MyViewAssign('data', $data);

        // 关闭头尾
        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        return MyView('../../../plugins/view/distribution/index/extraction/detail');
    }

    /**
     * 取货点信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     */
    public function ApplyInfo($params = [])
    {
        // 获取取货点信息
        $ret = ExtractionService::ExtractionData($this->user['id']);
        MyViewAssign('data', $ret['data']);

        // 加载地图api
        MyViewAssign('is_load_map_api', 1);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('取货点信息 - 我的分销', 1));

        // 编辑器文件存放地址定义
        MyViewAssign('editor_path_type', 'plugins_distribution-user_extraction-'.$this->user['id']);
        
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/distribution/index/extraction/applyinfo');
    }

    /**
     * 取货点信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     */
    public function ApplySave($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        $params['user'] = $this->user;
        return ExtractionService::ExtractionSave($params);
    }

    /**
     * 取货点切换页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     */
    public function SwitchInfo($params = [])
    {
        // 自提点地址列表
        $address = ConfigService::SiteTypeExtractionAddressList(null, $params);
        MyViewAssign('extraction_address', $address['data']);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('取货点切换', 1));
        
        MyViewAssign('is_herder', 0);
        MyViewAssign('is_footer', 0);
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/distribution/index/extraction/switchinfo');
    }

    /**
     * 取货点切换保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     */
    public function SwitchSave($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        $params['user'] = $this->user;
        return ExtractionService::ExtractionSwitchSave($params);
    }

    /**
     * 取货
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-03
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Take($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 发货操作
        $params['creator'] = $this->user['id'];
        $params['creator_name'] = $this->user['user_name_view'];
        $params['user_type'] = 'admin';
        return OrderService::OrderDelivery($params);
    }
}
?>