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
namespace app\plugins\intellectstools\index;

use app\service\ResourcesService;
use app\service\SystemBaseService;
use app\plugins\intellectstools\service\BaseService;

/**
 * 智能工具箱 - 公共
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2019-08-12
 * @desc    description
 */
class Common
{
    // 插件配置信息
    protected $plugins_config;

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
        // 参数赋值属性
        foreach($params as $k=>$v)
        {
            $this->$k = $v;
        }

        // 视图初始化
        $this->ViewInit();

        // 动态表格初始化
        $this->FormTableInit();
    }

    /**
     * 视图初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-02
     * @desc    description
     */
    public function ViewInit()
    {
        // 用户
        MyViewAssign('user', $this->user);

        // 分页信息
        MyViewAssign('page', $this->page);
        MyViewAssign('page_size', $this->page_size);

        // 货币符号
        MyViewAssign('currency_symbol', ResourcesService::CurrencyDataSymbol());

        // 图片host地址
        MyViewAssign('attachment_host', SystemBaseService::AttachmentHost());

        // 插件配置信息
        $base = BaseService::BaseConfig();
        $this->plugins_config = $base['data'];
        MyViewAssign('plugins_config', $this->plugins_config);
    }

    /**
     * 动态表格初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-02
     * @desc    description
     */
    public function FormTableInit()
    {
        MyViewAssign('form_table', $this->form_table);
        MyViewAssign('form_params', $this->form_params);
        MyViewAssign('form_md5_key', $this->form_md5_key);
        MyViewAssign('form_user_fields', $this->form_user_fields);
        MyViewAssign('form_order_by', $this->form_order_by);
        MyViewAssign('form_error', $this->form_error);
    }

    /**
     * 多商户登录校验
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-15
     * @desc    description
     */
    protected function IsShopLogin()
    {
        if(empty($this->user))
        {
            if(IS_AJAX)
            {
                exit(json_encode(DataReturn('登录失效，请重新登录', -400)));
            } else {
                MyRedirect(PluginsHomeUrl('shop', 'login', 'index'), true);
            }
        }
    }

    /**
     * 错误提示
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-07-15
     * @desc    description
     * @param   [string]      $msg [提示信息、默认（操作失败）]
     */
    public function error($msg)
    {
        if(IS_AJAX)
        {
            return DataReturn($msg, -1);
        } else {
            MyViewAssign('msg', $msg);
            return MyView('public/jump_error');
        }
    }
}
?>