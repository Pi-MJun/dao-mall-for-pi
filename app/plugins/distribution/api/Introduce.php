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

use app\plugins\distribution\api\Common;
use app\plugins\distribution\service\BaseService;
use app\plugins\distribution\service\LevelService;

/**
 * 分销 - 介绍
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Introduce extends Common
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 等级列表
        $level = LevelService::DataList();

        // 等级列表
        $level_list = [];
        if(isset($this->plugins_base['is_show_introduce']) && $this->plugins_base['is_show_introduce'] == 1)
        {
            $level_params = [
                'where' => [
                    'is_enable'     => 1,
                    'is_level_auto' => 1,
                ],
            ];
            $level = LevelService::DataList($level_params);
            $level_list = empty($level['data']) ? [] : $level['data'];
        }

        // 返回数据
        $result = [
            'base'          => $this->plugins_base,
            'level_list'    => $level_list,
        ];
        return DataReturn('success', 0, $result);
    }
}
?>