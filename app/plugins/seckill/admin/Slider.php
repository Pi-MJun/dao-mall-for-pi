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
 * 限时秒杀 - 轮播图片
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Slider extends Common
{
    /**
     * 幻灯片页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        $ret = BaseService::SliderList(['where'=>$this->form_where]);
        MyViewAssign('data_list', $ret['data']);
        MyViewAssign('common_platform_type', MyConst('common_platform_type'));
        return MyView('../../../plugins/view/seckill/admin/slider/index');
    }

    /**
     * 详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        if(!empty($this->data_request['id']))
        {
            // 条件
            $where = [
                ['id', '=', intval($this->data_request['id'])],
            ];
            $ret = BaseService::SliderList(['where'=>$where]);
            $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
            MyViewAssign('data', $data);
        }
        MyViewAssign('common_platform_type', MyConst('common_platform_type'));
        return MyView('../../../plugins/view/seckill/admin/slider/detail');
    }

    /**
     * 幻灯片编辑
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        // 数据
        $data = [];
        if(!empty($params['id']))
        {
            $data_params = array(
                'where'     => ['id'=>intval($params['id'])],
            );
            $ret = BaseService::SliderList($data_params);
            $data = empty($ret['data'][0]) ? [] : $ret['data'][0];
        }
        MyViewAssign('data', $data);
        
        // 静态数据
        MyViewAssign('common_platform_type', MyConst('common_platform_type'));
        return MyView('../../../plugins/view/seckill/admin/slider/saveinfo');
    }

    /**
     * 幻灯片保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Save($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return BaseService::SliderSave($params);
    }

    /**
     * 幻灯片删除
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-15T11:03:30+0800
     * @param    [array]          $params [输入参数]
     */
    public function Delete($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return BaseService::SliderDelete($params);
    }

    /**
     * 幻灯片状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-12T22:23:06+0800
     * @param    [array]          $params [输入参数]
     */
    public function StatusUpdate($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return BaseService::SliderStatusUpdate($params);
    }
}
?>