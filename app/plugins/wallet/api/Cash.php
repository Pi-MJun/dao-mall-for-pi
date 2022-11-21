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
namespace app\plugins\wallet\api;

use app\plugins\wallet\api\Common;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\CashService;
use app\plugins\wallet\service\WalletService;

/**
 * 钱包 - 提现记录
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Cash extends Common
{
    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     */
    public function __construct()
    {
        parent::__construct();

        // 是否登录
        $this->IsLogin();
    }

    /**
     * 列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-19
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 参数
        $params = $this->data_post;
        $params['user'] = $this->user;
        $params['user_type'] = 'user';

        // 分页
        $number = 10;
        $page = max(1, isset($this->data_post['page']) ? intval($this->data_post['page']) : 1);

        // 条件
        $where = BaseService::CashWhere($params);

        // 获取总数
        $total = BaseService::CashTotal($where);
        $page_total = ceil($total/$number);
        $start = intval(($page-1)*$number);

        // 获取列表
        $data_params = array(
            'm'             => $start,
            'n'             => $number,
            'where'         => $where,
        );
        $data = BaseService::CashList($data_params);

        // 返回数据
        $result = [
            'total'             => $total,
            'page_total'        => $page_total,
            'data'              => $data['data'],
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 获取详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-07
     * @param   [array]          $params [输入参数]
     */
    public function Detail($params = [])
    {
        // 参数
        $params = $this->data_post;
        $params['user'] = $this->user;
        $params['user_type'] = 'user';
        if(empty($params['id']))
        {
            return DataReturn('参数有误', -1);
        }

        // 条件
        $where = BaseService::CashWhere($params);

        // 获取列表
        $data_params = array(
            'm'         => 0,
            'n'         => 1,
            'where'     => $where,
        );
        $ret = BaseService::CashList($data_params);
        if(!empty($ret['data'][0]))
        {
            // 返回信息
            $result = [
                'data'      => $ret['data'][0],
            ];

            return DataReturn('success', 0, $result);
        }
        return DataReturn('数据不存在或已删除', -100);
    }

    /**
     * 提现安全校验
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-23
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Auth($params = [])
    {
        // 获取基础配置信息
        $base = BaseService::BaseConfig();

        // 用户钱包
        $user_wallet = WalletService::UserWallet($this->user['id']);

        // 认证方式
        $check_account_list = CashService::UserCheckAccountList($this->user);

        // 返回数据
        $result = [
            'base'                  => empty($base['data']) ? null : $base['data'],
            'user_wallet'           => empty($user_wallet['data']) ? null : $user_wallet['data'],
            'check_account_list'    => $check_account_list,
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 验证码发送
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Verifysend($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        return CashService::VerifySend($params);
    }

    /**
     * 验证码校验
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function VerifyCheck($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        return CashService::VerifyCheck($params);
    }

    /**
     * 提现创建初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-07
     * @param   [array]          $params [输入参数]
     */
    public function CreateInit($params = [])
    {
        // 安全校验
        $ret = CashService::CashAuthCheck(['user'=>$this->user]);
        $check_status = ($ret['code'] == 0) ? $ret['data'] : 0;

        // 验证通过则读取相关数据
        if($check_status == 1)
        {
            // 获取基础配置信息
            $base = BaseService::BaseConfig();

            // 用户钱包
            $user_wallet = WalletService::UserWallet($this->user['id']);

            // 可提现最大金额
            $can_cash_max_money = CashService::CanCashMaxMoney($user_wallet['data']);

            // 默认提现信息
            $default_data = CashService::CachDefaultData($this->user['id']);
        }
        
        // 返回信息
        $result = [
            'check_status'          => $check_status,
            'base'                  => empty($base['data']) ? null : $base['data'],
            'user_wallet'           => empty($user_wallet['data']) ? null : $user_wallet['data'],
            'can_cash_max_money'    => isset($can_cash_max_money) ? $can_cash_max_money : 0,
            'default_data'          => empty($default_data) ? null : $default_data,
        ];

        return DataReturn('success', 0, $result);
    }

    /**
     * 提现创建
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-08
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Create($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        return CashService::CashCreate($params);
    }
}
?>