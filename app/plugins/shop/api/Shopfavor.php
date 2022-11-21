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
namespace app\plugins\shop\api;

use app\plugins\shop\api\Common;
use app\plugins\shop\service\ShopFavorService;

/**
 * 多商户 - 店铺收藏
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class ShopFavor extends Common
{
    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-30
     * @desc    description
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        $this->IsLogin();
    }

    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 分页
        $number = 10;
        $page = max(1, isset($this->data_post['page']) ? intval($this->data_post['page']) : 1);

        // 条件
        $where = [
            ['user_id', '=', $this->user['id']],
        ];

        // 获取总数
        $total = ShopFavorService::ShopFavorTotal($where);
        $page_total = ceil($total/$number);
        $start = intval(($page-1)*$number);

        // 获取列表
        $data_params = [
            'm'         => $start,
            'n'         => $number,
            'where'     => $where,
        ];
        $ret = ShopFavorService::ShopFavorList($data_params);

        // 返回数据
        $result = [
            'total'         => $total,
            'page_total'    => $page_total,
            'data'          => $ret['data'],
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 收藏+取消
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Favor($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        return ShopFavorService::ShopFavorCancel($params);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Delete($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        return ShopFavorService::ShopFavorDelete($params);
    }
}
?>