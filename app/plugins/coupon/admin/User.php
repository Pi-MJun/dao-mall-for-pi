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
namespace app\plugins\coupon\admin;

use app\service\GoodsService;
use app\plugins\coupon\admin\Common;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\CouponService;
use app\plugins\coupon\service\UserCouponAdminService;

/**
 * 优惠券 - 用户优惠券
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-05T21:51:08+0800
 */
class User extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-05T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 分页
        $number = MyC('admin_page_number', 10, true);

        // 条件
        $where = UserCouponAdminService::CouponUserWhere($params);

        // 获取总数
        $total = UserCouponAdminService::CouponUserTotal($where);

        // 分页
        $page_params = array(
                'number'    =>  $number,
                'total'     =>  $total,
                'where'     =>  $params,
                'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                'url'       =>  PluginsAdminUrl('coupon', 'user', 'index'),
            );
        $page = new \base\Page($page_params);
        MyViewAssign('page_html', $page->GetPageHtml());

        // 获取列表
        $data_list = [];
        if($total > 0)
        {
            $data_params = array(
                'm'         => $page->GetPageStarNumber(),
                'n'         => $number,
                'where'     => $where,
            );
            $data = UserCouponAdminService::CouponUserList($data_params);
            $data_list = $data['data'];
        }

        // 静态数据
        MyViewAssign('common_is_whether_list', BaseService::$common_is_whether_list);

        // 优惠券列表
        $coupon_params = [
            'field'     => 'id,name',
            'where'     => ['is_enable' => 1],
            'is_handle' => 0,
        ];
        $ret = CouponService::CouponList($coupon_params);
        MyViewAssign('coupon_list', $ret['data']);

        // 数据
        MyViewAssign('data_list', $data_list);
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/coupon/admin/user/index');
    }

    /**
     * 状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-12T22:23:06+0800
     * @param    [array]          $params [输入参数]
     */
    public function statusupdate($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return UserCouponAdminService::StatusUpdate($params);
    }

    /**
     * 删除
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-12T22:23:06+0800
     * @param    [array]          $params [输入参数]
     */
    public function delete($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return UserCouponAdminService::Delete($params);
    }
}
?>