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
namespace app\plugins\wallet\index;

use app\service\SeoService;
use app\plugins\wallet\index\Common;
use app\plugins\wallet\service\BaseService;
use app\plugins\wallet\service\PayService;
use app\plugins\wallet\service\RechargeService;

/**
 * 钱包 - 充值
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Recharge extends Common
{
    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-07-17
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        if(!isset($this->plugins_action_name) || $this->plugins_action_name != 'respond')
        {
            $this->IsLogin();
        }
    }

    /**
     * 充值明细
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 参数
        $params['user'] = $this->user;
        $params['user_type'] = 'user';

        // 分页
        $number = empty($params['page_size']) ? 10 : intval($params['page_size']);

        // 条件
        $where = BaseService::RechargeWhere($params);

        // 获取总数
        $total = BaseService::RechargeTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsHomeUrl('wallet', 'recharge', 'index'),
            );
        $page = new \base\Page($page_params);
        MyViewAssign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = array(
            'm'         => $page->GetPageStarNumber(),
            'n'         => $number,
            'where'     => $where,
        );
        $data = BaseService::RechargeList($data_params);
        MyViewAssign('data_list', $data['data']);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('充值明细 - 我的钱包', 1));

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/wallet/index/recharge/index');
    }

    /**
     * 充值订单创建
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function create($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 是否开启充值
        if(isset($this->plugins_base['is_enable_recharge']) && $this->plugins_base['is_enable_recharge'] == 0)
        {
            return DataReturn('暂时关闭了在线充值', -1);
        }

        // 用户
        $params['user'] = $this->user;
        $params['user_wallet'] = $this->user_wallet;
        $params['operate_id'] = $this->user['id'];
        $params['operate_name'] = $this->user['user_name_view'];
        return RechargeService::RechargeCreate($params);
    }

    /**
     * 支付
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function pay($params = [])
    {
        // 用户
        $params['user'] = $this->user;
        $params['operate_id'] = $this->user['id'];
        $params['operate_name'] = $this->user['user_name_view'];
        $ret = PayService::Pay($params);
        if($ret['code'] == 0)
        {
            return MyRedirect($ret['data']['data']);
        } else {
            MyViewAssign('msg', $ret['msg']);
            return MyView('public/tips_error');
        }
    }

    /**
     * 支付状态校验
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-03-15T23:51:50+0800
     * @param   [array]          $params [输入参数]
     */
    public function paycheck($params = [])
    {
        if(input('post.'))
        {
            $params['user'] = $this->user;
            return PayService::RechargePayCheck($params);
        } else {
            MyViewAssign('msg', '非法访问');
            return MyView('public/tips_error');
        }
    }

    /**
     * 支付同步页面
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function respond($params = [])
    {
        // 获取支付回调数据
        $params['user'] = $this->user;
        $ret = PayService::Respond($params);

        // 自定义链接
        MyViewAssign('to_url', PluginsHomeUrl('wallet', 'recharge', 'index'));
        MyViewAssign('to_title', '充值明细');

        // 状态
        if($ret['code'] == 0)
        {
            MyViewAssign('msg', '支付成功');
            return MyView('public/tips_success');
        } else {
            MyViewAssign('msg', $ret['msg']);
            return MyView('public/tips_error');
        }
    }

    /**
     * 充值纪录删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-14
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function delete($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error('非法访问');
        }

        // 开始处理
        $params['user'] = $this->user;
        return RechargeService::RechargeDelete($params);
    }
}
?>