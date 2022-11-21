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
use app\service\MessageService;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\WalletService;

/**
 * 提现服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class CashService
{
    // 提现状态
    public static $cash_status_list = [
        0 => ['value' => 0, 'name' => '未打款', 'checked' => true],
        1 => ['value' => 1, 'name' => '已打款'],
        2 => ['value' => 2, 'name' => '打款失败'],
    ];

    // 校验缓存 key
    public static $wallet_cash_check_success_key = 'plugins_wallet_cash_check_success_key_'.APPLICATION_CLIENT_TYPE;

    /**
     * 验证码发送
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-03-05T19:17:10+0800
     * @param    [array]          $params [输入参数]
     */
    public static function VerifySend($params = [])
    {
        // 数据验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'account_type',
                'error_msg'         => '身份认证方式有误',
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

        // 账户
        if(empty($params['user'][$params['account_type']]))
        {
            return DataReturn('当前验证类型账号未绑定', -1);
        }

        // 是否开启图片验证码
        // 仅 web 端校验图片验证码
        if(in_array(APPLICATION_CLIENT_TYPE, ['pc']))
        {
            $verify = self::IsImaVerify($params);
            if($verify['code'] != 0)
            {
                return $verify;
            }
        }

        // 当前验证账户
        $accounts = $params['user'][$params['account_type']];

        // 发送验证码
        $verify_params = array(
                'key_prefix' => md5('wallet_cash_'.$accounts),
                'expire_time' => MyC('common_verify_expire_time'),
                'interval_time' =>  MyC('common_verify_interval_time'),
            );
        $code = GetNumberCode(4);
        if($params['account_type'] == 'mobile')
        {
            $obj = new \base\Sms($verify_params);
            $status = $obj->SendCode($accounts, $code, MyC('common_sms_currency_template'));
        } else {
            $obj = new \base\Email($verify_params);
            $email_params = array(
                    'email'     =>  $accounts,
                    'content'   =>  MyC('common_email_currency_template'),
                    'title'     =>  MyC('home_site_name').' - 账户安全认证',
                    'code'      =>  $code,
                );
            $status = $obj->SendHtml($email_params);
        }
        
        // 状态
        if($status)
        {
            // 清除验证码
            if(isset($verify['data']) && is_object($verify['data']))
            {
                $verify['data']->Remove();
            }

            return DataReturn('发送成功', 0);
        }
        return DataReturn('发送失败'.'['.$obj->error.']', -100);
    }

    /**
     * 是否开启图片验证码校验
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-03-22T15:48:31+0800
     * @param    [array]    $params         [输入参数]
     * @return   [object]                   [图片验证码类对象]
     */
    private static function IsImaVerify($params)
    {
        if(MyC('common_img_verify_state') == 1)
        {
            if(empty($params['verify']))
            {
                return DataReturn('参数错误', -10);
            }

            // 验证码基础参数
            $verify_params = array(
                    'key_prefix' => 'wallet_cash',
                    'expire_time' => MyC('common_verify_expire_time'),
                    'interval_time' =>  MyC('common_verify_interval_time'),
                );
            $verify = new \base\Verify($verify_params);
            if(!$verify->CheckExpire())
            {
                return DataReturn('验证码已过期', -11);
            }
            if(!$verify->CheckCorrect($params['verify']))
            {
                return DataReturn('验证码错误', -12);
            }
            return DataReturn('操作成功', 0, $verify);
        }
        return DataReturn('操作成功', 0);
    }

    /**
     * 验证码校验
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-03-28T15:57:19+0800
     * @param    [array]          $params [输入参数]
     */
    public static function VerifyCheck($params = [])
    {
        // 数据验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'account_type',
                'error_msg'         => '身份认证方式有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'verify',
                'error_msg'         => '验证码不能为空',
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

        // 账户
        if(empty($params['user'][$params['account_type']]))
        {
            return DataReturn('当前验证类型账号未绑定', -1);
        }

        // 当前验证账户
        $accounts = $params['user'][$params['account_type']];

        // 验证码校验
        $verify_params = array(
                'key_prefix' => md5('wallet_cash_'.$accounts),
                'expire_time' => MyC('common_verify_expire_time')
            );
        if($params['account_type'] == 'mobile')
        {
            $obj = new \base\Sms($verify_params);
        } else {
            $obj = new \base\Email($verify_params);
        }
        // 是否已过期
        if(!$obj->CheckExpire())
        {
            return DataReturn('验证码已过期', -10);
        }
        // 是否正确
        if($obj->CheckCorrect($params['verify']))
        {
            // 基础配置信息
            $base = BaseService::BaseConfig();

            // 安全验证后规定时间内时间限制
            $cash_time_limit = (empty($base['data']) || empty($base['data']['cash_time_limit'])) ? 30 : intval($base['data']['cash_time_limit']);

            // 校验成功标记
            MyCache(self::$wallet_cash_check_success_key.$params['user']['id'], time(), $cash_time_limit*60);

            // 清除验证码
            $obj->Remove();

            return DataReturn('验证正确', 0);
        }
        return DataReturn('验证码错误', -11);
    }

    /**
     * 提现创建
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public static function CashCreate($params = [])
    {
        // 参数验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'money',
                'error_msg'         => '提现金额不能为空',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'bank_name',
                'checked_data'      => '1,60',
                'error_msg'         => '收款平台格式 1~60 个字符之间',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'bank_accounts',
                'checked_data'      => '1,60',
                'error_msg'         => '收款账号格式 1~60 个字符之间',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'bank_username',
                'checked_data'      => '1,30',
                'error_msg'         => '开户人姓名格式 1~30 个字符之间',
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

        // 基础配置
        $base = BaseService::BaseConfig();

        // 是否开启提现申请
        if(!empty($base['data']) && isset($base['data']['is_enable_cash']) && $base['data']['is_enable_cash'] == 0)
        {
            return DataReturn('暂时关闭了提现申请', -1);
        }

        // 用户钱包
        $user_wallet = WalletService::UserWallet($params['user']['id']);
        if($user_wallet['code'] != 0)
        {
            return $user_wallet;
        }

        // 提现金额
        $money = PriceNumberFormat($params['money']);
        if($money > $user_wallet['data']['normal_money'])
        {
            return DataReturn('提现金额不能大于有效金额', -1);
        }

        // 赠送金额是否可以提现、默认赠送金额不可提现
        $can_cach_max_money = self::CanCashMaxMoney($user_wallet['data']);
        if($money > $can_cach_max_money)
        {
            return DataReturn('赠送金额不可提现', -1);
        }

        // 是否开启最低金额限制
        if(!empty($base['data']) && isset($base['data']['cash_minimum_amount']) && $base['data']['cash_minimum_amount'] > 0 && $money < $base['data']['cash_minimum_amount'])
        {
            return DataReturn('提现最低金额'.$base['data']['cash_minimum_amount'].'π起', -1);
        }

        // 开始处理
        Db::startTrans();

        // 添加提现数据
        $data = [
            'cash_no'           => date('YmdHis').GetNumberCode(6),
            'user_id'           => $user_wallet['data']['user_id'],
            'wallet_id'         => $user_wallet['data']['id'],
            'status'            => 0,
            'money'             => $money,
            'bank_name'         => $params['bank_name'],
            'bank_accounts'     => $params['bank_accounts'],
            'bank_username'     => $params['bank_username'],
            'add_time'          => time(),
        ];
        $cash_id = Db::name('PluginsWalletCash')->insertGetId($data);
        if($cash_id <= 0)
        {
            Db::rollback();
            return DataReturn('提现操作失败', -100);
        }

        // 钱包更新
        $wallet_data = [
            'normal_money'  => PriceNumberFormat($user_wallet['data']['normal_money']-$money),
            'frozen_money'  => PriceNumberFormat($user_wallet['data']['frozen_money']+$money),
            'upd_time'      => time(),
        ];
        if(!Db::name('PluginsWallet')->where(['id'=>$user_wallet['data']['id']])->update($wallet_data))
        {
            Db::rollback();
            return DataReturn('钱包操作失败', -100);
        }

        // 日志
        $money_field = [
            ['field' => 'normal_money', 'money_type' => 0, 'msg' => ' [ 有效金额减少'.$money.'π ]'],
            ['field' => 'frozen_money', 'money_type' => 1, 'msg' => ' [ 冻结金额增加'.$money.'π ]'],
        ];
        foreach($money_field as $v)
        {
            // 有效金额
            if($user_wallet['data'][$v['field']] != $wallet_data[$v['field']])
            {
                $log_data = [
                    'user_id'           => $user_wallet['data']['user_id'],
                    'wallet_id'         => $user_wallet['data']['id'],
                    'business_type'     => 2,
                    'operation_type'    => ($user_wallet['data'][$v['field']] < $wallet_data[$v['field']]) ? 1 : 0,
                    'money_type'        => $v['money_type'],
                    'operation_money'   => ($user_wallet['data'][$v['field']] < $wallet_data[$v['field']]) ? PriceNumberFormat($wallet_data[$v['field']]-$user_wallet['data'][$v['field']]) : PriceNumberFormat($user_wallet['data'][$v['field']]-$wallet_data[$v['field']]),
                    'original_money'    => $user_wallet['data'][$v['field']],
                    'latest_money'      => $wallet_data[$v['field']],
                    'msg'               => '用户提现申请 '.$v['msg'],
                ];
                if(!WalletService::WalletLogInsert($log_data))
                {
                    Db::rollback();
                    return DataReturn('日志添加失败', -101);
                }

                // 消息通知
                MessageService::MessageAdd($log_data['user_id'], '账户余额变动', $log_data['msg'], BaseService::$business_type_name, $cash_id);

                // 通知
                NoticeService::Send([
                    'user_id'       => $log_data['user_id'],
                    'msg_title'     => '用户提现申请',
                    'money_type'    => WalletService::$money_type_list[$log_data['money_type']]['name'],
                    'opt_type'      => ($log_data['operation_type'] == 1) ? '增加' : '减少',
                    'opt_money'     => $log_data['operation_money'],
                    'content'       => $log_data['msg'],
                ]);
            }
        }

        // 提交事务
        Db::commit();
        return DataReturn('操作成功', 0);   
    }

    /**
     * 可提现最大金额
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-20
     * @desc    description
     * @param   [array]          $wallet [用户钱包数据]
     */
    public static function CanCashMaxMoney($wallet)
    {
        // 基础配置
        $base = BaseService::BaseConfig();

        // 赠送金额是否可以提现、默认赠送金额不可提现
        if(empty($base['data']) || !isset($base['data']['is_cash_retain_give']) || $base['data']['is_cash_retain_give'] == 1)
        {
            $money = PriceNumberFormat($wallet['normal_money']-$wallet['give_money']);
        } else {
            $money = $wallet['normal_money'];
        }
        return $money;
    }

    /**
     * 提现申请审核
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-10
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public static function CashAudit($params = [])
    {
        // 参数验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '提现id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'pay_money',
                'error_msg'         => '打款金额有误',
            ],
            [
                'checked_type'      => 'fun',
                'key_name'          => 'pay_money',
                'checked_data'      => 'CheckPrice',
                'error_msg'         => '请输入有效的打款金额有误',
            ],
            [
                'checked_type'      => 'min',
                'key_name'          => 'pay_money',
                'checked_data'      => 0.01,
                'error_msg'         => '打款金额有误，最低0.01π',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'msg',
                'checked_data'      => '180',
                'error_msg'         => '备注最多 180 个字符',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'type',
                'checked_data'      => ['agree', 'refuse'],
                'error_msg'         => '操作类型有误，同意或拒绝操作出错',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取提现数据
        $cash = Db::name('PluginsWalletCash')->find(intval($params['id']));
        if(empty($cash))
        {
            return DataReturn('提现数据不存在或已删除', -10);
        }

        // 状态
        if($cash['status'] != 0)
        {
            return DataReturn('状态不可操作['.self::$cash_status_list[$cash['status']]['name'].']', -11);
        }

        // 金额处理
        $pay_money = PriceNumberFormat($params['pay_money']);
        if($pay_money <= 0.00 || $pay_money > $cash['money'])
        {
            return DataReturn('打款金额有误，最低0.01π，最高'.$cash['money'].'π', -12);
        }

        // 获取用户钱包
        $wallet = Db::name('PluginsWallet')->find(intval($cash['wallet_id']));
        if(empty($wallet))
        {
            return DataReturn('用户钱包不存在或已删除', -20);
        }

        // 是否发送消息
        $is_send_message = (isset($params['is_send_message']) && $params['is_send_message'] == 1) ? 1 : 0;

        // 开始处理
        Db::startTrans();

        // 数据处理
        if($params['type'] == 'agree')
        {
            // 钱包更新数据
            $wallet_upd_data = [
                'frozen_money'  => PriceNumberFormat($wallet['frozen_money']-$cash['money']),
            ];

            // 提现更新数据
            $cash_upd_data = [
                'status'        => 1,
                'pay_money'     => $pay_money,
                'pay_time'      => time(),
            ];

            $money_field = [
                ['field' => 'frozen_money', 'money_type' => 1, 'msg' => ' [ 提现申请成功 , 冻结金额减少'.$cash['money'].'π ]'],
            ];

            // 打款金额是否小于提现金额
            if($pay_money < $cash['money'])
            {
                $surplus_money = PriceNumberFormat($cash['money']-$pay_money);
                $wallet_upd_data['normal_money'] = PriceNumberFormat($wallet['normal_money']+$surplus_money);

                $money_field[] = ['field' => 'normal_money', 'money_type' => 0, 'msg' => ' [ 提现申请成功 , 部分金额未打款 , 冻结金额退回至有效金额'.$surplus_money.'π ]'];
            }
        } else {
            // 钱包更新数据
            $wallet_upd_data = [
                'frozen_money'  => PriceNumberFormat($wallet['frozen_money']-$cash['money']),
                'normal_money'  => PriceNumberFormat($wallet['normal_money']+$cash['money']),
            ];

            // 提现更新数据
            $cash_upd_data = [
                'status'        => 2,
            ];

            $money_field = [
                ['field' => 'frozen_money', 'money_type' => 1, 'msg' => ' [ 提现申请失败 , 冻结金额释放 '.$cash['money'].'π ]'],
                ['field' => 'normal_money', 'money_type' => 0, 'msg' => ' [ 提现申请失败 , 冻结金额退回至有效金额'.$cash['money'].'π ]'],
            ];
        }

        // 提现更新
        $cash_upd_data['msg'] = empty($params['msg']) ? '' : $params['msg'];
        $cash_upd_data['upd_time'] = time();
        if(!Db::name('PluginsWalletCash')->where(['id'=>$cash['id']])->update($cash_upd_data))
        {
            Db::rollback();
            return DataReturn('提现申请操作失败', -100);
        }

        // 钱包更新
        if(!Db::name('PluginsWallet')->where(['id'=>$wallet['id']])->update($wallet_upd_data))
        {
            Db::rollback();
            return DataReturn('钱包操作失败', -101);
        }

        foreach($money_field as $v)
        {
            // 有效金额
            if($wallet[$v['field']] != $wallet_upd_data[$v['field']])
            {
                $log_data = [
                    'user_id'           => $wallet['user_id'],
                    'wallet_id'         => $wallet['id'],
                    'business_type'     => 2,
                    'operation_type'    => ($wallet[$v['field']] < $wallet_upd_data[$v['field']]) ? 1 : 0,
                    'money_type'        => $v['money_type'],
                    'operation_money'   => ($wallet[$v['field']] < $wallet_upd_data[$v['field']]) ? PriceNumberFormat($wallet_upd_data[$v['field']]-$wallet[$v['field']]) : PriceNumberFormat($wallet[$v['field']]-$wallet_upd_data[$v['field']]),
                    'original_money'    => $wallet[$v['field']],
                    'latest_money'      => $wallet_upd_data[$v['field']],
                    'msg'               => '管理员审核'.$v['msg'],
                ];
                if(!WalletService::WalletLogInsert($log_data))
                {
                    Db::rollback();
                    return DataReturn('日志添加失败', -101);
                }

                // 消息通知
                if($is_send_message == 1)
                {
                    // 消息通知
                    MessageService::MessageAdd($wallet['user_id'], '账户余额变动', $log_data['msg'], BaseService::$business_type_name, $cash['id']);

                    // 通知
                    NoticeService::Send([
                        'user_id'       => $log_data['user_id'],
                        'msg_title'     => '提现审核',
                        'money_type'    => WalletService::$money_type_list[$log_data['money_type']]['name'],
                        'opt_type'      => ($log_data['operation_type'] == 1) ? '增加' : '减少',
                        'opt_money'     => $log_data['operation_money'],
                        'content'       => $log_data['msg'],
                    ]);
                }
            }
        }

        // 处理成功
        Db::commit();
        return DataReturn('操作成功', 0);
    }

    /**
     * 用户钱包安全认证方式
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-20
     * @desc    description
     * @param   [array]          $user [用户数据]
     */
    public static function UserCheckAccountList($user)
    {
        $check_account_list = [];
        if(!empty($user['mobile_security']))
        {
            $check_account_list[] = [
                'field' => 'mobile',
                'value' => $user['mobile_security'],
                'name'  => '手机',
                'msg'   => '手机['.$user['mobile_security'].']',
            ];
        }
        if(!empty($user['email_security']))
        {
            $check_account_list[] = [
                'field' => 'email',
                'value' => $user['email_security'],
                'name'  => '邮箱',
                'msg'   => '邮箱['.$user['email_security'].']',
            ];
        }
        return $check_account_list;
    }

    /**
     * 用户提现安全验证状态
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-20
     * @desc    description
     * @param   [array]          $user [用户数据]
     */
    public static function CashAuthCheck($params = [])
    {
        // 数据验证
        $p = [
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

        // 基础配置信息
        $base = BaseService::BaseConfig();

        // 安全验证后规定时间内时间限制
        $cash_time_limit = (empty($base['data']) || empty($base['data']['cash_time_limit'])) ? 30 : intval($base['data']['cash_time_limit']);

        // 是否验证成功
        $check_time = MyCache(self::$wallet_cash_check_success_key.$params['user']['id']);
        $status = (!empty($check_time) && $check_time+($cash_time_limit*60) >= time()) ? 1 : 0;

        // 返回数据
        return DataReturn('验证成功', 0, $status);
    }

    /**
     * 获取默认提现信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-02-23
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function CachDefaultData($user_id)
    {
        $data = Db::name('PluginsWalletCash')->where(['user_id'=>$user_id])->order('id desc')->field('id,bank_name,bank_accounts,bank_username')->find();
        return empty($data) ? null : $data;
    }
}
?>