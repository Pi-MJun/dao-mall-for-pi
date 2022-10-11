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
namespace app\plugins\seckill\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\ResourcesService;
use app\service\GoodsService;
use app\service\AnswerService;

/**
 * 限时秒杀服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class BaseService
{
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
        return PluginsService::PluginsDataSave(['plugins'=>'seckill', 'data'=>$params], self::$base_config_attachment_field);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        $ret = PluginsService::PluginsData('seckill', self::$base_config_attachment_field, $is_cache);
        if(empty($ret['data']))
        {
            $ret['data'] = [];
        }

        // 描述
        $ret['data']['content_notice'] = empty($ret['data']['content_notice']) ? [] : explode("\n", $ret['data']['content_notice']);

        return $ret;
    }

    /**
     * 幻灯片数据列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-08-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function SliderList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $data = Db::name('PluginsSeckillSlider')->where($where)->order('sort asc')->select()->toArray();
        if(!empty($data))
        {
            $module = RequestModule();
            foreach($data as &$v)
            {
                // 图片地址
                $v['images_url'] = ResourcesService::AttachmentPathViewHandle($v['images_url']);

                // url地址处理
                $v['url'] = empty($v['url']) ? [] : json_decode($v['url'], true);
                if($module != 'admin')
                {
                    if(!empty($v['url']) && is_array($v['url']) && array_key_exists(APPLICATION_CLIENT_TYPE, $v['url']))
                    {
                        $v['url'] = $v['url'][APPLICATION_CLIENT_TYPE];
                    } else {
                        $v['url'] = '';
                    }
                }

                // 时间
                $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 幻灯片数据保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-19
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function SliderSave($params = [])
    {
        // 请求类型
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '2,60',
                'error_msg'         => '名称长度 2~60 个字符',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'images_url',
                'checked_data'      => '255',
                'error_msg'         => '请上传图片',
            ],            [
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

        // 附件
        $data_fields = ['images_url'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);

        // 数据
        $data = [
            'name'          => $params['name'],
            'url'           => empty($params['url']) ? '' : json_encode($params['url']),
            'images_url'    => $attachment['data']['images_url'],
            'sort'          => intval($params['sort']),
            'is_enable'     => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
        ];
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            if(Db::name('PluginsSeckillSlider')->insertGetId($data) > 0)
            {
                return DataReturn('添加成功', 0);
            }
            return DataReturn('添加失败', -100);
        } else {
            $data['upd_time'] = time();
            if(Db::name('PluginsSeckillSlider')->where(['id'=>intval($params['id'])])->update($data))
            {
                return DataReturn('编辑成功', 0);
            }
            return DataReturn('编辑失败', -100); 
        }
    }

    /**
     * 幻灯片删除
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function SliderDelete($params = [])
    {
        // 参数是否有误
        if(empty($params['ids']))
        {
            return DataReturn('商品id有误', -1);
        }
        // 是否数组
        if(!is_array($params['ids']))
        {
            $params['ids'] = explode(',', $params['ids']);
        }

        // 删除操作
        if(Db::name('PluginsSeckillSlider')->where(['id'=>$params['ids']])->delete())
        {
            return DataReturn('删除成功');
        }

        return DataReturn('删除失败', -100);
    }

    /**
     * 幻灯片状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function SliderStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
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
        if(Db::name('PluginsSeckillSlider')->where(['id'=>intval($params['id'])])->update(['is_enable'=>intval($params['state'])]))
        {
           return DataReturn('编辑成功');
        }
        return DataReturn('编辑失败或数据未改变', -100);
    }

    /**
     * 商品搜索
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function GoodsSearchList($params = [])
    {
        // 条件
        $where = [
            ['g.is_delete_time', '=', 0],
            ['g.is_shelves', '=', 1]
        ];

        // 关键字
        if(!empty($params['keywords']))
        {
            $where[] = ['g.title', 'like', '%'.$params['keywords'].'%'];
        }

        // 分类id
        if(!empty($params['category_id']))
        {
            $category_ids = GoodsService::GoodsCategoryItemsIds([$params['category_id']], 1);
            $category_ids[] = $params['category_id'];
            $where[] = ['gci.category_id', 'in', $category_ids];
        }

        // 指定字段
        $field = 'g.id,g.title,g.images,g.price';

        // 获取数据
        return GoodsService::CategoryGoodsList(['where'=>$where, 'm'=>0, 'n'=>100, 'field'=>$field, 'is_admin_access'=>1]);
    }

    /**
     * 关联商品保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-07
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public static function GoodsSave($params = [])
    {
        // 清除商品id
        Db::name('PluginsSeckillGoods')->where('id', '>', 0)->delete();

        // 写入商品id
        if(!empty($params['data']))
        {
            $data = [];
            foreach($params['data'] as &$v)
            {
                $v['add_time'] = time();
            }
            if(Db::name('PluginsSeckillGoods')->insertAll($params['data']) < count($params['data']))
            {
                return DataReturn('操作失败', -100);
            }
        }
        return DataReturn('操作成功', 0);
    }

    /**
     * 商品列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-06T21:31:53+0800
     * @param    [array]          $params [输入参数]
     */
    public static function GoodsList($params = [])
    {
        // 获取推荐商品id
        $where = empty($params['where']) ? [] : $params['where'];
        $goods = Db::name('PluginsSeckillGoods')->where($where)->column('goods_id,discount_rate,dec_price,is_recommend', 'goods_id');
        if(empty($goods))
        {
            return DataReturn('没有商品', 0);
        }

        // 获取应用信息
        $base = self::BaseConfig();
        if(isset($base['data']['time_start']) && isset($base['data']['time_end']))
        {
            $time = self::TimeCalculate($base['data']['time_start'], $base['data']['time_end']);
            $is_activity = (array_sum($time) > 0);
        } else {
            $is_activity = false;
        }


        // 条件
        $goods_ids = array_column($goods, 'goods_id');
        $where = [
            ['g.is_delete_time', '=', 0],
            ['g.is_shelves', '=', 1],
            ['g.id', 'in', $goods_ids],
        ];

        // 指定字段
        $field = 'g.id,g.title,g.simple_desc,g.images,g.min_price,g.price,g.original_price,g.min_original_price,g.sales_count,g.inventory,g.inventory_unit,g.is_exist_many_spec';

        // 获取数据
        $ret = GoodsService::CategoryGoodsList(['where'=>$where, 'm'=>0, 'n'=>0, 'field'=>$field]);
        if(!empty($ret['data']))
        {
            $result = [];
            $data = array_column($ret['data'], null, 'id');
            foreach($goods_ids as $gid)
            {
                if(isset($data[$gid]))
                {
                    $result[] = array_merge($data[$gid], $goods[$gid]);
                }
            }
            $ret['data'] = $result;
        }
        return $ret;
    }

    /**
     * 优惠价格计算
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [string]          $price            [商品展示金额]
     * @param   [int]             $discount_rate    [折扣系数]
     * @param   [int]             $dec_price        [减金额]
     */
    public static function PriceCalculate($price, $discount_rate = 0, $dec_price = 0)
    {
        if($discount_rate <= 0 && $dec_price <= 0)
        {
            return $price;
        }

        // 减金额
        if($dec_price > 0)
        {
            if(stripos($price, '-') !== false)
            {
                $text = explode('-', $price);
                $min_price = $text[0]-$dec_price;
                $max_price = $text[1]-$dec_price;
                $price = ($min_price <= 0) ? '0.00' : PriceNumberFormat($min_price);
                $price .= '-'.(($max_price <= 0) ? '0.00' : PriceNumberFormat($max_price));
            } else {
                $price = (float) $price-$dec_price;
                $price = ($price <= 0) ? '0.00' : PriceNumberFormat($price);
            }

        // 折扣
        } else if($discount_rate > 0)
        {
            if(stripos($price, '-') !== false)
            {
                $text = explode('-', $price);
                $min_price = $text[0]*$discount_rate;
                $max_price = $text[1]*$discount_rate;
                $price = ($min_price <= 0) ? '0.00' : PriceNumberFormat($min_price);
                $price .= '-'.(($max_price <= 0) ? '0.00' : PriceNumberFormat($max_price));
            } else {
                $price = (float) $price *$discount_rate;
                $price = ($price <= 0) ? '0.00' : PriceNumberFormat($price);
            }
        }
        
        return $price;
    }

    /**
     * 剩余时间计算
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [date]          $time_start [开始时间]
     * @param   [date]          $time_end   [结束时间]
     */
    public static function TimeCalculate($time_start, $time_end)
    {
        // 默认
        // status 0未开始, 1进行中(距离结束还有), 2已结束, 3异常错误
        $result = ['hours'=>'00', 'minutes'=>'00', 'seconds'=>'00', 'status'=>1, 'msg'=>'距离结束还有'];

        // 时间处理
        $ts = strtotime($time_start);
        if($ts > time())
        {
            $result['status'] = 0;
            $result['msg'] = '活动开始'.date('m-d H:i:s', $ts);
            return $result;
        }

        $te = strtotime($time_end);
        if($te < time())
        {
            $result['status'] = 2;
            $result['msg'] = '活动已结束';
            return $result;
        }

        $time = $te-$ts;
        if($time <= 0)
        {
            $result['status'] = 3;
            $result['msg'] = '活动配置有误';
            return $result;
        }

        // 活动正常，结束时间减去当前时间
        if($result['status'] == 1)
        {
            $time = $te-time();
        }

        // 计算时分秒
        $hours = intval($time/3600);
        $modulus = $time%3600;
        $minutes = intval($modulus/60);
        $seconds = $modulus%60;

        // 组合
        $result['hours'] = ($hours < 10) ? '0'.$hours : $hours;
        $result['minutes'] = ($minutes < 10) ? '0'.$minutes : $minutes;
        $result['seconds'] = ($seconds < 10) ? '0'.$seconds : $seconds;
        return $result;
    }

    /**
     * 秒杀信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-28
     * @desc    description
     * @param   array           $base   [插件配置信息]
     * @param   array           $params [输入参数]
     */
    public static function SeckillData($base = [], $params = [])
    {
        // 基础数据
        if(isset($base['time_start']) && isset($base['time_end']))
        {
            $time = self::TimeCalculate($base['time_start'], $base['time_end']);
        } else {
            $time = [
                'hours'     => '00',
                'minutes'   => '00',
                'seconds'   => '00',
                'msg'       => '未配置',
            ];
        }

        // 商品数据
        $goods = self::GoodsList($params);

        // 返回数据
        $data = [
            'base'      => $base,
            'time'      => $time,
            'goods'     => $goods['data'],
            'is_valid'  => ($time['status'] == 1) ? 1 : 0,
        ];
        return DataReturn('操作成功', 0, $data);
    }

    /**
     * 商品详情倒计时
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-07-15T23:19:57+0800
     * @param    [int]              $goods_id [商品id]
     */
    public static function GoodsDetailCountdown($goods_id)
    {
        // 根据商品id获取活动信息
        $ret = Db::name('PluginsSeckillGoods')->where('goods_id', $goods_id)->find();
        if(empty($ret))
        {
            return DataReturn('该商品未参加活动', -1);
        }

        // 配置信息
        $base = self::BaseConfig();
        if(!empty($base['data']) && isset($base['data']['time_start']) && isset($base['data']['time_end']))
        {
            $time = self::TimeCalculate($base['data']['time_start'], $base['data']['time_end']);
        } else {
            $time = [
                'hours'     => '00',
                'minutes'   => '00',
                'seconds'   => '00',
            ];
        }

        // 返回数据
        $attachment_host = MyConfig('shopxo.attachment_host');
        $data = [
            'time'      => $time,
            'icon'      => empty($base['data']['goods_detail_icon']) ? '秒杀价' : $base['data']['goods_detail_icon'],
            'title'     => empty($base['data']['goods_detail_title']) ? '限时秒杀' : $base['data']['goods_detail_title'],
            'is_valid'  => (isset($time['status']) && $time['status'] == 1) ? 1 : 0,
            'bg_img'    => $attachment_host.'/static/plugins/images/seckill/bg-img.png',
            'icon'      => $attachment_host.'/static/plugins/images/seckill/icon.png',
        ];
        return DataReturn('操作成功', 0, $data);
    }
}
?>