<?php
namespace app\api\controller;

use app\service\ApiService;
use app\service\SystemBaseService;
use app\service\UserService;
use app\service\OrderService;
use app\service\GoodsService;
use app\service\MessageService;
use app\service\AppCenterNavService;
use app\service\BuyService;
use app\service\GoodsFavorService;
use app\service\GoodsBrowseService;
use app\service\IntegralService;
use app\service\AppMiniUserService;
use think\facade\Log;


/**
 * 用户
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class PaymentPi extends Common
{

    public static apiKey = 'z6mtcfxfk8c3h5j9kgijkl4ri3pmatf0un5egina4q6xi39yrr3ikiyfsesccukw';
    // axios.defaults.headers.common['Authorization'] = 'Key ' + apiKey

    public static axiosClient = axios.create({baseURL:'https://api.minepi.com', timeout:60000});
    public static config = {headers: {'Authorization' : `Key ${this.apiKey}`, 'Access-Control-Allow-Origin':'*'}};

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
    }

    /**
     * 用户登录
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-04
     * @desc    description
     */
    public function approval()
    {
        Log::info("api/PaymentPi approval");
        return ApiService::ApiDataReturn(DataReturn('ok', 200));
    }

    /**
     * 用户登录-验证码发送
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-04
     * @desc    description
     */
    public function complete()
    {
        Log::info("api/PaymentPi complete");
        return ApiService::ApiDataReturn(DataReturn('ok', 200));
    }

    /**
     * 用户注册
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-04
     * @desc    description
     */
    public function cancel()
    {
        Log::info("api/PaymentPi cancel");
        return ApiService::ApiDataReturn(DataReturn('ok', 200));
    }

    /**
     * 用户注册
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-04
     * @desc    description
     */
    public function incomplete()
    {
        Log::info("api/PaymentPi incomplete");
        return ApiService::ApiDataReturn(DataReturn('ok', 200));
    }

    
}
?>