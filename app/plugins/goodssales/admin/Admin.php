<?php
namespace app\plugins\goodssales\admin;

use app\service\PluginsService;

/**
 * 商品销量 - 后台管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin
{
    // 后台管理入口
    public function index($params = [])
    {
        // 数组组装
        $ret = PluginsService::PluginsData('goodssales');
        if($ret['code'] == 0)
        {
            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/goodssales/admin/admin/index');
        } else {
            return $ret['msg'];
        }
    }
	
	/**
     * 编辑页面
     * @param    [array]          $params [输入参数]
     */
    public function saveinfo($params = [])
    {
        $ret = PluginsService::PluginsData('goodssales');
        if($ret['code'] == 0)
        {
            // 是否
            $begin_style =  [
                0 => array('id' => 0, 'name' => '固定', 'checked' => true),
                1 => array('id' => 1, 'name' => '随机'),
            ];

            MyViewAssign('begin_style', $begin_style);
            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/goodssales/admin/admin/saveinfo');
        } else {
            return $ret['msg'];
        }
    }
	
	/**
     * 数据保存
     * @param    [array]          $params [输入参数]
     */
    public function save($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'goodssales', 'data'=>$params]);
    }
}
?>