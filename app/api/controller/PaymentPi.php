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

    // public static apiKey = '1zpvlbygh8c265qi1ubmutt54mfs3yxbhibsxz6kouqkxt1ys3h8zpn6wg1nthyf';
    // axios.defaults.headers.common['Authorization'] = 'Key ' + apiKey

    // public static axiosClient = axios.create({baseURL:'https://api.minepi.com', timeout:60000});
    // public static config = {headers: {'Authorization' : `Key ${this.apiKey}`, 'Access-Control-Allow-Origin':'*'}};

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

        $params = $this->data_request;
        Log::info($params);
        // Log::info($params['id']);
        // Log::info($params['price']);
        Log::info($params['paymentId']);

        //order id
        // $orderid = $params['id'];
        // $price = $params['price'];
        // Log::info($orderid);
        // Log::info($price);


        $data  = array();
        // $data=json_encode($data);
        $Url = "https://api.minepi.com/v2/payments/".$params['paymentId']."/approve";
        // $Url = "https://api.minepi.com/v2/payments/";
        $header=array(
            "Accept: */*",
            "Accept-Encoding: *",
            "Content-Type: application/json",
            // "Content-Length: ".strlen($data),
            "Authorization: "."Key 1zpvlbygh8c265qi1ubmutt54mfs3yxbhibsxz6kouqkxt1ys3h8zpn6wg1nthyf",
            "Access-Control-Allow-Origin: *"
            );//Header参数

        Log::info("params:");
        Log::info($Url);
        Log::info($header);
        Log::info($data);

        $curl = curl_init();//初始化


        curl_setopt($curl, CURLOPT_URL, $Url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);

        // 本地调试开启代理
        // curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1');
        // curl_setopt($curl, CURLOPT_PROXYPORT, '10809');
        // curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);


        $data = curl_exec($curl);//返回参数

        if($errno = curl_errno($curl)) {
            Log::info("err:");
            Log::info($errno);
            $error_message = curl_strerror($errno);
            Log::info($error_message);

            curl_close($curl);
            return ApiService::ApiDataReturn(DataReturn($error_message, 500));
        }

        curl_close($curl);
        Log::info("ret:");
        Log::info($data);
        return ApiService::ApiDataReturn(DataReturn($data, 200));
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
        Log::info($this);

        $params = $this->data_request;
        Log::info($params);
        Log::info($params['paymentId']);
        Log::info($params['txid']);
        // Log::info($params['id']);


        $data=json_encode(array('txid'=> $params['txid']));

        $Url = "https://api.minepi.com/v2/payments/".$params['paymentId']."/complete";
        $header=array(
            "Accept: */*",
            "Content-Type: application/json",
            // "Content-Length: ".strlen($data),
            "Authorization: "."Key 1zpvlbygh8c265qi1ubmutt54mfs3yxbhibsxz6kouqkxt1ys3h8zpn6wg1nthyf",
            "Access-Control-Allow-Origin: *"
            );//Header参数

        Log::info("params:");
        Log::info($Url);
        Log::info($header);
        Log::info($data);


        $curl = curl_init();//初始化
        
        curl_setopt($curl, CURLOPT_URL, $Url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);

        // 本地调试开启代理
        // curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1');
        // curl_setopt($curl, CURLOPT_PROXYPORT, '10809');
        // curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);


        $data = curl_exec($curl);//返回参数

        if($errno = curl_errno($curl)) {
            Log::info("err:");
            Log::info($errno);
            $error_message = curl_strerror($errno);
            Log::info($error_message);

            curl_close($curl);
            return ApiService::ApiDataReturn(DataReturn($error_message, 500));
        }

        curl_close($curl);
        Log::info("ret:");
        Log::info($data);

        // Log::info("call OrderService::Notify:");
        // $notifyRet = OrderService::Notify();
        // Log::info("notifyRet:");
        // Log::info($notifyRet);

        return ApiService::ApiDataReturn(DataReturn($data, 200));
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
        
        $params = $this->data_request;
        Log::info($params);
        Log::info($params['paymentId']);
        Log::info($params['txid']);
        Log::info($params['id']);


        $data=json_encode(array('txid'=> $params['txid']));

        $Url = "https://api.minepi.com/v2/payments/".$params['paymentId']."/complete";
        $header=array(
            "Accept: */*",
            "Content-Type: application/json",
            // "Content-Length: ".strlen($data),
            "Authorization: "."Key 1zpvlbygh8c265qi1ubmutt54mfs3yxbhibsxz6kouqkxt1ys3h8zpn6wg1nthyf",
            "Access-Control-Allow-Origin: *"
            );//Header参数

        Log::info("params:");
        Log::info($Url);
        Log::info($header);
        Log::info($data);


        $curl = curl_init();//初始化
        
        curl_setopt($curl, CURLOPT_URL, $Url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);

        // 本地调试开启代理
        // curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1');
        // curl_setopt($curl, CURLOPT_PROXYPORT, '10809');
        // curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);



        $data = curl_exec($curl);//返回参数

        if($errno = curl_errno($curl)) {
            Log::info("err:");
            Log::info($errno);
            $error_message = curl_strerror($errno);
            Log::info($error_message);

            curl_close($curl);
            return ApiService::ApiDataReturn(DataReturn($error_message, 500));
        }

        curl_close($curl);
        Log::info("ret:");
        Log::info($data);
        return ApiService::ApiDataReturn(DataReturn($data, 200));

    }

    
}
?>