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
namespace app\plugins\orderremind\admin;

use app\plugins\orderremind\service\NoticeService;

/**
 * 新订单语音提醒 - 通知
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Notice
{
    /**
     * 新订单
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Order($params = [])
    {
        $ret = NoticeService::NewOrderData();
        if($ret['code'] == 0 && !empty($ret['data']))
        {
            $view = MyView('../../../plugins/view/orderremind/admin/public/voice');
            $result = [
                'order' => $ret['data'],
                'view'  => $view,
            ];
            return DataReturn('success', 0, $result);
        }
        return $ret;
    }
}
?>