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
namespace app\plugins\coupon\api;

use app\service\SeoService;
use app\plugins\coupon\api\Common;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\CouponService;
use app\plugins\coupon\service\UserCouponService;

/**
 * 用户优惠券
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
     * 列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2019-08-12
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        $coupon_params = [
            'user'  => $this->user,
            'where' => [
                'user_id'   => $this->user['id'],
                'is_valid'  => 1,
            ],
        ];
        return UserCouponService::CouponUserList($coupon_params);
    }

    /**
     * 领取优惠券
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-10-15
     * @desc    description
     */
    public function Receive()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 领取优惠券
        return CouponService::UserReceiveCoupon($this->data_post);
    }
}
?>