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
namespace app\plugins\blog\service;

use think\facade\Db;
use app\service\ResourcesService;
use app\service\GoodsService;
use app\plugins\blog\service\BlogService;

/**
 * 博客 - 推荐服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class RecommendService
{
    /**
     * 获取列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-08-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function RecommendList($params)
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'sort asc, id desc' : trim($params['order_by']);
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        $data = Db::name('PluginsBlogRecommend')->field($field)->where($where)->order($order_by)->limit($m, $n)->select()->toArray();
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
            $blog = self::JoinBlogList(array_column($data, 'id'), $params);
            foreach($data as &$v)
            {
                // 更多指向分类
                if(array_key_exists('more_category_id', $v))
                {
                    if(empty($v['more_category_id']))
                    {
                        $v['more_url'] = (APPLICATION == 'web') ? PluginsHomeUrl('blog', 'index', 'search') : '/pages/plugins/blog/search/search';
                    } else {
                        $v['more_url'] = (APPLICATION == 'web') ? PluginsHomeUrl('blog', 'index', 'search', ['id'=>$v['more_category_id']]) : '/pages/plugins/blog/search/search?id='.$v['more_category_id'];
                    }
                }

                // 关联博客
                if(array_key_exists('data_type', $v))
                {
                    if($v['data_type'] == 0)
                    {
                        $v['blog_list'] = self::AutoBlogList($v);
                    } else {
                        $v['blog_list'] = (empty($blog) || !array_key_exists($v['id'], $blog)) ? [] : $blog[$v['id']];
                    }
                }

                // 关键字
                if(array_key_exists('keywords', $v))
                {
                    $v['keywords_arr'] = empty($v['keywords']) ? [] : explode(',', $v['keywords']);
                }

                // 封面图片
                if(array_key_exists('cover', $v))
                {
                    $v['cover'] = ResourcesService::AttachmentPathViewHandle($v['cover']);
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
                    $v['add_time_date_cn'] = date('m月d日', $v['add_time']).' · '.date('Y年', $v['add_time']);
                    $v['add_time_date'] = date('Y-m-d', $v['add_time']);
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
     * @param   [array]          $data [推荐数据]
     */
    public static function AutoBlogList($data)
    {
        // 排序规则
        $type = BaseService::$recommend_order_by_type_list;
        $rule = BaseService::$recommend_order_by_rule_list;
        $order_by_type = (isset($data['order_by_type']) && isset($type[$data['order_by_type']])) ? $type[$data['order_by_type']]['value'] : 'id';
        $order_by_rule = (isset($data['order_by_rule']) && isset($rule[$data['order_by_rule']])) ? $rule[$data['order_by_rule']]['value'] : 'desc';

        // 条件
        $where = [
            ['is_enable', '=', 1]
        ];
        // 是否指定分类
        if(!empty($data['data_auto_category_id']))
        {
            $where[] = ['blog_category_id', '=', $data['data_auto_category_id']];
        }

        // 获取博客数据
        $data_params = [
            'where'     => $where,
            'm'         => 0,
            'n'         => empty($data['data_auto_number']) ? 10 : $data['data_auto_number'],
            'field'     => 'id,title,title_color,describe,cover,video_url,add_time',
            'order_by'  => $order_by_type.' '.$order_by_rule,
        ];
        $ret = BlogService::BlogList($data_params);
        return empty($ret['data']) ? [] : $ret['data'];
    }

    /**
     * 博客列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]         $recommend_ids [推荐id]
     * @param   [array]         $params        [输入参数]
     */
    public static function JoinBlogList($recommend_ids, $params = [])
    {
        $result = [];
        if(!empty($recommend_ids))
        {
            // 获取关联数据
            $where = ['recommend_id'=>$recommend_ids];
            if(!empty($params['blog_where']) && is_array($params['blog_where']))
            {
                $where = array_merge($where, $params['blog_where']);
            }
            $join_data = Db::name('PluginsBlogRecommendJoin')->where($where)->select()->toArray();
            if(!empty($join_data))
            {
                // 获取博客数据
                $data_params = [
                    'where'     => [
                        ['id', 'in', array_column($join_data, 'blog_id')],
                        ['is_enable', '=', 1],
                    ],
                    'm'         => 0,
                    'n'         => 0,
                    'field'     => 'id,title,title_color,describe,cover,video_url,add_time'
                ];
                $ret = BlogService::BlogList($data_params);
                if(!empty($ret['data']))
                {
                    $temp_blog = array_column($ret['data'], null, 'id');
                    foreach($join_data as $jv)
                    {
                        if(!array_key_exists($jv['recommend_id'], $result))
                        {
                            $result[$jv['recommend_id']] = [];
                        }
                        if(array_key_exists($jv['blog_id'], $temp_blog))
                        {
                            $result[$jv['recommend_id']][] = array_merge($jv, $temp_blog[$jv['blog_id']]);
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
    public static function RecommendTotal($where)
    {
        return (int) Db::name('PluginsBlogRecommend')->where($where)->count();
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
    public static function RecommendSave($params = [])
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
                'checked_type'      => 'length',
                'key_name'          => 'describe',
                'checked_data'      => '230',
                'is_checked'        => 1,
                'error_msg'         => '描述格式 最多230个字符',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'home_data_location',
                'checked_data'      => array_keys(BaseService::$home_floor_location_list),
                'error_msg'         => '首页数据位置数据值范围有误',
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

        // 关联博客
        $blog_data = empty($params['blog_data']) ? [] : $params['blog_data'];

        // 附件
        $data_fields = ['cover'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);

        $data = [
            'title'                 => $params['title'],
            'vice_title'            => $params['vice_title'],
            'color'                 => empty($params['color']) ? '' : $params['color'],
            'describe'              => empty($params['describe']) ? '' : $params['describe'],
            'more_category_id'      => empty($params['more_category_id']) ? 0 : intval($params['more_category_id']),
            'keywords'              => empty($params['keywords']) ? '' : $params['keywords'],
            'cover'                 => $attachment['data']['cover'],
            'blog_count'            => count($blog_data),
            'is_enable'             => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            'is_home'               => isset($params['is_home']) ? intval($params['is_home']) : 0,
            'is_goods_detail'       => isset($params['is_goods_detail']) ? intval($params['is_goods_detail']) : 0,
            'home_data_location'    => isset($params['home_data_location']) ? intval($params['home_data_location']) : 0,
            'style_type'            => isset($params['style_type']) ? intval($params['style_type']) : 0,
            'data_type'             => isset($params['data_type']) ? intval($params['data_type']) : 0,
            'order_by_type'         => isset($params['order_by_type']) ? intval($params['order_by_type']) : 0,
            'order_by_rule'         => isset($params['order_by_rule']) ? intval($params['order_by_rule']) : 0,
            'data_auto_category_id' => isset($params['data_auto_category_id']) ? intval($params['data_auto_category_id']) : 0,
            'data_auto_number'      => isset($params['data_auto_number']) ? intval($params['data_auto_number']) : 0,
            'time_start'            => empty($params['time_start']) ? 0 : strtotime($params['time_start']),
            'time_end'              => empty($params['time_end']) ? 0 : strtotime($params['time_end']),
            'sort'                  => empty($params['sort']) ? 0 : intval($params['sort']),
            'seo_title'             => empty($params['seo_title']) ? '' : $params['seo_title'],
            'seo_keywords'          => empty($params['seo_keywords']) ? '' : $params['seo_keywords'],
            'seo_desc'              => empty($params['seo_desc']) ? '' : $params['seo_desc'],
        ];

        // 启动事务
        Db::startTrans();

        // 捕获异常
        try {
            $recommend_id = 0;
            if(empty($params['id']))
            {
                $data['add_time'] = time();
                $recommend_id = Db::name('PluginsBlogRecommend')->insertGetId($data);
                if($recommend_id <= 0)
                {
                    throw new \Exception('添加失败');
                }
            } else {
                $recommend_id = intval($params['id']);
                $data['upd_time'] = time();
                if(!Db::name('PluginsBlogRecommend')->where(['id'=>$recommend_id])->update($data))
                {
                    throw new \Exception('编辑失败');
                }
            }

            // 先删除关联数据再添加
            Db::name('PluginsBlogRecommendJoin')->where(['recommend_id'=>$recommend_id])->delete();

            // 添加关联数据
            if(!empty($blog_data))
            {
                foreach($blog_data as &$bv)
                {
                    $bv['recommend_id'] = $recommend_id;
                    $bv['is_recommend'] = (isset($bv['is_recommend']) && $bv['is_recommend'] == 1) ? 1 : 0;
                    $bv['add_time'] = time();
                }
                if(Db::name('PluginsBlogRecommendJoin')->insertAll($blog_data) < count($blog_data))
                {
                    throw new \Exception('关联博客添加失败');
                }
            }

            // 提交事务
            Db::commit();
            return DataReturn('操作成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
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
    public static function RecommendAccessCountInc($params = [])
    {
        if(!empty($params['id']))
        {
            return Db::name('PluginsBlogRecommend')->where(['id'=>intval($params['id'])])->inc('access_count')->update();
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
    public static function RecommendDelete($params = [])
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
        if(Db::name('PluginsBlogRecommend')->where(['id'=>$params['ids']])->delete())
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
    public static function RecommendStatusUpdate($params = [])
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
        if(Db::name('PluginsBlogRecommend')->where(['id'=>intval($params['id'])])->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
        {
            return DataReturn('编辑成功');
        }
        return DataReturn('编辑失败', -100);
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
    public static function RecommendFloorData($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $data_params = [
            'where'         => array_merge([
                ['is_enable', '=', 1],
                ['is_home', '=', 1],
            ], $where),
            'm'             => 0,
            'n'             => 0,
            'blog_where'   => ['is_recommend'=>1],
        ];
        $ret = self::RecommendList($data_params);
        $result = [];
        if(!empty($ret['data']))
        {
            foreach($ret['data'] as $k=>$v)
            {
                // 是否存在博客数据、空则不展示
                if(!empty($v['blog_list']))
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