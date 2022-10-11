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
namespace app\plugins\thirdpartylogin\admin;

use app\service\ResourcesService;
use app\plugins\thirdpartylogin\service\BaseService;

/**
 * 第三方登录 - 公共
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class Common
{
    // 管理员
    protected $admin;

    // 输入参数 post|get|request
    protected $data_post;
    protected $data_get;
    protected $data_request;

    // 分页信息
    protected $page;
    protected $page_size;

    // 动态表格
    protected $form_table;
    protected $form_where;
    protected $form_params;
    protected $form_error;

    // 插件基础配置
    protected $plugins_config;

    /**
     * 构造方法
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
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
     * @date    2021-05-07
     * @desc    description
     */
    public function ViewInit()
    {
        // 管理员
        MyViewAssign('admin', $this->admin);

        // 分页信息
        MyViewAssign('page', $this->page);
        MyViewAssign('page_size', $this->page_size);

        // 货币符号
        MyViewAssign('currency_symbol', ResourcesService::CurrencyDataSymbol());

        // 图片host地址
        MyViewAssign('attachment_host', MyConfig('shopxo.attachment_host'));

        // 插件基础配置
        $base = BaseService::BaseConfig();
        $this->plugins_config = empty($base['data']) ? [] : $base['data'];
    }

    /**
     * 动态表格初始化
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     */
    public function FormTableInit()
    {
        MyViewAssign('form_table', $this->form_table);
        MyViewAssign('form_params', $this->form_params);
        MyViewAssign('form_error', $this->form_error);
    }
}
?>