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
use app\plugins\shop\service\ShopService;

/**
 * 多商户 - 店铺收藏服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-02-14
 * @desc    description
 */
class ShopFavorService
{
    /**
     * 店铺收藏/取消
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-08-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ShopFavorCancel($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '店铺id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 查询用户状态是否正常
        $ret = UserService::UserStatusCheck('id', $params['user']['id']);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 开始操作
        $data = ['shop_id'=>intval($params['id']), 'user_id'=>$params['user']['id']];
        $temp = Db::name('PluginsShopFavor')->where($data)->find();
        if(empty($temp))
        {
            // 添加收藏
            $data['add_time'] = time();
            if(Db::name('PluginsShopFavor')->insertGetId($data) > 0)
            {
                return DataReturn('收藏成功', 0, [
                    'text'      => '已收藏',
                    'status'    => 1,
                    'count'     => self::ShopFavorTotal(['shop_id'=>$data['shop_id']]),
                ]);
            } else {
                return DataReturn('收藏失败');
            }
        } else {
            // 是否强制收藏
            if(isset($params['is_mandatory_favor']) && $params['is_mandatory_favor'] == 1)
            {
                return DataReturn('收藏成功', 0, [
                    'text'      => '已收藏',
                    'status'    => 1,
                    'count'     => self::ShopFavorTotal(['shop_id'=>$data['shop_id']]),
                ]);
            }

            // 删除收藏
            if(Db::name('PluginsShopFavor')->where($data)->delete() > 0)
            {
                return DataReturn('取消成功', 0, [
                    'text'      => '收藏',
                    'status'    => 0,
                    'count'     => self::ShopFavorTotal(['shop_id'=>$data['shop_id']]),
                ]);
            } else {
                return DataReturn('取消失败');
            }
        }
    }

    /**
     * 用户是否收藏了店铺
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-08-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     * @return  [int]                    [1已收藏, 0未收藏]
     */
    public static function IsUserShopFavor($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'shop_id',
                'error_msg'         => '店铺id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        $data = ['shop_id'=>intval($params['shop_id']), 'user_id'=>$params['user']['id']];
        $temp = Db::name('PluginsShopFavor')->where($data)->find();
        return DataReturn('操作成功', 0, empty($temp) ? 0 : 1);
    }

    /**
     * 店铺收藏总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function ShopFavorTotal($where = [])
    {
        return (int) Db::name('PluginsShopFavor')->where($where)->count();
    }

    /**
     * 店铺收藏列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ShopFavorList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        // 获取数据
        $data = Db::name('PluginsShopFavor')->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            // 店铺信息
            $shop = ShopService::DataHandle(Db::name('PluginsShop')->where(['id'=>array_column($data, 'shop_id')])->column('id,name,logo,describe', 'id'));

            // 用户类型
            $user_type = empty($params['user_type']) ? 'user' : $params['user_type'];

            foreach($data as &$v)
            {
                // 用户信息
                if(array_key_exists('user_id', $v))
                {
                    $v['user'] = ($user_type == 'admin') ? UserService::GetUserViewInfo($v['user_id']) : [];
                }

                // 店铺信息
                $v['shop_info'] = array_key_exists($v['shop_id'], $shop) ? $shop[$v['shop_id']] : [];

                // 时间
                if(array_key_exists('add_time', $v))
                {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 店铺收藏删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ShopFavorDelete($params = [])
    {
        // 请求参数
        $p = [
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

        // 条件
        $where = ['id' => $params['ids']];

        // 用户id
        if(!empty($params['user']))
        {
            $where['user_id'] = $params['user']['id'];
        }

        // 删除
        if(Db::name('PluginsShopFavor')->where($where)->delete())
        {
            return DataReturn('操作成功', 0);
        }
        return DataReturn('操作失败', -100);
    }

    /**
     * 获取用户收藏的店铺
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-06
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function UserShopFavorData($user_id)
    {
        return Db::name('PluginsShopFavor')->where(['user_id'=>intval($user_id)])->column('shop_id');
    }
}
?>