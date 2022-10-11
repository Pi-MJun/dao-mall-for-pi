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
use app\service\GoodsService;
use app\plugins\intellectstools\service\BaseService;

/**
 * 智能工具箱 - 商品批量调价服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class GoodsAllPriceService
{
    /**
     * 批量调价
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-04-22
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsAllPriceEdit($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'in',
                'key_name'          => 'price_type',
                'checked_data'      => array_merge(array_column(BaseService::$price_type_list, 'value')),
                'error_msg'         => '请选择价格类型',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'modify_price_rules',
                'checked_data'      => array_merge(array_column(BaseService::$modify_price_rules_list, 'value')),
                'error_msg'         => '请选择调整规则',
            ],
            [
                'checked_type'      => 'isset',
                'key_name'          => 'rules_value',
                'error_msg'         => '请填写调整值、最大数10000000',
            ],
            [
                'checked_type'      => 'min',
                'key_name'          => 'rules_value',
                'checked_data'      => 0,
                'error_msg'         => '调整值不能小于0',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 条件
        $params['field'] = 'goods_id';
        $data = BaseService::GoodsWhere($params);

        // 价格字段
        $field = BaseService::$price_type_list[$params['price_type']]['field'];

        // 操作符号
        $opt = BaseService::$modify_price_rules_list[$params['modify_price_rules']]['type'];

        // 调整值
        $rules_value = floatval($params['rules_value']);

        // 启动事务
        Db::startTrans();

        // 捕获异常
        try {
            // 批量处理
            $where = $data['where'];
            // 排除0元数据
            if(in_array($opt, ['-', '/', '*']))
            {
                $where[] = ['price', '>', 0];
            }
            // 调整值
            $value = ($opt == 'fixed') ? $rules_value : '`'.$field.'`'.$opt.$rules_value;
            $res = Db::name('GoodsSpecBase')->where($where)->exp($field, $value)->update();
            if(!$res)
            {
                throw new \Exception('价格规格操作失败、影响('.$res.')项');
            }

            // 避免小于0的金额
            $where = $data['where'];
            $where[] = [$field, '<', 0];
            Db::name('GoodsSpecBase')->where($where)->update([$field=>0]);

            // 批量更新基础信息
            foreach($data['goods_ids'] as $gid)
            {
                $ret = GoodsService::GoodsSaveBaseUpdate($gid);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }
            }

            // 完成
            Db::commit();
            return DataReturn('操作成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }
}
?>