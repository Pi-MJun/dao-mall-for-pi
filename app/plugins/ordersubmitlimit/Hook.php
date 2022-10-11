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
namespace app\plugins\ordersubmitlimit;

use think\facade\Db;
use app\service\PluginsService;
use app\service\UserService;
use app\plugins\ordersubmitlimit\service\BaseService;

/**
 * 订单提交限制 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-11T21:51:08+0800
 */
class Hook
{
    // 配置信息
    private $base_config;

    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-11T14:25:44+0800
     * @param    [array]          $params [输入参数]
     */
    public function handle($params = [])
    {
        // 钩子名称
        $ret = '';
        if(!empty($params['hook_name']))
        {
            // 配置信息
            $config = BaseService::BaseConfig();
            $this->base_config = empty($config['data']) ? [] : $config['data'];

            switch($params['hook_name'])
            {
                // 订单提交限制
                case 'plugins_service_buy_order_insert_begin' :
                    $ret = $this->BuyOrderInsertBeginHandle($params);
                    break;

                // 商品详情页面导航购买按钮处理
                case 'plugins_service_goods_buy_nav_button_handle' :
                    $this->GoodsDetailBuyNavButtonContent($params);
                    break;
            }
        }
        return $ret;
    }

    /**
     * 商品详情页面导航购买按钮处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsDetailBuyNavButtonContent($params = [])
    {
        // 单品限制
        if(isset($this->base_config['is_goods_all_order_limit']) && intval($this->base_config['is_goods_all_order_limit']) == 1 && !empty($params) && !empty($params['goods']) && !empty($params['data']) && is_array($params['data']) && !empty($params['goods']['buy_max_number']))
        {
            // 当前登录用户
            $user = UserService::LoginUserInfo();
            if(!empty($user))
            {
                // 获取用户已购商品所有总数
                $where = [
                    ['od.goods_id', '=', $params['goods']['id']],
                    ['o.status', 'not in', [5,6]],
                    ['o.user_id', '=', $user['id']],
                ];
                $buy_number = Db::name('Order')->alias('o')->join('order_detail od', 'o.id=od.order_id')->where($where)->sum('od.buy_number');
                // 是否超过限制
                if(!empty($buy_number) && $buy_number >= $params['goods']['buy_max_number'])
                {
                    $text = empty($this->base_config['goods_detail_exceed_limit_tips']) ? '超过限购' : $this->base_config['goods_detail_exceed_limit_tips'];
                    $params['data'] = [];
                    $params['error'] = $text;
                }
            }
        }
    }

    /**
     * 订单提交限制
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-11
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    private function BuyOrderInsertBeginHandle($params = [])
    {
        // 必须配置并且必须有订单信息
        if(!empty($this->base_config) && !empty($params['order']))
        {
            // 订单模式限制
            if(!empty($this->base_config['order_model']) && is_array($this->base_config['order_model']))
            {
                // 订单模式
                $order_model = isset($params['order']['order_model']) ? intval($params['order']['order_model']) : 0;
                if(!in_array($order_model, $this->base_config['order_model']))
                {
                    return DataReturn('无需限制', 0);
                }
            }

            // 订单金额限制
            if(!empty($this->base_config['order_price_limit']))
            {
                // 限制类型
                $price_limit_type_list = BaseService::$price_limit_type_list;
                $order_price_limit_type = isset($this->base_config['order_price_limit_type']) ? intval($this->base_config['order_price_limit_type']) : 0;

                // 字段类型
                $price_field = isset($price_limit_type_list[$order_price_limit_type]) ? $price_limit_type_list[$order_price_limit_type]['field'] : 'price';
                if(!isset($params['order'][$price_field]))
                {
                    return DataReturn('插件有误[订单限制]', -1);
                }

                // 限制金额
                $order_price_limit = PriceNumberFormat($this->base_config['order_price_limit']);
                if($params['order'][$price_field] < $order_price_limit)
                {
                    return DataReturn('金额小于'.$order_price_limit.'π', -1);
                }
            }

            // 订单商品
            if(!empty($params['goods']))
            {
                // 数量限制
                $order_buy_number_limit = isset($this->base_config['order_buy_number_limit']) ? intval($this->base_config['order_buy_number_limit']) : 0;
                if($order_buy_number_limit > 0)
                {
                    // 数量类型
                    $order_buy_number_limit_type = isset($this->base_config['order_buy_number_limit_type']) ? intval($this->base_config['order_buy_number_limit_type']) : 0;
                    $count = 0;
                    $title = '商品';
                    switch($order_buy_number_limit_type)
                    {
                        // suk 数量
                        case 0 :
                            $count = array_sum(array_column($params['goods'], 'buy_number'));
                            $title = '数量';
                            break;

                        // 商品数量
                        case 1 :
                            $count = count($params['goods']);
                            $title = '商品';
                            break;

                        // 未配置项
                        default :
                            return DataReturn('插件有误[订单限制]', -1);
                    }
                    if($count < $order_buy_number_limit)
                    {
                        return DataReturn($title.'小于'.$order_buy_number_limit.'件', -1);
                    }
                }

                // 单品限制
                if(isset($this->base_config['is_goods_all_order_limit']) && intval($this->base_config['is_goods_all_order_limit']) == 1 && !empty($params['params']) && !empty($params['params']['user']))
                {
                    // 获取商品最大限购数量
                    $where = [
                        ['id', 'in', array_column($params['goods'], 'goods_id')],
                        ['buy_max_number', '>', 0],
                    ];
                    $goods = Db::name('Goods')->where($where)->column('buy_max_number', 'id');
                    if(!empty($goods))
                    {
                        // 获取用户已购商品所有总数
                        $where = [
                            ['od.goods_id', 'in', array_keys($goods)],
                            ['o.status', 'not in', [5,6]],
                            ['o.user_id', '=', $params['params']['user']['id']],
                        ];
                        $order_goods = Db::name('Order')->alias('o')->join('order_detail od', 'o.id=od.order_id')->where($where)->column('sum(od.buy_number) as buy_number', 'goods_id');
                        $goods_buy_number = [];
                        foreach($params['goods'] as $v)
                        {
                            // 是否存在商品限制中
                            if(!array_key_exists($v['goods_id'], $goods))
                            {
                                continue;
                            }

                            // 购买数量
                            if(!array_key_exists($v['goods_id'], $goods_buy_number))
                            {
                                $goods_buy_number[$v['goods_id']] = 0;
                            }
                            $goods_buy_number[$v['goods_id']] += $v['buy_number'];

                            // 是否存在已经购买的订单
                            if(!empty($order_goods) && array_key_exists($v['goods_id'], $order_goods))
                            {
                                $goods_buy_number[$v['goods_id']] += $order_goods[$v['goods_id']];
                            }
                        }
                        if(!empty($goods_buy_number))
                        {
                            foreach($params['goods'] as $v)
                            {
                                // 是否超过限制
                                if(isset($goods_buy_number[$v['goods_id']]) && $goods_buy_number[$v['goods_id']] > $goods[$v['goods_id']])
                                {
                                    return DataReturn($v['title'].'超过商品数量限制('.$goods_buy_number[$v['goods_id']].'>'.$goods[$v['goods_id']].')', -1);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>