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
namespace app\plugins\intellectstools\admin;

use app\plugins\intellectstools\admin\Common;
use app\plugins\intellectstools\service\BaseService;

/**
 * 智能工具箱 - 管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class Admin extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2021-05-07
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        MyViewAssign('data', $this->plugins_config);
        return MyView('../../../plugins/view/intellectstools/admin/admin/index');
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2021-05-07
     * @param    [array]          $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        MyViewAssign('data', $this->plugins_config);
        return MyView('../../../plugins/view/intellectstools/admin/admin/saveinfo');
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @date     2021-05-07
     * @param    [array]          $params [输入参数]
     */
    public function Save($params = [])
    {
        return BaseService::BaseConfigSave($params);
    }
}
?>