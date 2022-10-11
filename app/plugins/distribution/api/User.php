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
use app\plugins\distribution\service\ExtractionService;

/**
 * 分销 - 用户
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class User extends Common
{
    /**
     * [__construct 构造方法]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-03T12:39:08+0800
     */
    public function __construct()
    {
        // 调用父类前置方法
        parent::__construct();

        // 是否登录
        $this->IsLogin();
    }

    /**
     * 用户中心
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T21:10:41+0800
     */
    public function Index()
    {
        // 分销信息
        $user_level = BaseService::UserDistributionLevel($this->user['id']);

        // 取货点信息
        if(isset($this->plugins_base['is_enable_self_extraction']) && $this->plugins_base['is_enable_self_extraction'] == 1)
        {
            $extraction = ExtractionService::ExtractionData($this->user['id']);
        }

        // 上级用户
        if(isset($this->plugins_base['is_show_superior']) && $this->plugins_base['is_show_superior'] == 1)
        {
            $superior = BaseService::UserSuperiorData($this->user);
        }

        // 阶梯返佣提示
        if(isset($this->plugins_base['is_show_profit_ladder_tips']) && $this->plugins_base['is_show_profit_ladder_tips'] == 1)
        {
            $profit_ladder = BaseService::AppointProfitLadderOrderLevel($this->plugins_base, $this->user['id']);
        }

        // 返回数据
        $result = [
            'base'          => $this->plugins_base,
            'user_level'    => $user_level['data'],
            'extraction'    => (isset($extraction) && !empty($extraction['data'] )) ? $extraction['data'] : null,
            'superior'      => empty($superior) ? null : $superior,
            'profit_ladder' => empty($profit_ladder) ? null : $profit_ladder,
            'nav_list'      => BaseService::UserCenterNav($this->plugins_base),
        ];
        return DataReturn('success', 0, $result);
    }
}
?>