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
use app\plugins\distribution\service\LevelService;

/**
 * 分销等级 - 管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Level extends Common
{

    /**
     * 等级页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        $ret = LevelService::DataList(['where'=>$this->form_where]);
        if($ret['code'] == 0)
        {
            MyViewAssign('data_list', $ret['data']);
            MyViewAssign('params', $params);
            return MyView('../../../plugins/view/distribution/admin/level/index');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        $data = [];
        if(!empty($params['id']))
        {
            $where  = [
                ['id', '=', intval($params['id'])],
            ];
            $ret = LevelService::DataList(['where'=>$where]);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
            }
        }
        MyViewAssign('data', $data);

        // 关闭头尾
        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        return MyView('../../../plugins/view/distribution/admin/level/detail');
    }

    /**
     * 等级编辑
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
            // 获取列表
            $data_params = [
                'where' => ['id' => $params['id']],
            ];
            $ret = LevelService::DataList($data_params);
            $data = empty($ret['data'][0]) ? [] : $ret['data'][0];
        }
        
        MyViewAssign('data', $data);
        return MyView('../../../plugins/view/distribution/admin/level/saveinfo');
    }

    /**
     * 等级保存
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
        return LevelService::DataSave($params);
    }

    /**
     * 等级状态更新
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
        return LevelService::DataStatusUpdate($params);
    }

    /**
     * 等级删除
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
        return LevelService::DataDelete($params);
    }
}
?>