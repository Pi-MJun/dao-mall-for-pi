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
namespace app\plugins\shop\service;

use think\facade\Db;
use app\plugins\shop\service\BaseService;

/**
 * 多商户 - 导航管理服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class ShopNavigationService
{    
    /**
     * 获取店铺导航
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-12
     * @desc    description
     * @param   [array]          $config [配置信息]
     * @param   [array]          $shop   [店铺信息]
     */
    public static function Nav($config, $shop)
    {
        // 获取所有导航数据
        $where = [
            ['is_show', '=', 1],
            ['shop_id', '=', $shop['id']],
        ];
        $data = self::NavDataAll(['where'=>$where]);

        // 中间大导航添加首页导航
        if(APPLICATION == 'web')
        {
            array_unshift($data, [
                'id'                    => 0,
                'pid'                   => 0,
                'name'                  => '店铺首页',
                'url'                   => BaseService::ShopHomeUrl($config, $shop),
                'data_type'             => 'system',
                'is_show'               => 1,
                'is_new_window_open'    => 0,
            ]);
        }

        // 选中处理
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                $v['active'] = ($v['url'] == __MY_VIEW_URL__) ? 1 : 0;
            }
        }

        return $data;
    }

    /**
     * 获取导航数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-15
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function NavDataAll($params = [])
    {
        // 获取导航数据
        $field = 'id,pid,shop_id,name,url,value,data_type,is_new_window_open';
        $order_by = 'sort asc,id asc';
        $where = empty($params['where']) ? [['is_show', '=', 1]] : $params['where'];
        return self::NavDataTypeUrlHandle(Db::name('PluginsShopNavigation')->field($field)->where($where)->order($order_by)->select()->toArray());
    }

    /**
     * 导航url处理
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-02-05T21:36:46+0800
     * @param    [array]      $data [需要处理的数据]
     * @return   [array]            [处理好的数据]
     */
    public static function NavDataTypeUrlHandle($data)
    {
        if(!empty($data) && is_array($data))
        {
            $client_type = APPLICATION_CLIENT_TYPE;
            foreach($data as $k=>$v)
            {
                // url处理
                switch($v['data_type'])
                {
                    // 页面设计
                    case 'design' :
                        // web端
                        $v['url'] = ($client_type == 'pc') ? PluginsHomeUrl('shop', 'design', 'detail', ['id'=>$v['value']]) : '/pages/plugins/shop/design/design?id='.$v['value'];
                        break;

                    // 商品分类
                    case 'category' :
                        // web端
                        $v['url'] = ($client_type == 'pc') ? PluginsHomeUrl('shop', 'search', 'index', ['shop_id'=>$v['shop_id'], 'category_id'=>$v['value']]) : '/pages/plugins/shop/search/search?shop_id='.$v['shop_id'].'&category_id='.$v['value'];
                        break;
                }
                $data[$k] = $v;
            }
        }
        return $data;
    }

    /**
     * 获取导航列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params  [输入参数]
     */
    public static function NavList($params = [])
    {
        // 基础参数
        $field = '*';
        $where = empty($params['where']) ? [] : $params['where'];
        $order_by = empty($params['order_by']) ? 'sort asc,id asc' : $params['order_by'];

        // 获取数据
        $data = self::NavigationHandle(self::NavDataTypeUrlHandle(Db::name('PluginsShopNavigation')->field($field)->where($where)->order($order_by)->select()->toArray()));
        return DataReturn('success', 0, $data);
    }

    /**
     * 数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-06-15
     * @desc    description
     * @param   [array]          $data [导航数据]
     */
    public static function NavigationHandle($data)
    {
        if(!empty($data) && is_array($data))
        {
            $data_type_list = BaseService::$plugins_data_type_list;
            foreach($data as &$v)
            {
                // 数据类型
                $v['data_type_text'] = isset($data_type_list[$v['data_type']]) ? $data_type_list[$v['data_type']]['name'] : '';

                // 时间
                if(array_key_exists('add_time', $v))
                {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if(array_key_exists('upd_time', $v))
                {
                    $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
                }
            }
        }
        return $data;
    }

    /**
     * 导航保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-07T21:58:19+0800
     * @param    [array]          $params [输入参数]
     */
    public static function NavSave($params = [])
    {
        if(empty($params['data_type']))
        {
            return DataReturn('操作类型有误', -1);
        }

        // 请求类型
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户id有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'sort',
                'checked_data'      => '4',
                'error_msg'         => '顺序 0~255 之间的数值',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'is_show',
                'checked_data'      => [0,1],
                'error_msg'         => '是否显示范围值有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'is_new_window_open',
                'checked_data'      => [0,1],
                'error_msg'         => '是否新窗口打开范围值有误',
            ]
        ];
        switch($params['data_type'])
        {
            // 自定义导航
            case 'custom' :
                $p = [
                    [
                        'checked_type'      => 'length',
                        'key_name'          => 'name',
                        'checked_data'      => '2,16',
                        'error_msg'         => '导航名称格式 2~16 个字符',
                    ],
                    [
                        'checked_type'      => 'fun',
                        'key_name'          => 'url',
                        'checked_data'      => 'CheckUrl',
                        'error_msg'         => 'url格式有误',
                    ],
                ];
                break;

            // 页面设计导航
            case 'design' :
                $p = [
                    [
                        'checked_type'      => 'length',
                        'key_name'          => 'name',
                        'checked_data'      => '2,16',
                        'is_checked'        => 1,
                        'error_msg'         => '导航名称格式 2~16 个字符',
                    ],
                    [
                        'checked_type'      => 'empty',
                        'key_name'          => 'value',
                        'error_msg'         => '页面设计选择有误',
                    ],
                ];
                break;

            // 商品分类导航
            case 'category' :
                $p = [
                    [
                        'checked_type'      => 'length',
                        'key_name'          => 'name',
                        'checked_data'      => '2,16',
                        'is_checked'        => 1,
                        'error_msg'         => '导航名称格式 2~16 个字符',
                    ],
                    [
                        'checked_type'      => 'empty',
                        'key_name'          => 'value',
                        'error_msg'         => '商品分类选择有误',
                    ],
                ];
                break;

            // 没找到
            default :
                return DataReturn('操作类型有误', -1);
        }

        // 参数
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 保存数据
        return self::NacDataSave($params); 
    }

    /**
     * 导航数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-02-05T20:12:30+0800
     * @param    [array]          $params [输入参数]
     */
    public static function NacDataSave($params = [])
    {
        // 自定义链接
        if($params['data_type'] == 'custom')
        {
            // 插件基础配置
            $base = BaseService::BaseConfig();

            // 是否需要验证地址非本站
            if(!empty($base['data']) && isset($base['data']['is_check_url_self_site']) && $base['data']['is_check_url_self_site'] == 1)
            {
                if(GetUrlHost($params['url']) != GetUrlHost(__MY_URL__))
                {
                    return DataReturn('请填写本站相关链接地址', -1);
                }
            }
        }

        // 非自定义导航数据处理
        if(empty($params['name']))
        {
            switch($params['data_type'])
            {
                // 页面设计导航
                case 'design' :
                    $temp_name = Db::name('PluginsShopDesign')->where(['id'=>$params['value']])->value('name');
                    break;

                // 商品分类导航
                case 'category' :
                    $temp_name = Db::name('PluginsShopGoodsCategory')->where(['id'=>$params['value']])->value('name');
                    break;
            }
            // 只截取16个字符
            if(!empty($temp_name))
            {
                $params['name'] = mb_substr($temp_name, 0, 16, MyConfig('shopxo.default_charset'));
            }
        }

        // 用户店铺
        $shop = ShopService::UserShopInfo($params['user_id']);
        if(empty($shop))
        {
            return DataReturn('未申请店铺或店铺无效', -1);
        }

        // 数据
        $data = [
            'user_id'               => $shop['user_id'],
            'shop_id'               => $shop['id'],
            'pid'                   => isset($params['pid']) ? intval($params['pid']) : 0,
            'value'                 => isset($params['value']) ? intval($params['value']) : 0,
            'name'                  => $params['name'],
            'url'                   => isset($params['url']) ? $params['url'] : '',
            'data_type'             => $params['data_type'],
            'sort'                  => intval($params['sort']),
            'is_show'               => intval($params['is_show']),
            'is_new_window_open'    => intval($params['is_new_window_open']),
        ];

        // id为空则表示是新增
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            if(Db::name('PluginsShopNavigation')->insertGetId($data) <= 0)
            {
                return DataReturn('新增失败', -100);
            }
        } else {
            $data['upd_time'] = time();
            if(Db::name('PluginsShopNavigation')->where(['id'=>intval($params['id']), 'user_id'=>$shop['user_id'], 'shop_id'=>$shop['id']])->update($data) === false)
            {
                return DataReturn('更新失败', -100);
            }
        }
        return DataReturn('操作成功', 0);
    }

    /**
     * 导航删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function NavDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'ids',
                'error_msg'         => '操作id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 删除操作
        if(Db::name('PluginsShopNavigation')->where(['id'=>$params['ids'], 'user_id'=>$params['user_id']])->delete() !== false)
        {
            return DataReturn('删除成功');
        }
        return DataReturn('删除失败', -100);
    }

    /**
     * 状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function NavStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'field',
                'error_msg'         => '未指定操作字段',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'state',
                'checked_data'      => [0,1],
                'error_msg'         => '状态有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据更新
        if(Db::name('PluginsShopNavigation')->where(['id'=>intval($params['id']), 'user_id'=>$params['user_id']])->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
        {
            return DataReturn('操作成功');
        }
        return DataReturn('操作失败', -100);
    }
}
?>