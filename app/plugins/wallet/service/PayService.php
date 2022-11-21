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
namespace app\plugins\wallet\service;

use think\facade\Db;
use app\service\UserService;
use app\service\PaymentService;
use app\service\PayLogService;
use app\service\MessageService;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\WalletService;
use app\plugins\wallet\service\RechargeService;
use app\plugins\wallet\service\NoticeService;

/**
 * 支付服务层
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
                'key_name'          => 'recharge_id',
                'error_msg'         => '充值日志id不能为空',
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
        $payment = PaymentService::PaymentData(['where'=>['id'=>intval($params['payment_id']), 'is_enable'=>1]]);
        if(empty($payment))
        {
            return DataReturn('支付方式有误', -1);
        }

        // 非线上支付方式不可用
        if(in_array($payment['payment'], MyConfig('shopxo.under_line_list')))
        {
            return DataReturn('不能使用非线上支付方式进行充值', -10);
        }

        // 获取充值数据
        $data = Db::name('PluginsWalletRecharge')->where(['id'=>intval($params['recharge_id'])])->find();
        if(empty($data))
        {
            return DataReturn('充值数据不存在', -1);
        }
        if($data['status'] == 1)
        {
            return DataReturn('该数据已充值，请重新创建充值订单', -2);
        }

        // 支付入口文件检查
        $pay_checked = PaymentService::EntranceFileChecked($payment['payment'], 'wallet');
        if($pay_checked['code'] != 0)
        {
            // 入口文件不存在则创建
            $payment_params = [
                'payment'       => $payment['payment'],
                'business'      => [
                    ['name' => 'Wallet', 'desc' => '钱包'],
                ],
                'respond'       => '/index/plugins/index/pluginsname/wallet/pluginscontrol/recharge/pluginsaction/respond',
                'notify'        => '/index/plugins/index/pluginsname/wallet/pluginscontrol/rechargenotify/pluginsaction/notify',
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
            $redirect_url = PluginsHomeUrl('wallet', 'recharge', 'index');
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
        $subject = BaseService::$business_type_name.'(账户充值)';
        $pay_log = self::WalletPayLogInsert([
            'user_id'       => $current_user['id'],
            'business_ids'  => $data['id'],
            'business_nos'  => $data['recharge_no'],
            'total_price'   => $data['money'],
            'payment'       => $payment['payment'],
            'payment_name'  => $payment['name'],
            'subject'       => $subject,
        ]);
        if($pay_log['code'] != 0)
        {
            return $pay_log;
        }

        // 发起支付
        $pay_data = [
            'params'        => $params,
            'user'          => $current_user,
            'out_user'      => md5($current_user['id']),
            'business_type' => 'plugins-wallet',
            'business_ids'  => [$data['id']],
            'business_nos'  => [$data['recharge_no']],
            'order_id'      => $pay_log['data']['id'],
            'order_no'      => $pay_log['data']['log_no'],
            'name'          => $subject,
            'total_price'   => $data['money'],
            'notify_url'    => $notify_url,
            'call_back_url' => $respond_url,
            'redirect_url'  => $redirect_url,
            'site_name'     => MyC('home_site_name', 'ShopXO', true),
            'check_url'     => PluginsHomeUrl('wallet', 'recharge', 'paycheck')
        ];
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
        return DataReturn(
            empty($ret['msg']) ? '支付接口异常' : $ret['msg'],
            isset($ret['code']) ? $ret['code'] : -1,
            isset($ret['data']) ? $ret['data'] : '');
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
    public static function WalletPayLogInsert($params = [])
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
    public static function RechargePayCheck($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'order_no',
                'error_msg'         => '充值单号有误',
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
            return DataReturn('充值数据不存在', -400, ['url'=>__MY_URL__]);
        }
        if($pay_log['status'] == 1)
        {
            return DataReturn('支付成功', 0, ['url'=>PluginsHomeUrl('wallet', 'recharge', 'index')]);
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

        // 是否存在处理方法
        $method = method_exists($payment_obj, 'Notify') ? 'Notify' : 'Respond';
        $pay_ret = $payment_obj->$method(array_merge(input('get.'), input('post.')));
        if(!isset($pay_ret['code']) || $pay_ret['code'] != 0)
        {
            return $pay_ret;
        }

        // 支付结果处理
        return self::NotifyHandle($pay_ret['data'], $payment, $params);
    }

    /**
     * 支付异步处理
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-28
     * @desc    description
     * @param   [array]          $data      [支付数据]
     * @param   [array]          $payment   [支付方式]
     * @param   [array]          $params    [数参数]
     */
    public static function NotifyHandle($data, $payment, $params = [])
    {
        // 获取支付日志订单
        $pay_log_data = Db::name('PayLog')->where([
            'log_no'    => $data['out_trade_no'],
        ])->find();
        if(empty($pay_log_data))
        {
            return DataReturn('日志订单有误', -1);
        }
        if($pay_log_data['status'] == 1)
        {
            return DataReturn('日志订单已支付、无需重复处理', 0);
        }

        // 获取关联信息
        $pay_log_value = Db::name('PayLogValue')->where(['pay_log_id'=>$pay_log_data['id']])->column('business_id');
        if(empty($pay_log_value))
        {
            return DataReturn('日志订单关联信息有误', -1);
        }

        // 获取充值信息
        $recharge = Db::name('PluginsWalletRecharge')->where(['id'=>$pay_log_value])->find();

        // 支付处理
        $pay_params = [
            'params'        => $params,
            'data'          => $recharge,
            'payment'       => $payment,
            'pay_log_data'  => $pay_log_data,
            'pay'       => [
                'trade_no'      => $data['trade_no'],
                'subject'       => $data['subject'],
                'buyer_user'    => $data['buyer_user'],
                'pay_price'     => $data['pay_price'],
            ],
        ];
        return self::RechargePayHandle($pay_params);
    }

    /**
     * 充值支付处理
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2018-10-05T23:02:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function RechargePayHandle($params = [])
    {
        // 订单信息
        if(empty($params['data']))
        {
            return DataReturn('资源不存在或已被删除', -1);
        }
        if($params['data']['status'] > 0)
        {
            $status_name = RechargeService::$recharge_status_list[$params['data']['status']]['name'];
            return DataReturn('状态不可操作['.$status_name.']', 0);
        }

        // 支付方式
        if(empty($params['payment']))
        {
            return DataReturn('支付方式有误', -1);
        }

        // 支付金额
        $pay_price = isset($params['pay']['pay_price']) ? $params['pay']['pay_price'] : 0;

        // 获取用户钱包校验
        $user_wallet = WalletService::UserWallet($params['data']['user_id']);
        if($user_wallet['code'] != 0)
        {
            return $user_wallet;
        } else {
            if($user_wallet['data']['id'] != $params['data']['wallet_id'])
            {
                return DataReturn('用户钱包不匹配', -1);
            }
        }

        // 开启事务
        Db::startTrans();

        // 更新充值状态
        $upd_data = array(
            'status'        => 1,
            'pay_money'     => $pay_price,
            'payment_id'    => $params['payment']['id'],
            'payment'       => $params['payment']['payment'],
            'payment_name'  => $params['payment']['name'],
            'pay_time'      => time(),
        );
        if(!Db::name('PluginsWalletRecharge')->where(['id'=>$params['data']['id']])->update($upd_data))
        {
            Db::rollback();
            return DataReturn('充值状态更新失败', -100);
        }

        // 是否有赠送金额
        $give_money = self::RechargeGiveMoneyHandle($pay_price);

        // 字段名称 金额类型 描述
        if($give_money > 0)
        {
            $money_field = [
                ['field' => 'normal_money', 'money_type' => 0, 'msg' => ' [ '.$pay_price.'π , 赠送'.$give_money.'π ]'],
                ['field' => 'give_money', 'money_type' => 2, 'msg' => ' [ 赠送'.$give_money.'π ]'],
            ];
        } else {
            $money_field = [
                ['field' => 'normal_money', 'money_type' => 0, 'msg' => ' [ '.$pay_price.'π ]'],
            ];
        }

        // 钱包更新数据
        $data = [
            'normal_money'      => PriceNumberFormat($user_wallet['data']['normal_money']+$pay_price+$give_money),
            'give_money'        => PriceNumberFormat($user_wallet['data']['give_money']+$give_money),
            'upd_time'          => time(),
        ];
        if(!Db::name('PluginsWallet')->where(['id'=>$user_wallet['data']['id']])->update($data))
        {
            Db::rollback();
            return DataReturn('钱包更新失败', -10);
        }

        // 输入参数
        $params_old = empty($params['params']) ? [] : $params['params'];

        // 有效金额和赠送金额字段数据处理
        foreach($money_field as $v)
        {
            // 有效金额
            if($user_wallet['data'][$v['field']] != $data[$v['field']])
            {
                $log_data = [
                    'user_id'           => $user_wallet['data']['user_id'],
                    'wallet_id'         => $user_wallet['data']['id'],
                    'business_type'     => 1,
                    'operation_type'    => 1,
                    'money_type'        => $v['money_type'],
                    'operation_money'   => ($user_wallet['data'][$v['field']] < $data[$v['field']]) ? PriceNumberFormat($data[$v['field']]-$user_wallet['data'][$v['field']]) : PriceNumberFormat($user_wallet['data'][$v['field']]-$data[$v['field']]),
                    'original_money'    => $user_wallet['data'][$v['field']],
                    'latest_money'      => $data[$v['field']],
                    'msg'               => '账户充值'.$v['msg'],
                    'operate_id'        => empty($params_old['operate_id']) ? 0 : intval($params_old['operate_id']),
                    'operate_name'      => empty($params_old['operate_name']) ? '' : trim($params_old['operate_name']),
                ];
                if(!WalletService::WalletLogInsert($log_data))
                {
                    Db::rollback();
                    return DataReturn('日志添加失败', -101);
                }

                // 消息通知
                MessageService::MessageAdd($log_data['user_id'], '账户余额变动', $log_data['msg'], BaseService::$business_type_name, $params['data']['id']);

                // 通知
                NoticeService::Send([
                    'user_id'       => $log_data['user_id'],
                    'msg_title'     => '账户充值',
                    'money_type'    => WalletService::$money_type_list[$log_data['money_type']]['name'],
                    'opt_type'      => '增加',
                    'opt_money'     => $log_data['operation_money'],
                    'content'       => $log_data['msg'],
                ]);
            }
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

        // 提交事务
        Db::commit();
        return DataReturn('支付成功', 0);        
    }

    /**
     * 充值赠送金额计算
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-05-08T00:12:48+0800
     * @param    [float]                   $pay_price [支付金额]
     */
    private static function RechargeGiveMoneyHandle($pay_price)
    {
        $give_money = 0.00;
        $status = false;
        $ret = BaseService::BaseConfig();
        if(!empty($ret['data']))
        {
            // 自定义充值赠送
            if(!empty($ret['data']['custom_recharge_give']) && is_array($ret['data']['custom_recharge_give']))
            {
                foreach($ret['data']['custom_recharge_give'] as $v)
                {
                    if(!empty($v))
                    {
                        $temp = explode('+', $v);
                        if(count($temp) == 2)
                        {
                            if($pay_price == $temp[0])
                            {
                                $give_money = floatval($temp[1]);
                                $status = true;
                                break;
                            }
                        }
                    }
                }
            }

            // 固定金额或比例充值赠送
            if($status === false && !empty($ret['data']['recharge_give_value']) && isset($ret['data']['recharge_give_type']))
            {
                switch($ret['data']['recharge_give_type'])
                {
                    // 固定金额
                    case 0 :
                        $give_money = floatval($ret['data']['recharge_give_value']);
                        break;

                    // 比例
                    case 1 :
                        $give_money = ($ret['data']['recharge_give_value']/100)*$pay_price;
                        break;
                }
            }
        }
        return PriceNumberFormat($give_money);
    }
}
?>