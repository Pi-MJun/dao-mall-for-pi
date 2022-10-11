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
namespace app\plugins\activity\service;

use think\facade\Db;
use app\service\ResourcesService;
use app\service\GoodsService;

/**
 * 活动配置 - 活动服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class ActivityService
{
    /**
     * 搜索活动列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function SearchList($params = [])
    {
        // 返回格式
        $result = [
            'page_total'    => 0,
            'total'         => 0,
            'data'          => [],
        ];
        
        // 搜索条件
        $where = self::SearchWhereHandle($params);

        // 获取总数
        $result['total'] = self::ActivityTotal($where);

        // 存在总数则查询数据
        if($result['total'] > 0)
        {
            // 分页计算
            $field = 'id,title,vice_title,color,describe,cover,keywords';
            $page = max(1, isset($params['page']) ? intval($params['page']) : 1);
            $n = 20;
            $m = intval(($page-1)*$n);

            // 获取列表
            $data_params = [
                'where'             => $where,
                'm'                 => $m,
                'n'                 => $n,
                'field'             => $field,
            ];
            $ret = self::ActivityList($data_params);

            // 返回数据
            $result['data'] = $ret['data'];
            $result['page_total'] = ceil($result['total']/$n);
        }
        return DataReturn('处理成功', 0, $result);
    }

    /**
     * 搜索条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function SearchWhereHandle($params = [])
    {
        $where = [
            ['is_enable', '=', 1],
        ];

        // 分类
        if(!empty($params['category_id']))
        {
            $where[] = ['activity_category_id', '=', intval($params['category_id'])];
        }

        return $where;
    }

    /**
     * 获取列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-08-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ActivityList($params)
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'sort asc, id desc' : trim($params['order_by']);
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        $data = Db::name('PluginsActivity')->field($field)->where($where)->order($order_by)->limit($m, $n)->select()->toArray();
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
            $category_names = Db::name('PluginsActivityCategory')->where(['id'=>array_column($data, 'activity_category_id')])->column('name', 'id');
            $goods_where = empty($params['goods_where']) ? [] : $params['goods_where'];
            foreach($data as &$v)
            {
                // url
                $v['url'] = (APPLICATION == 'web') ? PluginsHomeUrl('activity', 'index', 'detail', ['id'=>$v['id']]) : '/pages/plugins/activity/detail/detail?id='.$v['id'];

                // 分类名称
                if(isset($v['activity_category_id']))
                {
                    $v['activity_category_name'] = isset($category_names[$v['activity_category_id']]) ? $category_names[$v['activity_category_id']] : '';
                }

                // 关联商品
                if(isset($v['goods_count']))
                {
                    $v['goods_list'] = ($v['goods_count'] > 0) ? self::GoodsList(Db::name('PluginsActivityGoods')->where(array_merge($goods_where, ['activity_id'=>$v['id']]))->column('activity_id,goods_id,discount_rate,dec_price,is_recommend', 'goods_id')) : [];
                }

                // 关键字
                if(isset($v['keywords']))
                {
                    $v['keywords_arr'] = empty($v['keywords']) ? [] : explode(',', $v['keywords']);
                }

                // 封面图片
                if(isset($v['cover']))
                {
                    $v['cover'] = ResourcesService::AttachmentPathViewHandle($v['cover']);
                }

                // 有效时间
                if(isset($v['time_start']))
                {
                    $v['time_start'] = empty($v['time_start']) ? '' : date('Y-m-d H:i:s', $v['time_start']);
                }
                if(isset($v['time_end']))
                {
                    $v['time_end'] = empty($v['time_end']) ? '' : date('Y-m-d H:i:s', $v['time_end']);
                }

                // 时间
                if(isset($v['add_time']))
                {
                    $v['add_time_date_cn'] = date('m月d日', $v['add_time']).' · '.date('Y年', $v['add_time']);
                    $v['add_time_date'] = date('Y-m-d', $v['add_time']);
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
     * 商品列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]         $params [商品]
     */
    public static function GoodsList($goods)
    {
        $result = [];
        if(!empty($goods))
        {
            // 条件
            $where = [
                ['is_delete_time', '=', 0],
                ['is_shelves', '=', 1],
                ['id', 'in', array_column($goods, 'goods_id')],
            ];

            // 获取数据
            $ret = GoodsService::GoodsList(['where'=>$where, 'm'=>0, 'n'=>0]);
            if(!empty($ret['data']))
            {
                $temp_goods = array_column($ret['data'], null, 'id');
                foreach($goods as $g)
                {
                    if(array_key_exists($g['goods_id'], $temp_goods))
                    {
                        $result[] = array_merge($g, $temp_goods[$g['goods_id']]);
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
    public static function ActivityTotal($where)
    {
        return (int) Db::name('PluginsActivity')->where($where)->count();
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
    public static function ActivitySave($params = [])
    {
        // 请求类型
        $p = [
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
                'checked_type'      => 'empty',
                'key_name'          => 'activity_category_id',
                'error_msg'         => '请选择分类',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'cover',
                'checked_data'      => '255',
                'error_msg'         => '请上传封面图片',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'describe',
                'checked_data'      => '230',
                'is_checked'        => 1,
                'error_msg'         => '描述格式 最多230个字符',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'home_data_location',
                'checked_data'      => array_column(BaseService::$home_floor_location_list, 'value'),
                'error_msg'         => '首页数据位置数据值范围有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'style_type',
                'checked_data'      => array_keys(BaseService::$recommend_style_type_list),
                'error_msg'         => '样式类型数据值范围有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'sort',
                'checked_data'      => '3',
                'error_msg'         => '顺序 0~255 之间的数值',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'seo_title',
                'checked_data'      => '100',
                'is_checked'        => 1,
                'error_msg'         => 'SEO标题格式 最多100个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'seo_keywords',
                'checked_data'      => '130',
                'is_checked'        => 1,
                'error_msg'         => 'SEO关键字格式 最多130个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'seo_desc',
                'checked_data'      => '230',
                'is_checked'        => 1,
                'error_msg'         => 'SEO描述格式 最多230个字符',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 推荐商品
        $goods = empty($params['goods']) ? [] : $params['goods'];

        // 附件
        $data_fields = ['cover'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);

        $data = [
            'title'                 => $params['title'],
            'vice_title'            => $params['vice_title'],
            'color'                 => empty($params['color']) ? '' : $params['color'],
            'activity_category_id'  => intval($params['activity_category_id']),
            'describe'              => empty($params['describe']) ? '' : $params['describe'],
            'keywords'              => empty($params['keywords']) ? '' : $params['keywords'],
            'cover'                 => $attachment['data']['cover'],
            'goods_count'           => count($goods),
            'is_enable'             => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            'is_home'               => isset($params['is_home']) ? intval($params['is_home']) : 0,
            'home_data_location'    => isset($params['home_data_location']) ? intval($params['home_data_location']) : 0,
            'style_type'            => isset($params['style_type']) ? intval($params['style_type']) : 0,
            'is_goods_detail'       => isset($params['is_goods_detail']) ? intval($params['is_goods_detail']) : 0,
            'time_start'            => empty($params['time_start']) ? 0 : strtotime($params['time_start']),
            'time_end'              => empty($params['time_end']) ? 0 : strtotime($params['time_end']),
            'sort'                  => empty($params['sort']) ? 0 : intval($params['sort']),
            'seo_title'             => empty($params['seo_title']) ? '' : $params['seo_title'],
            'seo_keywords'          => empty($params['seo_keywords']) ? '' : $params['seo_keywords'],
            'seo_desc'              => empty($params['seo_desc']) ? '' : $params['seo_desc'],
        ];

        // 启动事务
        Db::startTrans();

        $activity_id = 0;
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            $activity_id = Db::name('PluginsActivity')->insertGetId($data);
            if($activity_id <= 0)
            {
                // 回滚事务
                Db::rollback();
                return DataReturn('活动添加失败', -100);
            }
        } else {
            $activity_id = intval($params['id']);
            $data['upd_time'] = time();
            if(!Db::name('PluginsActivity')->where(['id'=>$activity_id])->update($data))
            {
                // 回滚事务
                Db::rollback();
                return DataReturn('活动编辑失败', -100); 
            }
        }

        // 关联推荐商品
        // 先删除关联数据再添加
        Db::name('PluginsActivityGoods')->where(['activity_id'=>$activity_id])->delete();

        // 添加关联数据
        if(!empty($goods))
        {
            foreach($goods as &$g)
            {
                $g['activity_id'] = $activity_id;
                $g['discount_rate'] = (empty($g['discount_rate']) || $g['discount_rate'] <= 0) ? 0 : (float) $g['discount_rate'];
                $g['dec_price'] = (empty($g['dec_price']) || $g['dec_price'] <= 0) ? 0 : (float) $g['dec_price'];
                $g['is_recommend'] = (isset($g['is_recommend']) && $g['is_recommend'] == 1) ? 1 : 0;
                $g['add_time'] = time();
            }
            if(Db::name('PluginsActivityGoods')->insertAll($goods) < count($goods))
            {
                // 回滚事务
                Db::rollback();
                return DataReturn('关联推荐商品添加失败', -100); 
            }
        }

        // 提交事务
        Db::commit();
        return DataReturn((empty($params['id']) ? '添加' : '编辑').'成功', 0); 
    }

    /**
     * 访问统计加1
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-10-15
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ActivityAccessCountInc($params = [])
    {
        if(!empty($params['id']))
        {
            return Db::name('PluginsActivity')->where(['id'=>intval($params['id'])])->inc('access_count')->update();
        }
        return false;
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
    public static function ActivityDelete($params = [])
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
        if(Db::name('PluginsActivity')->where(['id'=>$params['ids']])->delete())
        {
            return DataReturn('删除成功');
        }

        return DataReturn('删除失败', -100);
    }

    /**
     * 状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function ActivityStatusUpdate($params = [])
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
        if(Db::name('PluginsActivity')->where(['id'=>intval($params['id'])])->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
        {
            return DataReturn('编辑成功');
        }
        return DataReturn('编辑失败', -100);
    }

    /**
     * 活动商品
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-30
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     */
    public static function ActivityGoodsData($goods_id)
    {
        $data = Db::name('PluginsActivityGoods')->alias('ag')->join('plugins_activity a', 'a.id=ag.activity_id')->where([
            ['ag.goods_id', '=', $goods_id],
            ['a.is_enable', '=', 1],
        ])->field('a.time_start,a.time_end,ag.goods_id,ag.discount_rate,ag.dec_price')->order('a.id desc')->select()->toArray();
        if(!empty($data))
        {
            foreach($data as $v)
            {
                // 未配置时间则表示不限制
                if(empty($v['time_start']) && empty($v['time_end']))
                {
                    return $v;
                }

                // 配置时间 - 开始时间
                if(!empty($v['time_start']) && empty($v['time_end']))
                {
                    if($v['time_start'] <= time())
                    {
                        return $v;
                    }
                }

                // 配置时间 - 结束时间
                if(empty($v['time_start']) && !empty($v['time_end']))
                {
                    if($v['time_end'] >= time())
                    {
                        return $v;
                    }
                }

                // 配置时间 - 都配置了
                if($v['time_start'] <= time() && $v['time_end'] >= time())
                {
                    return $v;
                }
            }
        }
        return [];
    }

    /**
     * 首页推荐活动
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ActivityFloorData($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $data_params = [
            'field'         => 'id,title,vice_title,describe,color,keywords,goods_count,home_data_location,style_type',
            'where'         => array_merge([
                ['is_enable', '=', 1],
                ['is_home', '=', 1],
                ['goods_count', '>', 0],
            ], $where),
            'm'             => 0,
            'n'             => 0,
            'goods_where'   => ['is_recommend'=>1],
        ];
        $ret = self::ActivityList($data_params);
        $result = [];
        if(!empty($ret['data']))
        {
            foreach($ret['data'] as $k=>$v)
            {
                if(!empty($v['goods_list']))
                {
                    $result[] = $v;
                }
            }
        }
        return $result;
    }
}
?>