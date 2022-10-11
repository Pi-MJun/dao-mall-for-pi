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
namespace app\plugins\seckill\admin;

use app\plugins\seckill\admin\Common;
use app\plugins\seckill\service\BaseService;

/**
 * 限时秒杀 - 后台管理
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
    public function index($params = [])
    {
        // 基础数据
        MyViewAssign('data', $this->plugins_config);

        // 幻灯片
        $data_params = [
            'where'     => ['is_enable'=>1],
        ];
        $slider = BaseService::SliderList($data_params);
        MyViewAssign('slider', isset($slider['data']) ? $slider['data'] : []);

        // 商品数据
        $goods = BaseService::GoodsList();
        MyViewAssign('goods_list', $goods['data']);
        
        MyViewAssign('common_platform_type', MyConst('common_platform_type'));
        return MyView('../../../plugins/view/seckill/admin/admin/index');
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function saveinfo($params = [])
    {
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {
            // 是否
            $is_whether_list =  [
                0 => array('id' => 0, 'name' => '否', 'checked' => true),
                1 => array('id' => 1, 'name' => '是'),
            ];

            MyViewAssign('is_whether_list', $is_whether_list);
            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/seckill/admin/admin/saveinfo');
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
        return BaseService::BaseConfigSave($params);
    }
}
?>