<?php
namespace app\plugins\qiandao\api;


use app\service\UserService;
use app\module\FormHandleModule;

/**
 * 签到 - 公共
 */
class Common
{
    // 用户信息
	protected $user;

    // 当前操作名称
    protected $module_name;
    protected $controller_name;
    protected $action_name;

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
     */
    public function __construct()
    {
        // 用户信息
        $this->user = UserService::LoginUserInfo();

        // 输入参数
        $this->data_post = input('post.');
        $this->data_get = input('get.');
        $this->data_request = input();
		
		// 动态表格初始化
        $this->FormTableInit();

		// 公共数据初始化
		$this->CommonInit();
    }

    /**
     * 登录校验
     */
    protected function IsLogin()
    {
        if(empty($this->user))
        {
            exit(json_encode(DataReturn('登录失效，请重新登录', -400)));
        }
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
        // 获取表格模型
        $module = FormModulePath($this->data_request);
        if(!empty($module))
        {
            // 调用表格处理
            $params = $this->data_request;
            $ret = (new FormHandleModule())->Run($module['module'], $module['action'], $params);
            if($ret['code'] == 0)
            {
                $this->form_table = $ret['data']['table'];
                $this->form_where = $ret['data']['where'];
                $this->form_params = $ret['data']['params'];
                $this->form_md5_key = $ret['data']['md5_key'];
                $this->form_user_fields = $ret['data']['user_fields'];
                $this->form_order_by = $ret['data']['order_by'];
            } else {
                $this->form_error = $ret['msg'];
            }
        }
    }
	
	/**
	 * 公共数据初始化
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-03-09T11:43:48+0800
	 */
	private function CommonInit()
	{
		// 用户数据
		$this->user = UserService::LoginUserInfo();

        // 当前操作名称
        $this->module_name = RequestModule();
        $this->controller_name = RequestController();
        $this->action_name = RequestAction();

        // 分页信息
        $this->page = max(1, isset($this->data_request['page']) ? intval($this->data_request['page']) : 1);
        $this->page_size = 10;
	}

	/**
	 * 空方法操作
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  0.0.1
	 * @datetime 2017-02-25T15:47:50+0800
	 * @param    [string]      $name [方法名称]
	 */
	protected function _empty($name)
	{
		exit(json_encode(DataReturn($name.' 非法访问', -1000)));
	}
}
?>