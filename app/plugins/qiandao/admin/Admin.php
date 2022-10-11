<?php
namespace app\plugins\qiandao\admin;

use app\service\PluginsService;
use app\service\ResourcesService;
use app\plugins\qiandao\service\BaseService;

/**
 * 积分签到 - 后台管理
 */
class Admin
{
    // 后台管理入口
    public function index($params = [])
    {
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {
			// 插件数据未配置前，为空，初始化一些数据
			if(empty($ret['data'])){
				BaseService::Init();
			}
			
			MyViewAssign('zhouqi', BaseService::$zhouqi);
            MyViewAssign('data', $ret['data']);
			
            return MyView('../../../plugins/view/qiandao/admin/admin/index');
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
        $ret = BaseService::BaseConfig(false);
        if($ret['code'] == 0)
        {
            MyViewAssign('zhouqi', BaseService::$zhouqi);
            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/qiandao/admin/admin/saveinfo');
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
		// 组装奖励
		$jiangli = [];
		if(isset($params['jiang_value'])){
			$jiangli = [
				'jiang_value' 			=> $params['jiang_value'],
				'jiang_type'    		=> $params['jiang_type'],
			];
		}
		$params['jiangli'] = $jiangli;
		$params['guize'] = isset($params['guize']) ? htmlspecialchars_decode($params['guize']) : '';
		if($params['guize'] != ''){
			$params['guize'] =  ResourcesService::ContentStaticReplace($params['guize'], 'add');
		}
        return PluginsService::PluginsDataSave(['plugins'=>'qiandao', 'data'=>$params]);
    }
	
	// 搜索优惠券
    public function soucoupon($params = [])
    {
		$data = [];
		$ret = BaseService::PluginsExist('coupon');
		if($ret['code'] != 0)
		{
			// 开始操作
			$params = input('post.');
			
			// 搜索数据
			$ret = BaseService::CouponSearchList($params);
			if($ret['code'] == 0)
			{
				$data = $ret['data']['data'];
			}
		}else{
			return $ret;
		}
		return DataReturn('success', 0, $data);
    }
}
?>