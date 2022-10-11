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
use app\plugins\multilingual\service\BaseService;

/**
 * 多语言 - 翻译服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class TranslateService
{
    /**
     * 翻译
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-01
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function Run($params = [])
    {
        // 参数配置
        $base = BaseService::BaseConfig();
        if(empty($base['data']) || empty($base['data']['appid']) || empty($base['data']['appkey']))
        {
            return DataReturn('多语言插件配置有误', -1);
        }
        $config = $base['data'];

        // 开始处理
        $result = [
            'from'          => empty($params['from']) ? 'zh' : $params['from'],
            'to'            => $params['to'],
            'trans_result'  => [],
        ];
        if(!empty($params['q']))
        {
            // 参数处理
            $qurest_arr = [];
            $result_arr = [];
            $q_arr = array_map(function($v){return htmlspecialchars_decode($v);}, explode("\n", $params['q']));

            // 缓存key
            $key_first = BaseService::$multilingual_data_cache_key.$result['from'].'_'.$result['to'].'_';

            // 是否数据库模式
            $is_db_storage = isset($config['is_db_storage']) && $config['is_db_storage'] == 1;
            if($is_db_storage)
            {
                // key处理
                $md5_key_arr = [];
                foreach($q_arr as $v)
                {
                    $md5_key_arr[] = md5($key_first.$v);
                }
                // 获取数据库数据
                $db_data = Db::name('PluginsMultilingualTrData')->where(['md5_key'=>array_unique($md5_key_arr)])->column('to_value', 'md5_key');
                if(empty($db_data))
                {
                    $qurest_arr = $q_arr;
                    $result_arr = array_map(function($v) {return ['src'=>$v, 'dst'=>$v];}, $q_arr);
                } else {
                    foreach($q_arr as $v)
                    {
                        // 是否存在
                        $key = md5($key_first.$v);
                        $temp_v = MyCache($key);
                        if(array_key_exists($key, $db_data))
                        {
                            $temp_v = $db_data[$key];
                        } else {
                            $qurest_arr[$key] = $v;
                            $temp_v = $v;
                        }
                        // 加入返回数据
                        $result_arr[md5($v)] = [
                            'src'   => $v,
                            'dst'   => $temp_v,
                        ];
                    }
                }
            } else {
                // 数据匹配
                foreach($q_arr as $v)
                {
                    // 是否存在
                    $key = $key_first.md5($v);
                    $temp_v = MyCache($key);
                    if(empty($temp_v))
                    {
                        $qurest_arr[$key] = $v;
                        $temp_v = $v;
                    }
                    // 加入返回数据
                    $result_arr[md5($v)] = [
                        'src'   => $v,
                        'dst'   => $temp_v,
                    ];
                }
            }
            $qurest_arr = array_filter(array_values($qurest_arr));

            // 需要翻译的字符为空则直接返回
            if(!empty($qurest_arr))
            {
                $data = [
                    'appid'     => $config['appid'],
                    'appkey'    => $config['appkey'],
                    'salt'      => time(),
                    'q'         => implode("\n", $qurest_arr),
                    'from'      => $result['from'],
                    'to'        => $params['to'],
                ];
                $data['sign'] = md5($data['appid'].$data['q'].$data['salt'].$data['appkey']);
                $ret = CurlPost('http://api.fanyi.baidu.com/api/trans/vip/translate', $data);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
                $res = empty($ret['data']) ? [] : json_decode($ret['data'], true);
                if(!empty($res) && is_array($res) && !empty($res['trans_result']) && is_array($res['trans_result']))
                {
                    // 存储缓存、是否数据库模式
                    if($is_db_storage)
                    {
                        $insert_data = [];
                        foreach($res['trans_result'] as $v)
                        {
                            $insert_data[] = [
                                'md5_key'       => md5($key_first.$v['src']),
                                'from_type'     => $result['from'],
                                'to_type'       => $result['to'],
                                'from_value'    => $v['src'],
                                'to_value'      => $v['dst'],
                                'add_time'      => time(),
                            ];
                        }
                        Db::name('PluginsMultilingualTrData')->insertAll($insert_data);
                    } else {
                        foreach($res['trans_result'] as $v)
                        {
                            $key = $key_first.md5($v['src']);
                            MyCache($key, $v['dst']);
                        }
                    }
                    
                    // 匹配到返回的数组中去
                    foreach($result_arr as $k=>$a)
                    {
                        foreach($res['trans_result'] as $v)
                        {
                            if($a['src'] == $v['src'])
                            {
                                $result_arr[$k]['dst'] = $v['dst'];
                                break;
                            }
                        }
                    }
                }
            }
            
            // 数据赋值
            $result['trans_result'] = $result_arr;
        }
        return DataReturn('success', 0, $result);
    }
}
?>