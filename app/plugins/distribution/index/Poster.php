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
namespace app\plugins\distribution\index;

use app\service\SeoService;
use app\plugins\distribution\index\Common;
use app\plugins\distribution\service\PosterService;

/**
 * 分销 - 推广返佣
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Poster extends Common
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
        // 海报
        $poster = PosterService::UserPoster(['user'=>$this->user]);

        // 海报更新版本号，防止缓存
        $user_poster_images_ver = MySession('user_poster_images_ver');
        if($user_poster_images_ver != null)
        {
            $join = (stripos($poster['data'], '?') === false) ? '?' : '&';
            $poster['data'] .= $join.'ver='.$user_poster_images_ver;
        }
        MyViewAssign('poster', $poster);

        // 分享地址
        MyViewAssign('user_share_url', PosterService::UserShareUrl($this->user['id'], $this->plugins_config));

        // 二维码地址
        $qrcode = PosterService::UserShareQrcodeCreate($this->user['id'], $this->user['add_time'], $this->plugins_config);
        MyViewAssign('user_share_qrode', $qrcode['data']);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('推广返利 - 我的分销', 1));
        
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/distribution/index/poster/index');
    }

    /**
     * 海报刷新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-08T23:28:49+0800
     * @param    [array]          $params [输入参数]
     */
    public function refresh()
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        $params['user'] = $this->user;
        return PosterService::UserPosterRefresh($params);
    }
}
?>