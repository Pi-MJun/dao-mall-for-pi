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
namespace app\plugins\exchangerate\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\UserService;
use app\plugins\exchangerate\service\CurrencyService;

/**
 * 汇率 - 基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class BaseService
{
    // 参数key
    public static $request_currency_key = 'currency';

    // 货币选择缓存key
    public static $currency_cache_key = 'plugins_exchangerate_currency';

    // 基础数据附件字段
    public static $base_config_attachment_field = [];

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'exchangerate', 'data'=>$params], self::$base_config_attachment_field);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * 
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        return PluginsService::PluginsData('exchangerate', self::$base_config_attachment_field, $is_cache);
    }

    /**
     * 用户选择的货币id
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     */
    public static function GetUserCurrencyCacheValue()
    {
        // 参数指定
        $value = input(self::$request_currency_key);
        
        // session读取
        if(empty($value))
        {
            $value = MySession(self::$currency_cache_key);
        }

        // uuid读取
        if(empty($value))
        {
            $uuid = input('uuid');
            if(!empty($uuid))
            {
                $value = MyCache(self::$currency_cache_key.'_'.$uuid);
            }
        }
                
        // 用户读取
        if(empty($value))
        {
            $user = UserService::LoginUserInfo();
            if(!empty($user['id']))
            {
                // 缓存读取
                $value = MyCache(self::$currency_cache_key.'_'.$user['id']);
            }
        }

        return $value;
    }

    /**
     * 设置用户选择的货币id
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [int]          $value [货币id]
     */
    public static function SetUserCurrencyCacheValue($value)
    {
        // session
        MySession(self::$currency_cache_key, $value);

        // 当前用户
        $user = UserService::LoginUserInfo();
        if(!empty($user['id']))
        {
            MyCache(self::$currency_cache_key.'_'.$user['id'], $value);
        }

        // uuid
        $uuid = input('uuid');
        if(!empty($uuid))
        {
            MyCache(self::$currency_cache_key.'_'.$uuid, $value);
        }

        return true;
    }

    /**
     * 获取当前用户的默认货币信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [int]          $value [当前选中的货币id]
     */
    public static function UserCurrencyData($value = '')
    {
        // 缓存读取
        $cache_key = 'plugins_exchangerate_currency';
        $data = MyCache($cache_key);
        if(empty($data))
        {
            // 获取货币列表
            $data_params = [
                'where' => ['is_enable' => 1],
                'field' => 'id,name,code,symbol,rate,is_default',
            ];
            $ret = CurrencyService::CurrencyList($data_params);
            $data = $ret['data'];

            // 缓存数据、60秒
            MyCache($cache_key, $data, 60);
        }
        
        // 存在货币则处理
        $default = [];
        if(!empty($data))
        {
            foreach($data as $v)
            {
                // 默认货币
                if(empty($default) && $v['is_default'] == 1)
                {
                    $default = $v;
                }

                // 当前选择的货币
                if(!empty($value) && $v['id'] == $value)
                {
                    $default = $v;
                }
            }

            // 未匹配到则使用第一个
            if(empty($default))
            {
                $default = $data[0];
            }
        }

        return [
            'default'   => $default,
            'data'      => $data,
        ];
    }
}
?>