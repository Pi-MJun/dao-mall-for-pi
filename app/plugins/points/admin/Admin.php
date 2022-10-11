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
namespace app\plugins\points\admin;

use app\service\GoodsService;
use app\plugins\points\admin\Common;
use app\plugins\points\service\BaseService;

/**
 * 积分商城 - 管理
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
        $ret = BaseService::BaseConfig(true, true);
        if($ret['code'] == 0)
        {
            MyViewAssign('data', $ret['data']);

            // 静态数据
            MyViewAssign('common_platform_type', MyConst('common_platform_type'));
            return MyView('../../../plugins/view/points/admin/admin/index');
        } else {
            return $ret['msg'];
        }
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
        $ret = BaseService::BaseConfig(true, true);
        if($ret['code'] == 0)
        {
            // 商品分类
            MyViewAssign('goods_category_list', GoodsService::GoodsCategoryAll());

            // 静态数据
            MyViewAssign('common_platform_type', MyConst('common_platform_type'));

            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/points/admin/admin/saveinfo');
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
    public function Save($params = [])
    {
        return BaseService::BaseConfigSave($params);
    }

    /**
     * 商品搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-16
     * @desc    description
     * @param   [array]           $params [商品搜索]
     */
    public function GoodsSearch($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 搜索数据
        $ret = BaseService::GoodsSearchList($params);
        if($ret['code'] == 0)
        {
            MyViewAssign('data', $ret['data']['data']);
            $ret['data']['data'] = MyView('../../../plugins/view/points/admin/public/goodssearch');
        }
        return $ret;
    }
}
?>