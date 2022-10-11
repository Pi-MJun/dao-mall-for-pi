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
namespace app\plugins\coupon\index;

use app\service\SeoService;
use app\plugins\coupon\index\Common;
use app\plugins\coupon\service\UserCouponService;

/**
 * 优惠券 - 用户优惠券
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Coupon extends Common
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
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * 2019-08-12
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 优惠券列表
        $coupon_params = [
            'user'  => $this->user,
            'where' => [
                'user_id'   => $this->user['id'],
                'is_valid'  => 1,
            ],
        ];
        $ret = UserCouponService::CouponUserList($coupon_params);
        MyViewAssign('coupon_list', $ret['data']);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('我的卡劵', 1));

        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/coupon/index/coupon/index');
    }
}
?>