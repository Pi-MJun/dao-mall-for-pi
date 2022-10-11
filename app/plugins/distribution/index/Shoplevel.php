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
use app\plugins\distribution\service\LevelService;

/**
 * 分销 - 多商户等级返佣配置
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class ShopLevel extends Common
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
        $this->IsShopLogin();

        // 关闭顶部底部内容
        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
    }

    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function Index($params = [])
    {
        // 分销等级列表
        $ret = LevelService::ShopLevelDataList(['user_id'=>$this->user['id']]);
        MyViewAssign('level_data', $ret['data']);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('商家等级返佣配置 - 我的分销', 1));

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/distribution/index/shoplevel/index');
    }

    /**
     * 编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        // 分销等级列表
        $ret = LevelService::ShopLevelDataList(['user_id'=>$this->user['id']]);
        MyViewAssign('level_data', $ret['data']);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('商家等级返佣配置编辑 - 我的分销', 1));

        // 参数
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/distribution/index/shoplevel/saveinfo');
    }

    /**
     * 等级保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
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
        return LevelService::ShopDataSave($params);
    }
}
?>