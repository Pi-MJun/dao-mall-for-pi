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
namespace app\plugins\multilingual\index;

use app\plugins\multilingual\index\Common;
use app\plugins\multilingual\service\TranslateService;

/**
 * 多语言
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-28
 * @desc    description
 */
class Index extends Common
{
    /**
     * 翻译
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-01
     * @desc    description
     */
    public function Fanyi()
    {
        return TranslateService::Run($this->data_post);
    }
}
?>