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
namespace app\plugins\Intellectstools\service;

use think\facade\Db;
use app\service\UserService;
use app\service\GoodsService;
use app\plugins\Intellectstools\service\BaseService;

/**
 * 智能工具箱 - 批量评价服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class CommentsDataService
{
    /**
     * 获取列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-17
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CommentsDataList($params)
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : trim($params['order_by']);
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        // 获取列表
        $data = Db::name('PluginsIntellectstoolsComments')->where($where)->field($field)->order($order_by)->limit($m, $n)->select()->toArray();
        return DataReturn('处理成功', 0, self::DataHandle($data, $params));
    }

    /**
     * 获取总数
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-17
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function CommentsDataTotal($where)
    {
        return (int) Db::name('PluginsIntellectstoolsComments')->where($where)->count();
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
        if(!empty($data) && is_array($data))
        {
            // 字段列表
            $keys = ArrayKeys($data);

            // 商品分类名称
            if(in_array('goods_category_id', $keys))
            {
                $category = Db::name('GoodsCategory')->where(['id'=>array_column($data, 'goods_category_id')])->column('name', 'id');
            }
            foreach($data as &$v)
            {
                // 商品分类名称
                if(array_key_exists('goods_category_id', $v))
                {
                    $v['goods_category_name'] = (!empty($category) && array_key_exists($v['goods_category_id'], $category)) ? $category[$v['goods_category_id']] : '';
                }
                // 时间
                if(array_key_exists('add_time', $v))
                {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
            }
        }
        return $data;
    }

    /**
     * 导入
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-05
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CommentsDataSave($params = [])
    {
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'content',
                'error_msg'         => '请填写评价内容',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 内容处理
        $data = [];
        $goods_category_id = empty($params['goods_category_id']) ? 0 : intval($params['goods_category_id']);
        foreach(explode("\n", $params['content']) as $v)
        {
            if(!empty($v))
            {
                $data[] = [
                    'goods_category_id'     => $goods_category_id,
                    'content'               => $v,
                    'add_time'              => time(),
                ];
            }
        }
        $count = Db::name('PluginsIntellectstoolsComments')->insertAll($data);
        if($count > 0)
        {
            return DataReturn('导入成功'.$count.'条', 0);
        }
        return DataReturn('导入失败', -100);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function CommentsDataDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'ids',
                'error_msg'         => '数据id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 删除操作
        if(Db::name('PluginsIntellectstoolsComments')->where(['id'=>$params['ids']])->delete())
        {
            return DataReturn('删除成功', 0);
        }
        return DataReturn('删除失败', -100);
    }

    /**
     * 商品评价独立配置保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-07
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     * @param   [array]        $params   [输入参数]
     */
    public static function CommentsGoodsConfigSave($goods_id, $params = [])
    {
        $data = [
            'goods_id'              => $goods_id,
            'first_number_min'      => empty($params['first_number_min']) ? 0 : intval($params['first_number_min']),
            'first_number_max'      => empty($params['first_number_max']) ? 0 : intval($params['first_number_max']),
            'last_number_min'       => empty($params['last_number_min']) ? 0 : intval($params['last_number_min']),
            'last_number_max'       => empty($params['last_number_max']) ? 0 : intval($params['last_number_max']),
            'last_interval_time'    => empty($params['last_interval_time']) ? 0 : intval($params['last_interval_time']),
            'time_interval_min'     => empty($params['time_interval_min']) ? 0 : intval($params['time_interval_min']),
            'time_interval_max'     => empty($params['time_interval_max']) ? 0 : intval($params['time_interval_max']),
            'rating_rand_min'       => empty($params['rating_rand_min']) ? 0 : intval($params['rating_rand_min']),
            'auto_control_number'   => empty($params['auto_control_number']) ? 0 : intval($params['auto_control_number']),
        ];
        $info = self::CommentsGoodsConfigData($goods_id);
        if(empty($info))
        {
            $data['add_time'] = time();
            return (Db::name('PluginsIntellectstoolsCommentsGoodsConfig')->insertGetId($data) > 0);
        } else {
            $data['upd_time'] = time();
            return Db::name('PluginsIntellectstoolsCommentsGoodsConfig')->where(['id'=>$info['id']])->update($data);
        }
    }

    /**
     * 商品评价独立配置数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-07
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     */
    public static function CommentsGoodsConfigData($goods_id)
    {
        return empty($goods_id) ? [] : Db::name('PluginsIntellectstoolsCommentsGoodsConfig')->where(['goods_id'=>$goods_id])->find();
    }

    /**
     * 商品评价处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-05
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     * @param   [array]        $config   [配置信息]
     * @param   [array]        $params   [输入参数]
     */
    public static function GoodsCommentsHandle($goods_id, $config, $params = [])
    {
        // 模板条件、根据商品分类筛选
        $where = self::CommentsGoodsCategoryWhere($goods_id);

        // 评论模板数据总数
        $total = self::CommentsDataTotal($where);
        if($total > 0)
        {
            // 商品独立配置、存在则覆盖基础配置
            if(isset($config['is_goods_alone_comments_config']) && $config['is_goods_alone_comments_config'] == 1)
            {
                $rules = self::CommentsGoodsConfigData($goods_id);
                if(!empty($rules))
                {
                    unset($rules['id'], $rules['goods_id'], $rules['add_time'], $rules['upd_time']);
                    foreach($rules as $k=>$v)
                    {
                        if(!empty($v))
                        {
                            $config[$k] = $v;
                        }
                    }
                }
            }

            // 最后一次记录
            $info = Db::name('PluginsIntellectstoolsCommentsGoods')->where(['goods_id'=>$goods_id])->find();

            // 首次增加
            if(empty($info))
            {
                $min = empty($config['first_number_min']) ? 10 : intval($config['first_number_min']);
                $max = empty($config['first_number_max']) ? 30 : intval($config['first_number_max']);
                $rand = rand($min, $max);
                $m = rand(0, $total-$rand);
                $ret =self::GoodsCommentsInsert($goods_id, $config, $where, $m, $rand);
            } else {
                // 续增、超过间隔时间则增加
                $last_interval_time = (empty($config['last_interval_time']) ? 1440 : intval($config['last_interval_time']))*60;
                if($info['last_time']+$last_interval_time < time())
                {
                    $min = empty($config['last_number_min']) ? 1 : intval($config['last_number_min']);
                    $max = empty($config['last_number_max']) ? 5 : intval($config['last_number_max']);
                    $rand = rand($min, $max);
                    $m = rand(0, $total-$rand);
                    $ret =self::GoodsCommentsInsert($goods_id, $config, $where, $m, $rand, $info);
                } else {
                    $ret = DataReturn('续增间隔限制时间内', -1);
                }
            }
        } else {
            $ret = DataReturn('无相关评价模板', -1);
        }
        return $ret;
    }

    /**
     * 商品评价数据添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-05
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     * @param   [array]        $config   [配置信息]
     * @param   [array]        $where    [条件]
     * @param   [int]          $m        [起始值]
     * @param   [int]          $n        [条数值]
     * @param   [array]        $info     [基础数据]
     */
    public static function GoodsCommentsInsert($goods_id, $config, $where, $m, $n, $info = [])
    {
        // 是否智能控制
        if(!empty($config['auto_control_number']))
        {
            // 获取商品销量计算基数
            $sales_count = Db::name('Goods')->where(['id'=>$goods_id])->value('sales_count');
            if($sales_count <= 0)
            {
                return DataReturn('商品销量为0、智能控制取消操作', -1);
            }

            // 计算最大可生成的评价数据
            $can_comments_max = intval($sales_count*($config['auto_control_number']/100));

            // 获取商品评价总数
            $goods_comments_count = Db::name('GoodsComments')->where(['goods_id'=>$goods_id])->count();
            if($goods_comments_count >= $can_comments_max)
            {
                return DataReturn('已有评价满足基准值['.$goods_comments_count.'>='.$can_comments_max.']', -1);
            }

            // 现有生成是否超出限制、超出则扣除
            $goods_comments_count += $n;
            if($goods_comments_count > $can_comments_max)
            {
                $n -= $goods_comments_count-$can_comments_max;
            }
            if($n <= 0)
            {
                return DataReturn('已达到控制基数值['.$goods_comments_count.'>'.$can_comments_max.']', -1);
            }
        }

        // 分页值处理、避免出现负数和0无限制条数
        if($m < 0)
        {
            $m = 0;
        }
        if($n <= 0)
        {
            $n = rand(1, 30);
        }
        $comments = Db::name('PluginsIntellectstoolsComments')->where($where)->limit($m, $n)->column('content');
        if(!empty($comments))
        {
            // 数据时间配置
            $time_interval_min = empty($config['time_interval_min']) ? 30 : intval($config['time_interval_min']);
            $time_interval_max = empty($config['time_interval_max']) ? 180 : intval($config['time_interval_max']);
            $is_skip_night = !isset($config['is_skip_night']) || $config['is_skip_night'] == 1;
            $is_comments_show = (!isset($config['is_comments_show']) || $config['is_comments_show'] == 1) ? 1 : 0;
            $is_rand_anonymous = !isset($config['is_rand_anonymous']) || $config['is_rand_anonymous'] == 1;
            $is_rand_user = !isset($config['is_rand_user']) || $config['is_rand_user'] == 1;
            $rating_rand_min = empty($config['rating_rand_min']) ? 1 : intval($config['rating_rand_min']);

            // 用户总数
            if($is_rand_user)
            {
                $user_total = UserService::UserTotal([]);
            }

            // 评价数据处理
            $data = [];
            foreach($comments as $v)
            {
                if(!empty($v))
                {
                    // 按照上一条记录随机增加时间
                    $time = empty($data) ? time() : $data[count($data)-1]['add_time']+rand($time_interval_min*60, $time_interval_max*60);

                    // 夜间时段跳过（23~7）
                    if($is_skip_night)
                    {
                        $h = date('H', $time);
                        if($h >= 23 || $h < 7)
                        {
                            $rs = ($h >= 23) ? 8 : 7;
                            $time += rand($rs, 10)*3600;
                        }
                    }

                    // 评价数据
                    $user_id = ($is_rand_user && $user_total > 0) ? rand(1, $user_total) : 0;
                    $is_anonymous = ($is_rand_anonymous && $user_id > 0) ? rand(0, 1) : 1;
                    $data[] = [
                        'user_id'       => $user_id,
                        'order_id'      => 0,
                        'goods_id'      => $goods_id,
                        'business_type' => 'order',
                        'content'       => $v,
                        'images'        => '',
                        'rating'        => rand($rating_rand_min, 5),
                        'is_anonymous'  => $is_anonymous,
                        'is_show'       => $is_comments_show,
                        'add_time'      => $time,
                    ];
                }
            }
            if(Db::name('GoodsComments')->insertAll($data))
            {
                // 更新记录时间、不存在则添加
                if(empty($info))
                {
                    if(Db::name('PluginsIntellectstoolsCommentsGoods')->insertGetId([
                        'goods_id'      => $goods_id,
                        'inc_count'     => 1,
                        'data_count'    => count($data),
                        'last_time'     => time(),
                        'add_time'      => time(),
                    ]))
                    {
                        return DataReturn('记录新增成功', 0);
                    }
                } else {
                    if(Db::name('PluginsIntellectstoolsCommentsGoods')->where(['id'=>$info['id']])->update([
                        'inc_count'     => $info['inc_count']+1,
                        'data_count'    => $info['data_count']+count($data),
                        'last_time'     => time(),
                    ]))
                    {
                        return DataReturn('记录更新成功', 0);
                    }
                }
            } else {
                return DataReturn('评价内容批量新增失败', -1);
            }
        }
        return DataReturn('无可用评价模板', -1);
    }

    /**
     * 根据商品分类获取评价条件
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-06
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     */
    public static function CommentsGoodsCategoryWhere($goods_id)
    {
        // 商品关联的分类
        $goods_cids = Db::name('GoodsCategoryJoin')->where(['goods_id'=>$goods_id])->column('category_id');
        // 获取所有父级id
        $ids = empty($goods_cids) ? [] : self::GoodsCategoryParentIds($goods_cids, 1);

        // 追加不限定的模板
        return [['goods_category_id', 'in', array_merge($ids, [0])]];
    }

    /**
     * 获取商品分类的所有上级分类id
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-08-29
     * @desc    description
     * @param   [array]         $ids       [分类id数组]
     * @param   [int]           $is_enable [是否启用 null, 0否, 1是]
     * @param   [int]           $level     [指定级别 null, 整数、默认则全部下级]
     */
    public static function GoodsCategoryParentIds($ids = [], $is_enable = null, $level = null)
    {
        if(!is_array($ids))
        {
            $ids = explode(',', $ids);
        }
        $where = [
            ['id', 'in', $ids],
            ['pid', '>', 0],
        ];
        if($is_enable !== null)
        {
            $where[] = ['is_enable', '=', $is_enable];
        }

        // 级别记录处理
        if($level !== null)
        {
            if(is_array($level))
            {
                $level['temp'] += 1;
            } else {
                $level = [
                    'value' => $level,
                    'temp'  => 1,
                ];
            }
        }

        // 是否超过级别限制
        if($level === null || $level['temp'] < $level['value'])
        {
            $data = Db::name('GoodsCategory')->where($where)->column('pid');
            if(!empty($data))
            {
                $temp = self::GoodsCategoryParentIds($data, $is_enable, $level);
                if(!empty($temp))
                {
                    $data = array_merge($data, $temp);
                }
            }
        }
        return empty($data) ? $ids : array_unique(array_merge($ids, $data));
    }
}
?>