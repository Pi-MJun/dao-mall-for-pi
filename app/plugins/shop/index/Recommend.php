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
namespace app\plugins\shop\index;

use app\plugins\shop\index\Base;
use app\plugins\shop\service\BaseService;
use app\plugins\shop\service\ShopRecommendService;
use app\plugins\shop\service\ShopGoodsCategoryService;

/**
 * 多商户 - 首页推荐
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-29
 * @desc    description
 */
class Recommend extends Base
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
     * 列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 总数
        $total = ShopRecommendService::ShopRecommendTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    =>  $this->page_size,
            'total'     =>  $total,
            'where'     =>  $params,
            'page'      =>  $this->page,
            'url'       =>  PluginsHomeUrl('shop', 'recommend', 'index'),
        ];
        $page = new \base\Page($page_params);

        // 获取列表
        $data_params = [
            'where'         => $this->form_where,
            'm'             => $page->GetPageStarNumber(),
            'n'             => $this->page_size,
            'user_id'       => $this->user['id'],
        ];
        $ret = ShopRecommendService::ShopRecommendList($data_params);

        // 基础参数赋值
        MyViewAssign('params', $params);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);
        return MyView('../../../plugins/view/shop/index/recommend/index');
    }

    /**
     * 详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        $data = [];
        if(!empty($params['id']))
        {
            // 获取列表
            $data_params = [
                'm'             => 0,
                'n'             => 1,
                'where'         => [
                    ['id', '=', intval($params['id'])],
                    ['user_id', '=', $this->user['id']],
                ],
                'user_id'   => $this->user['id'],
            ];
            $ret = ShopRecommendService::ShopRecommendList($data_params);
            $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
        }
        MyViewAssign('data', $data);
        return MyView('../../../plugins/view/shop/index/recommend/detail');
    }

    /**
     * 添加/编辑页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        // 数据
        $data = [];
        if(!empty($params['id']))
        {
            // 获取列表
            $data_params = array(
                'm'         => 0,
                'n'         => 1,
                'where'     => [
                    ['id', '=', intval($params['id'])],
                    ['user_id', '=', $this->user['id']],
                ],
                'user_id'   => $this->user['id'],
            );
            $ret = ShopRecommendService::ShopRecommendList($data_params);
            $data = empty($ret['data'][0]) ? [] : $ret['data'][0];
        }

        // 店铺商品分类
        $category = ShopGoodsCategoryService::GoodsCategoryAll(['user_id'=>$this->user['id'], 'field'=>'id,name']);
        MyViewAssign('shop_goods_category', $category['data']);

        // 静态数据
        MyViewAssign('recommend_style_type_list', BaseService::$recommend_style_type_list);
        MyViewAssign('recommend_data_type_list', BaseService::$recommend_data_type_list);
        MyViewAssign('recommend_order_by_type_list', BaseService::$recommend_order_by_type_list);
        MyViewAssign('recommend_order_by_rule_list', BaseService::$recommend_order_by_rule_list);

        // 数据
        MyViewAssign('data', $data);
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/shop/index/recommend/saveinfo');
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Save($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        return ShopRecommendService::ShopRecommendSave($params);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Delete($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        return ShopRecommendService::ShopRecommendDelete($params);
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function StatusUpdate($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user_id'] = $this->user['id'];
        return ShopRecommendService::ShopRecommendStatusUpdate($params);
    }

    /**
     * 商品搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsSearch($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 搜索数据
        $params['user_id'] = $this->user['id'];
        return BaseService::GoodsSearchList($params);
    }
}
?>