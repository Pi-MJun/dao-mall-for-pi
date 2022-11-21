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
use app\service\ResourcesService;
use app\plugins\shop\service\ShopService;

/**
 * 多商户 - 轮播图服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-29
 * @desc    description
 */
class ShopSliderService
{
    /**
     * 用户端获取轮播
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-04-17
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ClientShopSliderList($params = [])
    {
        $user_id = empty($params['user_id']) ? 0 : intval($params['user_id']);
        $where = [
            ['platform', '=', APPLICATION_CLIENT_TYPE],
            ['is_enable', '=', 1],
            ['user_id', '=', $user_id],
        ];
        $data = Db::name('PluginsShopSlider')->field('name,images_url,event_value,event_type')->where($where)->order('sort asc,id asc')->select()->toArray();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // 图片
                $v['images_url'] = ResourcesService::AttachmentPathViewHandle($v['images_url']);
                $v['event_value'] = empty($v['event_value']) ? null : $v['event_value'];

                // 事件值
                // 地图
                if($v['event_type'] == 3)
                {
                    $v['event_value_data'] = empty($v['event_value']) ? null : explode('|', $v['event_value']);
                }
            }
        }
        return $data;
    }

    /**
     * 数据列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-08-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ShopSliderList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $order_by = 'sort asc,id asc';
        $data = Db::name('PluginsShopSlider')->field($field)->where($where)->order($order_by)->limit($m, $n)->select()->toArray();
        if(!empty($data))
        {
            $common_platform_type = MyConst('common_platform_type');
            $common_is_enable_tips = MyConst('common_is_enable_tips');
            $common_app_event_type = MyConst('common_app_event_type');
            foreach($data as &$v)
            {
                // 图片地址
                if(isset($v['images_url']))
                {
                    $v['images_url'] = ResourcesService::AttachmentPathViewHandle($v['images_url']);
                }

                // 时间
                if(isset($v['add_time']))
                {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if(isset($v['upd_time']))
                {
                    $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
                }
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function ShopSliderTotal($where = [])
    {
        return (int) Db::name('PluginsShopSlider')->where($where)->count();
    }

    /**
     * 数据保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-19
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ShopSliderSave($params = [])
    {
        // 请求类型
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '2,60',
                'error_msg'         => '名称长度 2~60 个字符',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'platform',
                'checked_data'      => array_column(MyConst('common_platform_type'), 'value'),
                'error_msg'         => '平台类型有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'event_type',
                'checked_data'      => array_column(MyConst('common_app_event_type'), 'value'),
                'is_checked'        => 2,
                'error_msg'         => '事件值类型有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'event_value',
                'checked_data'      => '255',
                'error_msg'         => '事件值最多 255 个字符',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'images_url',
                'checked_data'      => '255',
                'error_msg'         => '请上传图片',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'sort',
                'checked_data'      => '3',
                'error_msg'         => '顺序 0~255 之间的数值',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 用户店铺
        $shop = ShopService::UserShopInfo($params['user_id']);
        if(empty($shop))
        {
            return DataReturn('未申请店铺或店铺无效', -1);
        }

        // 附件
        $data_fields = ['images_url'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);

        // 数据
        $data = [
            'user_id'       => $shop['user_id'],
            'shop_id'       => $shop['id'],
            'name'          => $params['name'],
            'platform'      => $params['platform'],
            'event_type'    => isset($params['event_type']) ? intval($params['event_type']) : -1,
            'event_value'   => $params['event_value'],
            'images_url'    => $attachment['data']['images_url'],
            'sort'          => intval($params['sort']),
            'is_enable'     => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
        ];

        if(empty($params['id']))
        {
            $data['add_time'] = time();
            if(Db::name('PluginsShopSlider')->insertGetId($data) > 0)
            {
                return DataReturn('添加成功', 0);
            }
            return DataReturn('添加失败', -100);
        } else {
            $data['upd_time'] = time();
            if(Db::name('PluginsShopSlider')->where(['id'=>intval($params['id']), 'shop_id'=>$data['shop_id']])->update($data))
            {
                return DataReturn('编辑成功', 0);
            }
            return DataReturn('编辑失败', -100); 
        }
    }

    /**
     * 状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function ShopSliderStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
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
        $where = [
            ['id', '=', intval($params['id'])],
            ['user_id', '=', $params['user_id']],
        ];
        if(Db::name('PluginsShopSlider')->where($where)->update([$params['field']=>intval($params['state']), 'upd_time'=>time()]))
        {
           return DataReturn('编辑成功');
        }
        return DataReturn('编辑失败', -100);
    }

    /**
     * 删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ShopSliderDelete($params = [])
    {
        // 参数是否有误
        if(empty($params['ids']))
        {
            return DataReturn('数据id有误', -1);
        }
        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 删除操作
        $where = [
            ['id', 'in', $params['ids']],
            ['user_id', '=', $params['user_id']],
        ];
        if(Db::name('PluginsShopSlider')->where($where)->delete())
        {
            return DataReturn('删除成功');
        }

        return DataReturn('删除失败', -100);
    }
}
?>