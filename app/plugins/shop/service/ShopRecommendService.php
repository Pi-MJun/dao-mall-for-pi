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
namespace app\plugins\shop\service;

use think\facade\Db;
use app\service\ResourcesService;
use app\service\GoodsService;
use app\plugins\shop\service\ShopService;

/**
 * 多商户 - 首页推荐服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class ShopRecommendService
{
    /**
     * 列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-08-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ShopRecommendList($params)
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'sort asc, id desc' : trim($params['order_by']);
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        $data = Db::name('PluginsShopRecommend')->field($field)->where($where)->order($order_by)->limit($m, $n)->select()->toArray();
        return DataReturn('处理成功', 0, self::DataHandle($data, $params));
    }

    /**
     * 数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]          $data      [数据]
     * @param   [array]          $params    [输入参数]
     */
    public static function DataHandle($data, $params = [])
    {
        if(!empty($data))
        {
            $goods = self::JoinGoodsList(array_column($data, 'data_appoint_goods_ids', 'id'), $params);
            foreach($data as &$v)
            {
                // 更多指向分类
                if(array_key_exists('more_category_id', $v))
                {
                    if(empty($v['more_category_id']))
                    {
                        $v['more_url'] = (APPLICATION == 'web') ? PluginsHomeUrl('shop', 'search', 'index', ['id'=>$v['shop_id']]) : '/pages/plugins/shop/search/search?shop_id='.$v['shop_id'];
                    } else {
                        $v['more_url'] = (APPLICATION == 'web') ? PluginsHomeUrl('shop', 'search', 'index', ['id'=>$v['shop_id'], 'cid'=>$v['more_category_id']]) : '/pages/plugins/shop/search/search?shop_id='.$v['shop_id'].'&category_id='.$v['more_category_id'];
                    }
                }

                // 关联商品
                if(array_key_exists('data_type', $v))
                {
                    if($v['data_type'] == 0)
                    {
                        $v['goods_list'] = self::AutoGoodsList($v, $params);
                    } else {
                        $v['goods_list'] = (empty($goods) || !array_key_exists($v['id'], $goods)) ? [] : $goods[$v['id']];
                    }
                }

                // 关键字
                if(array_key_exists('keywords', $v))
                {
                    $v['keywords_arr'] = empty($v['keywords']) ? [] : explode(',', $v['keywords']);
                }

                // 有效时间
                if(array_key_exists('time_start', $v))
                {
                    $v['time_start'] = empty($v['time_start']) ? '' : date('Y-m-d H:i:s', $v['time_start']);
                }
                if(array_key_exists('time_end', $v))
                {
                    $v['time_end'] = empty($v['time_end']) ? '' : date('Y-m-d H:i:s', $v['time_end']);
                }

                // 时间
                if(array_key_exists('add_time', $v))
                {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if(array_key_exists('upd_time', $v))
                {
                    $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
                }
            }
        }
        return $data;
    }

    /**
     * 自动模式数据读取
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-20
     * @desc    description
     * @param   [array]          $data      [推荐数据]
     * @param   [array]          $params    [输入参数]
     */
    public static function AutoGoodsList($data, $params = [])
    {
        // 排序规则
        $type = BaseService::$recommend_order_by_type_list;
        $rule = BaseService::$recommend_order_by_rule_list;
        $order_by_type = (isset($data['order_by_type']) && isset($type[$data['order_by_type']])) ? $type[$data['order_by_type']]['value'] : 'id';
        $order_by_rule = (isset($data['order_by_rule']) && isset($rule[$data['order_by_rule']])) ? $rule[$data['order_by_rule']]['value'] : 'desc';

        // 条件
        $user_id = empty($params['user_id']) ? 0 : intval($params['user_id']);
        $where = [
            ['is_shelves', '=', 1],
            ['is_delete_time', '=', 0],
            ['shop_user_id', '=', $user_id],
        ];
        // 是否指定分类
        if(!empty($data['data_auto_category_id']))
        {
            $where[] = ['shop_category_id', '=', $data['data_auto_category_id']];
        }

        // 获取商品列表
        $data_params = [
            'm'         => 0,
            'n'         => $data['data_auto_number'],
            'where'     => $where,
            'order_by'  => $order_by_type.' '.$order_by_rule,
        ];
        $ret = GoodsService::GoodsList($data_params);
        return empty($ret['data']) ? [] : $ret['data'];
    }

    /**
     * 关联商品列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]         $appoint_goods_ids  [指定商品id]
     * @param   [array]         $params             [输入参数]
     */
    public static function JoinGoodsList($appoint_goods_ids, $params = [])
    {
        $result = [];
        if(!empty($appoint_goods_ids))
        {
            // id值处理
            $ids = [];
            foreach($appoint_goods_ids as $v)
            {
                if(!empty($v))
                {
                    $ids = array_merge($ids, explode(',', $v));
                }
            }
            if(!empty($ids))
            {
                $user_id = empty($params['user_id']) ? 0 : intval($params['user_id']);
                $data_params = [
                    'm'     => 0,
                    'n'     => 0,
                    'where' => [
                        ['id', 'in', array_unique($ids)],
                        ['is_shelves', '=', 1],
                        ['is_delete_time', '=', 0],
                        ['shop_user_id', '=', $user_id],
                    ],
                ];
                $ret = GoodsService::GoodsList($data_params);
                if(!empty($ret['data']))
                {
                    $goods = array_column($ret['data'], null, 'id');
                    foreach($appoint_goods_ids as $k=>$v)
                    {
                        if(!empty($v))
                        {
                            $result[$k] = [];
                            $temp_ids = explode(',', $v);
                            foreach($temp_ids as $gid)
                            {
                                if(array_key_exists($gid, $goods))
                                {
                                    $result[$k][] = $goods[$gid];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-10T22:16:29+0800
     * @param    [array]          $where [条件]
     */
    public static function ShopRecommendTotal($where)
    {
        return (int) Db::name('PluginsShopRecommend')->where($where)->count();
    }

    /**
     * 保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ShopRecommendSave($params = [])
    {
        // 请求类型
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'title',
                'checked_data'      => '1,20',
                'error_msg'         => '标题长度 1~20 个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'vice_title',
                'checked_data'      => '1,35',
                'error_msg'         => '副标题长度 1~35 个字符',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'style_type',
                'checked_data'      => array_keys(BaseService::$recommend_style_type_list),
                'error_msg'         => '样式类型数据值范围有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'data_type',
                'checked_data'      => array_keys(BaseService::$recommend_data_type_list),
                'error_msg'         => '数据类型数据值范围有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'order_by_type',
                'checked_data'      => array_keys(BaseService::$recommend_order_by_type_list),
                'error_msg'         => '排序类型数据值范围有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'order_by_rule',
                'checked_data'      => array_keys(BaseService::$recommend_order_by_rule_list),
                'error_msg'         => '排序规则数据值范围有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'sort',
                'checked_data'      => '3',
                'error_msg'         => '顺序 0~255 之间的数值',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 用户店铺
        $shop = ShopService::UserShopInfo($params['user_id']);
        if(empty($shop))
        {
            return DataReturn('未申请店铺或店铺无效', -1);
        }

        // 数据
        $data = [
            'user_id'               => $shop['user_id'],
            'shop_id'               => $shop['id'],
            'title'                 => $params['title'],
            'vice_title'            => $params['vice_title'],
            'color'                 => empty($params['color']) ? '' : $params['color'],
            'more_category_id'      => empty($params['more_category_id']) ? 0 : intval($params['more_category_id']),
            'keywords'              => empty($params['keywords']) ? '' : $params['keywords'],
            'is_enable'             => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            'is_home'               => isset($params['is_home']) ? intval($params['is_home']) : 0,
            'style_type'            => isset($params['style_type']) ? intval($params['style_type']) : 0,
            'data_type'             => isset($params['data_type']) ? intval($params['data_type']) : 0,
            'order_by_type'         => isset($params['order_by_type']) ? intval($params['order_by_type']) : 0,
            'order_by_rule'         => isset($params['order_by_rule']) ? intval($params['order_by_rule']) : 0,
            'data_appoint_goods_ids'=> empty($params['data_appoint_goods_ids']) ? '' : (is_array($params['data_appoint_goods_ids']) ? implode(',', $params['data_appoint_goods_ids']) : $params['data_appoint_goods_ids']),
            'data_auto_category_id' => isset($params['data_auto_category_id']) ? intval($params['data_auto_category_id']) : 0,
            'data_auto_number'      => isset($params['data_auto_number']) ? intval($params['data_auto_number']) : 0,
            'time_start'            => empty($params['time_start']) ? 0 : strtotime($params['time_start']),
            'time_end'              => empty($params['time_end']) ? 0 : strtotime($params['time_end']),
            'sort'                  => empty($params['sort']) ? 0 : intval($params['sort']),
        ];

        // 捕获异常
        try {
            if(empty($params['id']))
            {
                $data['add_time'] = time();
                if(Db::name('PluginsShopRecommend')->insertGetId($data) <= 0)
                {
                    throw new \Exception('添加失败');
                }
            } else {
                $data['upd_time'] = time();
                if(!Db::name('PluginsShopRecommend')->where(['id'=>intval($params['id']), 'shop_id'=>$data['shop_id']])->update($data))
                {
                    throw new \Exception('编辑失败');
                }
            }
            return DataReturn('操作成功', 0);
        } catch(\Exception $e) {
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function ShopRecommendStatusUpdate($params = [])
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
        $where = [
            ['id', '=', intval($params['id'])],
            ['user_id', '=', $params['user_id']],
        ];
        if(Db::name('PluginsShopRecommend')->where($where)->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
        {
            return DataReturn('编辑成功');
        }
        return DataReturn('编辑失败', -100);
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
    public static function ShopRecommendDelete($params = [])
    {
        // 参数是否有误
        if(empty($params['ids']))
        {
            return DataReturn('商品id有误', -1);
        }
        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 删除操作
        $where = [
            ['id', 'in', $params['ids']],
            ['user_id', '=', $params['user_id']],
        ];
        if(Db::name('PluginsShopRecommend')->where($where)->delete())
        {
            return DataReturn('删除成功');
        }

        return DataReturn('删除失败', -100);
    }

    /**
     * 首页推荐数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ShopRecommendData($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $data_params = [
            'where'         => array_merge([
                ['is_enable', '=', 1],
                ['is_home', '=', 1],
            ], $where),
            'm'             => 0,
            'n'             => 0,
            'user_id'       => empty($params['user_id']) ? 0 : intval($params['user_id']),
        ];
        $ret = self::ShopRecommendList($data_params);
        $result = [];
        if(!empty($ret['data']))
        {
            foreach($ret['data'] as $k=>$v)
            {
                // 是否存在数据、空则不展示
                if(!empty($v['goods_list']))
                {
                    // 配置时间 - 开始时间
                    if(!empty($v['time_start']))
                    {
                        if(strtotime($v['time_start']) > time())
                        {
                            continue;
                        }
                    }

                    // 配置时间 - 结束时间
                    if(!empty($v['time_end']))
                    {
                        if(strtotime($v['time_end']) < time())
                        {
                            continue;
                        }
                    }

                    // 加入返回数据
                    $result[] = $v;
                }
            }
        }
        return $result;
    }
}
?>