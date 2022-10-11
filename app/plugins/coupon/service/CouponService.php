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
use app\service\GoodsService;
use app\service\UserService;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\UserNoticeService;

/**
 * 优惠券服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-05T21:51:08+0800
 */
class CouponService
{
    /**
     * 商品搜索
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2019-08-07T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function GoodsSearchList($params = [])
    {
        // 条件
        $where = [
            ['g.is_delete_time', '=', 0],
            ['g.is_shelves', '=', 1]
        ];

        // 关键字
        if(!empty($params['keywords']))
        {
            $where[] = ['g.title', 'like', '%'.$params['keywords'].'%'];
        }

        // 分类id
        if(!empty($params['category_id']))
        {
            $category_ids = GoodsService::GoodsCategoryItemsIds([$params['category_id']], 1);
            $category_ids[] = $params['category_id'];
            $where[] = ['gci.category_id', 'in', $category_ids];
        }

        // 商品id
        if(!empty($params['goods_ids']))
        {
            $goods_ids = is_array($params['goods_ids']) ? $params['goods_ids'] : explode(',', $params['goods_ids']);
            $where[] = ['g.id', 'in', $goods_ids];
        }

        // 指定字段
        $field = 'g.id,g.title';

        // 获取数据
        return GoodsService::CategoryGoodsList(['where'=>$where, 'm'=>0, 'n'=>100, 'field'=>$field, 'is_admin_access'=>1]);
    }

    /**
     * 优惠券保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CouponSave($params = [])
    {
        // 请求类型
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '1,30',
                'error_msg'         => '名称长度 1~30 个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'desc',
                'checked_data'      => '60',
                'error_msg'         => '描述长度最多 60 个字符',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'bg_color',
                'checked_data'      => array_keys(BaseService::$coupon_bg_color_list),
                'error_msg'         => '优惠券颜色值有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'sort',
                'checked_data'      => '3',
                'error_msg'         => '顺序 0~255 之间的数值',
            ],
            [
                'checked_type'      => 'max',
                'key_name'          => 'sort',
                'checked_data'      => 255,
                'error_msg'         => '顺序不能大于 255 数值',
            ],
        ];
        
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否编辑
        if(!empty($params['id']))
        {
            $coupon = Db::name('PluginsCoupon')->find(intval($params['id']));
        }

        // 非编辑或者已发放数量为0则需要校验核心数据
        if(empty($coupon) || $coupon['already_send_count'] <= 0)
        {
            $p[] = [
                'checked_type'      => 'in',
                'key_name'          => 'type',
                'checked_data'      => array_keys(BaseService::$coupon_type_list),
                'error_msg'         => '优惠券类型值有误',
            ];
            $p[] = [
                'checked_type'      => 'in',
                'key_name'          => 'expire_type',
                'checked_data'      => array_keys(BaseService::$common_expire_type_list),
                'error_msg'         => '到期类型值有误',
            ];
            $p[] = [
                'checked_type'      => 'isset',
                'key_name'          => 'where_order_price',
                'error_msg'         => '订单最低金额有误',
            ];
            $p[] = [
                'checked_type'      => 'in',
                'key_name'          => 'use_limit_type',
                'checked_data'      => array_keys(BaseService::$common_use_limit_type_list),
                'error_msg'         => '使用限制值有误',
            ];
            if(isset($params['type']) && $params['type'] == 1)
            {
                $p[] = [
                    'checked_type'      => 'isset',
                    'key_name'          => 'discount_price',
                    'error_msg'         => '减免金额有误',
                ];
            } else {
                $p[] = [
                    'checked_type'      => 'isset',
                    'key_name'          => 'discount_rate',
                    'error_msg'         => '折扣率有误',
                ];
            }
        }

        // 使用限制值
        $use_value_ids = '';
        if(isset($params['use_limit_type']))
        {
            if($params['use_limit_type'] == 1 && !empty($params['category_ids']))
            {
                $use_value_ids = json_encode(explode(',', $params['category_ids']));
            } else if($params['use_limit_type'] == 2 && !empty($params['goods_ids']))
            {
                $use_value_ids = json_encode(explode(',', $params['goods_ids']));
            }
        }

        // 数据
        $data = [
            'name'              => $params['name'],
            'desc'              => $params['desc'],
            'bg_color'          => $params['bg_color'],
            'sort'              => intval($params['sort']),
            'is_user_receive'   => isset($params['is_user_receive']) ? intval($params['is_user_receive']) : 0,
            'is_regster_send'   => isset($params['is_regster_send']) ? intval($params['is_regster_send']) : 0,
            'is_enable'         => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
        ];

        // 非编辑或者已发放数量为0则需要校验核心数据
        if(empty($coupon) || $coupon['already_send_count'] <= 0)
        {
            $data['type'] = intval($params['type']);
            $data['expire_type'] = intval($params['expire_type']);
            $data['expire_hour'] = ($params['expire_type'] == 0 && isset($params['expire_hour'])) ? intval($params['expire_hour']) : 0;
            $data['use_limit_type'] = isset($params['use_limit_type']) ? intval($params['use_limit_type']) : 0;
            $data['fixed_time_start'] = empty($params['fixed_time_start']) ? 0 : strtotime($params['fixed_time_start']);
            $data['fixed_time_end'] = empty($params['fixed_time_end']) ? 0 : strtotime($params['fixed_time_end']);
            $data['where_order_price'] = PriceNumberFormat($params['where_order_price']);
            $data['use_value_ids'] = $use_value_ids;
            $data['discount_value'] = ($params['type'] == 1) ? PriceNumberFormat($params['discount_rate']) : PriceNumberFormat($params['discount_price']);
            $data['limit_send_count'] = isset($params['limit_send_count']) ? intval($params['limit_send_count']) : 0;
        }

        if(empty($coupon))
        {
            $data['add_time'] = time();
            if(Db::name('PluginsCoupon')->insertGetId($data) > 0)
            {
                return DataReturn('添加成功', 0);
            }
            return DataReturn('添加失败', -100);
        } else {
            $data['upd_time'] = time();
            if(Db::name('PluginsCoupon')->where(['id'=>$coupon['id']])->update($data))
            {
                return DataReturn('编辑成功', 0);
            }
            return DataReturn('编辑失败', -100); 
        }
    }

    /**
     * 优惠券列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date    2019-08-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CouponList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'sort asc,id desc' : $params['order_by'];
        $is_handle = isset($params['is_handle']) ? intval($params['is_handle']) : 1;
        $is_sure_receive = isset($params['is_sure_receive']) ? intval($params['is_sure_receive']) : 0;

        // 获取数据列表
        $data = Db::name('PluginsCoupon')->field($field)->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if($is_handle == 1 && !empty($data))
        {
            // 静态资源
            $common_is_whether_list = BaseService::$common_is_whether_list;
            $coupon_type_list = BaseService::$coupon_type_list;
            $coupon_bg_color_list = BaseService::$coupon_bg_color_list;
            $common_expire_type_list = BaseService::$common_expire_type_list;
            $common_use_limit_type_list = BaseService::$common_use_limit_type_list;

            // 插件基础配置
            $base = BaseService::BaseConfig();

            // 是否允许重复领取优惠券
            $is_repeat_receive = (isset($base['data']['is_repeat_receive']) && $base['data']['is_repeat_receive'] == 1) ? 1 : 0;

            foreach($data as &$v)
            {
                // 该优惠券是否可以操作
                $v['is_operable'] = 1;
                $v['is_operable_name'] = '领取';

                // 校验用户是否已领取
                // 不允许重复领取
                // 用户已登录
                // 达到以上三个条件则校验当前登录用户是否还可以领取当前优惠券
                if($is_sure_receive == 1 && $is_repeat_receive != 1 && !empty($params['user']))
                {
                    $temp = Db::name('PluginsCouponUser')->where(['coupon_id'=>$v['id'], 'user_id'=>$params['user']['id']])->find();
                    if(!empty($temp))
                    {
                        $v['is_operable'] = 0;
                        $v['is_operable_name'] = '已领取';
                    }
                }

                // 是否已过期
                if($v['is_operable'] == 1 && isset($v['expire_type']) && $v['expire_type'] == 1 && isset($v['fixed_time_end']) && $v['fixed_time_end'] < time())
                {
                    $v['is_operable'] = 0;
                    $v['is_operable_name'] = '已过期';
                }

                // 是否超限
                if($v['is_operable'] == 1 && isset($v['limit_send_count']) && isset($v['already_send_count']) && $v['limit_send_count'] > 0 && $v['limit_send_count'] <= $v['already_send_count'])
                {
                    $v['is_operable'] = 0;
                    $v['is_operable_name'] = '已领光';
                }

                // 优惠券类型
                $v['type_name'] = (isset($v['type']) && isset($coupon_type_list[$v['type']])) ? $coupon_type_list[$v['type']]['name'] : '未知';
                $v['type_unit'] = (!isset($v['type']) || $v['type'] == 0) ? 'π' : '折';

                // 背景色
                if((isset($v['bg_color']) && isset($coupon_bg_color_list[$v['bg_color']])))
                {
                    $v['bg_color_name'] = $coupon_bg_color_list[$v['bg_color']]['name'];
                    $v['bg_color_value'] = $coupon_bg_color_list[$v['bg_color']]['color'];
                } else {
                    $v['bg_color_name'] = '未知';
                    $v['bg_color_value'] = '#D2364C';
                }

                // 过期类型
                $v['expire_type_name'] = (isset($v['expire_type']) && isset($common_expire_type_list[$v['expire_type']])) ? $common_expire_type_list[$v['expire_type']]['name'] : '未知';

                // 使用限制类型
                $v['use_limit_type_name'] = (isset($v['use_limit_type']) && isset($common_use_limit_type_list[$v['use_limit_type']])) ? $common_use_limit_type_list[$v['use_limit_type']]['name'] : '未知';

                // 优惠使用限制关联id
                $v['use_value_ids_all'] = empty($v['use_value_ids']) ? [] : json_decode($v['use_value_ids'], true);
                $v['use_value_ids_str'] = empty($v['use_value_ids_all']) ? '' : implode(',', $v['use_value_ids_all']);

                // 过期时间
                $v['fixed_time_start'] = empty($v['fixed_time_start']) ? '' : date('Y-m-d H:i:s', $v['fixed_time_start']);
                $v['fixed_time_end'] = empty($v['fixed_time_end']) ? '' : date('Y-m-d H:i:s', $v['fixed_time_end']);

                // 优惠金额/折扣美化
                $v['discount_value'] = PriceBeautify($v['discount_value']);

                // 是否开启
                $v['is_enable_name'] = (isset($v['is_enable']) && isset($common_is_whether_list[$v['is_enable']])) ? $common_is_whether_list[$v['is_enable']]['name'] : '未知';

                // 是否开放领取
                $v['is_user_receive_name'] = (isset($v['is_user_receive']) && isset($common_is_whether_list[$v['is_user_receive']])) ? $common_is_whether_list[$v['is_user_receive']]['name'] : '未知';

                // 是否注册发放
                $v['is_regster_send_name'] = (isset($v['is_regster_send']) && isset($common_is_whether_list[$v['is_regster_send']])) ? $common_is_whether_list[$v['is_regster_send']]['name'] : '未知';

                // 使用限制
                if(!empty($v['use_value_ids_all']))
                {
                    // 商品分类
                    if($v['use_limit_type'] == 1)
                    {
                        $v['category_names'] = Db::name('GoodsCategory')->where('id', 'in', $v['use_value_ids_all'])->column('name');

                    // 商品
                    } else if($v['use_limit_type'] == 2)
                    {
                        $goods = self::GoodsSearchList(['goods_ids'=>$v['use_value_ids_all']]);
                        if(isset($goods['data']))
                        {
                            $v['goods_items'] = $goods['data'];
                        }
                    }
                }

                // 时间
                $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                $v['upd_time_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 优惠券总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CouponTotal($where = [])
    {
        return (int) Db::name('PluginsCoupon')->where($where)->count();
    }

    /**
     * 优惠券条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CouponWhere($params = [])
    {
        $where = [];

        // 关键字
        if(!empty($params['keywords']))
        {
            $where[] = ['name|desc', 'like', '%'.$params['keywords'].'%'];
        }

        // 更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            // 等值 业务
            if(isset($params['type']) && $params['type'] > -1)
            {
                $where[] = ['type', '=', $params['type']];
            }
            if(isset($params['bg_color']) && $params['bg_color'] > -1)
            {
                $where[] = ['bg_color', '=', $params['bg_color']];
            }
            if(isset($params['expire_type']) && $params['expire_type'] > -1)
            {
                $where[] = ['expire_type', '=', $params['expire_type']];
            }
            if(isset($params['use_limit_type']) && $params['use_limit_type'] > -1)
            {
                $where[] = ['use_limit_type', '=', $params['use_limit_type']];
            }

            // 等值 状态
            if(isset($params['is_enable']) && $params['is_enable'] > -1)
            {
                $where[] = ['is_enable', '=', $params['is_enable']];
            }
            if(isset($params['is_user_receive']) && $params['is_user_receive'] > -1)
            {
                $where[] = ['is_user_receive', '=', $params['is_user_receive']];
            }
            if(isset($params['is_regster_send']) && $params['is_regster_send'] > -1)
            {
                $where[] = ['is_regster_send', '=', $params['is_regster_send']];
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
        if(Db::name('PluginsCoupon')->where(['id'=>intval($params['id'])])->update([$params['field']=>intval($params['state'])]))
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
        $where = [
            ['id', '=', intval($params['id'])],
            ['already_send_count', '<=', 0],
        ];
        if(Db::name('PluginsCoupon')->where($where)->delete())
        {
            return DataReturn('删除成功');
        }

        return DataReturn('删除失败或资源不存在', -100);
    }

    /**
     * 用户搜索
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-10T17:29:34+0800
     * @param    [array]             $params [输入参数]
     */
    public static function UserSearchList($params = [])
    {
        if(empty($params['keywords']))
        {
            return DataReturn('搜索关键字不能为空', -1);
        }

        // 关键字条件
        $where = [];
        foreach(explode(' ', $params['keywords']) as $wd)
        {
            $where[] = ['username|nickname|mobile|email', 'like', '%'.$wd.'%'];
        }

        $data = Db::name('User')->whereOr($where)->field('id,username,nickname,mobile,email,avatar')->select()->toArray();
        if(!empty($data))
        {
            foreach($data as &$user)
            {
                $user = UserService::GetUserViewInfo(null, $user);
            }
        }
        return DataReturn('获取成功', 0, $data);
    }

    /**
     * 用户优惠券发放
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-10T18:30:52+0800
     * @param    [array]           $params [输入参数]
     */
    public static function CouponSend($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'coupon_id',
                'error_msg'         => '优惠券id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_ids',
                'error_msg'         => '请指定发放用户',
            ],
            [
                'checked_type'      => 'is_array',
                'key_name'          => 'user_ids',
                'error_msg'         => '发放用户有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取优惠券
        $coupon = Db::name('PluginsCoupon')->find(intval($params['coupon_id']));
        if(empty($coupon))
        {
            return DataReturn('优惠券不存在或已删除', -1);
        }

        // 基础判断
        if($coupon['is_enable'] != 1)
        {
            return DataReturn('优惠券未启用['.$coupon['name'].']', -1);
        }
        if($coupon['limit_send_count'] > 0 && $coupon['already_send_count'] >= $coupon['limit_send_count'])
        {
            return DataReturn('优惠券发放数量已超限['.$coupon['name'].']', -1);
        }

        // 用户领取
        if(isset($params['is_user_receive']) && $params['is_user_receive'] == 1 && $coupon['is_user_receive'] != 1)
        {
            return DataReturn('未开放领取['.$coupon['name'].']', -1);
        }

        // 注册发放
        if(isset($params['is_regster_send']) && $params['is_regster_send'] == 1 && $coupon['is_regster_send'] != 1)
        {
            return DataReturn('不支持注册发放['.$coupon['name'].']', -1);
        }

        // 是否已过期
        switch($coupon['expire_type'])
        {
            // 领取生效
            case 0 :
                if($coupon['expire_hour'] <= 0)
                {
                    return DataReturn('优惠券有效时间有误['.$coupon['name'].']', -1);
                }
                break;

            // 固定日期
            case 1 :
                if($coupon['fixed_time_end'] < time())
                {
                    return DataReturn('优惠券已过期['.$coupon['name'].']', -1);
                }
                break;

            default :
                return DataReturn('优惠券过期类型有误['.$coupon['name'].']', -1);
        }

        // 过期时间计算
        switch($coupon['expire_type'])
        {
            // 领取生效
            case 0 :
                $time_start = time();
                $time_end = time()+(3600*$coupon['expire_hour']);
                break;

            // 固定日期
            case 1 :
                $time_start = $coupon['fixed_time_start'];
                $time_end = $coupon['fixed_time_end'];
                break;
        }

        // 添加优惠券
        $data = [];
        $add_time = time();
        foreach($params['user_ids'] as $user_id)
        {
            $data[] = [
                'coupon_id'     => $coupon['id'],
                'user_id'       => $user_id,
                'is_valid'      => 1,
                'time_start'    => $time_start,
                'time_end'      => $time_end,
                'add_time'      => $add_time,
            ];
        }

        // 添加用户优惠券
        Db::startTrans();
        $count = count($data);
        if(Db::name('PluginsCouponUser')->insertAll($data) >= $count)
        {
            // 更新发放条数
            if(Db::name('PluginsCoupon')->where(['id'=>$coupon['id']])->inc('already_send_count', $count)->update())
            {
                Db::commit();

                // 是否需要通知
                if(!empty($params['config']))
                {
                    UserNoticeService::Send($params['config'], $data, $coupon);
                }

                return DataReturn('发放成功', 0);
            }
        }
        Db::rollback();
        return DataReturn('发放失败', -100);
    }

    /**
     * 用户领取优惠券
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-11T15:36:19+0800
     * @param    [array]          $params [输入参数]
     */
    public static function UserReceiveCoupon($params = [])
    {
        // 优惠券id是否正常
        if(empty($params['coupon_id']))
        {
            return DataReturn('优惠券id有误', -1);
        }

        // 是否登录
        $user = UserService::LoginUserInfo();
        if(empty($user))
        {
            return DataReturn('请先登录', -400);
        }
        $coupon_id = intval($params['coupon_id']);

        // 是否允许重复领取
        // 是否已领取过
        $base = BaseService::BaseConfig();
        if(!isset($base['data']['is_repeat_receive']) || $base['data']['is_repeat_receive'] != 1)
        {
            $temp = Db::name('PluginsCouponUser')->where(['coupon_id'=>$coupon_id, 'user_id'=>$user['id']])->find();
            if(!empty($temp))
            {
                return DataReturn('该优惠券已领取过，请勿重复领取', -1);
            }
        }

        // 领取优惠券
        $coupon_params = [
            'user_ids'          => [$user['id']],
            'coupon_id'         => $coupon_id,
            'is_user_receive'   => 1,
            'config'            => $base['data'],
        ];
        $ret = self::CouponSend($coupon_params);
        if($ret['code'] == 0)
        {
            return DataReturn('领取成功', 0);
        }
        return $ret;
    }
}
?>