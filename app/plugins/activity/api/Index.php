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
namespace app\plugins\activity\api;

use app\plugins\activity\api\Common;
use app\plugins\activity\service\ActivityService;
use app\plugins\activity\service\SliderService;
use app\plugins\activity\service\CategoryService;

/**
 * 活动配置
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-01-03
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 轮播
        $slider_list = SliderService::ClientSliderList();

        // 分类
        $activity_category = CategoryService::CategoryList(['field'=>'id,name']);

        // 返回数据
        $result = [
            'base'              => $this->plugins_config,
            'slider_list'       => $slider_list,
            'activity_category' => $activity_category['data'],
        ];
        return DataReturn('success', 0, $result);
    }

    /**
     * 列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function DataList($params = [])
    {
        return ActivityService::SearchList($this->data_request);
    }

    /**
     * 详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        if(!empty($this->data_request['id']))
        {
            // 条件
            $where = [
                ['id', '=', intval($this->data_request['id'])],
                ['is_enable', '=', 1],
            ];

            // 获取列表
            $data_params = [
                'm'             => 0,
                'n'             => 1,
                'where'         => $where,
            ];
            $ret = ActivityService::ActivityList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                // 返回数据
                $result = [
                    'base'  => $this->plugins_config,
                    'data'  => $ret['data'][0],
                ];
                return DataReturn('success', 0, $result);
            }
        }
        return DataReturn('活动不存在或已删除', -1);
    }
}
?>