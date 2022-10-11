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
namespace app\plugins\seckill;

use think\facade\Db;
use app\service\SystemBaseService;
use app\plugins\seckill\service\BaseService;

/**
 * 限时秒杀 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    // 模块、控制器、方法
    private $module_name;
    private $controller_name;
    private $action_name;

    // 配置信息
    private $plugins_config;

    // 商品是否实际使用
    public $is_actual_discount_goods = 0;

    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-09T14:25:44+0800
     * @param    [array]          $params [输入参数]
     */
    public function handle($params = [])
    {
        // 后端访问不处理
        if(isset($params['params']['is_admin_access']) && $params['params']['is_admin_access'] == 1)
        {
            return DataReturn('无需处理', 0);
        }

        // 钩子名称
        $ret = '';
        if(!empty($params['hook_name']))
        {
            // 当前模块/控制器/方法
            $this->module_name = RequestModule();
            $this->controller_name = RequestController();
            $this->action_name = RequestAction();
            $mca = $this->module_name.$this->controller_name.$this->action_name;

            // 配置信息
            $config = BaseService::BaseConfig();
            $this->plugins_config = empty($config['data']) ? [] : $config['data'];

            switch($params['hook_name'])
            {
                case 'plugins_css' :
                    $ret = 'static/plugins/css/seckill/index/style.css';
                    break;

                // js
                case 'plugins_js' :
                    $ret = 'static/plugins/js/seckill/index/style.js';
                    break;

                // 在前面添加导航
                case 'plugins_service_navigation_header_handle' :
                    if(is_array($params['header']))
                    {
                        // 获取应用数据
                        if(!empty($this->plugins_config['application_name']))
                        {
                            $nav = [
                                'id'                    => 0,
                                'pid'                   => 0,
                                'name'                  => $this->plugins_config['application_name'],
                                'url'                   => PluginsHomeUrl('seckill', 'index', 'index'),
                                'data_type'             => 'custom',
                                'is_show'               => 1,
                                'is_new_window_open'    => 0,
                                'items'                 => [],
                            ];
                            array_unshift($params['header'], $nav);
                        }
                    }
                    break;

                // 商品数据处理后
                case 'plugins_service_goods_handle_end' :
                    if($this->module_name != 'admin')
                    {
                        // 是否支持优惠
                        if(SystemBaseService::IsGoodsDiscount($params))
                        {
                            $this->GoodsHandleEnd($params);
                        }

                        // 使用优惠处理
                        if(!empty($params) && !empty($params['goods']) && !empty($params['goods']['id']))
                        {
                            SystemBaseService::GoodsDiscountRecord($params['goods']['id'], 'seckill', $this->is_actual_discount_goods);
                        }
                    }
                    break;

                // 商品规格基础数据
                case 'plugins_service_goods_spec_base' :
                    // 是否支持优惠
                    if(SystemBaseService::IsGoodsDiscount($params))
                    {
                        $this->GoodsSpecBase($params);
                    }
                    break;

                // 商品页面基础信息顶部钩子
                case 'plugins_view_goods_detail_base_top' :
                    if($mca == 'indexgoodsindex')
                    {
                        // 是否已支持优惠
                        if(!empty($params['goods']) && !empty($params['goods']['id']) && SystemBaseService::IsGoodsDiscountRecord($params['goods']['id'], 'seckill'))
                        {
                            $ret = $this->GoodsDetailBaseTopHtml($params);
                        }
                    }
                    break;

                // 楼层数据上面
                case 'plugins_view_home_floor_top' :
                    $ret = $this->HomeFloorTopAdv($params);
                    break;

                // 首页接口数据
                case 'plugins_service_base_data_return_api_index_index' :
                    $ret = $this->IndexResultHandle($params);
                    break;

                // 商品接口数据
                case 'plugins_service_base_data_return_api_goods_detail' :
                    $ret = $this->GoodsResultHandle($params);
                    break;
            }
        }
        return $ret;
    }

    /**
     * 商品接口数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsResultHandle($params = [])
    {
        if(!empty($params['data']) && !empty($params['data']['goods']))
        {
            $ret = BaseService::GoodsDetailCountdown($params['data']['goods']['id']);
            if($ret['code'] == 0 && !empty($ret['data']))
            {
                $params['data']['plugins_seckill_data'] = $ret['data'];
            }
        }
    }

    /**
     * 首页接口数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function IndexResultHandle($params = [])
    {
        if(isset($this->plugins_config['is_home_show']) && $this->plugins_config['is_home_show'] == 1)
        {
            // 基础数据
            $ret = BaseService::SeckillData($this->plugins_config, ['where'=>['is_recommend'=>1]]);
            if(!empty($ret['data']))
            {
                $params['data']['plugins_seckill_data'] = $ret['data'];
            }
        }
    }

    /**
     * 首页楼层顶部秒杀推荐
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-06T16:16:34+0800
     * @param    [array]          $params [输入参数]
     */
    public function HomeFloorTopAdv($params = [])
    {
        // 基础数据
        if(isset($this->plugins_config['is_home_show']) && $this->plugins_config['is_home_show'] == 1)
        {
            $ret = BaseService::SeckillData($this->plugins_config, ['where'=>['is_recommend'=>1]]);
            MyViewAssign('plugins_seckill_countdown', $ret['data']['time']);
            MyViewAssign('plugins_seckill_is_valid', $ret['data']['is_valid']);

            // 商品数据
            MyViewAssign('plugins_seckill_goods', $ret['data']['goods']);

            MyViewAssign('plugins_config', $this->plugins_config);
            return MyView('../../../plugins/view/seckill/index/public/home');
        }
    }

    /**
     * 商品规格基础数据
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsSpecBase($params = [])
    {
        if(isset($this->plugins_config['time_start']) && isset($this->plugins_config['time_end']))
        {
            $time = BaseService::TimeCalculate($this->plugins_config['time_start'], $this->plugins_config['time_end']);
            if($time['status'] == 1)
            {
                $ret = Db::name('PluginsSeckillGoods')->where(['goods_id'=>$params['data']['spec_base']['goods_id']])->find();
                if(!empty($ret))
                {
                    if(isset($params['data']['spec_base']['price']))
                    {
                        // 使用销售价作为原价
                        if(isset($params['data']['spec_base']['original_price']) && isset($this->plugins_config['is_actas_price_original']) && $this->plugins_config['is_actas_price_original'] == 1)
                        {
                            $params['data']['spec_base']['original_price'] = $params['data']['spec_base']['price'];
                        }

                        // 价格处理
                        $params['data']['spec_base']['price'] = $this->PriceCalculate($params['data']['spec_base']['goods_id'], $params['data']['spec_base']['price']);
                    }
                }
            }
        }
    }

    /**
     * 商品处理结束钩子
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsHandleEnd($params = [])
    {
        if(!empty($params['goods']) && isset($this->plugins_config['time_start']) && isset($this->plugins_config['time_end']))
        {
            // 当当前请求是否秒杀首页、秒杀首页在待开始的情况下则使用秒杀价格信息
            $pn = input('pluginsname');
            $pc = input('pluginscontrol', 'index');
            $pa = input('pluginsaction', 'index');
            $is_seckill_home = (!empty($pn) && !empty($pc) && !empty($pa) && $pn.$pc.$pa == 'seckillindexindex');

            // key字段
            $key_field = empty($params['params']['data_key_field']) ? 'id' : $params['params']['data_key_field'];
            if(isset($params['goods'][$key_field]))
            {
                // 秒杀基础信息
                $time = BaseService::TimeCalculate($this->plugins_config['time_start'], $this->plugins_config['time_end']);
                if($time['status'] == 1 || ($time['status'] == 0 && $is_seckill_home))
                {
                    $ret = Db::name('PluginsSeckillGoods')->where(['goods_id'=>$params['goods'][$key_field]])->find();
                    if(!empty($ret))
                    {
                        // 使用销售价作为原价
                        $is_actas_price_original = isset($this->plugins_config['is_actas_price_original']) && $this->plugins_config['is_actas_price_original'] == 1;

                        // 无价格字段则不处理
                        if(isset($params['goods']['price']))
                        {
                            // 使用销售价作为原价
                            if(isset($params['goods']['original_price']) && $is_actas_price_original)
                            {
                                $params['goods']['original_price'] = $params['goods']['price'];
                            }
                            
                            // 价格处理
                            $params['goods']['price'] = $this->PriceCalculate($params['goods'][$key_field], $params['goods']['price']);
                            $goods_detail_icon = empty($this->plugins_config['goods_detail_icon']) ? '秒杀价' : $this->plugins_config['goods_detail_icon'];
                            $params['goods']['show_field_price_text'] = '<a href="'.PluginsHomeUrl('seckill', 'index', 'index').'" class="plugins-seckill-goods-price-icon" title="'.$goods_detail_icon.'">'.$goods_detail_icon.'</a>';
                        }

                        // 最低价最高价
                        if(isset($params['goods']['min_price']))
                        {
                            // 使用销售价作为原价
                            if(isset($params['goods']['min_original_price']) && $is_actas_price_original)
                            {
                                $params['goods']['min_original_price'] = $params['goods']['min_price'];
                            }

                            // 价格处理
                            $params['goods']['min_price'] = $this->PriceCalculate($params['goods'][$key_field], $params['goods']['min_price']);
                        }
                        if(isset($params['goods']['max_price']))
                        {
                            // 使用销售价作为原价
                            if(isset($params['goods']['max_original_price']) && $is_actas_price_original)
                            {
                                $params['goods']['max_original_price'] = $params['goods']['max_price'];
                            }

                            // 价格处理
                            $params['goods']['max_price'] = $this->PriceCalculate($params['goods'][$key_field], $params['goods']['max_price']);
                        }

                        // 使用优惠标记
                        $this->is_actual_discount_goods = 1;
                    }
                }
            }
        }
    }

    /**
     * 商品页面html
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function GoodsDetailBaseTopHtml($params = [])
    {
        if(!empty($params['goods_id']))
        {
            $ret = Db::name('PluginsSeckillGoods')->where(['goods_id'=>$params['goods_id']])->find();
            if(!empty($ret))
            {
                if(isset($this->plugins_config['time_start']) && isset($this->plugins_config['time_end']))
                {
                    $time = BaseService::TimeCalculate($this->plugins_config['time_start'], $this->plugins_config['time_end']);
                    MyViewAssign('plugins_countdown', $time);
                    MyViewAssign('plugins_config', $this->plugins_config);
                    return MyView('../../../plugins/view/seckill/index/public/countdown');
                }
            }
        }
        return '';
    }

    /**
     * 价格处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [int]            $goods_id [商品id]
     * @param   [float]          $price    [商品价格]
     */
    private function PriceCalculate($goods_id, $price)
    {
        $data = Db::name('PluginsSeckillGoods')->where(['goods_id'=>$goods_id])->find();
        if(!empty($data))
        {
            $price = BaseService::PriceCalculate($price, $data['discount_rate'], $data['dec_price']);
        }
        return $price;
    }
}
?>