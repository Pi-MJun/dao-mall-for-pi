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

use app\service\UserService;
use app\service\SeoService;
use app\plugins\coupon\index\Common;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\CouponService;
use app\plugins\coupon\service\UserCouponService;

/**
 * 优惠券 - 优惠券首页
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-11T15:36:19+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 基础配置
        $base = BaseService::BaseConfig();
        MyViewAssign('plugins_base', $base['data']);

        // 优惠券列表
        $coupon_params = [
            'where'             => [
                'is_enable'         => 1,
                'is_user_receive'   => 1,
            ],
            'm'                 => 0,
            'n'                 => 0,
            'is_sure_receive'   => 1,
            'user'              => $this->user,
        ];
        $ret = CouponService::CouponList($coupon_params);
        MyViewAssign('coupon_list', $ret['data']);

        // 浏览器名称
        if(!empty($base['data']['application_name']))
        {
            MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($base['data']['application_name'], 1));
        }

        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/coupon/index/index/index');
    }

    /**
     * 领取优惠券
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-11T15:36:19+0800
     * @param    [array]          $params [输入参数]
     */
    public function receive($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 领取优惠券
        return CouponService::UserReceiveCoupon($params);
    }

    /**
     * 优惠券过期处理
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-18T16:57:38+0800
     * @param    [array]          $params [输入参数]
     */
    public function expire($params = [])
    {
        $ret = UserCouponService::CouponUserExpireHandle();
        return 'success:'.$ret['data'];
    }
}
?>