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
namespace app\plugins\invoice\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\UserService;

/**
 * 发票 - 基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [];

    // 状态（0待审核、1待开票、2已开票、3已拒绝、4已关闭）
    public static $invoice_status_list = [
        0 => ['id'=>0, 'name'=>'待审核'],
        1 => ['id'=>1, 'name'=>'待开票'],
        2 => ['id'=>2, 'name'=>'已开票'],
        3 => ['id'=>3, 'name'=>'已拒绝'],
        4 => ['id'=>4, 'name'=>'已关闭'],
    ];

    // 业务类型（0订单、...更多）
    public static $business_type_list = [
        0 => ['id'=>0, 'name'=>'订单'],
    ];

    // 申请类型（0个人、1企业）
    public static $apply_type_list = [
        0 => ['id'=>0, 'name'=>'个人'],
        1 => ['id'=>1, 'name'=>'企业'],
    ];

    // 发票类型（0增值税普通电子发票、1增值税普通纸质发票、2增值税专业纸质发票）
    public static $invoice_type_list = [
        0 => ['id'=>0, 'name'=>'增值税普通电子发票'],
        1 => ['id'=>1, 'name'=>'增值税普通纸质发票'],
        2 => ['id'=>2, 'name'=>'增值税专用纸质发票'],
    ];

    // 审核类型
    public static $audit_type_list = [
        0 => ['id'=>0, 'name'=>'拒绝'],
        1 => ['id'=>1, 'name'=>'同意'],
    ];

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'invoice', 'data'=>$params]);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * 
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        $ret = PluginsService::PluginsData('invoice', self::$base_config_attachment_field, $is_cache);
        if(!empty($ret['data']))
        {
            // 可选发票类型
            $ret['data']['can_invoice_type'] = isset($ret['data']['can_invoice_type']) ? explode(',', $ret['data']['can_invoice_type']) : [];

            // 开具发票内容
            $ret['data']['invoice_content_type'] = empty($ret['data']['invoice_content_type']) ? [] : explode("\n", $ret['data']['invoice_content_type']);

            // 描述
            $ret['data']['invoice_desc'] = empty($ret['data']['invoice_desc']) ? [] : explode("\n", $ret['data']['invoice_desc']);
        }
        return $ret;
    }

    /**
     * 可选发票列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-04
     * @desc    description
     * @param   [array]           $data [发票类型]
     */
    public static function CanInvoiceTypeList($data = [])
    {
        // 可选发票
        if(empty($data))
        {
            $ret = self::BaseConfig();
            $data = isset($ret['data']['can_invoice_type']) ? $ret['data']['can_invoice_type'] : [];
        }
        
        // 空则全部
        if(empty($data))
        {
            return self::$invoice_type_list;
        }

        // 匹配可选项
        $result = [];
        foreach($data as $v)
        {
            if(array_key_exists($v, self::$invoice_type_list))
            {
                $result[$v] = self::$invoice_type_list[$v];
            }
        }
        return array_values($result);
    }

    /**
     * 订单开票基础条件
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-02
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function UserInvoiceOrderWhere($params = [])
    {
        // 条件
        $where = [];

        // 插件配置信息
        $base = self::BaseConfig();

        // 是否下单即可开票
        $is_order_submit_invoice = (isset($base['data']['is_order_submit_invoice']) && $base['data']['is_order_submit_invoice'] == 1) ? 1 : 0;

        // 下单开票
        if($is_order_submit_invoice == 1)
        {
            // 有效订单
            $where[] = ['status', 'in', [1,2,3,4]];

            // 支付金额必须大于0
            $where[] = ['total_price', '>', 0];
        } else {
            // 订单完成起始时间
            $order_success_start_time = empty($base['data']['order_success_start_time']) ? 43200 : intval($base['data']['order_success_start_time']);
            $where[] = ['collect_time', '<', time()-($order_success_start_time*60)];

            // 已完成状态
            $where[] = ['status', '=', 4];

            // 已支付状态
            $where[] = ['pay_status', '=', 1];

            // 支付金额必须大于0
            $where[] = ['pay_price', '>', 0];
        }

        // 可开具多久时间的订单
        $order_add_max_time = empty($base['data']['order_add_max_time']) ? 525600 : intval($base['data']['order_add_max_time']);
        $where[] = ['add_time', '>=', time()-($order_add_max_time*60)];

        // 当前用户
        $user = UserService::LoginUserInfo();
        $user_id = empty($user['id']) ? 0 : $user['id'];
        $where[] = ['user_id', '=', $user_id];

        // 已开票订单id排除、默认排除
        if(!isset($params['is_not_oid']) || $params['is_not_oid'] == 1)
        {
            $business_ids = Db::name('PluginsInvoice')->alias('pi')->join('plugins_invoice_value piv', 'pi.id=piv.invoice_id')->where(['pi.user_id'=>$user_id, 'pi.business_type'=>0, 'pi.status'=>[0,1,2,3]])->column('piv.business_id');
            if(!empty($business_ids))
            {
                $where[] = ['id', 'not in', array_unique($business_ids)];
            }
        }

        return $where;
    }

    /**
     * 用户中心菜单
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-18
     * @desc    description
     * @param   [array]          $base [配置信息]
     */
    public static function UserCenterNav($base)
    {
        $host = MyConfig('shopxo.attachment_host').'/static/plugins/images/invoice/app/';
        $data = [
            [
                'icon'  => $host.'user-center-invoice-icon.png',
                'title' => '我的发票',
                'url'   => '/pages/plugins/invoice/invoice/invoice',
            ],
            [
                'icon'  => $host.'user-center-order-icon.png',
                'title' => '订单开票',
                'url'   => '/pages/plugins/invoice/order/order',
            ]
        ];
        return $data;
    }
}
?>