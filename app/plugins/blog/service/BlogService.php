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

/**
 * 博客 - 博文服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class BlogService
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
    public static function BlogList($params)
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : trim($params['order_by']);
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        $data = Db::name('PluginsBlog')->field($field)->where($where)->order($order_by)->limit($m, $n)->select()->toArray();
        return DataReturn('处理成功', 0, self::DataHandle($data));
    }

    /**
     * 数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]          $data [数据]
     */
    public static function DataHandle($data)
    {
        if(!empty($data))
        {
            $category_names = Db::name('PluginsBlogCategory')->where(['id'=>array_column($data, 'blog_category_id')])->column('name', 'id');
            foreach($data as &$v)
            {
                // url
                if(array_key_exists('id', $v))
                {
                    $v['url'] = (APPLICATION == 'web') ? PluginsHomeUrl('blog', 'index', 'detail', ['id'=>$v['id']]) : '/pages/plugins/blog/detail/detail?id='.$v['id'];
                }

                // 分类名称
                if(array_key_exists('blog_category_id', $v))
                {
                    $v['blog_category_name'] = isset($category_names[$v['blog_category_id']]) ? $category_names[$v['blog_category_id']] : '';
                }

                // 内容
                if(array_key_exists('content', $v))
                {
                    $v['content'] = ResourcesService::ContentStaticReplace($v['content'], 'get');
                }

                // 图片组
                if(array_key_exists('images', $v))
                {
                    if(!empty($v['images']))
                    {
                        $images = json_decode($v['images'], true);
                        foreach($images as &$img)
                        {
                            $img = ResourcesService::AttachmentPathViewHandle($img);
                        }
                        $v['images'] = $images;
                    }
                }

                // 关联商品
                if(array_key_exists('goods_count', $v))
                {
                    $v['goods_list'] = [];
                    if($v['goods_count'] > 0)
                    {
                        $goods_ids = Db::name('PluginsBlogGoods')->where(['blog_id'=>$v['id']])->column('goods_id');
                        $goods = BaseService::GoodsList($goods_ids);
                        $v['goods_list'] = empty($goods['data']['goods']) ? [] : $goods['data']['goods'];
                    }
                }

                // 封面图片
                if(array_key_exists('cover', $v))
                {
                    $v['cover'] = ResourcesService::AttachmentPathViewHandle($v['cover']);
                    if(empty($v['cover']))
                    {
                        if(!empty($v['images']))
                        {
                            if(is_array($v['images']))
                            {
                                $v['cover'] = isset($v['images'][0]) ? $v['images'][0] : '';
                            } else {
                                $images = json_decode($v['images'], true);
                                if(isset($images[0]))
                                {
                                    $v['cover'] = ResourcesService::AttachmentPathViewHandle($images[0]);
                                }
                            }
                        }
                    }
                }

                // 视频地址
                if(array_key_exists('video_url', $v))
                {
                    $v['video_url'] = ResourcesService::AttachmentPathViewHandle($v['video_url']);
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
     * 总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-10T22:16:29+0800
     * @param    [array]          $where [条件]
     */
    public static function BlogTotal($where)
    {
        return (int) Db::name('PluginsBlog')->where($where)->count();
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
    public static function BlogSave($params = [])
    {
        // 请求类型
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'title',
                'checked_data'      => '2,60',
                'error_msg'         => '标题长度 2~60 个字符',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'blog_category_id',
                'error_msg'         => '请选择分类',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'jump_url',
                'checked_data'      => 'CheckUrl',
                'is_checked'        => 1,
                'error_msg'         => '跳转url地址格式有误',
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
                'checked_type'      => 'length',
                'key_name'          => 'content',
                'checked_data'      => '30,105000',
                'error_msg'         => '内容 30~105000 个字符',
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

        // 编辑器内容
        $content = empty($params['content']) ? '' : ResourcesService::ContentStaticReplace(htmlspecialchars_decode($params['content']), 'add');

        // 描述
        $describe = empty($params['describe']) ? '' : strip_tags($params['describe']);
        if(empty($describe) && empty($params['id']))
        {
            $describe = empty($content) ? '' : mb_substr(strip_tags($content), 0, 200, 'utf-8');
        }

        // 推荐商品
        $goods_ids = empty($params['goods_ids']) ? [] : explode(',', $params['goods_ids']);

        // 附件
        $data_fields = ['cover', 'video_url'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);

        // 图片组
        $images = ResourcesService::RichTextMatchContentAttachment($content, 'plugins_blog');
        $data = [
            'title'             => $params['title'],
            'title_color'       => empty($params['title_color']) ? '' : $params['title_color'],
            'blog_category_id'  => intval($params['blog_category_id']),
            'jump_url'          => empty($params['jump_url']) ? '' : $params['jump_url'],
            'describe'          => $describe,
            'content'           => $content,
            'cover'             => $attachment['data']['cover'],
            'goods_count'       => count($goods_ids),
            'images'            => empty($images) ? '' : json_encode($images),
            'images_count'      => count($images),
            'is_enable'         => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            'is_recommended'    => isset($params['is_recommended']) ? intval($params['is_recommended']) : 0,
            'is_live_play'      => isset($params['is_live_play']) ? intval($params['is_live_play']) : 0,
            'video_url'         => $attachment['data']['video_url'],
            'seo_title'         => empty($params['seo_title']) ? '' : $params['seo_title'],
            'seo_keywords'      => empty($params['seo_keywords']) ? '' : $params['seo_keywords'],
            'seo_desc'          => empty($params['seo_desc']) ? '' : $params['seo_desc'],
        ];

        // 启动事务
        Db::startTrans();

        $blog_id = 0;
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            $blog_id = Db::name('PluginsBlog')->insertGetId($data);
            if($blog_id <= 0)
            {
                // 回滚事务
                Db::rollback();
                return DataReturn('博文添加失败', -100);
            }
        } else {
            $blog_id = intval($params['id']);
            $data['upd_time'] = time();
            if(!Db::name('PluginsBlog')->where(['id'=>$blog_id])->update($data))
            {
                // 回滚事务
                Db::rollback();
                return DataReturn('博文编辑失败', -100); 
            }
        }

        // 关联推荐商品
        // 先删除关联数据再添加
        Db::name('PluginsBlogGoods')->where(['blog_id'=>$blog_id])->delete();

        // 添加关联数据
        if(!empty($goods_ids))
        {
            $goods_data = [];
            foreach($goods_ids as $goods_id)
            {
                $goods_data[] = [
                    'blog_id'   => $blog_id,
                    'goods_id'  => $goods_id,
                    'add_time'  => time(),
                ];
            }
            if(Db::name('PluginsBlogGoods')->insertAll($goods_data) < count($goods_data))
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
    public static function BlogAccessCountInc($params = [])
    {
        if(!empty($params['id']))
        {
            return Db::name('PluginsBlog')->where(['id'=>intval($params['id'])])->inc('access_count')->update();
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
    public static function BlogDelete($params = [])
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
        if(Db::name('PluginsBlog')->where(['id'=>$params['ids']])->delete())
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
    public static function BlogStatusUpdate($params = [])
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
        if(Db::name('PluginsBlog')->where(['id'=>intval($params['id'])])->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
        {
            return DataReturn('编辑成功');
        }
        return DataReturn('编辑失败', -100);
    }

    /**
     * 上一篇、下一篇数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-09
     * @desc    description
     * @param   [int]          $blog_id [博文id]
     */
    public static function BlogLastNextData($blog_id)
    {
        // 指定字段
        $field = 'id,title,add_time';

        // 上一条数据
        $where = [
            ['is_enable', '=', 1],
            ['id', '<', $blog_id],
        ];
        $last = self::DataHandle(Db::name('PluginsBlog')->where($where)->field($field)->order('id desc')->limit(1)->select()->toArray());

        // 下一条数据
        $where = [
            ['is_enable', '=', 1],
            ['id', '>', $blog_id],
        ];
        $next = self::DataHandle(Db::name('PluginsBlog')->where($where)->field($field)->order('id asc')->limit(1)->select()->toArray());

        return [
            'last'  => empty($last) ? null : $last[0],
            'next'  => empty($next) ? null : $next[0],
        ];
    }
}
?>