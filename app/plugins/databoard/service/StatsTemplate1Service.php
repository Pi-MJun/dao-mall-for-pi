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
namespace app\plugins\databoard\service;

use think\facade\Db;
use app\service\UserService;
use app\service\ResourcesService;
use app\plugins\databoard\service\BaseService;

/**
 * 数据看板 - 数据统计模板1服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2022-01-26
 * @desc    description
 */
class StatsTemplate1Service
{
    /**
     * 概述
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function Overview($params = [])
    {
        // 用户总数
        $where = [
            ['is_delete_time', '=', 0],
        ];
        $user_count = Db::name('User')->where($where)->count();
        // 订单总数
        $where = [
            ['is_delete_time', '=', 0],
        ];
        $order_count = Db::name('Order')->where($where)->count();
        // 订单完成总数
        $where = [
            ['is_delete_time', '=', 0],
            ['status', '=', 4],
        ];
        $order_done_count = Db::name('Order')->where($where)->count();
        // 有效订单总数
        $where = [
            ['is_delete_time', '=', 0],
            ['status', 'in', [2,3,4]],
        ];
        $order_income_price = PriceBeautify(PriceNumberFormat(Db::name('Order')->where($where)->sum('total_price')/10000));
        return [
            'user_count'            => $user_count,
            'order_count'           => $order_count,
            'order_done_count'      => $order_done_count,
            'order_income_price'    => $order_income_price,
        ];
    }

    /**
     * 最新订单、用户列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function NewOrderUserList($params = [])
    {
        // 订单
        $order_list = [];
        $ordeer = Db::name('Order')->field('total_price,status,order_model,add_time')->order('id desc')->limit(20)->select()->toArray();
        if(!empty($ordeer))
        {
            $order_status_list = MyConst('common_order_status');
            foreach($ordeer as $v)
            {
                $currency_symbol = ResourcesService::CurrencyDataSymbol();
                $order_list[] = [
                    'item1' => date('m-d H:i', $v['add_time']),
                    'item2' => $currency_symbol.$v['total_price'],
                    'item3' => ($v['order_model'] == 2 && $v['status'] == 2) ? '待取货' : (array_key_exists($v['status'], $order_status_list) ? $order_status_list[$v['status']]['name'] : '未知'),
                ];
            }
        }

        // 用户
        $user_list = [];
        $user = Db::name('User')->field('username,nickname,mobile,email,gender,add_time')->order('id desc')->limit(10)->select()->toArray();
        if(!empty($user))
        {
            foreach($user as $v)
            {
                $temp = UserService::UserHandle($v);
                $user_list[] = [
                    'item1' => date('m-d H:i', $v['add_time']),
                    'item2' => $temp['user_name_view'],
                    'item3' => $temp['gender_text'],
                ];
            }
        }

        return [
            'order_list'    => $order_list,
            'user_list'     => $user_list,
        ];
    }

    /**
     * 热销商品
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function GoodsHot($params = [])
    {
        // 获取订单id
        $where = [
            ['status', '<=', 4],
        ];
        if(!empty($params['start']))
        {
            $where[] = ['add_time', '>=', $params['start']];
        }
        if(!empty($params['end']))
        {
            $where[] = ['add_time', '<=', $params['end']];
        }
        $order_ids = Db::name('Order')->where($where)->column('id');

        // 获取订单详情热销商品
        if(empty($order_ids))
        {
            $data = [];
        } else {
            $data = Db::name('OrderDetail')->field('goods_id, sum(buy_number) AS value')->where('order_id', 'IN', $order_ids)->group('goods_id')->order('value desc')->limit(30)->select()->toArray();
        }
        if(!empty($data))
        {
            $names = Db::name('OrderDetail')->where('goods_id', 'in', array_column($data, 'goods_id'))->group('goods_id')->column('title', 'goods_id');
            foreach($data as &$v)
            {
                $v['name'] = $names[$v['goods_id']];
                if(mb_strlen($v['name'], 'utf-8') > 6)
                {
                    $v['name'] = mb_substr($v['name'], 0, 6, 'utf-8').'...';
                }
                unset($v['goods_id']);
            }
        }

        // 总数
        $goods_count = Db::name('Goods')->count();
        $sales_count = Db::name('Order')->alias('o')->join('order_detail od', 'o.id=od.order_id')->where('o.status', '<=', 4)->sum('od.buy_number');

        return [
            'goods'         => $data,
            'goods_count'   => $goods_count,
            'sales_count'   => $sales_count,
        ];
    }

    /**
     * 订单按月总量
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderMonthTotalNumber($params = [])
    {
        // 时间处理及计算
        $name_arr = [];
        $time = strtotime(date('Y-m-01 00:00:00'));
        $start = strtotime('-5 month', $time);
        $date = BaseService::DayCreate($start, $time, '+1 month');
        $date[] = [
            'start' => $time,
            'end'   => time(),
        ];
        $order_type_list = BaseService::OrderTypeList();
        foreach($date as $day)
        {
            // 当前日期名称
            $name_arr[] = date('n月', $day['start']);

            // 根据订单类型获取数量
            foreach($order_type_list as $v)
            {
                // 排除展示订单类型
                if($v['value'] == 1)
                {
                    continue;
                }

                // 获取订单
                $where = [
                    ['order_model', '=', $v['value']],
                    ['status', 'in', [2,3,4]],
                    ['add_time', '>=', $day['start']],
                    ['add_time', '<=', $day['end']],
                ];
                $value_arr[$v['value']][] = Db::name('Order')->where($where)->count();
            }
        }

        // 数据格式组装
        foreach($order_type_list as $v)
        {
            // 排除展示订单类型
            if($v['value'] == 1)
            {
                continue;
            }
            $data[] = [
                'name'      => $v['name'],
                'type'      => 'line',
                'smooth'    => true,
                'areaStyle' => (object) [],
                'data'      => empty($value_arr[$v['value']]) ? [] : $value_arr[$v['value']],
            ];
        }

        // 总数
        $where = [
            ['status', 'in', [2,3,4]],
            ['add_time', '>=', $start],
            ['add_time', '<=', time()],
        ];
        $user_count = Db::name('Order')->where($where)->count('distinct user_id');
        $order_count = Db::name('Order')->where($where)->count();

        // 数据组装
        return [
            'name_arr'      => $name_arr,
            'data'          => $data,
            'user_count'    => $user_count,
            'order_count'   => $order_count,
        ];
    }

    /**
     * 订单按天总量
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderDayTotalNumber($params = [])
    {
        // 24小时
        $where = [
            ['add_time', '>=', strtotime('-1 day')],
            ['add_time', '<=', time()],
            ['status', 'in', [2,3,4]],
        ];
        $day1_count = Db::name('Order')->where($where)->count();
        $day1_total_price = PriceBeautify(PriceNumberFormat(Db::name('Order')->where($where)->sum('total_price')));

        // 30天
        $where[0] = ['add_time', '>=', strtotime('-30 day')];
        $day30_count = Db::name('Order')->where($where)->count();
        $day30_total_price = PriceBeautify(PriceNumberFormat(Db::name('Order')->where($where)->sum('total_price')));

        // 90天
        $where[0] = ['add_time', '>=', strtotime('-90 day')];
        $day90_count = Db::name('Order')->where($where)->count();
        $day90_total_price = PriceBeautify(PriceNumberFormat(Db::name('Order')->where($where)->sum('total_price')));

        // 365天
        $where[0] = ['add_time', '>=', strtotime('-365 day')];
        $day365_count = Db::name('Order')->where($where)->count();
        $day365_total_price = PriceBeautify(PriceNumberFormat(Db::name('Order')->where($where)->sum('total_price')));

        return [
            'day1' => [
                'count'         => $day1_count,
                'total_price'   => $day1_total_price,
            ],
            'day30' => [
                'count'         => $day30_count,
                'total_price'   => $day30_total_price,
            ],
            'day90' => [
                'count'         => $day90_count,
                'total_price'   => $day90_total_price,
            ],
            'day365' => [
                'count'         => $day365_count,
                'total_price'   => $day365_total_price,
            ],
        ];
    }

    /**
     * 订单销售额
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function OrderSalesTotalPrice($params = [])
    {
        // 年
        $time = strtotime(date('Y-m-01 00:00:00'));
        $start = strtotime('-11 month', $time);
        $date = BaseService::DayCreate($start, $time, '+1 month');
        $date[] = [
            'start' => $time,
            'end'   => time(),
        ];
        $year = self::OrderSalesTotalPriceHandle($date);

        // 季度
        $time = strtotime(date('Y-m-01 00:00:00'));
        $start = strtotime('-2 month', $time);
        $date = BaseService::DayCreate($start, $time, '+1 month');
        $date[] = [
            'start' => $time,
            'end'   => time(),
        ];
        $quarter = self::OrderSalesTotalPriceHandle($date);

        // 月
        $time = strtotime(date('Y-m-d 00:00:00'));
        $start = strtotime(date('Y-m-01 00:00:00'));
        $date = BaseService::DayCreate($start, $time, '+1 day');
        $date[] = [
            'start' => $time,
            'end'   => time(),
        ];
        $month = self::OrderSalesTotalPriceHandle($date, 'j日');

        // 周
        $time = strtotime(date('Y-m-d 00:00:00'));
        $start = strtotime('-6 day');
        $date = BaseService::DayCreate($start, $time, '+1 day');
        $date[] = [
            'start' => $time,
            'end'   => time(),
        ];
        $week = self::OrderSalesTotalPriceHandle($date, 'j日');

        // 数据组装
        return [
            'year'      => $year,
            'quarter'   => $quarter,
            'month'     => $month,
            'week'      => $week,
        ];
    }

    /**
     * 订单销售额总计处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]          $date [日期]
     * @param   [string]         $date [标题日期格式]
     */
    public static function OrderSalesTotalPriceHandle($date, $date_str = 'n月')
    {
        $order_status_list = MyConst('common_order_status');
        foreach($date as $day)
        {
            // 当前日期名称
            $name_arr[] = date($date_str, $day['start']);

            // 根据订单状态获取数量
            foreach($order_status_list as $v)
            {
                // 获取订单
                $where = [
                    ['status', '=', $v['id']],
                    ['add_time', '>=', $day['start']],
                    ['add_time', '<=', $day['end']],
                ];
                $value_arr[$v['id']][] = PriceBeautify(PriceNumberFormat(Db::name('Order')->where($where)->sum('total_price')));
            }
        }

        // 数据格式组装
        foreach($order_status_list as $v)
        {
            $data[] = [
                'name'      => $v['name'],
                'type'      => 'line',
                'smooth'    => true,
                'data'      => empty($value_arr[$v['id']]) ? [] : $value_arr[$v['id']],
            ];
        }
        return [
            'name_arr'      => $name_arr,
            'data'          => $data,
        ];
    }

    /**
     * 支付方式分布
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function PaymentScatter($params = [])
    {
        $where = [
            ['business_type', '<>', ''],
            ['status', '=', 1],
        ];
        $payment = Db::name('PayLog')->where($where)->group('payment_name')->order('value desc')->column('payment_name as name, count(*) as value');
        $result = [];
        if(!empty($payment))
        {
            $other = ['name'=>'其他', 'value'=>0];
            $count = array_sum(array_column($payment, 'value'));
            foreach($payment as $k=>$v)
            {
                // 仅展示前面3个，后面的归类到其他里面
                if($k <= 2)
                {
                    $v['rate'] = intval(($v['value']/$count)*100);
                    $result[] = $v;
                } else {
                    $other['value'] += $v['value'];
                }
            }
            if($other['value'] > 0)
            {
                $other['rate'] = intval(($other['value']/$count)*100);
                $result[] = $other;
            }
            // 超出100则从最大的一个中扣除
            $rate = array_column($result, 'rate');
            $sum = array_sum($rate);
            if($sum > 100)
            {
                $index = array_search(max($rate), $result);
                $result[$index]['rate'] -= $sum-100;
            }
        }
        return $result;
    }

    /**
     * 当月销售进度
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function MonthSalesSpeed($params = [])
    {
        // 上月销售额
        $time = strtotime(date('Y-m-01 00:00:00'));
        $where = [
            ['status', 'in', [2,3,4]],
            ['add_time', '>=', strtotime('-1 month', $time)],
            ['add_time', '<', $time],
        ];
        $last_month = Db::name('Order')->where($where)->sum('total_price');

        // 当月
        $where = [
            ['status', 'in', [2,3,4]],
            ['add_time', '>=', $time],
            ['add_time', '<=', time()],
        ];
        $current_month = Db::name('Order')->where($where)->sum('total_price');

        // 增长计算
        $increase = ($last_month > 0) ? PriceBeautify(PriceNumberFormat((($current_month-$last_month)/$last_month)*100)) : ($current_month > 0 ? 100 : 0);
        $speed = ($last_month > 0) ? PriceBeautify(PriceNumberFormat(($current_month/$last_month)*100)) : ($current_month > 0 ? 100 : 0);

        return [
            'current_month' => PriceBeautify(PriceNumberFormat($current_month/10000)),
            'increase'      => $increase,
            'speed'         => $speed,
        ];
    }

    /**
     * 全国热榜
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function NationalHot($params = [])
    {
        // 全部热榜商品、前三个
        $time = strtotime('-30 day');
        $where = [
            ['o.status', '<=', 4],
            ['o.add_time', '>=', $time],
            ['o.add_time', '<=', time()],
        ];
        $goods_hot = Db::name('Order')->alias('o')->join('order_detail od', 'o.id=od.order_id')->where($where)->group('od.goods_id')->order('count desc')->limit(3)->column('od.title,sum(od.buy_number) as count');

        // 各省
        $order_province = Db::name('Order')->alias('o')->join('order_address oa', 'o.id=oa.order_id')->where($where)->group('oa.province_name')->limit(5)->column('oa.province_name');
        $province = [];
        if(!empty($order_province))
        {
            foreach($order_province as $v)
            {
                $where = [
                    ['o.status', '<=', 4],
                    ['o.add_time', '>=', $time],
                    ['o.add_time', '<=', time()],
                    ['oa.province_name', '=', $v],
                ];
                // 销售金额
                $sales = Db::name('Order')->alias('o')->join('order_detail od', 'o.id=od.order_id')->join('order_address oa', 'o.id=oa.order_id')->where($where)->sum('o.total_price');
                // 最高6个热销产品
                $goods = Db::name('Order')->alias('o')->join('order_detail od', 'o.id=od.order_id')->join('order_address oa', 'o.id=oa.order_id')->where($where)->group('od.goods_id')->order('o.id desc')->limit(6)->column('od.title,sum(od.buy_number) as count');
                if(!empty($goods))
                {
                    foreach($goods as &$g)
                    {
                        $g['flag'] = rand(0, 1) == 1;
                    }
                }
                $province[] = [
                    'name'  => $v,
                    'sales' => PriceBeautify(PriceNumberFormat($sales/10000)),
                    'flag'  => rand(0, 1) == 1,
                    'goods' => $goods,
                ];
            }
        }
        return [
            'goods_hot' => $goods_hot,
            'province'  => $province,
        ];
    }

    /**
     * 地图
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-26
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function RegionMap($params = [])
    {
        $where = [
            ['o.status', '<=', 4],
        ];
        $order_province = Db::name('Order')->alias('o')->join('order_address oa', 'o.id=oa.order_id')->where($where)->group('oa.province_name')->column('oa.province_name');
        $result = [];
        if(!empty($order_province))
        {
            foreach($order_province as $v)
            {
                $where = [
                    ['o.status', '<=', 4],
                    ['oa.province_name', '=', $v],
                ];
                $count = Db::name('Order')->alias('o')->join('order_address oa', 'o.id=oa.order_id')->where($where)->group('o.id')->count('o.id');
                $result[] = [
                    'name'  => str_replace(['省', '市', '壮族自治区', '回族自治区', '维吾尔自治区', '特别行政区', '自治州', '自治区'], '', $v),
                    'value' => $count,
                ];
            }
        }
        return $result;
    }
}
?>