<?php
namespace app\plugins\qiandao\index;


use app\service\ResourcesService;
use app\service\UserService;

/**
 * 签到前端公共控制器
 */
class Common
{
	// 用户信息
    protected $user;
	
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
    protected $form_md5_key;
    protected $form_user_fields;
    protected $form_order_by;
    protected $form_error;
	
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
		// 输入参数
        $this->data_post = input('post.');
        $this->data_get = input('get.');
        $this->data_request = input();

        // 参数赋值属性
        foreach($params as $k=>$v)
        {
            $this->$k = $v;
        }
		
		// 公共数据初始化
        $this->CommonInit();
		
		// 视图初始化
        $this->ViewInit();
		
		// 动态表格初始化
        $this->FormTableInit();
    }
	
	/**
     * [CommonInit 公共数据初始化]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-03-09T11:43:48+0800
     */
    private function CommonInit()
    {
        // 用户数据
        $this->user = UserService::LoginUserInfo();
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
        // 用户
        MyViewAssign('user', $this->user);

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

    /**
     * [IsLogin 登录校验]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-03-09T11:43:48+0800
     */
    protected function IsLogin()
    {
        if(empty($this->user))
        {
            if(IS_AJAX)
            {
                exit(json_encode(DataReturn('登录失效，请重新登录', -400)));
            } else {
                return MyRedirect('index/user/logininfo', true);
            }
        }
    }
}
?>