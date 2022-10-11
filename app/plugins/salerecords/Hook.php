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
namespace app\plugins\salerecords;

use app\plugins\salerecords\service\BaseService;
use app\plugins\salerecords\service\SaleRecordsService;

/**
 * 销售记录 - 钩子入口
 * @author   Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2019-04-11
 * @desc    description
 */
class Hook
{
    // 模块、控制器、方法
    private $module_name;
    private $controller_name;
    private $action_name;

    // 配置信息
    private $plugins_config;

    /**
     * 应用响应入口
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function handle($params = [])
    {
        // 钩子名称
        if(!empty($params['hook_name']))
        {
            // 当前模块/控制器/方法
            $this->module_name = RequestModule();
            $this->controller_name = RequestController();
            $this->action_name = RequestAction();

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            // 开关状态
            $is_home_bottom = (isset($this->plugins_config['is_home_bottom']) && $this->plugins_config['is_home_bottom'] == 1) ? 1 : 0;
            $is_goods_detail = (isset($this->plugins_config['is_goods_detail']) && $this->plugins_config['is_goods_detail'] == 1) ? 1 : 0;
   
            // 走钩子
            $ret = '';
            switch($params['hook_name'])
            {
                // 公共css
                case 'plugins_css' :
                    if(in_array($this->module_name.$this->controller_name.$this->action_name, ['indexindexindex', 'indexgoodsindex']) && ($is_home_bottom == 1 || $is_goods_detail == 1))
                    {
                        $ret = 'static/plugins/css/salerecords/index/public/style.css';
                    }
                    break;

                // 公共js
                case 'plugins_js' :
                    if($this->module_name.$this->controller_name.$this->action_name == 'indexgoodsindex' && $is_goods_detail == 1)
                    {
                        $ret = 'static/plugins/js/salerecords/index/public/style.js';
                    }
                    break;

                // 首页底部
                case 'plugins_view_home_floor_bottom' :
                    if($is_home_bottom == 1)
                    {
                        $ret = $this->HomeFloorBottom($params);
                    }
                    break;

                // 商品详情
                case 'plugins_view_goods_detail_base_bottom' :
                    if($is_goods_detail == 1 && !empty($params['goods']) && !empty($params['goods']['id']))
                    {
                        $params['goods_id'] = $params['goods']['id'];
                        $ret = $this->GoodsDetailBaseBottom($params);
                    }
                    break;

                // api 首页
                case 'plugins_service_base_data_return_api_index_index' :
                    if($is_home_bottom == 1)
                    {
                        $data = SaleRecordsService::HomeFloorBottom($this->plugins_config, $params);
                        if(!empty($data))
                        {
                            $params['data']['plugins_salerecords_data'] = [
                                'base'  => $this->plugins_config,
                                'data'  => $data,
                            ];
                        }
                    }
                    break;

                // api 商品详情
                case 'plugins_service_base_data_return_api_goods_detail' :
                    if($is_goods_detail == 1 && !empty($params['data']) && !empty($params['data']['goods']))
                    {
                        $params['goods_id'] = $params['data']['goods']['id'];
                        $data = SaleRecordsService::GoodsDetailBaseBottom($this->plugins_config, $params);
                        if(!empty($data))
                        {
                            $params['data']['plugins_salerecords_data'] = [
                                'base'  => $this->plugins_config,
                                'data'  => $data,
                            ];
                        }
                    }
                    break;
            }
            return $ret;
        }
    }

    /**
     * 商品详情
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsDetailBaseBottom($params = [])
    {
        $data = SaleRecordsService::GoodsDetailBaseBottom($this->plugins_config, $params);
        return MyView('../../../plugins/view/salerecords/index/public/goods_base_bottom', [
            'data'              => $data,
            'plugins_config'    => $this->plugins_config,
        ]);
    }

    /**
     * 首页底部
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function HomeFloorBottom($params = [])
    {
        $data = SaleRecordsService::HomeFloorBottom($this->plugins_config, $params);
        return MyView('../../../plugins/view/salerecords/index/public/home_bottom',[
            'data'              => $data,
            'plugins_config'    => $this->plugins_config,
        ]);
    }
}
?>