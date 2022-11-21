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
namespace app\plugins\popupscreen\admin;

use app\plugins\popupscreen\admin\Common;
use app\plugins\popupscreen\service\BaseService;

/**
 * 弹屏广告 - 管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @date     2019-06-23
 */
class Admin extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2019-06-23
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        MyViewAssign('data', $this->base_data);
        MyViewAssign('common_platform_type', MyConst('common_platform_type'));
        return MyView('../../../plugins/view/popupscreen/admin/admin/index');
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2019-06-23
     * @param    [array]          $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        MyViewAssign('data', $this->base_data);
        MyViewAssign('common_platform_type', MyConst('common_platform_type'));
        return MyView('../../../plugins/view/popupscreen/admin/admin/saveinfo');
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2019-06-23
     * @param    [array]          $params [输入参数]
     */
    public function Save($params = [])
    {
        return BaseService::BaseConfigSave($params);
    }
}
?>