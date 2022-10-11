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
use app\service\UserService;
use app\service\PaymentService;
use app\service\PayLogService;
use app\service\MessageService;
use app\service\PluginsService;
use app\plugins\membershiplevelvip\service\BaseService;

/**
 * 会员等级服务层 - 支付
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class PayService
{
    /**
     * 支付
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-29
     * @desc    description
     * @param   array           $params [description]
     */
    public static function Pay($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '开通会员订单id不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'payment_id',
                'error_msg'         => '请选择支付方式',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 支付方式;
        $payment = PaymentService::PaymentData(['where'=>['id'=>intval($params['payment_id']), 'is_enable'=>1, 'is_open_user'=>1]]);
        if(empty($payment))
        {
            return DataReturn('支付方式有误', -1);
        }

        // 非线上支付方式不可用
        if(in_array($payment['payment'], MyConfig('shopxo.under_line_list')))
        {
            return DataReturn('不能使用非线上支付方式进行支付', -10);
        }

        // 获取开通订单数据
        $data = Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where(['id'=>intval($params['id'])])->find();
        if(empty($data))
        {
            return DataReturn('开通订单数据不存在', -1);
        }
        if($data['status'] == 1)
        {
            return DataReturn('该数据'.BaseService::$payment_user_order_status_list[$data['status']]['name'].'，请重新创建订单', -2);
        }

        // 支付入口文件检查
        $pay_checked = PaymentService::EntranceFileChecked($payment['payment'], 'membershiplevelvip');
        if($pay_checked['code'] != 0)
        {
            // 入口文件不存在则创建
            $payment_params = [
                'payment'       => $payment['payment'],
                'business'      => [
                    ['name' => 'MembershipLevelVip', 'desc' => '会员等级'],
                ],
                'respond'       => '/index/plugins/index/pluginsname/membershiplevelvip/pluginscontrol/buy/pluginsaction/respond',
                'notify'        => '/index/plugins/index/pluginsname/membershiplevelvip/pluginscontrol/paynotify/pluginsaction/notify',
            ];
            $ret = PaymentService::PaymentEntranceCreated($payment_params);
            if($ret['code'] != 0)
            {
                return $ret;
            }
        }

        // 回调地址
        $respond_url = $pay_checked['data']['respond'];
        $notify_url = $pay_checked['data']['notify'];

        // 是否指定同步回调地址
        if(!empty($params['redirect_url']))
        {
            $redirect_url = base64_decode(urldecode($params['redirect_url']));
            if(!empty($redirect_url))
            {
                // 赋值同步返回地址
                $respond_url = $redirect_url;
            }
        }
        if(empty($redirect_url))
        {
            $redirect_url = PluginsHomeUrl('membershiplevelvip', 'vip', 'index');
        }

        // 当前用户
        $current_user = empty($params['user']) ? UserService::LoginUserInfo() : $params['user'];
        if(!empty($current_user))
        {
            // 获取用户最新信息
            $temp_user = UserService::UserHandle(UserService::UserInfo('id', $current_user['id']));
            if(!empty($temp_user))
            {
                $current_user = $temp_user;
            }
        }

        // 新增支付日志
        $name = empty($data['number']) ? '终身' : $data['number'].'天';
        $subject = BaseService::$business_type_name.'('.$name.')';
        $pay_log = self::MembershiplevelVipPayLogInsert([
            'user_id'       => $current_user['id'],
            'business_ids'  => $data['id'],
            'business_nos'  => $data['payment_user_order_no'],
            'total_price'   => $data['price'],
            'payment'       => $payment['payment'],
            'payment_name'  => $payment['name'],
            'subject'       => $subject,
        ]);
        if($pay_log['code'] != 0)
        {
            return $pay_log;
        }

        // 发起支付数据
        $pay_data = [
            'params'        => $params,
            'user'          => $current_user,
            'out_user'      => md5($current_user['id']),
            'business_type' => 'plugins-membershiplevelvip',
            'business_ids'  => [$data['id']],
            'business_nos'  => [$data['payment_user_order_no']],
            'order_id'      => $pay_log['data']['id'],
            'order_no'      => $pay_log['data']['log_no'],
            'name'          => $subject,
            'total_price'   => $data['price'],
            'notify_url'    => $notify_url,
            'call_back_url' => $respond_url,
            'redirect_url'  => $redirect_url,
            'site_name'     => MyC('home_site_name', 'ShopXO', true),
            'check_url'     => PluginsHomeUrl('membershiplevelvip', 'buy', 'paycheck')
        ];

        // 微信中打开并且webopenid为空
        if(APPLICATION_CLIENT_TYPE == 'pc' && IsWeixinEnv() && empty($pay_data['user']['weixin_web_openid']))
        {
            // 授权成功后回调订单详情页面重新自动发起支付
            $url = PluginsHomeUrl('membershiplevelvip', 'vip', 'index', ['is_pay_auto'=>$pay_data['order_id'], 'payment_id'=>$payment['id']]);
            MySession('plugins_weixinwebauth_pay_callback_view_url', $url);
        }

        // 发起支付
        $pay_name = 'payment\\'.$payment['payment'];
        $ret = (new $pay_name($payment['config']))->Pay($pay_data);
        if(isset($ret['code']) && $ret['code'] == 0)
        {
            // 支付信息返回
            $ret['data'] = [
                // 支付模块处理数据
                'data'              => $ret['data'],

                // 支付日志id
                'order_id'          => $pay_log['data']['id'],
                'order_no'          => $pay_log['data']['log_no'],

                // 支付方式信息
                'payment'           => [
                    'id'        => $payment['id'],
                    'name'      => $payment['name'],
                    'payment'   => $payment['payment'],
                ],
            ];
            return $ret;
        }
        return DataReturn(empty($ret['msg']) ? '支付接口异常' : $ret['msg'], -1);
    }

    /**
     * 新增订单支付日志
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-28
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function MembershiplevelVipPayLogInsert($params = [])
    {
        $business_ids = isset($params['business_ids']) ? $params['business_ids'] : [];
        $business_nos = isset($params['business_nos']) ? $params['business_nos'] : [];
        return PayLogService::PayLogInsert([
            'user_id'       => isset($params['user_id']) ? intval($params['user_id']) : 0,
            'business_ids'  => is_array($business_ids) ? $business_ids : [$business_ids],
            'business_nos'  => is_array($business_nos) ? $business_nos : [$business_nos],
            'total_price'   => isset($params['total_price']) ? PriceNumberFormat($params['total_price']) : 0.00,
            'subject'       => empty($params['subject']) ? BaseService::$business_type_name : $params['subject'],
            'payment'       => isset($params['payment']) ? $params['payment'] : '',
            'payment_name'  => isset($params['payment_name']) ? $params['payment_name'] : '',
            'business_type' => BaseService::$business_type_name,
        ]);
    }

    /**
     * 支付状态校验
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-01-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function LevelPayCheck($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'order_no',
                'error_msg'         => '开通会单号有误',
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

        // 获取订单状态
        $where = ['log_no'=>$params['order_no'], 'user_id'=>$params['user']['id']];
        $pay_log = Db::name('PayLog')->where($where)->field('id,status')->find();
        if(empty($pay_log))
        {
            return DataReturn('开通会员订单数据不存在', -400, ['url'=>__MY_URL__]);
        }
        if($pay_log['status'] == 1)
        {
            return DataReturn('支付成功', 0, ['url'=>PluginsHomeUrl('membershiplevelvip', 'vip', 'index')]);
        }
        return DataReturn('支付中', -300);
    }

    /**
     * 支付同步处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-28
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function Respond($params = [])
    {
        // 支付方式
        $payment_name = defined('PAYMENT_TYPE') ? PAYMENT_TYPE : (isset($params['paymentname']) ? $params['paymentname'] : '');
        if(empty($payment_name))
        {
            return DataReturn('支付方式标记异常', -1);
        }
        $payment = PaymentService::PaymentData(['where'=>['payment'=>$payment_name]]);
        if(empty($payment))
        {
            return DataReturn('支付方式有误', -1);
        }

        // 支付数据校验
        $pay_name = 'payment\\'.$payment_name;
        $ret = (new $pay_name($payment['config']))->Respond(array_merge($_GET, $_POST));
        if(isset($ret['code']) && $ret['code'] == 0)
        {
            return DataReturn('支付成功', 0);
        }
        return DataReturn(empty($ret['msg']) ? '支付失败' : $ret['msg'], -100);
    }

    /**
     * 支付异步处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-28
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function Notify($params = [])
    {
        // 支付方式
        $payment = PaymentService::PaymentData(['where'=>['payment'=>PAYMENT_TYPE]]);
        if(empty($payment))
        {
            return DataReturn('支付方式有误', -1);
        }

        // 支付数据校验
        $pay_name = 'payment\\'.PAYMENT_TYPE;
        if(!class_exists($pay_name))
        {
            return DataReturn('支付方式不存在['.PAYMENT_TYPE.']', -1);
        }
        $payment_obj = new $pay_name($payment['config']);

        // 是否存在异步方法
        $method = method_exists($payment_obj, 'Notify') ? 'Notify' : 'Respond';
        $pay_ret = $payment_obj->$method(array_merge(input('get.'), input('post.')));
        if(!isset($pay_ret['code']) || $pay_ret['code'] != 0)
        {
            return $pay_ret;
        }

        // 获取支付日志订单
        $pay_log_data = Db::name('PayLog')->where([
            'log_no'    => $pay_ret['data']['out_trade_no'],
            'status'    => 0,
        ])->find();
        if(empty($pay_log_data))
        {
            return DataReturn('日志订单有误', -1);
        }

        // 获取关联信息
        $pay_log_value = Db::name('PayLogValue')->where(['pay_log_id'=>$pay_log_data['id']])->column('business_id');
        if(empty($pay_log_value))
        {
            return DataReturn('日志订单关联信息有误', -1);
        }

        // 获取订单信息
        $data = Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where(['id'=>$pay_log_value])->find();
        if(empty($data))
        {
            return DataReturn('开通订单信息有误', -1);
        }

        // 支付处理
        $pay_params = [
            'data'          => $data,
            'payment'       => $payment,
            'pay_log_data'  => $pay_log_data,
            'pay'       => [
                'trade_no'      => $pay_ret['data']['trade_no'],
                'subject'       => $pay_ret['data']['subject'],
                'buyer_user'    => $pay_ret['data']['buyer_user'],
                'pay_price'     => $pay_ret['data']['pay_price'],
            ],
        ];
        return self::LevelPayHandle($pay_params);
    }

    /**
     * 会员购买支付处理
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2018-10-05T23:02:14+0800
     * @param   [array]          $params [输入参数]
     */
    private static function LevelPayHandle($params = [])
    {
        // 订单信息
        if(empty($params['data']))
        {
            return DataReturn('资源不存在或已被删除', -1);
        }
        if($params['data']['status'] > 0)
        {
            $status_text = BaseService::$payment_user_order_status_list[$params['data']['status']]['name'];
            return DataReturn('状态不可操作['.$status_text.']', 0);
        }

        // 支付方式
        if(empty($params['payment']))
        {
            return DataReturn('支付方式有误', -1);
        }

        // 支付金额
        $pay_price = isset($params['pay']['pay_price']) ? $params['pay']['pay_price'] : 0;

        // 获取用户会员信息
        $pay_user = Db::name('PluginsMembershiplevelvipPaymentUser')->where(['id'=>$params['data']['payment_user_id']])->find();
        if(empty($pay_user))
        {
            return DataReturn('用户会员信息不存在', -1);
        }

        // 会员信息解析
        $level_data = empty($params['data']['level_data']) ? '' : json_decode($params['data']['level_data'], true);
        if(empty($level_data))
        {
            return DataReturn('会员等级数据有误', -1);
        }

        // 开启事务
        Db::startTrans();

        // 更新会员信息数据
        $upd_data = [
            'level_id'              => $level_data['id'],
            'level_name'            => $level_data['name'],
            'is_supported_renew'    => $level_data['is_supported_renew'],
            'upd_time'              => time(),
        ];

        // 购买时长处理，空则是终生
        if(empty($params['data']['number']))
        {
            $upd_data['is_permanent'] = 1; 
        } else {
            $number = $params['data']['number']*24*3600;
            $time = empty($pay_user['expire_time']) ? time() : ($pay_user['expire_time'] < time() ? time() : $pay_user['expire_time']);
            $upd_data['expire_time'] = $time+$number;
        }

        // 更新用户会员信息
        if(!Db::name('PluginsMembershiplevelvipPaymentUser')->where(['id'=>$params['data']['payment_user_id']])->update($upd_data))
        {
            Db::rollback();
            return DataReturn('会员信息更新失败', -100);
        }

        // 订单信息更新
        $upd_data = [
            'payment_id'    => $params['payment']['id'],
            'payment'       => $params['payment']['payment'],
            'payment_name'  => $params['payment']['name'],
            'pay_price'     => $pay_price,
            'status'        => 1,
            'pay_time'      => time(),
            'upd_time'      => time(),
        ];
        if(!Db::name('PluginsMembershiplevelvipPaymentUserOrder')->where(['id'=>$params['data']['id']])->update($upd_data))
        {
            Db::rollback();
            return DataReturn('会员购买纪录更新失败', -100);
        }

        // 更新支付日志
        $pay_log_data = [
            'log_id'        => $params['pay_log_data']['id'],
            'trade_no'      => isset($params['pay']['trade_no']) ? $params['pay']['trade_no'] : '',
            'buyer_user'    => isset($params['pay']['buyer_user']) ? $params['pay']['buyer_user'] : '',
            'pay_price'     => isset($params['pay']['pay_price']) ? $params['pay']['pay_price'] : 0,
            'subject'       => isset($params['pay']['subject']) ? $params['pay']['subject'] : BaseService::$business_type_name,
            'payment'       => $params['payment']['payment'],
            'payment_name'  => $params['payment']['name'],
        ];
        $ret = PayLogService::PayLogSuccess($pay_log_data);
        if($ret['code'] != 0)
        {
            // 事务回滚
            Db::rollback();
            return $ret;
        }

        // 消息通知
        $msg = '会员购买成功，时长：'.$params['data']['number'].'天，费用：'.$params['data']['price'];
        MessageService::MessageAdd($pay_user['user_id'], '会员购买成功', $msg, BaseService::$business_type_name, $params['data']['id']);

        // 提交事务
        Db::commit();

        // 会员缓存删除
        MyCache(md5(BaseService::$user_vip_data_key.$pay_user['user_id']), null);

        // 返回处理状态
        return DataReturn('支付成功', 0);        
    }
}
?>