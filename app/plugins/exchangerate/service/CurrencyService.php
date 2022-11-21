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
namespace app\plugins\exchangerate\service;

use think\facade\Db;

/**
 * 汇率 - 货币配置服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class CurrencyService
{
    /**
     * 数据列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params  [输入参数]
     */
    public static function CurrencyList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'sort desc, id desc' : trim($params['order_by']);
        $data = Db::name('PluginsExchangerateCurrency')->field($field)->where($where)->order($order_by)->select()->toArray();
        return DataReturn('处理成功', 0, self::DataHandle($data));
    }

    /**
     * 数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-18
     * @desc    description
     * @param   [array]          $data [仓库数据]
     */
    public static function DataHandle($data)
    {
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // 时间
                if(isset($v['add_time']))
                {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if(isset($v['upd_time']))
                {
                    $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
                }
            }
        }
        return $data;
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CurrencySave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '1,60',
                'error_msg'         => '请填写名称、最多60个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'code',
                'checked_data'      => '1,30',
                'error_msg'         => '请填写代码、最多30个字符',
            ],
            [
                'checked_type'      => 'unique',
                'key_name'          => 'code',
                'checked_data'      => 'PluginsExchangerateCurrency',
                'checked_key'       => 'id',
                'error_msg'         => '代码已存在[{$var}]',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'symbol',
                'checked_data'      => '1,30',
                'error_msg'         => '请填写符号、最多30个字符',
            ],
            [
                'checked_type'      => 'isset',
                'key_name'          => 'rate',
                'error_msg'         => '汇率不能为空',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'sort',
                'checked_data'      => '3',
                'error_msg'         => '顺序 0~255 之间的数值',
            ],
            [
                'checked_type'      => 'max',
                'key_name'          => 'sort',
                'checked_data'      => 255,
                'error_msg'         => '顺序 0~255 之间的数值',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        
        // 操作数据
        $is_enable = isset($params['is_enable']) ? intval($params['is_enable']) : 0;
        $is_default = isset($params['is_default']) ? intval($params['is_default']) : 0;
        $data = [
            'name'              => $params['name'],
            'code'              => $params['code'],
            'symbol'            => $params['symbol'],
            'rate'              => PriceNumberFormat($params['rate'], 6),
            'sort'              => intval($params['sort']),
            'is_enable'         => $is_enable,
            'is_default'        => $is_default,
        ];

        Db::startTrans();

        // 默认地址处理
        if($is_default == 1)
        {
            Db::name('PluginsExchangerateCurrency')->where(['is_default'=>1])->update(['is_default'=>0]);
        }

        // 添加/更新数据
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            if(Db::name('PluginsExchangerateCurrency')->insertGetId($data) > 0)
            {
                Db::commit();
                return DataReturn('新增成功', 0);
            } else {
                Db::rollback();
                return DataReturn('新增失败', -100);
            }
        } else {
            $data['upd_time'] = time();
            if(Db::name('PluginsExchangerateCurrency')->where(['id'=>intval($params['id'])])->update($data))
            {
                Db::commit();
                return DataReturn('更新成功', 0);
            } else {
                Db::rollback();
                return DataReturn('更新失败', -100);
            }
        }
    }

    /**
     * 删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CurrencyDelete($params = [])
    {
        // 参数是否有误
        if(empty($params['ids']))
        {
            return DataReturn('数据id有误', -1);
        }
        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 删除操作
        if(Db::name('PluginsExchangerateCurrency')->where(['id'=>$params['ids']])->delete())
        {
            return DataReturn('删除成功');
        }
        return DataReturn('删除失败', -100);
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CurrencyStatusUpdate($params = [])
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
        if(Db::name('PluginsExchangerateCurrency')->where(['id'=>intval($params['id'])])->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
        {
            return DataReturn('编辑成功');
        }
        return DataReturn('编辑失败', -100);
    }
}
?>