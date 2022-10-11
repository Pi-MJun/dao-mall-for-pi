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
namespace app\plugins\intellectstools\admin;

use app\service\ResourcesService;
use app\service\UserAddressService;
use app\service\ExpressService;
use app\plugins\intellectstools\admin\Common;
use app\plugins\intellectstools\service\OrderBaseService;
use app\plugins\intellectstools\service\OrderPriceService;
use app\plugins\intellectstools\service\OrderAddressService;
use app\plugins\intellectstools\service\OrderExpressService;
use app\plugins\intellectstools\service\OrderNoteService;

/**
 * 智能工具箱 - 订单
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class Order extends Common
{
    /**
     * 订单修改页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function EditInfo($params = [])
    {
        // 默认从订单基础中读取
        $ret = OrderBaseService::OrderDetail($params);
        if($ret['code'] != 0)
        {
            MyViewAssign('msg', $ret['msg']);
            return MyView('public/tips_error');
        }
        $data = $ret['data'];
        $order_id = $data['id'];

        // 默认页面
        $nav_type = empty($params['nav_type']) ? 0 : intval($params['nav_type']);

        // 订单修改信息
        $order_edit_nav_list = OrderBaseService::OrderOperateEditInfo($this->plugins_config, $data);
        if(!empty($order_edit_nav_list) && array_key_exists($nav_type, $order_edit_nav_list))
        {
            // 当前默认页面
            $default_view = $order_edit_nav_list[$nav_type];

            // 订单信息、模块是否自定义订单信息
            $service_class = '\app\plugins\intellectstools\service\\Order'.ucfirst($default_view['type']).'Service';
            if(class_exists($service_class))
            {
                // 调用方法
                $action = 'OrderDetail';
                if(method_exists($service_class, $action))
                {
                    $ret = $service_class::$action($data);
                    if($ret['code'] != 0)
                    {
                        MyViewAssign('msg', $ret['msg']);
                        return MyView('public/tips_error');
                    }
                    $data = $ret['data'];
                }
            }

            // 根据业务类型处理特殊数据
            switch($default_view['type'])
            {
                // 用户地址
                case 'address' :
                    // 使用地址数据
                    $data = empty($data['address_data']) ? [] :$data['address_data'];

                    // 加载百度地图api
                    MyViewAssign('is_load_baidu_map_api', 1);

                    // 附件存储位置
                    MyViewAssign('editor_path_type', ResourcesService::EditorPathTypeValue(UserAddressService::EditorAttachmentPathType($data['user_id'])));
                    break;

                // 快递信息
                case 'express' :
                    // 快递公司
                    MyViewAssign('express_list', ExpressService::ExpressList());
                    break;
            }

            // 基础数据
            MyViewAssign('data', $data);
            MyViewAssign('order_id', $order_id);
            MyViewAssign('default_view', $default_view);
            MyViewAssign('order_edit_nav_list', $order_edit_nav_list);
            return MyView('../../../plugins/view/intellectstools/admin/order/'.$default_view['type'].'info');
        }

        MyViewAssign('msg', '订单信息不可修改');
        return MyView('public/tips_error');
    }

    /**
     * 订单备注页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function NoteInfo($params = [])
    {
        // 订单信息
        $ret = OrderBaseService::OrderDetail($params);
        if($ret['code'] != 0)
        {
            MyViewAssign('msg', $ret['msg']);
            return MyView('public/tips_error');
        }
        $order_data = $ret['data'];
        MyViewAssign('order_data', $order_data);

        // 备注信息
        $note_data = OrderNoteService::OrderNoteData($order_data['id']);
        MyViewAssign('note_data', $note_data);
        return MyView('../../../plugins/view/intellectstools/admin/order/noteinfo');
    }

    /**
     * 订单备注保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function NoteSave($params = [])
    {
        return OrderNoteService::OrderNoteSave($params);
    }

    /**
     * 订单改价保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function PriceSave($params = [])
    {
        return OrderPriceService::OrderPriceSave($params);
    }

    /**
     * 订单地址修改保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function AddressSave($params = [])
    {
        return OrderAddressService::OrderAddressSave($params);
    }

    /**
     * 订单快递修改保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function ExpressSave($params = [])
    {
        return OrderExpressService::OrderExpressSave($params);
    }
}
?>