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
namespace app\plugins\invoice\admin;

use app\plugins\invoice\admin\Common;
use app\plugins\invoice\service\BaseService;

/**
 * 发票 - 管理
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
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {
            if(!empty($ret['data']))
            {
                // 可选发票类型
                $ret['data']['can_invoice_type'] = array_column(BaseService::CanInvoiceTypeList(empty($ret['data']['can_invoice_type']) ? [] : $ret['data']['can_invoice_type']), 'name');
            }

            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/invoice/admin/admin/index');
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
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {
            // 静态数据
            MyViewAssign('invoice_type_list', BaseService::$invoice_type_list);

            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/invoice/admin/admin/saveinfo');
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
}
?>