<?php
namespace app\plugins\goodssales;

use think\facade\Db;
use think\Controller;
use app\service\GoodsService;
use app\service\PluginsService;

/**
 * 商品销量 - 钩子入口
 * @author   YX
 * @version  0.0.1
 * @datetime 2020-04-24
 */
class Hook
{
    // 应用响应入口
    public function handle($params = [])
    {
        if(!empty($params['hook_name']))
        {
            switch($params['hook_name'])
            {
                // 商品编辑页面钩子
                case 'plugins_view_admin_goods_save' :
                    $ret = $this->html($params);
                    break;
					
				// 商品保存钩子，将销量字段保存
                case 'plugins_service_goods_save_handle' :
                    $ret = $this->GoodsBeforeSave($params);
                    break;
					
                default :
                    $ret = '';
            }
            return $ret;
        }
    }
	
	/**
     * 视图
     * @param    [array]          $params [输入参数]
     */
    public function html($params = [])
    {
		$sales_count = $access_count = 0;
		
		// 编辑时获取商品现有销量
		if(!empty($params['goods_id'])){
			$sales_count = $params['data']['sales_count'];
			$access_count = $params['data']['access_count'];
			
		}else{
			// 添加商品时默认的销量，可改为随机数，也可固定一个值
			// 获取应用数据
            $ret = PluginsService::PluginsData('goodssales');
            if($ret['code'] == 0)
            {
				if(!empty($ret['data'])){
					// 随机
					if(isset($ret['data']['begin_style']) && intval($ret['data']['begin_style']) == 1){
						$sales_count = rand(intval($ret['data']['begin_num']), intval($ret['data']['end_num']));
						$access_count = rand(intval($ret['data']['end_num'])*10, intval($ret['data']['end_num'])*30);
					}
					// 固定
					else{
						$sales_count = intval($ret['data']['begin_num']);
						$access_count = rand(intval($ret['data']['begin_num'])*10,intval($ret['data']['begin_num'])*30);
					}
				}
			}
		}
		
		MyViewAssign('sales_count', $sales_count);
		MyViewAssign('access_count', $access_count);
		
		return MyView('../../../plugins/view/goodssales/admin/admin/content');
    }
	
	// 将销量字段保存
	public function GoodsBeforeSave($params = [])
    {
		if(isset($params['params']['sales_count'])){
			$params['data']['sales_count'] = $params['params']['sales_count'];
		}
		if(isset($params['params']['access_count'])){
			$params['data']['access_count'] = $params['params']['access_count'];
		}
		return DataReturn('无须处理', 0);
    }
}
?>