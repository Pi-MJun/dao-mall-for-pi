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
namespace app\plugins\intellectstools\service;

use think\facade\Db;
use app\service\MessageService;
use app\plugins\intellectstools\service\OrderBaseService;

/**
 * 智能工具箱 - 订单改价服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class OrderPriceService
{
    // 改价业务标记
    public static $plugins_business_value = 'plugins_intellectstools_order_price';

    /**
     * 获取订单详情信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-15
     * @desc    description
     * @param   [array]          $params [订单数据]
     */
    public static function OrderDetail($data = [])
    {
        // 状态判断
        if(!in_array($data['status'], OrderBaseService::$operate_price_order_status))
        {
            $order_status_list = MyConst('common_order_status');
            return DataReturn('订单状态不可操作['.$order_status_list[$data['status']]['name'].']', -1);
        }

        // 数据处理
        $plugins_business_opt_type = 0;
        $plugins_business_opt_price = '0.00';
        $data['order_opt_total_price'] = $data['total_price'];
        if(!empty($data['extension_data']) && is_array($data['extension_data']))
        {
            foreach($data['extension_data'] as $v)
            {
                // 是否存在已修改价格
                if(!empty($v['business']) && $v['business'] == self::$plugins_business_value)
                {
                    if($v['type'] == 1)
                    {
                        $data['order_opt_total_price'] = $data['total_price']-$v['price'];
                    } else {
                        $data['order_opt_total_price'] = $data['total_price']+$v['price'];
                    }
                    $plugins_business_opt_type = $v['type'];
                    $plugins_business_opt_price = $v['price'];
                }
            }
        }
        $data['plugins_business_opt_type'] = $plugins_business_opt_type;
        $data['plugins_business_opt_price'] = $plugins_business_opt_price;

        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 订单价格修改
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-15
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderPriceSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'in',
                'key_name'          => 'opt_type',
                'checked_data'      => [0,1],
                'error_msg'         => '操作类型有误',
            ],
            [
                'checked_type'      => 'isset',
                'key_name'          => 'opt_price',
                'error_msg'         => '请输入有效的操作金额',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '订单id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        $opt_type = intval($params['opt_type']);
        $opt_price = PriceNumberFormat($params['opt_price']);

        // 获取订单信息
        $ret = OrderBaseService::OrderDetail($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 开始计算
        $order_price = $ret['data']['price'];
        $increase_price = 0;
        $preferential_price = 0;

        // 扩展数据处理
        $key = -1;
        if(!empty($ret['data']['extension_data']) && is_array($ret['data']['extension_data']))
        {
            foreach($ret['data']['extension_data'] as $k=>$v)
            {
                // 排除当前插件的费用
                if(isset($v['business']) && $v['business'] == self::$plugins_business_value)
                {
                    $key = $k;
                } else {
                    if($v['type'] == 1)
                    {
                        $increase_price += $v['price'];
                    } else {
                        $preferential_price += $v['price'];
                    }
                }
            }
        } else {
            $ret['data']['extension_data'] = [];
        }

        // 获取订单货币数据
        $currency_data = $ret['data']['currency_data'];

        // 扩展数据
        if($opt_price > 0)
        {
            $ext = [
                'name'       => empty($params['opt_name']) ? '管理员修改' : $params['opt_name'],
                'price'      => $opt_price,
                'type'       => $opt_type,
                'tips'       => (($opt_type == 1) ? '+' : '-').$currency_data['currency_symbol'].$opt_price.'元',
                'business'   => self::$plugins_business_value,
            ];
            if($key == -1)
            {
                $ret['data']['extension_data'][] = $ext;
            } else {
                $ret['data']['extension_data'][$key] = $ext;
            }
        } else {
            if($key != -1)
            {
                unset($ret['data']['extension_data'][$key]);
                if(!empty($ret['data']['extension_data']))
                {
                    sort($ret['data']['extension_data']);
                }
            }
        }
        

        // 当前操作类型
        if($opt_type == 1)
        {
            $increase_price += $opt_price;
        } else {
            $preferential_price += $opt_price;
        }

        // 更新数据
        $data = [
            'total_price'       => PriceNumberFormat(($order_price+$increase_price)-$preferential_price),
            'increase_price'    => $increase_price,
            'preferential_price'=> $preferential_price,
            'extension_data'    => empty($ret['data']['extension_data']) ? '' : json_encode($ret['data']['extension_data']),
            'upd_time'          => time(),
        ];
        if(Db::name('Order')->where(['id'=>$ret['data']['id']])->update($data))
        {
            // 消息通知
            self::UserMessageNotice($ret['data']['user_id'], $ret['data']['id'], $ret['data']['order_no'], $data['total_price'], $currency_data['currency_symbol']);

            return DataReturn('修改成功', 0);
        }
        return DataReturn('修改失败', -100);
    }

    /**
     * 消息通知
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-02-15
     * @desc    description
     * @param   [int]        $user_id           [用户id]
     * @param   [int]        $order_id          [订单id]
     * @param   [int]        $order_no          [订单号]
     * @param   [float]      $total_price       [金额]
     * @param   [string]     $currency_symbol   [货币符号]
     */
    private static function UserMessageNotice($user_id, $order_id, $order_no, $total_price, $currency_symbol)
    {
        // 是否发送站内信
        $msg = '管理员将订单'.$order_no.', 价格修改为'.$currency_symbol.$total_price.'元, 请尽快支付哦！';
        MessageService::MessageAdd($user_id, '订单价格修改', $msg, OrderBaseService::$message_business_type_order_price, $order_id);
    }
}
?>