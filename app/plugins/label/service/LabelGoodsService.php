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
namespace app\plugins\label\service;

use think\facade\Db;
use app\service\GoodsService;

/**
 * 标签 - 标签商品服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class LabelGoodsService
{
    // 筛选排序规则列表
    public static $map_order_by_list = [
        [
            'name'  => '综合',
            'type'  => 'default',
            'value' => 'desc',
        ],
        [
            'name'  => '销量',
            'type'  => 'sales',
            'value' => 'desc',
        ],
        [
            'name'  => '热度',
            'type'  => 'access',
            'value' => 'desc',
        ],
        [
            'name'  => '价格',
            'type'  => 'price',
            'value' => 'desc',
        ],
        [
            'name'  => '最新',
            'type'  => 'new',
            'value' => 'desc',
        ],
    ];

    /**
     * 排序列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function LabelGoodsMapOrderByList($params = [])
    {
        $id = empty($params['id']) ? 0 : intval($params['id']);
        $ov = empty($params['ov']) ? ['default'] : explode('-', $params['ov']);
        $data = self::$map_order_by_list;
        foreach($data as &$v)
        {
            // 是否选中
            $v['active'] = ($ov[0] == $v['type']) ? 1 : 0;

            // url
            $temp_ov = '';
            if($v['type'] == 'default')
            {
                $temp_params = $params;
                unset($temp_params['ov']);
            } else {
                // 类型
                if($ov[0] == $v['type'])
                {
                    $v['value'] = ($ov[1] == 'desc') ? 'asc' : 'desc';
                }

                // 参数值
                $temp_ov = $v['type'].'-'.$v['value'];
                $temp_params = array_merge($params, ['ov'=>$temp_ov]);
            }
            $v['url'] = PluginsHomeUrl('label', 'index', 'detail', $temp_params);
        }
        return $data;
    }

    /**
     * 数据列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params  [输入参数]
     */
    public static function LabelGoodsList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'pg.id desc' : $params['order_by'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        // 获取数据
        $data = Db::name('PluginsLabelGoods')->alias('pg')->join('plugins_label pl', 'pg.label_id=pl.id')->join('goods g', 'g.id=pg.goods_id')->where($where)->field($field)->limit($m, $n)->order($order_by)->select()->toArray();
        return GoodsService::GoodsDataHandle($data, $params);
    }

    /**
     * 总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function LabelGoodsTotal($where = [])
    {
        return (int) Db::name('PluginsLabelGoods')->alias('pg')->join('plugins_label pl', 'pg.label_id=pl.id')->join('goods g', 'g.id=pg.goods_id')->where($where)->count();
    }

    /**
     * 条件
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-08-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function LabelGoodsMap($params = [])
    {
        // 基础条件
        $where = [
            ['pl.is_enable', '=', 1],
            ['pl.id', '=', intval($params['id'])],
            ['g.is_shelves', '=', 1],
        ];

        // 排序
        $order_by = 'g.access_count desc, g.sales_count desc, g.id desc';
        if(!empty($params['ov']))
        {
            // 数据库字段映射关系
            $fields = [
                'sales'     => 'g.sales_count',
                'access'    => 'g.access_count',
                'price'     => 'g.min_price',
                'new'       => 'g.id',
            ];

            // 参数判断
            $temp = explode('-', $params['ov']);
            if(count($temp) == 2 && $temp[0] != 'default' && array_key_exists($temp[0], $fields) && in_array($temp[1], ['desc', 'asc']))
            {
                $order_by = $fields[$temp[0]].' '.$temp[1];
            }
        }
        return [
            'field'     => 'g.id,g.title,g.simple_desc,g.images,g.price,g.min_price,g.max_price,g.sales_count,g.inventory,g.inventory_unit,g.is_exist_many_spec',
            'where'     => $where,
            'order_by'  => $order_by,
        ];
    }
}
?>