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
namespace app\plugins\membershiplevelvip\service;

use think\facade\Db;
use app\service\ResourcesService;
use app\plugins\membershiplevelvip\service\BusinessService;

/**
 * 会员等级服务层 - 会员等级
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class LevelService
{
    /**
     * 获取等级数据列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-22
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function DataList($params = [])
    {
        // 参数
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id asc' : trim($params['order_by']);

        // 获取数据
        $data = Db::name('PluginsMembershiplevelvipLevel')->field($field)->where($where)->order($order_by)->select()->toArray();

        // 数据处理
        return self::DataHandle($data, $params);
    }

    /**
     * 用户等级数据列表处理
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-27T01:08:23+0800
     * @param    [array]                   $data   [等级数据]
     * @param    [array]                   $params [输入参数]
     */
    public static function DataHandle($data, $params = [])
    {
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // 数据值美化
                $v['rules_min'] = PriceBeautify($v['rules_min']);
                $v['rules_max'] = PriceBeautify($v['rules_max']);
                $v['discount_rate'] = PriceBeautify($v['discount_rate']);

                // 付费规则
                $v['pay_period_rules'] = empty($v['pay_period_rules']) ? '' : json_decode($v['pay_period_rules'], true);

                // 图片地址
                $v['images_url'] = ResourcesService::AttachmentPathViewHandle($v['images_url']);

                // 创建时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = date('Y-m-d', $v['add_time']);
                $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);

                // 更新时间
                $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 获取等级数据保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-22
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function DataSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '1,30',
                'error_msg'         => '名称长度 1~30 个字符',
            ],
            [
                'checked_type'      => 'isset',
                'key_name'          => 'rules_min',
                'is_checked'        => 1,
                'error_msg'         => '请填写规则最小值',
            ],
            [
                'checked_type'      => 'isset',
                'key_name'          => 'rules_max',
                'is_checked'        => 1,
                'error_msg'         => '请填写规则最大值',
            ],
            [
                'checked_type'      => 'max',
                'key_name'          => 'discount_rate',
                'checked_data'      => 0.99,
                'is_checked'        => 1,
                'error_msg'         => '折扣率应输入 0.00~0.99 的数字,小数保留两位',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'order_price',
                'checked_data'      => 'CheckPrice',
                'is_checked'        => 1,
                'error_msg'         => '请输入有效的订单满金额',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'full_reduction_price',
                'checked_data'      => 'CheckPrice',
                'is_checked'        => 1,
                'error_msg'         => '请输入有效的满减金额',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 请求参数
        $p = [
            [
                'checked_type'      => 'eq',
                'key_name'          => 'rules_min',
                'checked_data'      => $params['rules_max'],
                'is_checked'        => 1,
                'error_msg'         => '规则最小值不能最大值相等',
            ],
            [
                'checked_type'      => 'eq',
                'key_name'          => 'rules_max',
                'checked_data'      => $params['rules_min'],
                'is_checked'        => 1,
                'error_msg'         => '规则最大值不能最小值相等',
            ],
        ];
        if(intval($params['rules_max']) > 0)
        {
            $p[] = [
                'checked_type'      => 'max',
                'key_name'          => 'rules_min',
                'checked_data'      => intval($params['rules_max']),
                'error_msg'         => '规则最小值不能大于最大值['.intval($params['rules_max']).']',
            ];
            $p[] = [
                'checked_type'      => 'min',
                'key_name'          => 'rules_max',
                'checked_data'      => intval($params['rules_min']),
                'error_msg'         => '规则最大值不能小于最小值['.intval($params['rules_min']).']',
            ];
        }
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 附件
        $data_fields = ['images_url'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);

        // 数据
        $data = [
            'name'                  => $params['name'],
            'rules_min'             => $params['rules_min'],
            'rules_max'             => $params['rules_max'],
            'images_url'            => $attachment['data']['images_url'],
            'is_enable'             => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            'discount_rate'         => isset($params['discount_rate']) ? $params['discount_rate'] : 0,
            'order_price'           => empty($params['order_price']) ? 0.00 : PriceNumberFormat($params['order_price']),
            'full_reduction_price'  => empty($params['full_reduction_price']) ? 0.00 : PriceNumberFormat($params['full_reduction_price']),
            'is_supported_pay_buy'  => isset($params['is_supported_pay_buy']) ? intval($params['is_supported_pay_buy']) : 0,
            'is_supported_renew'  => isset($params['is_supported_renew']) ? intval($params['is_supported_renew']) : 0,
            'pay_period_rules'      => self::PayPeriodRulesParams($params),
        ];

        // 不存在更新 则添加
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            if(Db::name('PluginsMembershiplevelvipLevel')->insertGetId($data) > 0)
            {
                return DataReturn('添加成功', 0);
            }
            return DataReturn('添加失败', -100);
        } else {
            $data['upd_time'] = time();
            if(Db::name('PluginsMembershiplevelvipLevel')->where(['id'=>intval($params['id'])])->update($data))
            {
                return DataReturn('编辑成功', 0);
            }
            return DataReturn('编辑失败', -100); 
        }
    }

    /**
     * 会员付费数据参数获取
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-10
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    private static function PayPeriodRulesParams($params)
    {
        $result = [];

        // 周期和费用必须
        if(!empty($params['pay_period_number']) && !empty($params['pay_period_price']) && is_array($params['pay_period_number']) && is_array($params['pay_period_price']) && count($params['pay_period_number']) == count($params['pay_period_price']))
        {
            // 返佣数据
            $commission = empty($params['pay_period_commission']) ? [] : $params['pay_period_commission'];

            // 循环处理规则数据
            foreach($params['pay_period_number'] as $k=>$v)
            {
                if(array_key_exists($k, $params['pay_period_price']))
                {
                    $value_unit = BusinessService::UserExpireTimeValueUnit($v);
                    $result[] = [
                        'number'        => $v,
                        'value'         => $value_unit['value'],
                        'unit'          => $value_unit['unit'],
                        'price'         => $params['pay_period_price'][$k],
                        'commission'    => array_key_exists($k, $commission) ? $commission[$k] : '',
                    ];
                }
            }
        }
        return empty($result) ? '' : json_encode($result);
    }

    /**
     * 数据删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function DataDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 删除操作
        if(Db::name('PluginsMembershiplevelvipLevel')->where(['id'=>intval($params['id'])])->delete())
        {
            return DataReturn('删除成功');
        }
        return DataReturn('删除失败或资源不存在', -100);
    }

    /**
     * 数据状态更新
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function DataStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'field',
                'error_msg'         => '操作字段有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'state',
                'checked_data'      => [0,1],
                'error_msg'         => '状态有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据更新
        if(Db::name('PluginsMembershiplevelvipLevel')->where(['id'=>intval($params['id'])])->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
        {
            return DataReturn('编辑成功');
        }
        return DataReturn('编辑失败或数据未改变', -100);
    }
}
?>