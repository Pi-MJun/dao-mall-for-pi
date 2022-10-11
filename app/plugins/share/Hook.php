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
namespace app\plugins\share;

use think\facade\Db;
use app\service\UserService;
use app\service\ResourcesService;
use app\plugins\share\service\BaseService;

/**
 * 分享 - 钩子入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Hook
{
    // 基础配置信息
    public $plugins_config;

    /**
     * 应用响应入口
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-09T14:25:44+0800
     * @param    [array]                    $params [输入参数]
     */
    public function handle($params = [])
    {
        $ret = '';
        if(!empty($params['hook_name']))
        {
            // 获取配置信息
            $base = BaseService::BaseConfig();
            $this->plugins_config = $base['data'];

            // 根据钩子处理
            switch($params['hook_name'])
            {
                case 'plugins_css' :
                    $ret = [
                        'static/plugins/css/share/index/iconfont.css',
                        'static/plugins/css/share/index/style.css',
                    ];
                    break;

                case 'plugins_js' :
                    $ret = [
                        'static/plugins/js/share/index/style.js',
                    ];
                    // 微信环境引入js文件
                    if(IsWeixinEnv())
                    {
                        $ret[] = 'https://res.wx.qq.com/open/js/jweixin-1.4.0.js';
                    }
                    break;

                case 'plugins_view_common_bottom' :
                    // 公共数据
                    $ret = $this->CommonContent($params);
                    break;

                case 'plugins_common_page_bottom' :
                    // 页面最底部
                    $ret = $this->CommonBottom($params);
                    break;

                case 'plugins_view_goods_detail_photo_bottom' :
                    $ret = $this->GoodsShareContent($params);
                    break;
            }
        }
        return $ret;
    }

    /**
     * 商品页面分享
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-22T02:08:26+0800
     * @param    [array]          $params [输入参数]
     */
    private function GoodsShareContent($params = [])
    {
        if(!empty($this->plugins_config))
        {
            $share_pic = isset($params['goods']['photo'][0]['images']) ? $params['goods']['photo'][0]['images'] : '';
            return MyView('../../../plugins/view/share/index/public/goods_detail_share', [
                'share_pic' => $share_pic,
                'data'      => $this->plugins_config,
            ]);
        }
        return '';
    }

    /**
     * 公共视图html
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-06T16:16:34+0800
     * @param    [array]          $params [输入参数]
     */
    private function CommonContent($params = [])
    {
        // 获取应用数据
        if(!empty($this->plugins_config))
        {
            MyViewAssign('base_shar_type_list', BaseService::$base_shar_type_list);
            MyViewAssign('plugins_config', $this->plugins_config);
            return MyView('../../../plugins/view/share/index/public/content');
        }
        return '';
    }

    /**
     * 公共页面最底部
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-06T16:16:34+0800
     * @param    [array]          $params [输入参数]
     */
    private function CommonBottom($params = [])
    {
        // 获取应用数据
        if(!empty($this->plugins_config))
        {
            // 获取用户信息
            $user = UserService::LoginUserInfo();

            // 分享地址
            $share_referrer = empty($user) ? '' : UserService::UserReferrerEncryption($user['id']);
            MyViewAssign('share_referrer', $share_referrer);

            // 微信环境、微信是否已配置
            if(IsWeixinEnv() && !empty($this->plugins_config) && !empty($this->plugins_config['weixin_appid']) && !empty($this->plugins_config['weixin_secret']))
            {
                // 获取微信配置信息
                $obj = new \base\Wechat($this->plugins_config['weixin_appid'], $this->plugins_config['weixin_secret']);
                $package = $obj->GetSignPackage();
                MyViewAssign('package', $package);
            }

            // 当前操作名称, 兼容插件模块名称
            $module_name = RequestModule();
            $controller_name = RequestController();
            $action_name = RequestAction();

            // 业务图片自定义处理
            $id = input('id');
            $custom_cover_pic = '';
            $module = $module_name.$controller_name.$action_name;
            switch($module)
            {
                // 商品页面获取第一张相册图片
                case 'indexgoodsindex' :
                    if(!empty($id))
                    {
                        $custom_cover_pic = ResourcesService::AttachmentPathViewHandle(Db::name('GoodsPhoto')->where(['goods_id'=>$id])->order('id asc')->value('images'));
                    }
                    break;

                // 文章页面读取第一张图片
                // 自定义页面读取第一张图片
                case 'indexarticleindex' :
                case 'indexcustomviewindex' :
                    if(!empty($id))
                    {
                        $table_arr = [
                            'indexarticleindex' => 'Article',
                            'indexcustomviewindex' => 'CustomView',
                        ];
                        if(isset($table_arr[$module]))
                        {
                            $images = Db::name($table_arr[$module])->where(['id'=>$id])->value('images');
                            if(!empty($images))
                            {
                                $images = json_decode($images, true);
                                if(!empty($images[0]))
                                {
                                    $custom_cover_pic = ResourcesService::AttachmentPathViewHandle($images[0]);
                                }
                            }
                        }
                    }
                    break;
            }
            return MyView('../../../plugins/view/share/index/public/bottom', [
                'custom_cover_pic'  => $custom_cover_pic,
                'data'              => $this->plugins_config,
            ]);
        }
        return '';
    }
}
?>