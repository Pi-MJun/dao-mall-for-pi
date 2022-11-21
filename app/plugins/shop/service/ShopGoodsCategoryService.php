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

/**
 * 多商户 - 商品分类服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-03-31
 * @desc    description
 */
class ShopGoodsCategoryService
{
    /**
     * 分类列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsCategoryAll($params = [])
    {
        // 条件参数
        $where = [
            'user_id'   => isset($params['user_id']) ? intval($params['user_id']) : 0,
            'is_enable' => isset($params['is_enable']) ? intval($params['is_enable']) : 1,
        ];

        // 获取数据
        $field = empty($params['field']) ? 'id,pid,icon,name,sort,is_enable,seo_title,seo_keywords,seo_desc' : $params['field'];
        $data = self::DataHandle(Db::name('PluginsShopGoodsCategory')->field($field)->where($where)->order('sort asc')->select()->toArray());
        return DataReturn('操作成功', 0, $data);
    }

    /**
     * 获取分类节点数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsCategoryNodeSon($params = [])
    {
        // 条件参数
        $where = [
            'pid'       => isset($params['id']) ? intval($params['id']) : 0,
            'user_id'   => isset($params['user_id']) ? intval($params['user_id']) : 0,
        ];

        // 获取数据
        $field = 'id,pid,icon,name,sort,is_enable,seo_title,seo_keywords,seo_desc';
        $data = Db::name('PluginsShopGoodsCategory')->field($field)->where($where)->order('sort asc')->select()->toArray();
        if(!empty($data))
        {
            $data = self::DataHandle($data);
            foreach($data as &$v)
            {
                $v['is_son']    = (Db::name('PluginsShopGoodsCategory')->where(['pid'=>$v['id']])->count() > 0) ? 'ok' : 'no';
                $v['json']      = json_encode($v);
            }
            return DataReturn('操作成功', 0, $data);
        }
        return DataReturn('没有相关数据', -100);
    }

    /**
     * 数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]          $data [二维数组]
     */
    public static function DataHandle($data)
    {
        if(!empty($data) && is_array($data))
        {
            foreach($data as &$v)
            {
                if(is_array($v))
                {
                    $v['name_alias'] = $v['name'].' ('.$v['id'].')';
                    if(array_key_exists('icon', $v))
                    {
                        $v['icon'] = ResourcesService::AttachmentPathViewHandle($v['icon']);
                    }
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
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsCategorySave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '2,16',
                'error_msg'         => '名称格式 2~16 个字符',
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

        // 其它附件
        $data_fields = ['icon'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);
        if($attachment['code'] != 0)
        {
            return $attachment;
        }

        // 获取店铺信息
        $shop_id = Db::name('PluginsShop')->where(['user_id'=>$params['user_id'], 'status'=>2])->value('id');
        if(empty($shop_id))
        {
            return DataReturn('用户店铺有误', -1);
        }

        // 数据
        $data = [
            'shop_id'       => $shop_id,
            'user_id'       => $params['user_id'],
            'name'          => $params['name'],
            'pid'           => isset($params['pid']) ? intval($params['pid']) : 0,
            'sort'          => isset($params['sort']) ? intval($params['sort']) : 0,
            'is_enable'     => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            'icon'          => $attachment['data']['icon'],
            'seo_title'     => empty($params['seo_title']) ? '' : $params['seo_title'],
            'seo_keywords'  => empty($params['seo_keywords']) ? '' : $params['seo_keywords'],
            'seo_desc'      => empty($params['seo_desc']) ? '' : $params['seo_desc'],
        ];

        // 父级id宇当前id不能相同
        if(!empty($params['id']) && $params['id'] == $data['pid'])
        {
            return DataReturn('父级不能与当前相同', -10);
        }

        // 添加/编辑
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            $data['id'] = Db::name('PluginsShopGoodsCategory')->insertGetId($data);
            if($data['id'] <= 0)
            {
                return DataReturn('添加失败', -100);
            }
        } else {
            $data['upd_time'] = time();
            if(Db::name('PluginsShopGoodsCategory')->where(['id'=>intval($params['id'])])->update($data) === false)
            {
                return DataReturn('编辑失败', -100);
            } else {
                $data['id'] = $params['id'];
            }
        }

        $res = self::DataHandle([$data]);
        return DataReturn('操作成功', 0, json_encode($res[0]));
    }
    
    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsCategoryDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '删除数据id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取分类下所有分类id
        $ids = self::GoodsCategoryItemsIds([$params['id']]);
        $ids[] = $params['id'];

        // 开始删除
        if(Db::name('PluginsShopGoodsCategory')->where(['id'=>$ids, 'user_id'=>$params['user_id']])->delete())
        {
            return DataReturn('删除成功', 0);
        }
        return DataReturn('删除失败', -100);
    }

    /**
     * 获取分类下的所有分类id
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]          $ids       [分类id数组]
     * @param   [int]            $is_enable [是否启用 null, 0否, 1是]
     * @param   [string]         $order_by  [排序, 默认sort asc]
     */
    public static function GoodsCategoryItemsIds($ids = [], $is_enable = null, $order_by = 'sort asc')
    {
        if(!is_array($ids))
        {
            $ids = explode(',', $ids);
        }
        $where = ['pid'=>$ids];
        if($is_enable !== null)
        {
            $where['is_enable'] = $is_enable;
        }
        $data = Db::name('PluginsShopGoodsCategory')->where($where)->order($order_by)->column('id');
        if(!empty($data))
        {
            $temp = self::GoodsCategoryItemsIds($data, $is_enable, $order_by);
            if(!empty($temp))
            {
                $data = array_merge($data, $temp);
            }
        }
        $data = empty($data) ? $ids : array_unique(array_merge($ids, $data));
        return $data;
    }
}
?>