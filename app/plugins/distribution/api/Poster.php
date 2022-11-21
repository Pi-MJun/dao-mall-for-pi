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
        // 分销信息
        $user_level = BaseService::UserDistributionLevel($this->user['id']);
        if(!empty($user_level['data']))
        {
            // 分享地址
            $user_share_url = PosterService::UserShareUrl($this->user['id'], $this->plugins_base);

            // 二维码地址
            $qrcode = PosterService::UserShareQrcodeCreate($this->user['id'], $this->user['add_time'], $this->plugins_base);

            // 海报
            $poster = PosterService::UserPoster(['user'=>$this->user]);
            if(!empty($poster['data']))
            {
                // 海报更新版本号，防止缓存
                $user_poster_images_ver = MySession('user_poster_images_ver');
                if($user_poster_images_ver != null)
                {
                    $join = (stripos($poster['data'], '?') === false) ? '?' : '&';
                    $poster['data'] .= $join.'ver='.$user_poster_images_ver;
                }
            }

            // 返回数据
            $result = [
                'user_share_url'        => $user_share_url,
                'user_share_qrode'      => empty($qrcode['data']) ? null : $qrcode['data'],
                'user_share_poster'     => empty($poster['data']) ? null : $poster['data'],
            ];
            return DataReturn('success', 0, $result);
        }
        return DataReturn('请先加入分销', -1);
    }

    /**
     * 海报刷新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-08T23:28:49+0800
     * @param    [array]          $params [输入参数]
     */
    public function refresh($params = [])
    {
        // 开始处理
        $params['user'] = $this->user;
        return PosterService::UserPosterRefresh($params);
    }
}
?>