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
namespace app\plugins\distribution\admin;

use app\plugins\distribution\admin\Common;
use app\plugins\distribution\service\BaseService;

/**
 * 海报 - 管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Poster extends Common
{
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
        $ret = BaseService::PosterData(['is_handle_data'=>1]);
        if($ret['code'] == 0)
        {
            // 静态数据
            MyViewAssign('distribution_border_style_list', BaseService::$distribution_border_style_list);
            MyViewAssign('distribution_is_enable_list', BaseService::$distribution_is_enable_list);

            MyViewAssign('data', $ret['data']);
            MyViewAssign('params', $params);
            return MyView('../../../plugins/view/distribution/admin/poster/index');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function save($params = [])
    {
        return BaseService::PosterDataSave($params);
    }

    /**
     * 海报清空
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-12T20:34:52+0800
     * @param    [array]          $params [输入参数]
     */
    public function delete($params = [])
    {
        return BaseService::PosterDelete($params);
    }
}
?>