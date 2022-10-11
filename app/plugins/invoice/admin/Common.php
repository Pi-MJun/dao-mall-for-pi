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
namespace app\plugins\invoice\admin;

use app\service\ResourcesService;

/**
 * 发票 - 公共
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Common
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
     * [ViewInit 视图初始化]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-03T12:30:06+0800
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
}
?>