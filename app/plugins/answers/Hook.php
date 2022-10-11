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
namespace app\plugins\answers;

use app\service\PluginsService;

/**
 * 问答 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    /**
     * 钩子入口
     * @author   Guoguo
     * @blog     http://gadmin.cojz8.com
     * @version  1.0.0
     * @datetime 2019年3月14日
     */
    public function handle($params = [])
    {
        // 大导航前面添加问答地址
        if(!empty($params['hook_name']) && $params['hook_name'] == 'plugins_service_navigation_header_handle')
        {
            if(is_array($params['header']))
            {
                // 获取应用数据
                $ret = PluginsService::PluginsData('answers', ['images', 'images_bottom']);
                if($ret['code'] == 0 && !empty($ret['data']['application_name']))
                {
                    $nav = [
                        'id'                    => 0,
                        'pid'                   => 0,
                        'name'                  => $ret['data']['application_name'],
                        'url'                   => PluginsHomeUrl('answers', 'index', 'index'),
                        'data_type'             => 'custom',
                        'is_show'               => 1,
                        'is_new_window_open'    => 0,
                        'items'                 => [],
                    ];
                    array_unshift($params['header'], $nav);
                }
            }
        }
    }
}
?>