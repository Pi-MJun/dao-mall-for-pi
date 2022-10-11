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
namespace app\plugins\membershiplevelvip\api;

use app\plugins\membershiplevelvip\api\Common;
use app\plugins\membershiplevelvip\service\BaseService;
use app\plugins\membershiplevelvip\service\LevelBuyService;

/**
 * 会员等级增强版插件 - 开通订单
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Order extends Common
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
        $where = BaseService::UserPayOrderWhere($params);

        // 获取总数
        $total = BaseService::UserPayOrderTotal($where);
        $page_total = ceil($total/$number);
        $start = intval(($page-1)*$number);

        // 获取列表
        $data_params = array(
            'm'             => $start,
            'n'             => $number,
            'where'         => $where,
        );
        $data = BaseService::UserPayOrderList($data_params);

        // 返回数据
        $result = [
            'total'             => $total,
            'page_total'        => $page_total,
            'data'              => $data['data'],
            'payment_list'      => BaseService::HomeBuyPaymentList(),
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
    public function Detail()
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
        $where = BaseService::UserPayOrderWhere($params);

        // 获取列表
        $data_params = array(
            'm'         => 0,
            'n'         => 1,
            'where'     => $where,
        );
        $ret = BaseService::UserPayOrderList($data_params);
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
     * 订单纪录取消
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Cancel($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error('非法访问');
        }

        // 开始处理
        $params['user_type'] = 'user';
        $params['user'] = $this->user;
        $params['value'] = 2;
        return LevelBuyService::BuyOrderInvalid($params);
    }

    /**
     * 订单纪录删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Delete($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error('非法访问');
        }

        // 开始处理
        $params['user_type'] = 'user';
        $params['user'] = $this->user;
        return LevelBuyService::BuyOrderDelete($params);
    }
}
?>