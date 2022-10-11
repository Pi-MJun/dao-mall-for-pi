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
namespace app\plugins\membershiplevelvip\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\ResourcesService;
use app\service\UserService;
use app\service\PaymentService;
use app\plugins\membershiplevelvip\service\LevelService;
use app\plugins\membershiplevelvip\service\BusinessService;

/**
 * 会员等级服务层 - 基础
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class BaseService
{
    // 类型名称
    public static $business_type_name = '会员等级';

    // 基础数据附件字段
    public static $base_config_attachment_field = [
        'default_level_images',
        'user_poster_share_images',
        'banner_bg_images',
    ];

    // 等级规则
    public static $members_level_rules_list = [
        0 => ['value' => 0, 'name' => '积分（可用积分）', 'checked' => true],
        1 => ['value' => 1, 'name' => '消费总额（已完成订单）'],
    ];

    // 开通会员订单状态
    // 订单状态（0待支付, 1已支付, 2已取消, 3已关闭）
    public static $payment_user_order_status_list = [
        0 => ['value' => 0, 'name' => '待支付', 'checked' => true],
        1 => ['value' => 1, 'name' => '已支付'],
        2 => ['value' => 2, 'name' => '已取消'],
        3 => ['value' => 3, 'name' => '已关闭'],
    ];

    // 结算状态（0待结算, 1结算中, 2已结算）
    public static $payment_user_order_settlement_status_list = [
        0 => ['value' => 0, 'name' => '待结算', 'checked' => true],
        1 => ['value' => 1, 'name' => '结算中'],
        2 => ['value' => 2, 'name' => '已结算'],
    ];

    // 收益结算状态（0待结算, 1已结算, 2已失效）
    public static $payment_user_profit_status_list = [
        0 => ['value' => 0, 'name' => '待结算', 'checked' => true],
        1 => ['value' => 1, 'name' => '已结算'],
        2 => ['value' => 2, 'name' => '已失效'],
    ];

    // 订单类型（0正常购买, 1续费）
    public static $payment_user_order_type_list = [
        0 => ['value' => 0, 'name' => '正常购买', 'checked' => true],
        1 => ['value' => 1, 'name' => '续费'],
    ];

    // 级别
    public static $level_name_list = [
        1 => ['value' => 1, 'name' => '一级'],
        2 => ['value' => 2, 'name' => '二级'],
        3 => ['value' => 3, 'name' => '三级'],
    ];

    // 会员VIP信息缓存key
    public static $user_vip_data_key = 'plugins_membershiplevelvip_user_vip_';

    /**
     * 获取用户列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:50:14+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserPaymentList($params = [])
    {
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];
        $is_privacy = isset($params['is_privacy']) ? (boolean) $params['is_privacy'] : true;

        $data = Db::name('PluginsMembershiplevelvipPaymentUser')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // 用户信息处理
                $v['user'] = UserService::GetUserViewInfo($v['user_id']);

                // 创建时间
                $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = empty($v['add_time']) ? '' : date('Y-m-d', $v['add_time']);

                // 更新时间
                $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);

                // 当前用户下级用户id列表
                $user_find_ids = Db::name('User')->where(['referrer'=>$v['user_id']])->column('id');

                // 当前用户下一级总数
                $v['referrer_count'] = empty($user_find_ids) ? 0 : count($user_find_ids);

                // 当前用户下一级消费总金额
                if(empty($user_find_ids))
                {
                    $v['find_order_total'] = '0.00';
                } else {
                    $find_where = [
                        ['user_id', 'in', $user_find_ids],
                        ['status', '=', 1],
                    ];
                    $v['find_order_total'] = PriceNumberFormat(Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where($find_where)->sum('pay_price'));
                }

                // 订单有效金额
                $order_where = [
                    ['user_id', '=', $v['user_id']],
                    ['status', '=', 1],
                ];
                $v['order_total'] = PriceNumberFormat(Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where($order_where)->sum('pay_price'));
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 用户列表条件
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T22:53:46+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserPaymentWhere($params = [])
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

        // 更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            // 是否永久
            if(isset($params['is_permanent']) && $params['is_permanent'] > -1)
            {
                $where[] = ['is_permanent', '=', intval($params['is_permanent'])];
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
    public static function UserPaymentTotal($where)
    {
        return (int) Db::name('PluginsMembershiplevelvipPaymentUser')->where($where)->count();
    }

    /**
     * 用户购买会员订单列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function UserPayOrderList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::name('PluginsMembershiplevelvipPaymentUserOrder')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // 用户信息
                $v['user'] = UserService::GetUserViewInfo($v['user_id']);

                // 类型
                $v['type_name'] = isset(self::$payment_user_order_type_list[$v['type']]) ? self::$payment_user_order_type_list[$v['type']]['name'] : '';

                // 结算状态
                $v['settlement_status_name'] = isset(self::$payment_user_order_settlement_status_list[$v['settlement_status']]) ? self::$payment_user_order_settlement_status_list[$v['settlement_status']]['name'] : '';

                // 返佣总额
                $v['profit_price_total'] = Db::name('PluginsMembershiplevelvipUserProfit')->where(['payment_user_order_id'=>$v['id']])->sum('profit_price');

                // 购买信息
                if(empty($v['number']))
                {
                    $value = '终身';
                    $unit = '';
                } else {
                    $value_uint = BusinessService::UserExpireTimeValueUnit($v['number']);
                    $value = $value_uint['value'];
                    $unit = $value_uint['unit'];
                }
                $v['period_value'] = $value;
                $v['period_unit'] = $unit;

                // 支付状态
                $v['status_name'] = isset($v['status']) ? self::$payment_user_order_status_list[$v['status']]['name'] : '';

                // 支付时间
                $v['pay_time_time'] = empty($v['pay_time']) ? '' : date('Y-m-d H:i:s', $v['pay_time']);
                $v['pay_time_date'] = empty($v['pay_time']) ? '' : date('Y-m-d', $v['pay_time']);

                // 创建时间
                $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = empty($v['add_time']) ? '' : date('Y-m-d', $v['add_time']);

                // 更新时间
                $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 用户购买会员订单列表总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function UserPayOrderTotal($where = [])
    {
        return (int) Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where($where)->count();
    }

    /**
     * 用户购买会员订单列表条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function UserPayOrderWhere($params = [])
    {
        $where = [];

        // 用户id
        if(!empty($params['user']))
        {
            $where[] = ['user_id', '=', $params['user']['id']];
        }

        // 指定用户id
        if(!empty($params['uid']))
        {
            $where[] = ['user_id', '=', intval($params['uid'])];
        }

        // id
        if(!empty($params['id']))
        {
            $where[] = ['id', '=', intval($params['id'])];
        }
        // 订单号
        if(!empty($params['orderno']))
        {
            $where[] = ['payment_user_order_no', '=', trim($params['orderno'])];
        }

        // 关键字根据用户筛选
        if(!empty($params['keywords']))
        {
            $user_ids = Db::name('User')->where('username|nickname|mobile|email', '=', $params['keywords'])->column('id');
            if(!empty($user_ids))
            {
                $where[] = ['user_id', 'in', $user_ids];
            } else {
                // 无数据条件，走单号条件
                $where[] = ['payment_user_order_no', '=', $params['keywords']];
            }
        }

        // 更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            // 订单状态
            if(isset($params['status']) && $params['status'] > -1)
            {
                $where[] = ['status', '=', $params['status']];
            }

            // 结算状态
            if(isset($params['settlement_status']) && $params['settlement_status'] > -1)
            {
                $where[] = ['settlement_status', '=', $params['settlement_status']];
            }
        }

        return $where;
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
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];
        $user_type = isset($params['user_type']) ? $params['user_type'] : 'user';

        $data = Db::name('PluginsMembershiplevelvipUserProfit')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            $common_platform_type = MyConst('common_platform_type');
            $order_status_list = MyConst('common_order_status');
            $order_pay_status = MyConst('common_order_pay_status');
            $status_list = self::$payment_user_profit_status_list;
            foreach($data as &$v)
            {
                // 用户信息
                $v['user'] = ($user_type == 'admin') ? UserService::GetUserViewInfo($v['user_id']) : [];

                // 级别
                $v['level_name'] = isset(self::$level_name_list[$v['level']]) ? self::$level_name_list[$v['level']]['name'] : '未知';

                // 佣金状态
                $v['status_name'] = isset($status_list[$v['status']]) ? $status_list[$v['status']]['name'] : '未知';

                // 创建时间
                $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = empty($v['add_time']) ? '' : date('Y-m-d', $v['add_time']);

                // 更新时间
                $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
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
            $order_ids = Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where('payment_user_order_no', '=', $params['keywords'])->column('id');
            if(!empty($order_ids))
            {
                $where[] = ['payment_user_order_id', 'in', $order_ids];
            } else {
                $user_ids = Db::name('User')->where('username|nickname|mobile|email', '=', $params['keywords'])->column('id');
                if(!empty($user_ids))
                {
                    $where[] = ['user_id', 'in', $user_ids];
                } else {
                    // 无数据条件，避免搜索条件没有数据造成的错觉
                    $where[] = ['id', '=', 0];
                }
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
            $where[] = ['user_id', '=', intval($params['uid'])];
        }

        // id
        if(!empty($params['id']))
        {
            $where[] = ['id', '=', intval($params['id'])];
        }

        // 更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            // 是否有退款
            if(isset($params['status']) && $params['status'] > -1)
            {
                $where[] = ['status', '=', intval($params['status'])];
            }

            // 级别
            if(isset($params['level']) && $params['level'] > 0)
            {
                $where[] = ['level', '=', intval($params['level'])];
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
     * 用户收益明细总数
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-09T23:10:43+0800
     * @param    [array]          $where [条件]
     */
    public static function UserProfitTotal($where)
    {
        return (int) Db::name('PluginsMembershiplevelvipUserProfit')->where($where)->count();
    }

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

                // 当前用户下级用户id列表
                $user_find_ids = Db::name('User')->where(['referrer'=>$v['id']])->column('id');

                // 当前用户下一级总数
                $v['referrer_count'] = empty($user_find_ids) ? 0 : count($user_find_ids);

                // 当前用户下一级消费总金额
                if(empty($user_find_ids))
                {
                    $v['find_order_total'] = '0.00';
                } else {
                    $find_where = [
                        ['user_id', 'in', $user_find_ids],
                        ['status', '=', 1],
                    ];
                    $v['find_order_total'] = PriceNumberFormat(Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where($find_where)->sum('pay_price'));
                }

                // 订单有效金额
                $order_where = [
                    ['user_id', '=', $v['id']],
                    ['status', '=', 1],
                ];
                $v['order_total'] = PriceNumberFormat(Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where($order_where)->sum('pay_price'));

                // 创建时间
                $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = empty($v['add_time']) ? '' : date('Y-m-d', $v['add_time']);

                // 更新时间
                $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
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

        // 关键字筛选
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

        // 用户
        if(!empty($params['user']))
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
     * 支付方式获取
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-19
     * @desc    description
     */
    public static function HomeBuyPaymentList()
    {
        // 排除线下支付和百度支付（百度不支持异步会掉自定义）
        $not = MyConfig('shopxo.under_line_list');
        $not[] = 'WalletPay';
        $where = [
            ['is_enable', '=', 1],
            ['is_open_user', '=', 1],
            ['payment', 'not in', $not],
        ];
        return PaymentService::BuyPaymentList(['where'=>$where]);
    }

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        // 会员首页描述
        if(!empty($params['banner_bottom_content']))
        {
            $params['banner_bottom_content'] = ResourcesService::ContentStaticReplace(htmlspecialchars_decode($params['banner_bottom_content']), 'add');
        }

        return PluginsService::PluginsDataSave(['plugins'=>'membershiplevelvip', 'data'=>$params], self::$base_config_attachment_field);
    }

    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        $ret = PluginsService::PluginsData('membershiplevelvip', self::$base_config_attachment_field, $is_cache);
        if(!empty($ret['data']))
        {
            // 未开通会员介绍
            if(!empty($ret['data']['not_opening_vip_desc']))
            {
                $ret['data']['not_opening_vip_desc'] = explode("\n", $ret['data']['not_opening_vip_desc']);
            }

            // 会员中心公告
            if(!empty($ret['data']['user_vip_center_notice']))
            {
                $ret['data']['user_vip_center_notice'] = explode("\n", $ret['data']['user_vip_center_notice']);
            }

            // 会员首页背景图片
            if(empty($ret['data']['banner_bg_images']))
            {
                $ret['data']['banner_bg_images'] = MyConfig('shopxo.attachment_host').'/static/plugins/images/membershiplevelvip/index-bg.png';
            }

            // 会员首页描述
            if(!empty($ret['data']['banner_bottom_content']))
            {
                $ret['data']['banner_bottom_content'] = ResourcesService::ContentStaticReplace($ret['data']['banner_bottom_content'], 'get');
            }
        }
        return $ret;
    }

    /**
     * 用户中心菜单
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-18
     * @desc    description
     * @param   [array]          $base [配置信息]
     */
    public static function UserCenterNav($base)
    {
        $host = MyConfig('shopxo.attachment_host').'/static/plugins/images/membershiplevelvip/app/';
        $data = [
            [
                'icon'  => $host.'user-center-order-icon.png',
                'title' => '开通订单',
                'url'   => '/pages/plugins/membershiplevelvip/order/order',
            ]
        ];

        // 开启返佣
        if(isset($base['is_commission']) && $base['is_commission'] == 1)
        {
            $data[] = [
                'icon'  => $host.'user-center-profit-icon.png',
                'title' => '收益明细',
                'url'   => '/pages/plugins/membershiplevelvip/profit/profit',
            ];
        }

        // 开启推广
        if(isset($base['is_propaganda']) && $base['is_propaganda'] == 1)
        {
            $data[] = [
                'icon'  => $host.'user-center-team-icon.png',
                'title' => '我的团队',
                'url'   => '/pages/plugins/membershiplevelvip/team/team',
            ];
            $data[] = [
                'icon'  => $host.'user-center-poster-icon.png',
                'title' => '推广返利',
                'url'   => '/pages/plugins/membershiplevelvip/poster/poster',
            ];
            $data[] = [
                'icon'  => $host.'user-center-statistics-icon.png',
                'title' => '数据统计',
                'url'   => '/pages/plugins/membershiplevelvip/statistics/statistics',
            ];
        }

        // 会员首页
        $data[] = [
            'icon'  => $host.'user-center-index-icon.png',
            'title' => '会员首页',
            'url'   => '/pages/plugins/membershiplevelvip/index/index',
        ];
        return $data;
    }

    /**
     * 二维码清空
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-12T20:36:38+0800
     * @param   [array]           $params [输入参数]
     */
    public static function QrcodeDelete($params = [])
    {
        $dir_all = ['qrcode'];
        foreach($dir_all as $v)
        {
            $dir = 'static'.DS.'upload'.DS.'images'.DS.'plugins_membershiplevelvip'.DS.$v;
            if(is_dir($dir))
            {
                // 是否有权限
                if(!is_writable($dir))
                {
                    return DataReturn('目录没权限', -1);
                }

                // 删除目录
                \base\FileUtil::UnlinkDir($dir);
            }
        }

        return DataReturn('操作成功', 0);
    }
}
?>