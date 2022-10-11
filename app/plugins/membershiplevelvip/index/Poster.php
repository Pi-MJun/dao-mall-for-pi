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
namespace app\plugins\membershiplevelvip\index;

use app\service\SeoService;
use app\plugins\membershiplevelvip\index\Common;
use app\plugins\membershiplevelvip\service\PosterService;

/**
 * 会员等级增强版插件 - 推广返佣
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Poster extends Common
{
    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-07-17
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        $this->IsLogin();

        // 公共用户数据
        $this->CommonUserView();
    }

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
        // 是否开启推广
        if(!isset($this->plugins_base['is_propaganda']) || $this->plugins_base['is_propaganda'] != 1)
        {
            return MyRedirect(PluginsHomeUrl('membershiplevelvip', 'vip', 'index'));
        }

        // 分享地址
        MyViewAssign('user_share_url', PosterService::UserShareUrl($this->user['id'], $this->plugins_base));

        // 二维码地址
        $qrcode = PosterService::UserShareQrcodeCreate($this->user['id'], $this->user['add_time'], $this->plugins_base);
        MyViewAssign('user_share_qrode', $qrcode['data']);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('推广返利 - 我的会员', 1));
        
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/membershiplevelvip/index/poster/index');
    }
}
?>