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
namespace app\plugins\multilingual\service;

use think\facade\Db;
use app\plugins\multilingual\service\MultilingualService;

/**
 * 多语言 - 翻译数据服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-11-18
 * @desc    description
 */
class TrDataService
{
    /**
     * 列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function TrDataList($params)
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : trim($params['order_by']);
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        $data = Db::name('PluginsMultilingualTrData')->field($field)->where($where)->order($order_by)->limit($m, $n)->select()->toArray();
        return DataReturn('处理成功', 0, self::DataHandle($data));
    }

    /**
     * 数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
     * @desc    description
     * @param   [array]          $data [数据]
     */
    public static function DataHandle($data)
    {
        if(!empty($data))
        {
            $ret = MultilingualService::MultilingualList();
            $multilingual = empty($ret['data']) ? [] : array_column($ret['data'], 'name', 'code');
            foreach($data as &$v)
            {
                // 语言类型
                if(isset($v['from_type']))
                {
                    $v['from_type_name'] = empty($multilingual) || !array_key_exists($v['from_type'], $multilingual) ? '' : $multilingual[$v['from_type']];
                }
                if(isset($v['to_type']))
                {
                    $v['to_type_name'] = empty($multilingual) || !array_key_exists($v['to_type'], $multilingual) ? '' : $multilingual[$v['to_type']];
                }

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
     * 总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function TrDataTotal($where)
    {
        return (int) Db::name('PluginsMultilingualTrData')->where($where)->count();
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
    public static function TrDataSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '数据id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'to_value',
                'error_msg'         => '请输入翻译值',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据
        $data = [
            'to_value'  => $params['to_value'],
            'upd_time'  => time(),
        ];
        if(Db::name('PluginsMultilingualTrData')->where(['id'=>intval($params['id'])])->update($data))
        {
            return DataReturn('更新成功', 0);
        }
        return DataReturn('更新失败', -1);
    }

    /**
     * 删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function TrDataDelete($params = [])
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
        if(Db::name('PluginsMultilingualTrData')->where(['id'=>$params['ids']])->delete())
        {
            return DataReturn('删除成功');
        }
        return DataReturn('删除失败', -100);
    }

    /**
     * 清空全部
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function TrDataAllDelete($params = [])
    {
        $where = [
            ['id', '>', 0]
        ];
        if(Db::name('PluginsMultilingualTrData')->where($where)->delete() === false)
        {
            return DataReturn('操作失败', -100);
        }
        return DataReturn('操作成功');
    }
}
?>