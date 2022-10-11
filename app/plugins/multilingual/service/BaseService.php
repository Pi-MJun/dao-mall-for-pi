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
namespace app\plugins\multilingual\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\UserService;
use app\plugins\multilingual\service\MultilingualService;

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
    public static $request_multilingual_key = 'language';

    // 多语言选择缓存key
    public static $multilingual_cache_key = 'plugins_multilingual_language';

    // 多语言数据缓存key
    public static $multilingual_data_cache_key = 'plugins_multilingual_data_';

    // 基础数据附件字段
    public static $base_config_attachment_field = [];

    // 基础私有字段
    public static $base_config_private_field = [
        'appid',
        'appkey',
    ];

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
        return PluginsService::PluginsDataSave(['plugins'=>'multilingual', 'data'=>$params]);
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
        return PluginsService::PluginsData('multilingual', self::$base_config_attachment_field, $is_cache);
    }

    /**
     * 缓存key
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-14
     * @desc    description
     * @param   [string]          $key [缓存key]
     */
    public static function MultilingualCacheKey($key)
    {
        return RequestModule().'_'.$key;
    }

    /**
     * 用户选择的语言id
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     */
    public static function GetUserMultilingualCacheValue()
    {
        // 参数指定
        $value = input(self::$request_multilingual_key);

        // session读取
        if(empty($value))
        {
            $value = MySession(self::MultilingualCacheKey(self::$multilingual_cache_key));
        }

        // uuid读取
        if(empty($value))
        {
            $uuid = input('uuid');
            if(!empty($uuid))
            {
                $value = MyCache(self::MultilingualCacheKey(self::$multilingual_cache_key.'_'.$uuid));
            }
        }

        // 用户读取
        if(empty($value))
        {
            $user = UserService::LoginUserInfo();
            if(!empty($user['id']))
            {
                // 缓存读取
                $value = MyCache(self::MultilingualCacheKey(self::$multilingual_cache_key.'_'.$user['id']));
            }
        }

        return $value;
    }

    /**
     * 设置用户选择的语言id
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [int]          $value [语言id]
     */
    public static function SetUserMultilingualCacheValue($value)
    {
        // session
        MySession(self::MultilingualCacheKey(self::$multilingual_cache_key), $value);

        // 当前用户
        $user = UserService::LoginUserInfo();
        if(!empty($user['id']))
        {
            MyCache(self::MultilingualCacheKey(self::$multilingual_cache_key.'_'.$user['id']), $value);
        }

        // uuid
        $uuid = input('uuid');
        if(!empty($uuid))
        {
            MyCache(self::MultilingualCacheKey(self::$multilingual_cache_key.'_'.$uuid), $value);
        }

        return true;
    }

    /**
     * 获取当前用户的默认语言信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [int]          $value [当前选中的语言id]
     */
    public static function UserMultilingualData($value = '')
    {
        // 缓存读取
        $key = self::MultilingualCacheKey(self::$multilingual_cache_key);
        $data = MyCache($key);
        if(empty($data))
        {
            // 获取语言列表
            $data_params = [];
            $ret = MultilingualService::MultilingualList($data_params);
            $data = $ret['data'];

            // 缓存数据、60秒
            MyCache($key, $data, 60);
        }
        
        // 存在语言则处理
        $default = [];
        if(!empty($data))
        {
            foreach($data as $v)
            {
                // 默认语言
                if(empty($default) && isset($v['is_default']) && $v['is_default'] == 1)
                {
                    $default = $v;
                }

                // 当前选择的语言
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