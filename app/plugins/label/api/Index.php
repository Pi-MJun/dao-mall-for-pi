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
namespace app\plugins\label\api;

use app\plugins\label\api\Common;
use app\plugins\label\service\LabelService;
use app\plugins\label\service\LabelGoodsService;

/**
 * 标签
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-03
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        return DataReturn('success', 0, []);
    }

    /**
     * 标签详情 - 初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-03
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function DetailInit($params = [])
    {
        $label = null;
        if(!empty($params['id']))
        {
            $data_params = [
                'm'         => 0,
                'n'         => 1,
                'where'     => [
                    ['id', '=', intval($params['id'])],
                    ['is_enable', '=', 1],
                ],
            ];
            $ret = LabelService::LabelList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $label = $ret['data'][0];
            } else {
                return DataReturn('标签数据不存在', -1);
            }
        }

        // 返回数据
        $result = [
            'base'      => $this->plugins_config,
            'label'     => $label,
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 标签详情 - 商品列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function DetailGoodsList($params = [])
    {
        // 返回格式
        $result = [
            'page_total'    => 0,
            'total'         => 0,
            'data'          => [],
        ];
        if(!empty($params['id']))
        {
            // 条件
            $map = LabelGoodsService::LabelGoodsMap($params);

            // 总数
            $result['total'] = LabelGoodsService::LabelGoodsTotal($map['where']);

            // 分页计算
            $field = 'id,title,vice_title,color,describe,cover,keywords';
            $page = max(1, isset($params['page']) ? intval($params['page']) : 1);
            $n = 20;
            $m = intval(($page-1)*$n);

            // 获取列表
            $data_params = [
                'm'             => $m,
                'n'             => $n,
                'where'         => $map['where'],
                'order_by'      => $map['order_by'],
                'field'         => $map['field'],
            ];
            $ret = LabelGoodsService::LabelGoodsList($data_params);

            // 返回数据
            $result['data'] = $ret['data'];
            $result['page_total'] = ceil($result['total']/$n);
        }
        return DataReturn('success', 0, $result);
    }
}
?>