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
namespace app\plugins\ordersubmitlimit\admin;

use app\plugins\ordersubmitlimit\admin\Common;
use app\plugins\ordersubmitlimit\service\BaseService;

/**
 * 订单提交限制 - 管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        MyViewAssign('price_limit_type_list', BaseService::$price_limit_type_list);
        MyViewAssign('number_limit_type_list', BaseService::$number_limit_type_list);
        MyViewAssign('data', $this->base_config);
        return MyView('../../../plugins/view/ordersubmitlimit/admin/admin/index');
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        MyViewAssign('price_limit_type_list', BaseService::$price_limit_type_list);
        MyViewAssign('number_limit_type_list', BaseService::$number_limit_type_list);
        MyViewAssign('data', $this->base_config);
        return MyView('../../../plugins/view/ordersubmitlimit/admin/admin/saveinfo');
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Save($params = [])
    {
        return BaseService::BaseConfigSave($params);
    }
}
?>