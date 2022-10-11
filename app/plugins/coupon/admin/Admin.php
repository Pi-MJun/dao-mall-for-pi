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

use app\plugins\coupon\admin\Common;
use app\plugins\coupon\service\BaseService;
use app\plugins\coupon\service\StatisticalService;

/**
 * 优惠券 - 基础配置
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2019-08-05T21:51:08+0800
 */
class Admin extends Common
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
        // 统计数据
        MyViewAssign('statistical', StatisticalService::StatisticalData());

        MyViewAssign('data', $this->plugins_config);
        return MyView('../../../plugins/view/coupon/admin/admin/index');
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-05T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function saveinfo($params = [])
    {
        MyViewAssign('data', $this->plugins_config);
        return MyView('../../../plugins/view/coupon/admin/admin/saveinfo');
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-05T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function save($params = [])
    {
        return BaseService::BaseConfigSave($params);
    }
}
?>