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
use app\service\UserService;
use app\service\ResourcesService;
use app\layout\service\BaseLayout;
use app\plugins\shop\service\ShopService;

/**
 * 多商户 - 页面设计服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-02-14
 * @desc    description
 */
class ShopDesignService
{
    /**
     * 列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-16
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function DesignList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        // 获取数据
        $data = Db::name('PluginsShopDesign')->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // logo
                if(array_key_exists('logo', $v))
                {
                    $v['logo'] = ResourcesService::AttachmentPathViewHandle($v['logo']);
                }

                // 时间
                if(array_key_exists('add_time', $v))
                {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if(array_key_exists('upd_time', $v))
                {
                    $v['upd_time'] = date('Y-m-d H:i:s', $v['upd_time']);
                }
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-16
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function DesignTotal($where = [])
    {
        return (int) Db::name('PluginsShopDesign')->where($where)->count();
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-16
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function DesignSave($params = [])
    {
        // 请求参数
        $p = [
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

        // 用户店铺
        $shop = ShopService::UserShopInfo($params['user_id']);
        if(empty($shop))
        {
            return DataReturn('未申请店铺或店铺无效', -1);
        }

        // 附件
        $data_fields = ['logo'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);

        // 配置信息
        $config = empty($params['config']) ? '' : BaseLayout::ConfigSaveHandle($params['config']);

        // 数据
        $data = [
            'user_id'       => $shop['user_id'],
            'shop_id'       => $shop['id'],
            'name'          => empty($params['name']) ? '默认页面'.date('mdHi') : $params['name'],
            'logo'          => $attachment['data']['logo'],
            'config'        => $config,
            'seo_title'     => empty($params['seo_title']) ? '' : $params['seo_title'],
            'seo_keywords'  => empty($params['seo_keywords']) ? '' : $params['seo_keywords'],
            'seo_desc'      => empty($params['seo_desc']) ? '' : $params['seo_desc'],
            'is_enable'     => isset($params['is_enable']) ? intval($params['is_enable']) : 1,
            'is_header'     => isset($params['is_header']) ? intval($params['is_header']) : 1,
            'is_footer'     => isset($params['is_footer']) ? intval($params['is_footer']) : 1,
        ];
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            $data_id = Db::name('PluginsShopDesign')->insertGetId($data);
            if($data_id <= 0)
            {
                return DataReturn('添加失败', -1);
            }
        } else {
            $data_id = intval($params['id']);
            $data['upd_time'] = time();
            if(Db::name('PluginsShopDesign')->where(['id'=>$data_id, 'user_id'=>$shop['user_id'], 'shop_id'=>$shop['id']])->update($data) === false)
            {
                return DataReturn('更新失败', -1);
            }
        }
        return DataReturn('操作成功', 0, $data_id);
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-23
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function DesignStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'field',
                'error_msg'         => '字段有误',
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
        if(Db::name('PluginsShopDesign')->where(['id'=>intval($params['id']), 'user_id'=>$params['user_id']])->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
        {
           return DataReturn('操作成功');
        }
        return DataReturn('操作失败', -100);
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
    public static function DesignDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'ids',
                'error_msg'         => '操作id有误',
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
        if(Db::name('PluginsShopDesign')->where(['id'=>$params['ids'], 'user_id'=>$params['user_id']])->delete())
        {
            // 删除数据库附件
            foreach($params['ids'] as $v)
            {
                ResourcesService::AttachmentPathTypeDelete(self::AttachmentPathTypeValue($params['user_id'], $v));
            }
            return DataReturn('删除成功');
        }

        return DataReturn('删除失败', -100);
    }
    
    /**
     * 页面访问统计加1
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-10-15
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function DesignAccessCountInc($params = [])
    {
        if(!empty($params['design_id']))
        {
            return Db::name('PluginsShopDesign')->where(['id'=>intval($params['design_id'])])->inc('access_count')->update();
        }
        return false;
    }

    /**
     * 附件标识
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-23
     * @desc    description
     * @param   [int]          $user_id [用户 id]
     * @param   [int]          $data_id [数据 id]
     */
    public static function AttachmentPathTypeValue($user_id, $data_id)
    {
        return 'plugins_shop-user_design-'.$user_id.'-'.$data_id;
    }

    /**
     * 同步到店铺首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-04-19
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function DesignSync($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取数据
        $info = Db::name('PluginsShopDesign')->where(['id'=>intval($params['id']), 'user_id'=>$params['user_id']])->field('id,config,shop_id')->find();
        if(empty($info))
        {
            return DataReturn('数据不存在', -1);
        }

        // 布局数据
        $data = [
            'layout_config' => $info['config'],
            'upd_time'      => time(),
        ];
        if(Db::name('PluginsShop')->where(['id'=>$info['shop_id']])->update($data))
        {
            return DataReturn('操作成功', 0);
        }
        return DataReturn('操作失败', -1);
    }
}
?>