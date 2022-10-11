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
namespace app\plugins\distribution\api;

use app\service\OrderService;
use app\service\ConfigService;
use app\plugins\distribution\api\Common;
use app\plugins\distribution\service\BaseService;
use app\plugins\distribution\service\BusinessService;
use app\plugins\distribution\service\ExtractionService;
use app\plugins\distribution\service\StatisticalService;

/**
 * 分销 - 取货点
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Extraction extends Common
{
    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-12
     * @desc    description
     */
    public function __construct()
    {
        parent::__construct();

        // 是否登录
        $this->IsLogin();
    }

    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-19
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 取货点信息
        if(isset($this->plugins_base['is_enable_self_extraction']) && $this->plugins_base['is_enable_self_extraction'] == 1)
        {
            $extraction = ExtractionService::ExtractionData($this->user['id']);
        }

        // 统计数据
        $statistical = [
            'order_wait'      => StatisticalService::ExtractionStatusTotal($this->user['id'], 0),
            'order_already'   => StatisticalService::ExtractionStatusTotal($this->user['id'], 1),
        ];

        // 返回数据
        $result = [
            'base'          => $this->plugins_base,
            'extraction'    => (isset($extraction) && !empty($extraction['data']))? $extraction['data'] : null,
            'statistical'   => $statistical,
            
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 取货订单
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-19
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Order($params = [])
    {
        // 参数
        $params = $this->data_post;
        $params['user'] = $this->user;
        $params['user_type'] = 'user';

        // 分页
        $number = 10;
        $page = max(1, isset($this->data_post['page']) ? intval($this->data_post['page']) : 1);

        // 条件
        $where = BusinessService::ExtractionOrderListWhere($params);

        // 获取总数
        $total = BusinessService::ExtractionOrderListTotal($where);
        $page_total = ceil($total/$number);
        $start = intval(($page-1)*$number);

        // 获取列表
        $data_params = array(
            'm'             => $start,
            'n'             => $number,
            'where'         => $where,
        );
        $data = BusinessService::ExtractionOrderList($data_params);

        // 返回数据
        $result = [
            'total'             => $total,
            'page_total'        => $page_total,
            'data'              => $data['data'],
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 取货
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-03
     * @desc    description
     */
    public function Take($params = [])
    {
        $params['creator'] = $this->user['id'];
        $params['creator_name'] = $this->user['user_name_view'];
        $params['user_type'] = 'admin';
        return OrderService::OrderDelivery($params);
    }

    /**
     * 取货点信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     */
    public function ApplyInfo($params = [])
    {
        // 获取取货点信息
        $extraction = ExtractionService::ExtractionData($this->user['id']);

        // 返回数据
        $result = [
            'extraction_data'   => $extraction['data'],
            'editor_path_type'  => 'plugins_distribution-user_extraction-'.$this->user['id'],
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 取货点申请保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     */
    public function ApplySave($params = [])
    {
        $params['user'] = $this->user;
        return ExtractionService::ExtractionSave($params);
    }

    /**
     * 取货点切换页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     */
    public function SwitchInfo($params = [])
    {
        // 自提点地址列表
        $address = ConfigService::SiteTypeExtractionAddressList(null, $params);

        // 返回数据
        $result = [
            'extraction_address'   => $address['data'],
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 取货点切换保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     */
    public function SwitchSave($params = [])
    {
        $params['user'] = $this->user;
        return ExtractionService::ExtractionSwitchSave($params);
    }
}
?>