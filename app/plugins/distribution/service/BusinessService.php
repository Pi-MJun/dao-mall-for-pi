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
namespace app\plugins\distribution\service;

use think\facade\Db;
use app\service\UserService;
use app\service\PaymentService;
use app\service\ResourcesService;
use app\plugins\distribution\service\BaseService;
use app\plugins\distribution\service\ExtractionService;

/**
 * 分销 - 业务服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class BusinessService
{
    /**
     * 获取用户团队列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:50:14+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserTeamList($params = [])
    {
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? 'id,username,nickname,mobile,email,avatar,add_time' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];
        $is_privacy = isset($params['is_privacy']) ? (boolean) $params['is_privacy'] : true;

        $data = Db::name('User')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // 用户信息处理
                $v = UserService::GetUserViewInfo($v['id'], $v, $is_privacy);
                $v['user'] = [
                    'avatar'        => $v['avatar'],
                    'user_name_view'=> $v['user_name_view'],
                    'username'      => $v['username'],
                    'nickname'      => $v['nickname'],
                    'mobile'        => $v['mobile'],
                    'email'         => $v['email'],
                ];

                // 加入时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);

                // 当前用户下一级总数
                $v['referrer_count'] = self::UserTeamTotal(self::UserTeamWhere(['referrer_id'=>$v['id']]));

                // 当前用户下一级消费总金额
                $find_where = [
                    ['u.referrer', '=', $v['id']],
                    ['o.status', 'in', [2,3,4]],
                ];
                $v['find_order_total'] = PriceNumberFormat(Db::name('Order')->alias('o')->join('user u', 'o.user_id=u.id')->where($find_where)->sum('o.total_price'));

                // 订单有效金额
                $order_where = [
                    ['user_id', '=', $v['id']],
                    ['status', 'in', [2,3,4]],
                ];
                $v['order_total'] = PriceNumberFormat(Db::name('Order')->where($order_where)->sum('total_price'));
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 用户团队列表条件
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:53:46+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserTeamWhere($params = [])
    {
        $where = [
            ['is_delete_time', '=', 0],
        ];

        // 关键字根据订单筛选
        if(!empty($params['keywords']))
        {
            $user_ids = Db::name('User')->where('username|nickname|mobile|email', '=', $params['keywords'])->column('id');
            if(!empty($user_ids))
            {
                $where[] = ['id', 'in', $user_ids];
            } else {
                // 无数据条件，避免搜索条件没有数据造成的错觉
                $where[] = ['id', '=', 0];
            }
        }

        // id
        if(!empty($params['id']))
        {
            $where[] = ['id', '=', intval($params['id'])];
        }

        // 指定邀请用户id
        if(!empty($params['referrer_id']))
        {
            $where[] = ['referrer', '=', intval($params['referrer_id'])];
        }

        // 用户数据
        if(!empty($params['user']) && !empty($params['user']['id']))
        {
            $where[] = ['referrer', '=', $params['user']['id']];
        }

        // 更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            // 性别
            if(isset($params['gender']) && $params['gender'] > -1)
            {
                $where[] = ['gender', '=', intval($params['gender'])];
            }

            // 时间
            if(!empty($params['time_start']))
            {
                $where[] = ['add_time', '>', strtotime($params['time_start'])];
            }
            if(!empty($params['time_end']))
            {
                $where[] = ['add_time', '<', strtotime($params['time_end'])];
            }
        }

        return $where;
    }

    /**
     * 用户团队总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T23:10:43+0800
     * @param    [array]          $where [条件]
     */
    public static function UserTeamTotal($where)
    {
        return (int) Db::name('User')->where($where)->count();
    }

    /**
     * 获取用户订单列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:50:14+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserOrderList($params = [])
    {
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? 'o.id,o.user_id,o.order_no,o.status,o.pay_status,o.total_price,o.refund_price,o.client_type,o.add_time,u.username,u.nickname,u.mobile,u.email,u.avatar' : $params['field'];
        $order_by = empty($params['order_by']) ? 'o.id desc' : $params['order_by'];
        $is_privacy = isset($params['is_privacy']) ? (boolean) $params['is_privacy'] : true;

        $data = Db::name('Order')->alias('o')->join('user u', 'o.user_id=u.id')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            // 支付方式名称
            $payment_list = PaymentService::OrderPaymentName(array_column($data, 'id'));

            // 静态数据
            $order_status_list = MyConst('common_order_status');
            $common_platform_type = MyConst('common_platform_type');
            $order_pay_status = MyConst('common_order_pay_status');
            foreach($data as &$v)
            {
                // 用户信息处理
                $v = UserService::GetUserViewInfo($v['user_id'], $v, $is_privacy);
                $v['user'] = [
                    'avatar'        => $v['avatar'],
                    'user_name_view'=> $v['user_name_view'],
                    'username'      => $v['username'],
                    'nickname'      => $v['nickname'],
                    'mobile'        => $v['mobile'],
                    'email'         => $v['email'],
                ];

                // 订单时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);

                // 支付方式
                $v['payment_name'] = (!empty($payment_list) && is_array($payment_list) && array_key_exists($v['id'], $payment_list)) ? $payment_list[$v['id']] : null;

                // 订单状态
                $v['order_status_name'] = $order_status_list[$v['status']]['name'];

                // 支付状态
                $v['order_pay_status_name'] = $order_pay_status[$v['pay_status']]['name'];

                // 客户端
                $v['order_client_type_name'] = isset($common_platform_type[$v['client_type']]) ? $common_platform_type[$v['client_type']]['name'] : '';
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 用户订单列表条件
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:53:46+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserOrderWhere($params = [])
    {
        $where = [];

        // 关键字根据订单筛选
        if(!empty($params['keywords']))
        {
            $order_ids = Db::name('Order')->where('order_no', '=', $params['keywords'])->column('id');
            if(!empty($order_ids))
            {
                $where[] = ['o.id', 'in', $order_ids];
            } else {
                $user_ids = Db::name('User')->where('username|nickname|mobile|email', '=', $params['keywords'])->column('id');
                if(!empty($user_ids))
                {
                    $where[] = ['u.referrer', 'in', $user_ids];
                } else {
                    // 无数据条件，避免搜索条件没有数据造成的错觉
                    $where[] = ['u.id', '=', 0];
                }
            }
        }

        // 用户
        if(!empty($params['user']))
        {
            $where[] = ['u.referrer', '=', $params['user']['id']];
            $where[] = ['o.status', '>', 0];
        }

        // id
        if(!empty($params['id']))
        {
            $where[] = ['o.id', '=', intval($params['id'])];
        }
        // 订单号
        if(!empty($params['order_no']))
        {
            $where[] = ['o.order_no', '=', trim($params['order_no'])];
        }

        // 指定用户id
        if(!empty($params['uid']))
        {
            $where[] = ['o.user_id', '=', intval($params['uid'])];
        }

        // 更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            // 是否有退款
            if(isset($params['is_refund']) && $params['is_refund'] > -1)
            {
                if($params['is_refund'] == 0)
                {
                    $where[] = ['o.refund_price', '<=', 0];
                } else {
                    $where[] = ['o.refund_price', '>', 0];
                }
            }

            // 订单状态
            if(isset($params['status']) && $params['status'] > -1)
            {
                $where[] = ['o.status', '=', intval($params['status'])];
            }

            // 来源
            if(!empty($params['client_type']))
            {
                $where[] = ['o.client_type', '=', $params['client_type']];
            }

            // 支付方式
            if(isset($params['payment_id']) && $params['payment_id'] > -1)
            {
                $where[] = ['o.payment_id', '=', intval($params['payment_id'])];
            }

            // 时间
            if(!empty($params['time_start']))
            {
                $where[] = ['o.add_time', '>', strtotime($params['time_start'])];
            }
            if(!empty($params['time_end']))
            {
                $where[] = ['o.add_time', '<', strtotime($params['time_end'])];
            }
        }

        return $where;
    }

    /**
     * 用户订单总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T23:10:43+0800
     * @param    [array]          $where [条件]
     */
    public static function UserOrderTotal($where)
    {
        return (int) Db::name('Order')->alias('o')->join('user u', 'o.user_id=u.id')->where($where)->count();
    }

    /**
     * 获取用户收益明细列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:50:14+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserProfitList($params = [])
    {
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? 'pdl.*, o.order_no, o.status as order_status, o.pay_status as order_pay_status, o.client_type as order_client_type, o.refund_price' : $params['field'];
        $order_by = empty($params['order_by']) ? 'pdl.id desc' : $params['order_by'];
        $user_type = isset($params['user_type']) ? $params['user_type'] : 'user';

        $data = Db::name('PluginsDistributionProfitLog')->alias('pdl')->join('order o', 'pdl.order_id=o.id')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            $common_platform_type = MyConst('common_platform_type');
            $order_status_list = MyConst('common_order_status');
            $order_pay_status = MyConst('common_order_pay_status');
            $level_name_list = BaseService::$level_name_list;
            $profit_status_list = BaseService::$profit_status_list;
            foreach($data as &$v)
            {
                // 用户信息
                $v['user'] = ($user_type == 'admin') ? UserService::GetUserViewInfo($v['user_id']) : [];

                // 级别
                $v['level_name'] = isset($level_name_list[$v['level']]) ? $level_name_list[$v['level']]['name'] : '未知';

                // 佣金状态
                $v['status_name'] = $profit_status_list[$v['status']]['name'];

                // 订单状态
                $v['order_status_name'] = $order_status_list[$v['order_status']]['name'];

                // 支付状态
                $v['order_pay_status_name'] = $order_pay_status[$v['order_pay_status']]['name'];

                // 客户端
                $v['order_client_type_name'] = isset($common_platform_type[$v['order_client_type']]) ? $common_platform_type[$v['order_client_type']]['name'] : '';

                // 添加时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);

                // 更新时间
                $v['upd_time_time'] = ($v['upd_time'] > 0) ? date('Y-m-d H:i:s', $v['upd_time']) : '';
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 用户收益明细列表条件
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:53:46+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserProfitWhere($params = [])
    {
        $where = [];

        // 关键字根据订单筛选
        if(!empty($params['keywords']))
        {
            $order_ids = Db::name('Order')->where('order_no', '=', $params['keywords'])->column('id');
            if(!empty($order_ids))
            {
                $where[] = ['o.id', 'in', $order_ids];
            } else {
                $user_ids = Db::name('User')->where('username|nickname|mobile|email', '=', $params['keywords'])->column('id');
                if(!empty($user_ids))
                {
                    $where[] = ['pdl.user_id', 'in', $user_ids];
                } else {
                    // 无数据条件，避免搜索条件没有数据造成的错觉
                    $where[] = ['pdl.id', '=', 0];
                }
            }
        }

        // 用户
        if(!empty($params['user']))
        {
            $where[] = ['pdl.user_id', '=', $params['user']['id']];
        }

        // 指定用户id
        if(!empty($params['uid']))
        {
            $where[] = ['pdl.user_id', '=', intval($params['uid'])];
        }

        // id
        if(!empty($params['id']))
        {
            $where[] = ['pdl.id', '=', intval($params['id'])];
        }

        // 更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            // 佣金状态
            if(isset($params['status']) && $params['status'] > -1)
            {
                $where[] = ['pdl.status', '=', intval($params['status'])];
            }

            // 订单状态
            if(isset($params['order_status']) && $params['order_status'] > -1)
            {
                $where[] = ['o.status', '=', intval($params['order_status'])];
            }

            // 是否有退款
            if(isset($params['is_refund']) && $params['is_refund'] > -1)
            {
                if($params['is_refund'] == 0)
                {
                    $where[] = ['o.refund_price', '<=', 0];
                } else {
                    $where[] = ['o.refund_price', '>', 0];
                }
            }

            // 级别
            if(isset($params['level']) && $params['level'] > 0)
            {
                $where[] = ['pdl.level', '=', intval($params['level'])];
            }

            // 时间
            if(!empty($params['time_start']))
            {
                $where[] = ['pdl.add_time', '>', strtotime($params['time_start'])];
            }
            if(!empty($params['time_end']))
            {
                $where[] = ['pdl.add_time', '<', strtotime($params['time_end'])];
            }
        }
        return $where;
    }

    /**
     * 用户收益明细总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T23:10:43+0800
     * @param    [array]          $where [条件]
     */
    public static function UserProfitTotal($where)
    {
        return (int) Db::name('PluginsDistributionProfitLog')->alias('pdl')->join('order o', 'pdl.order_id=o.id')->where($where)->count();
    }

    /**
     * 获取用户积分明细列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:50:14+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserIntegralList($params = [])
    {
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        $data = Db::name('PluginsDistributionIntegralLog')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            $integral_status_list = BaseService::$integral_status_list;
            foreach($data as &$v)
            {
                // 用户信息
                $v['user'] = UserService::GetUserViewInfo($v['user_id']);

                // 状态
                $v['status_name'] = $integral_status_list[$v['status']]['name'];

                // 添加时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);

                // 更新时间
                $v['upd_time_time'] = ($v['upd_time'] > 0) ? date('Y-m-d H:i:s', $v['upd_time']) : '';
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 用户积分明细列表条件
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:53:46+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserIntegralWhere($params = [])
    {
        $where = [];

        // 关键字根据订单筛选
        if(!empty($params['keywords']))
        {
            $user_ids = Db::name('User')->where('username|nickname|mobile|email', '=', $params['keywords'])->column('id');
            if(!empty($user_ids))
            {
                $where[] = ['user_id', 'in', $user_ids];
            } else {
                // 无数据条件，避免搜索条件没有数据造成的错觉
                $where[] = ['id', '=', 0];
            }
        }

        // 用户
        if(!empty($params['user']))
        {
            $where[] = ['user_id', '=', $params['user']['id']];
        }

        // 指定用户id
        if(!empty($params['uid']))
        {
            $where[] = ['pdl.user_id', '=', intval($params['uid'])];
        }

        // id
        if(!empty($params['id']))
        {
            $where[] = ['id', '=', intval($params['id'])];
        }

        // 更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            // 状态
            if(isset($params['status']) && $params['status'] > -1)
            {
                $where[] = ['status', '=', intval($params['status'])];
            }

            // 时间
            if(!empty($params['time_start']))
            {
                $where[] = ['add_time', '>', strtotime($params['time_start'])];
            }
            if(!empty($params['time_end']))
            {
                $where[] = ['add_time', '<', strtotime($params['time_end'])];
            }
        }
        return $where;
    }

    /**
     * 用户积分明细总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T23:10:43+0800
     * @param    [array]          $where [条件]
     */
    public static function UserIntegralTotal($where)
    {
        return (int) Db::name('PluginsDistributionIntegralLog')->where($where)->count();
    }

    /**
     * 获取取货点列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:50:14+0800
     * @param    [array]          $params [输入参数]
     */
    public static function ExtractionList($params = [])
    {
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $where = empty($params['where']) ? [] : $params['where'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        $data = Db::name('PluginsDistributionUserSelfExtraction')->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            $status_list = BaseService::$distribution_extraction_status_list;
            foreach($data as &$v)
            {
                // 基础数据处理
                $v = array_merge($v, ExtractionService::DataHandle($v));

                // 用户信息处理
                $v['user'] = UserService::GetUserViewInfo($v['user_id']);

                // logo
                $v['logo'] = ResourcesService::AttachmentPathViewHandle($v['logo']);

                // 状态
                $v['status_name'] = $status_list[$v['status']]['name'];

                // 添加时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);

                // 更新时间
                $v['upd_time_time'] = ($v['upd_time'] > 0) ? date('Y-m-d H:i:s', $v['upd_time']) : '';
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 取货点列表条件
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:53:46+0800
     * @param    [array]          $params [输入参数]
     */
    public static function ExtractionWhere($params = [])
    {
        $where = [];

        // 关键字根据订单筛选
        if(!empty($params['keywords']))
        {
            $where[] = ['name|tel|alias|address', 'like', '%'.$params['keywords'].'%'];
        }

        // 更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            // 状态
            if(isset($params['status']) && $params['status'] > -1)
            {
                $where[] = ['status', '=', intval($params['status'])];
            }

            // 时间
            if(!empty($params['time_start']))
            {
                $where[] = ['add_time', '>', strtotime($params['time_start'])];
            }
            if(!empty($params['time_end']))
            {
                $where[] = ['add_time', '<', strtotime($params['time_end'])];
            }
        }

        return $where;
    }

    /**
     * 取货点总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T23:10:43+0800
     * @param    [array]          $where [条件]
     */
    public static function ExtractionTotal($where)
    {
        return (int) Db::name('PluginsDistributionUserSelfExtraction')->where($where)->count();
    }

    /**
     * 取货点订单列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-03
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ExtractionOrderList($params = [])
    {
        // 参数
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $field = 'o.order_no,o.pay_price,o.order_model,o.status as order_status,o.user_id as order_user_id,po.*';
        $order_by = empty($params['order_by']) ? 'po.id desc' : $params['order_by'];
        
        // 获取数据
        $data = Db::name('PluginsDistributionUserSelfExtractionOrder')->alias('po')->join('order o', 'o.id=po.order_id')->where($where)->field($field)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            $user_type = isset($params['user_type']) ? $params['user_type'] : 'user';
            $order_status_list = MyConst('common_order_status');
            $take_status_list = BaseService::$order_status_list;
            foreach($data as &$v)
            {
                // 用户信息
                $v['user'] = ($user_type == 'admin') ? UserService::GetUserViewInfo($v['user_id']) : [];

                // 订单状态
                $v['order_status_name'] = ($v['order_model'] == 2 && $v['order_status'] == 2) ? '待取货' : $order_status_list[$v['order_status']]['name'];

                // 取货状态
                $v['status_name'] = isset($take_status_list[$v['status']]) ? $take_status_list[$v['status']]['name'] : '未知';

                // 创建时间
                $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);

                // 更新时间
                $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
            }
        }

        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 取货点订单列表 - 总数
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ExtractionOrderListTotal($where)
    {
        return (int) Db::name('PluginsDistributionUserSelfExtractionOrder')->alias('po')->join('order o', 'o.id=po.order_id')->where($where)->count();
    }

    /**
     * 取货点订单列表 - 条件
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ExtractionOrderListWhere($params = [])
    {
        $where = [
            'o.status'      => [2,3,4],
        ];
        if(isset($params['status']) && $params['status'] > -1)
        {
            $where['po.status'] = intval($params['status']);
        }

        // 用户
        if(!empty($params['user']))
        {
            $where['po.user_id'] = $params['user']['id'];
        }

        // 取货点用户id
        if(!empty($params['user_id']))
        {
            $where['po.user_id'] = intval($params['user_id']);
        }

        // 关键字
        if(!empty($params['keywords']))
        {
            $is_keywords = false;
            // 订单号
            if(strlen($params['keywords']) != 4)
            {
                $where['o.order_no'] = trim($params['keywords']);
                $is_keywords = true;
            } else {
                // 取件码
                $order_id = Db::name('OrderExtractionCode')->where(['code'=>trim($params['keywords'])])->value('order_id');
                if(!empty($order_id))
                {
                    $where['o.id'] = $order_id;
                    $is_keywords = true;
                }
            }

            // 关键字处理
            if($is_keywords === false)
            {
                $where['o.id'] = 0;
            }
        }

        return $where;
    }
}
?>