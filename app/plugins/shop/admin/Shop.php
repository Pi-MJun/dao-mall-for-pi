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
namespace app\plugins\shop\admin;

use app\plugins\shop\admin\Common;
use app\plugins\shop\service\BaseService;
use app\plugins\shop\service\ShopService;
use app\plugins\shop\service\ShopCategoryService;

/**
 * 多商户 - 店铺管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Shop extends Common
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
        $total = ShopService::ShopTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    =>  $this->page_size,
            'total'     =>  $total,
            'where'     =>  $this->data_request,
            'page'      =>  $this->page,
            'url'       =>  PluginsAdminUrl('shop', 'shop', 'index'),
        ];
        $page = new \base\Page($page_params);

        // 获取列表
        $data_params = [
            'm'             => $page->GetPageStarNumber(),
            'n'             => $this->page_size,
            'where'         => $this->form_where,
            'order_by'      => $this->form_order_by['data'],
            'user_type'     => 'admin',
        ];
        $ret = ShopService::ShopList($data_params);

        // 基础参数赋值
        MyViewAssign('params', $this->data_request);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);
        return MyView('../../../plugins/view/shop/admin/shop/index');
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
                'm'         => 0,
                'n'         => 1,
                'where'     => $where,
                'user_type' => 'admin',
            ];
            $ret = ShopService::ShopList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
            }
        }
        MyViewAssign('data', $data);
        return MyView('../../../plugins/view/shop/admin/shop/detail');
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
            $ret = ShopService::ShopList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
            }
        }
        MyViewAssign('data', $data);

        // 店铺分类
        $category = ShopCategoryService::ShopCategoryAll(['field'=>'id,name']);
        MyViewAssign('shop_category', $category['data']);

        // 静态数据
        MyViewAssign('plugins_week_list', BaseService::$plugins_week_list);
        MyViewAssign('plugins_settle_type', BaseService::$plugins_settle_type);
        MyViewAssign('plugins_auth_type_list', BaseService::$plugins_auth_type_list);
        MyViewAssign('plugins_shop_data_model_list', BaseService::$plugins_shop_data_model_list);

        // 加载百度地图api
        MyViewAssign('is_load_baidu_map_api', 1);

        // 编辑器文件存放地址定义
        $user_id = empty($data['user_id']) ? 0 : $data['user_id'];
        MyViewAssign('editor_path_type', 'plugins_shop-user_shop-'.$user_id);
        return MyView('../../../plugins/view/shop/admin/shop/saveinfo');
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Save($params = [])
    {
        $params['user_type'] = 'admin';
        $params['base_config'] = $this->base_config;
        return ShopService::ShopSave($params);
    }

    /**
     * 状态操作
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Status($params = [])
    {
        $params['base_config'] = $this->base_config;
        return ShopService::ShopStatusUpdate($params);
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
        $params['base_config'] = $this->base_config;
        return ShopService::ShopDelete($params);
    }
}
?>