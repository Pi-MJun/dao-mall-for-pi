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
use app\plugins\distribution\service\PosterGoodsService;

/**
 * 分销 - 部分相关接口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Distribution extends Common
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
     * 商品海报
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T21:10:41+0800
     */
    public function GoodsPoster()
    {
        // 是否开启海报功能
        if(isset($this->plugins_base['is_goods_detail_poster']) && $this->plugins_base['is_goods_detail_poster'] == 1)
        {
            return PosterGoodsService::GoodsCreateMiniWechat($this->data_post);
        }
        return DataReturn('海报功能未启用', -100);
    }
}
?>