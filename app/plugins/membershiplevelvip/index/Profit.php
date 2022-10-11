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
namespace app\plugins\membershiplevelvip\index;

use app\service\SeoService;
use app\plugins\membershiplevelvip\index\Common;
use app\plugins\membershiplevelvip\service\BaseService;

/**
 * 会员等级增强版插件 - 收益明细
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Profit extends Common
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
        $this->IsLogin();

        // 公共用户数据
        $this->CommonUserView();
    }

    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 是否开启返佣
        if(!isset($this->plugins_base['is_commission']) || $this->plugins_base['is_commission'] != 1)
        {
            return MyRedirect(PluginsHomeUrl('membershiplevelvip', 'vip', 'index'));
        }

        // 参数
        $params['user'] = $this->user;
        $params['user_type'] = 'user';

        // 分页
        $number = 10;

        // 条件
        $where = BaseService::UserProfitWhere($params);

        // 获取总数
        $total = BaseService::UserProfitTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsHomeUrl('membershiplevelvip', 'profit', 'index'),
            );
        $page = new \base\Page($page_params);
        MyViewAssign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_params = array(
            'm'         => $page->GetPageStarNumber(),
            'n'         => $number,
            'where'     => $where,
            'user_type' => 'user',
        );
        $data = BaseService::UserProfitList($data_params);
        MyViewAssign('data_list', $data['data']);

        // 静态数据
        MyViewAssign('payment_user_profit_status_list', BaseService::$payment_user_profit_status_list);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('收益明细 - 我的会员', 1));

        // 参数
        MyViewAssign('form_params', empty($params['uid']) ? [] : ['uid'=>$params['uid']]);
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/membershiplevelvip/index/profit/index');
    }
}
?>