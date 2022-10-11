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
namespace app\plugins\coupon\service;

use think\facade\Db;
use app\service\UserService;
use app\plugins\coupon\service\BaseService;

/**
 * 用户优惠券服务层-管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-05T21:51:08+0800
 */
class UserCouponAdminService
{
    /**
     * 用户优惠券列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date    2019-08-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CouponUserList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::name('PluginsCouponUser')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            $common_is_whether_list = BaseService::$common_is_whether_list;
            foreach($data as &$v)
            {
                // 用户信息
                $v['user'] = UserService::GetUserViewInfo($v['user_id']);

                // 优惠券名称
                if(!isset($coupon_names[$v['coupon_id']]))
                {
                    $coupon_names[$v['coupon_id']] = Db::name('PluginsCoupon')->where(['id'=>$v['coupon_id']])->value('name');
                }
                $v['coupon_name'] = $coupon_names[$v['coupon_id']];

                // 是否已使用
                $v['is_use_name'] = (isset($v['is_use']) && isset($common_is_whether_list[$v['is_use']])) ? $common_is_whether_list[$v['is_use']]['name'] : '未知';

                // 是否已过期
                $v['is_expire_name'] = (isset($v['is_expire']) && isset($common_is_whether_list[$v['is_expire']])) ? $common_is_whether_list[$v['is_expire']]['name'] : '未知';

                // 是否有效
                $v['is_valid_name'] = (isset($v['is_valid']) && isset($common_is_whether_list[$v['is_valid']])) ? $common_is_whether_list[$v['is_valid']]['name'] : '未知';

                // 使用时间
                $v['use_time_time'] = empty($v['use_time']) ? '' : date('Y-m-d H:i:s', $v['use_time']);

                // 有效时间
                $v['time_start_text'] = date('Y-m-d H:i:s', $v['time_start']);
                $v['time_end_text'] = date('Y-m-d H:i:s', $v['time_end']);

                // 时间
                $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                $v['upd_time_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 用户优惠券总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CouponUserTotal($where = [])
    {
        return (int) Db::name('PluginsCouponUser')->where($where)->count();
    }

    /**
     * 用户优惠券条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CouponUserWhere($params = [])
    {
        $where = [];

        // 用户
        if(!empty($params['keywords']))
        {
            $user_ids = Db::name('User')->where('username|nickname|mobile|email', '=', $params['keywords'])->column('id');
            if(!empty($user_ids))
            {
                $where[] = ['user_id', 'in', $user_ids];
            } else {
                // 无数据条件，避免用户搜索条件没有数据造成的错觉
                $where[] = ['id', '=', 0];
            }
        }

        // 更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            // 等值
            if(isset($params['is_valid']) && $params['is_valid'] > -1)
            {
                $where[] = ['is_valid', '=', $params['is_valid']];
            }
            if(isset($params['is_expire']) && $params['is_expire'] > -1)
            {
                $where[] = ['is_expire', '=', $params['is_expire']];
            }
            if(isset($params['is_use']) && $params['is_use'] > -1)
            {
                $where[] = ['is_use', '=', $params['is_use']];
            }
            if(isset($params['coupon_id']) && $params['coupon_id'] > -1)
            {
                $where[] = ['coupon_id', '=', $params['coupon_id']];
            }

            // 有效时间
            if(!empty($params['time_start']))
            {
                $where[] = ['time_start', '>', strtotime($params['time_start'])];
            }
            if(!empty($params['time_end']))
            {
                $where[] = ['time_end', '<', strtotime($params['time_end'])];
            }

            // 添加时间
            if(!empty($params['add_time_start']))
            {
                $where[] = ['add_time', '>', strtotime($params['add_time_start'])];
            }
            if(!empty($params['add_time_end']))
            {
                $where[] = ['add_time', '<', strtotime($params['add_time_end'])];
            }
        }

        return $where;
    }

    /**
     * 状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function StatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
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
        if(Db::name('PluginsCouponUser')->where(['id'=>intval($params['id'])])->update(['is_valid'=>intval($params['state'])]))
        {
           return DataReturn('编辑成功');
        }
        return DataReturn('编辑失败或数据未改变', -100);
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
    public static function Delete($params = [])
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

        // 删除操作
        $where = ['id' => intval($params['id'])];
        if(Db::name('PluginsCouponUser')->where($where)->delete())
        {
            return DataReturn('删除成功');
        }

        return DataReturn('删除失败或资源不存在', -100);
    }
}
?>