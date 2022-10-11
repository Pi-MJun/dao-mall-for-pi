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
namespace app\plugins\distribution\service;

use think\facade\Db;
use app\service\PluginsService;
use app\service\ResourcesService;
use app\service\UserService;
use app\service\GoodsService;
use app\plugins\distribution\service\LevelService;

/**
 * 分销 - 基础服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [
        'default_level_images',
        'default_qrcode_logo',
    ];

    // 消息类型
    public static $message_business_type = '分销';

    // 是否开启
    public static $distribution_is_enable_list = [
        0 => ['value' => 0, 'name' => '关闭', 'checked' => true],
        1 => ['value' => 1, 'name' => '开启'],
    ];

    // 分销层级
    public static $distribution_level_list = [
        0 => ['value' => 0, 'name' => '一级分销'],
        1 => ['value' => 1, 'name' => '二级分销'],
        2 => ['value' => 2, 'name' => '三级分销', 'checked' => true],
    ];

    // 边框样式
    public static $distribution_border_style_list = [
        0 => ['value' => 0, 'name' => '正方形', 'class' => ''],
        1 => ['value' => 1, 'name' => '圆角', 'class' => 'am-radius'],
        2 => ['value' => 2, 'name' => '圆形', 'class' => 'am-circle'],
    ];

    // 取货点状态
    public static $distribution_extraction_status_list = [
        0 => ['value' => 0, 'name' => '待审核', 'checked' => true],
        1 => ['value' => 1, 'name' => '已通过'],
        2 => ['value' => 2, 'name' => '已拒绝'],
        3 => ['value' => 3, 'name' => '已解约'],
    ];

    // 取货点返佣上级
    public static $distribution_extraction_profit_level_list = [
        0 => ['value' => 0, 'name' => '关闭', 'checked' => true],
        1 => ['value' => 1, 'name' => '上一级'],
        2 => ['value' => 2, 'name' => '上二级'],
    ];

    // 返佣类型
    public static $distribution_profit_type_list = [
        0 => ['value' => 0, 'name' => '所有订单', 'checked' => true],
        1 => ['value' => 1, 'name' => '首单'],
    ];

    // 级别
    public static $level_name_list = [
        0 => ['value' => 0, 'name' => '向下'],
        1 => ['value' => 1, 'name' => '一级'],
        2 => ['value' => 2, 'name' => '二级'],
        3 => ['value' => 3, 'name' => '三级'],
        4 => ['value' => 4, 'name' => '内购'],
        5 => ['value' => 5, 'name' => '自提点一级'],
        6 => ['value' => 6, 'name' => '自提点二级'],
        7 => ['value' => 7, 'name' => '自提点三级'],
        8 => ['value' => 8, 'name' => '指定商品返现'],
        9 => ['value' => 9, 'name' => '指定商品销售返佣'],
        10 => ['value' => 10, 'name' => '指定商品阶梯返佣'],
    ];

    // 收益结算状态（0待生效, 1待结算, 2已结算, 3已失效）
    public static $profit_status_list = [
        0 => ['value' => 0, 'name' => '待生效', 'checked' => true],
        1 => ['value' => 1, 'name' => '待结算'],
        2 => ['value' => 2, 'name' => '已结算'],
        3 => ['value' => 3, 'name' => '已失效'],
    ];

    // 积分发放状态（0待生效, 1待结算, 2已结算, 3已失效）
    public static $integral_status_list = [
        0 => ['value' => 0, 'name' => '待发放', 'checked' => true],
        1 => ['value' => 1, 'name' => '已发放'],
        2 => ['value' => 2, 'name' => '已退回'],
    ];

    // 自提订单状态（0待处理, 1已处理）
    public static $order_status_list = [
        0 => ['value' => 0, 'name' => '待处理', 'checked' => true],
        1 => ['value' => 1, 'name' => '已处理'],
    ];

    // 自动升级分销等级类型
    public static $auto_level_type_list = [
        0 => ['value' => 0, 'name' => '本人消费总额', 'checked' => true],
        1 => ['value' => 1, 'name' => '推广收益总额(已结算)'],
        2 => ['value' => 2, 'name' => '有效积分'],
    ];

    // 字体地址
    public static $font_path = ROOT.'public'.DS.'static'.DS.'common'.DS.'typeface'.DS.'Alibaba-PuHuiTi-Regular.ttf';

    /**
     * 海报数据保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-22
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function PosterDataSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'backdrop',
                'error_msg'         => '请上传海报背景图片',
            ],
            [
                'checked_type'      => 'isset',
                'key_name'          => 'avatar_width',
                'error_msg'         => '请设置头像宽度',
            ],
            [
                'checked_type'      => 'min',
                'key_name'          => 'avatar_width',
                'checked_data'      => 30,
                'error_msg'         => '头像宽度尺寸 30~300 之间',
            ],
            [
                'checked_type'      => 'max',
                'key_name'          => 'avatar_width',
                'checked_data'      => 300,
                'error_msg'         => '头像宽度尺寸 30~300 之间',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'avatar_border_style',
                'checked_data'      => array_column(self::$distribution_border_style_list, 'value'),
                'error_msg'         => '头像样式数据值有误',
            ],
            [
                'checked_type'      => 'isset',
                'key_name'          => 'qrcode_width',
                'error_msg'         => '请设置二维码宽度尺寸',
            ],
            [
                'checked_type'      => 'min',
                'key_name'          => 'qrcode_width',
                'checked_data'      => 60,
                'error_msg'         => '二维码宽度尺寸 60~300 之间',
            ],
            [
                'checked_type'      => 'max',
                'key_name'          => 'qrcode_width',
                'checked_data'      => 300,
                'error_msg'         => '二维码宽度尺寸 60~300 之间',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'qrcode_border_style',
                'checked_data'      => array_column(self::$distribution_border_style_list, 'value'),
                'error_msg'         => '二维码样式数据值有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据字段
        $data_field = 'poster_data';

        // 附件
        $data_fields = ['backdrop'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);

        // 数据
        $data = [
            'backdrop'              => $attachment['data']['backdrop'],
            'avatar_width'          => empty($params['avatar_width']) ? 60 : intval($params['avatar_width']),
            'qrcode_width'          => empty($params['qrcode_width']) ? 110 : intval($params['qrcode_width']),

            'avatar_top'            => empty($params['avatar_top']) ? 12 : intval($params['avatar_top']),
            'avatar_left'           => empty($params['avatar_left']) ? 119 : intval($params['avatar_left']),

            'nickname_top'          => empty($params['nickname_top']) ? 72 : intval($params['nickname_top']),
            'nickname_left'         => empty($params['nickname_left']) ? 113 : intval($params['nickname_left']),

            'qrcode_top'            => empty($params['qrcode_top']) ? 96 : intval($params['qrcode_top']),
            'qrcode_left'           => empty($params['qrcode_left']) ? 94 : intval($params['qrcode_left']),

            'avatar_border_style'   => isset($params['avatar_border_style']) ? intval($params['avatar_border_style']) : 2,
            'qrcode_border_style'   => isset($params['qrcode_border_style']) ? intval($params['qrcode_border_style']) : 0,

            'nickname_color'        => empty($params['nickname_color']) ? '#666' : $params['nickname_color'],
            'nickname_auto_center'  => isset($params['nickname_auto_center']) ? intval($params['nickname_auto_center']) : 1,
            'operation_time'        => time(),
        ];

        // 原有数据
        $ret = PluginsService::PluginsData('distribution', self::$base_config_attachment_field, false);

        // 保存
        $ret['data'][$data_field] = $data;
        return PluginsService::PluginsDataSave(['plugins'=>'distribution', 'data'=>$ret['data']]);
    }

    /**
     * 分享海报数据
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-22
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function PosterData($params = [])
    {
        // 数据字段
        $data_field = 'poster_data';

        // 获取数据
        $ret = PluginsService::PluginsData('distribution', self::$base_config_attachment_field);
        $data = (empty($ret['data']) || empty($ret['data'][$data_field])) ? [] : $ret['data'][$data_field];

        // 数据处理
        if(isset($params['is_handle_data']) && $params['is_handle_data'] == 1)
        {
            // 背景图片地址
            $data['backdrop_old'] = empty($data['backdrop']) ? '/static/plugins/images/distribution/default-backdrop.png' : $data['backdrop'];
            $data['backdrop'] = ResourcesService::AttachmentPathViewHandle($data['backdrop_old']);

            // 头像
            $data['avatar_width'] = empty($data['avatar_width']) ? 60 : intval($data['avatar_width']);
            $data['avatar_top'] = empty($data['avatar_top']) ? 12 : intval($data['avatar_top']);
            $data['avatar_left'] = empty($data['avatar_left']) ? 119 : intval($data['avatar_left']);
            $data['avatar_border_style'] = isset($data['avatar_border_style']) ? intval($data['avatar_border_style']) : 2;

            // 昵称
            $data['nickname_color'] = empty($data['nickname_color']) ? '#666' : $data['nickname_color'];
            $data['nickname_top'] = empty($data['nickname_top']) ? 72 : intval($data['nickname_top']);
            $data['nickname_left'] = empty($data['nickname_left']) ? 113 : intval($data['nickname_left']);
            $data['nickname_auto_center'] = isset($data['nickname_auto_center']) ? intval($data['nickname_auto_center']) : 1;

            // 二维码
            $data['qrcode_width'] = empty($data['qrcode_width']) ? 110 : intval($data['qrcode_width']);
            $data['qrcode_top'] = empty($data['qrcode_top']) ? 96 : intval($data['qrcode_top']);
            $data['qrcode_left'] = empty($data['qrcode_left']) ? 94 : intval($data['qrcode_left']);
            $data['qrcode_border_style'] = isset($data['qrcode_border_style']) ? intval($data['qrcode_border_style']) : 0;
        }

        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 获取用户分销数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-06-05
     * @desc    description
     * @param   [int]     $user_id      [用户id]
     */
    public static function UserDistributionLevel($user_id)
    {
        $level = [];

        // 基础配置
        $base = self::BaseConfig();
        $config = $base['data'];
        
        // 等级列表数据处理
        $level_list = LevelService::DataList(['where'=>['is_enable'=>1]]);
        if(!empty($level_list['data']))
        {
            // 等级根据id分组
            $level_id_group = array_column($level_list['data'], null, 'id');

            // 用户是否配置了自定义等级
            // 没有自定义的使用自动模式分配分销等级
            $user_level_id = Db::name('User')->where(['id'=>$user_id])->value('plugins_distribution_level');
            if(!empty($user_level_id) && array_key_exists($user_level_id, $level_id_group))
            {
                $level = $level_id_group[$user_level_id];
            }

            // 指定商品购买赠送等级
            if(empty($level) && !empty($config['is_appoint_goods']) && $config['is_appoint_goods'] == 1 && !empty($config['appoint_level_id']) && !empty($config['appoint_level_goods_ids']) && is_array($config['appoint_level_goods_ids']) && array_key_exists($config['appoint_level_id'], $level_id_group))
            {
                // 获取用户已完成订单商品总数
                $order_data = Db::name('Order')->alias('o')->join('order_detail od', 'o.id=od.order_id')->where(['o.user_id'=>$user_id, 'o.status'=>4, 'od.goods_id'=>$config['appoint_level_goods_ids']])->field('o.id,od.goods_id,od.buy_number')->select()->toArray();
                if(!empty($order_data))
                {
                    // 订单数据集合处理
                    $temp_order = [];
                    foreach($order_data as $v)
                    {
                        if(!array_key_exists($v['goods_id'], $temp_order))
                        {
                            $temp_order[$v['goods_id']] = [
                                'goods_count'   => 0,
                                'goods_ids'     => [],
                                'order_ids'     => [],
                            ];
                        }
                        $temp_order[$v['goods_id']]['goods_count'] += $v['buy_number'];
                        $temp_order[$v['goods_id']]['goods_ids'][] = $v['goods_id'];
                        $temp_order[$v['goods_id']]['order_ids'][] = $v['id'];
                    }

                    // 订单最低数量和商品最低数量
                    $order_min_number = empty($config['appoint_level_order_min_number']) ? 1 : intval($config['appoint_level_order_min_number']);
                    $goods_min_number = empty($config['appoint_level_goods_min_number']) ? 1 : intval($config['appoint_level_goods_min_number']);
                    foreach($config['appoint_level_goods_ids'] as $gid)
                    {
                        if(array_key_exists($gid, $temp_order))
                        {
                            $goods_count = $temp_order[$gid]['goods_count'];
                            $order_count = count(array_unique($temp_order[$gid]['order_ids']));
                            if($order_count >= $order_min_number && $goods_count >= $goods_min_number)
                            {
                                $level = $level_id_group[$config['appoint_level_id']];
                                break;
                            }
                        }
                    }
                }
            }
            
            // 自动分配
            if(empty($level))
            {
                // 升级类型值
                $value = self::UserAutoLevelTypeValue($user_id, $config);

                // 匹配相应的等级
                foreach($level_list['data'] as $rules)
                {
                    if(isset($rules['is_enable']) && $rules['is_enable'] == 1 && isset($rules['is_level_auto']) && $rules['is_level_auto'] == 1)
                    {
                        // 0-0
                        if($rules['rules_min'] <= 0 && $rules['rules_max'] <= 0)
                        {
                            $level = $rules;
                            break;
                        }

                        // 0-*
                        if($rules['rules_min'] <= 0 && $rules['rules_max'] > 0 && $value < $rules['rules_max'])
                        {
                            $level = $rules;
                            break;
                        }

                        // *-*
                        if($rules['rules_min'] > 0 && $rules['rules_max'] > 0 && $value >= $rules['rules_min'] && $value < $rules['rules_max'])
                        {
                            $level = $rules;
                            break;
                        }

                        // *-0
                        if($rules['rules_max'] <= 0 && $rules['rules_min'] > 0 && $value > $rules['rules_min'])
                        {
                            $level = $rules;
                            break;
                        }
                    }
                }
            }
        }
        if(empty($level))
        {
            return DataReturn('没有相关等级', -1);
        }
        return DataReturn('处理成功', 0, $level);
    }

    /**
     * 用户有效订单消费金额
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-05T22:08:34+0800
     * @param    [int]         $user_id [用户id]
     * @param    [array]       $config  [配置信息]
     */
    public static function UserAutoLevelTypeValue($user_id, $config = [])
    {
        // 金额类型
        $auto_level_type = isset($config['auto_level_type']) ? intval($config['auto_level_type']) : 0;
        $value = 0;
        switch($auto_level_type)
        {
            // 本人消费
            case 0 :
                // 订单状态（0待确认, 1已确认/待支付, 2已支付/待发货, 3已发货/待收货, 4已完成, 5已取消, 6已关闭）
                $where = [
                    ['user_id', '=', $user_id],
                    ['status', 'in', [2,3,4]],
                ];
                $value = Db::name('Order')->where($where)->sum('total_price');
                break;

            // 收益总额
            case 1 :
                // 结算状态（0待生效, 1待结算, 2已结算, 3已失效）
                $where = [
                    ['user_id', '=', $user_id],
                    ['status', '=', 2],
                ];
                $value = Db::name('PluginsDistributionProfitLog')->where($where)->sum('profit_price');
                break;

            // 用户积分
            case 2 :
                // 结算状态（0待生效, 1待结算, 2已结算, 3已失效）
                $where = [
                    ['id', '=', $user_id],
                ];
                $value = Db::name('User')->where($where)->value('integral');
                break;
        }
        return $value;
    }

    /**
     * 海报清空
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-12T20:36:38+0800
     * @param   [array]           $params [输入参数]
     */
    public static function PosterDelete($params = [])
    {
        $dir_all = ['poster', 'qrcode'];
        foreach($dir_all as $v)
        {
            $dir = ROOT.'public'.DS.'static'.DS.'upload'.DS.'images'.DS.'plugins_distribution'.DS.$v;
            if(is_dir($dir))
            {
                // 是否有权限
                if(!is_writable($dir))
                {
                    return DataReturn('目录没权限', -1);
                }

                // 删除目录
                \base\FileUtil::UnlinkDir($dir);
            }
        }

        return DataReturn('操作成功', 0);
    }


    /**
     * 商品海报数据
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-22
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function PosterGoodsData($params = [])
    {
        // 数据字段
        $data_field = 'poster_goods_data';

        // 获取数据
        $ret = PluginsService::PluginsData('distribution', self::$base_config_attachment_field);
        $data = (empty($ret['data']) || empty($ret['data'][$data_field])) ? [] : $ret['data'][$data_field];

        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 商品海报数据保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-22
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function PosterGoodsDataSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'bottom_left_text',
                'checked_data'      => '10',
                'is_checked'        => 1,
                'error_msg'         => '底部左侧文本不超过 10 个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'bottom_right_text',
                'checked_data'      => '6',
                'is_checked'        => 1,
                'error_msg'         => '底部右侧文本不超过 6 个字符',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据字段
        $data_field = 'poster_goods_data';

        // 数据
        $data = [
            'goods_title_text_color'    => empty($params['goods_title_text_color']) ? '' : $params['goods_title_text_color'],
            'goods_simple_text_color'   => empty($params['goods_simple_text_color']) ? '' : $params['goods_simple_text_color'],

            'bottom_left_text'          => empty($params['bottom_left_text']) ? '' : $params['bottom_left_text'],
            'bottom_left_text_color'    => empty($params['bottom_left_text_color']) ? '' : $params['bottom_left_text_color'],

            'bottom_right_text'         => empty($params['bottom_right_text']) ? '' : $params['bottom_right_text'],
            'bottom_right_text_color'   => empty($params['bottom_right_text_color']) ? '' : $params['bottom_right_text_color'],
        ];

        // 原有数据
        $ret = PluginsService::PluginsData('distribution', self::$base_config_attachment_field, false);

        // 保存
        $ret['data'][$data_field] = $data;
        return PluginsService::PluginsDataSave(['plugins'=>'distribution', 'data'=>$ret['data']]);
    }

    /**
     * 商品海报清空
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-12T20:36:38+0800
     * @param    [array]           $params [输入参数]
     */
    public static function PosterGoodsDelete($params = [])
    {
        $path = ROOT.'public'.DS.'static'.DS.'upload'.DS.'images'.DS.'plugins_distribution'.DS;
        $dir_all = ['poster_goods_qrcode', 'poster_goods'];
        foreach($dir_all as $v)
        {
            if(is_dir($path.$v))
            {
                // 是否有权限
                if(!is_writable($path.$v))
                {
                    return DataReturn('目录没权限['.$path.$v.']', -1);
                }

                // 删除目录
                \base\FileUtil::UnlinkDir($path.$v);
            }
        }
        return DataReturn('操作成功', 0);
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
        $ret = PluginsService::PluginsData('distribution', self::$base_config_attachment_field, $is_cache);

        if(!empty($ret['data']))
        {
            // 用户海报页面顶部描述
            if(!empty($ret['data']['user_poster_top_desc']))
            {
                $ret['data']['user_poster_top_desc'] = explode("\n", $ret['data']['user_poster_top_desc']);
            }

            // 等级介绍顶部描述
            if(!empty($ret['data']['user_center_level_desc']))
            {
                $ret['data']['user_center_level_desc'] = explode("\n", $ret['data']['user_center_level_desc']);
            }

            // 自提取货点申请介绍
            if(!empty($ret['data']['self_extraction_apply_desc']))
            {
                $ret['data']['self_extraction_apply_desc'] = explode("\n", $ret['data']['self_extraction_apply_desc']);
            }

            // 自提取货点顶部公告
            if(!empty($ret['data']['self_extraction_common_notice']))
            {
                $ret['data']['self_extraction_common_notice'] = explode("\n", $ret['data']['self_extraction_common_notice']);
            }

            // 不符合分销条件描述
            if(!empty($ret['data']['non_conformity_desc']))
            {
                $ret['data']['non_conformity_desc'] = explode("\n", $ret['data']['non_conformity_desc']);
            }

            // 分销中心公告
            if(!empty($ret['data']['user_center_notice']))
            {
                $ret['data']['user_center_notice'] = explode("\n", $ret['data']['user_center_notice']);
            }

            // 指定商品数据查询
            $ret['data']['appoint_level_goods_ids'] = empty($ret['data']['appoint_level_goods_ids']) ? [] : $ret['data']['appoint_level_goods_ids'];
            $ret['data']['appoint_level_goods_list'] = [];

            $ret['data']['appoint_profit_goods_ids'] = empty($ret['data']['appoint_profit_goods_ids']) ? [] : $ret['data']['appoint_profit_goods_ids'];
            $ret['data']['appoint_profit_goods_list'] = [];

            $ret['data']['appoint_sale_goods_ids'] = empty($ret['data']['appoint_sale_goods_ids']) ? [] : $ret['data']['appoint_sale_goods_ids'];
            $ret['data']['appoint_sale_goods_list'] = [];

            $ret['data']['appoint_ladder_goods_ids'] = empty($ret['data']['appoint_ladder_goods_ids']) ? [] : $ret['data']['appoint_ladder_goods_ids'];
            $ret['data']['appoint_ladder_goods_list'] = [];

            $ret['data']['appoint_repurchase_goods_ids'] = empty($ret['data']['appoint_repurchase_goods_ids']) ? [] : $ret['data']['appoint_repurchase_goods_ids'];
            $ret['data']['appoint_repurchase_goods_list'] = [];

            // 查询商品进行组装
            $goods_ids = array_merge($ret['data']['appoint_level_goods_ids'], $ret['data']['appoint_profit_goods_ids'], $ret['data']['appoint_sale_goods_ids'], $ret['data']['appoint_ladder_goods_ids'], $ret['data']['appoint_repurchase_goods_ids']);
            if(!empty($goods_ids))
            {
                $goods = Db::name('Goods')->where(['id'=>$goods_ids])->field('id,title,images')->select()->toArray();
                if(!empty($goods))
                {
                    foreach($goods as $g)
                    {
                        $g['goods_url'] = MyUrl('index/goods/index', ['id'=>$g['id']]);
                        if(in_array($g['id'], $ret['data']['appoint_level_goods_ids']))
                        {
                            $ret['data']['appoint_level_goods_list'][] = $g;
                        }
                        if(in_array($g['id'], $ret['data']['appoint_profit_goods_ids']))
                        {
                            $ret['data']['appoint_profit_goods_list'][] = $g;
                        }
                        if(in_array($g['id'], $ret['data']['appoint_sale_goods_ids']))
                        {
                            $ret['data']['appoint_sale_goods_list'][] = $g;
                        }
                        if(in_array($g['id'], $ret['data']['appoint_ladder_goods_ids']))
                        {
                            $ret['data']['appoint_ladder_goods_list'][] = $g;
                        }
                        if(in_array($g['id'], $ret['data']['appoint_repurchase_goods_ids']))
                        {
                            $ret['data']['appoint_repurchase_goods_list'][] = $g;
                        }
                    }
                }
            }
            

            // 指定商品返佣分销等级
            $ret['data']['appoint_level_name'] = empty($ret['data']['appoint_level_id']) ? '' : Db::name('PluginsDistributionLevel')->where(['id'=>intval($ret['data']['appoint_level_id'])])->value('name');
        }
        return $ret;
    }

    /**
     * 商品搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-13
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function GoodsSearchList($params = [])
    {
        // 返回数据
        $result = [
            'page_total'    => 0,
            'page_size'     => 20,
            'page'          => max(1, isset($params['page']) ? intval($params['page']) : 1),
            'total'         => 0,
            'data'          => [],
        ];

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

        // 获取商品总数
        $result['total'] = GoodsService::CategoryGoodsTotal($where);

        // 获取商品列表
        if($result['total'] > 0)
        {
            // 基础参数
            $field = 'g.id,g.title,g.images';
            $order_by = 'g.id desc';

            // 分页计算
            $m = intval(($result['page']-1)*$result['page_size']);
            $goods = GoodsService::CategoryGoodsList(['where'=>$where, 'm'=>$m, 'n'=>$result['page_size'], 'field'=>$field, 'order_by'=>$order_by]);
            $result['data'] = $goods['data'];
            $result['page_total'] = ceil($result['total']/$result['page_size']);
             // 数据处理
            if(!empty($result['data']) && is_array($result['data']) && !empty($params['goods_ids']) && is_array($params['goods_ids']))
            {
                foreach($result['data'] as &$v)
                {
                    // 是否已添加
                    $v['is_exist'] = in_array($v['id'], $params['goods_ids']) ? 1 : 0;
                }
            }
        }
        return DataReturn('处理成功', 0, $result);
    }

    /**
     * 优惠价格计算
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-26
     * @desc    description
     * @param   [string]          $price        [商品展示金额]
     * @param   [int]             $discount     [折扣系数]
     */
    public static function PriceCalculate($price, $discount = 0)
    {
        // 折扣
        if($discount > 0)
        {
            if(stripos($price, '-') !== false)
            {
                $text = explode('-', $price);
                $min_price = $text[0]*$discount;
                $max_price = $text[1]*$discount;
                $price = ($min_price <= 0) ? '0.00' : PriceNumberFormat($min_price);
                $price .= '-'.(($max_price <= 0) ? '0.00' : PriceNumberFormat($max_price));
            } else {
                $price = (float) $price *$discount;
                $price = ($price <= 0) ? '0.00' : PriceNumberFormat($price);
            }
        }
        return $price;
    }

    /**
     * 用户是否复购该商品
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-23
     * @desc    description
     * @param   [int]          $goods_id [商品id]
     */
    public static function IsUserRepurchaseGoods($goods_id)
    {
        // 当前用户信息
        $user = UserService::LoginUserInfo();
        if(!empty($user))
        {
            // 查询有效订单
            $count = Db::name('Order')->alias('o')->join('order_detail od', 'o.id=od.order_id')->where(['o.user_id'=>$user['id'], 'o.status'=>4, 'od.goods_id'=>intval($goods_id)])->count();
            return ($count > 0);
        }
        return false;
    }

    /**
     * 用户上级数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-08-24
     * @desc    description
     * @param   [array]          $user [用户信息]
     */
    public static function UserSuperiorData($user)
    {
        $result = [];
        if(!empty($user) && !empty($user['id']))
        {
            // 是否存在邀请人
            if(!isset($user['referrer']))
            {
                $user['referrer'] = Db::name('User')->where(['id'=>$user['id']])->value('referrer');
            }

            // 获取邀请人信息
            if(!empty($user['referrer']))
            {
                $temp = Db::name('User')->where(['id'=>$user['referrer']])->field('nickname,username,mobile,email,avatar')->find();
                if(!empty($temp))
                {
                    $result = UserService::UserHandle($temp);
                    unset($result['mobile'], $result['email'], $result['username'], $result['nickname'], $result['email_security'], $result['mobile_security']);
                }
            }
        }
        return $result;
    }

    /**
     * 用户中心菜单
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-18
     * @desc    description
     * @param   [array]          $base [配置信息]
     */
    public static function UserCenterNav($base)
    {
        $host = MyConfig('shopxo.attachment_host').'/static/plugins/images/distribution/app/';
        $data = [
            [
                'icon'  => $host.'user-center-order-icon.png',
                'title' => '分销订单',
                'url'   => '/pages/plugins/distribution/order/order',
            ],
            [
                'icon'  => $host.'user-center-profit-icon.png',
                'title' => '收益明细',
                'url'   => '/pages/plugins/distribution/profit/profit',
            ],
            [
                'icon'  => $host.'user-center-team-icon.png',
                'title' => '我的团队',
                'url'   => '/pages/plugins/distribution/team/team',
            ],
            [
                'icon'  => $host.'user-center-poster-icon.png',
                'title' => '推广返利',
                'url'   => '/pages/plugins/distribution/poster/poster',
            ],
            [
                'icon'  => $host.'user-center-statistics-icon.png',
                'title' => '数据统计',
                'url'   => '/pages/plugins/distribution/statistics/statistics',
            ]
        ];

        // 等级介绍
        if(isset($base['is_show_introduce']) && $base['is_show_introduce'] == 1)
        {
            $data[] = [
                'icon'  => $host.'user-center-introduce-icon.png',
                'title' => '等级介绍',
                'url'   => '/pages/plugins/distribution/introduce/introduce',
            ];
        }

        return $data;
    }

    /**
     * 指定商品购买阶梯返佣级别计算
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-12-03
     * @desc    description
     * @param   [array]          $config    [基础配置]
     * @param   [int]            $user_id   [用户id]
     * @param   [array]          $goods_ids [商品id]
     */
    public static function AppointProfitLadderOrderLevel($config, $user_id, $goods_ids = [])
    {
        if(!empty($config['appoint_goods_ladder_config']) && !empty($config['appoint_goods_ladder_config']['rate']) && !empty($config['appoint_goods_ladder_config']['price']) && count($config['appoint_goods_ladder_config']['rate']) == count($config['appoint_goods_ladder_config']['price']))
        {
            // 没指定商品id则使用配置的商品
            if(empty($goods_ids) && !empty($config['appoint_ladder_goods_ids']))
            {
                $goods_ids = $config['appoint_ladder_goods_ids'];
            }
            if(!empty($goods_ids))
            {
                // 阶梯分割时间
                $interval_time = empty($config['appoint_goods_ladder_interval_time']) ? 0 : intval($config['appoint_goods_ladder_interval_time'])*60;

                // 获取日志记录
                $where = [
                    ['pg.goods_id', 'in', $goods_ids],
                    ['p.user_id', '=', $user_id],
                ];
                $info = Db::name('PluginsDistributionAppointLadderLog')->alias('p')->join('plugins_distribution_appoint_ladder_log_goods pg', 'p.id=pg.log_id')->where($where)->group('p.id')->field('p.*')->order('p.id desc')->find();
                $level = 1;
                $count = count($config['appoint_goods_ladder_config']['rate']);
                if(!empty($info))
                {
                    if($interval_time <= 0 || $info['add_time']+$interval_time >= time())
                    {
                        // 匹配等级
                        if($count > $info['level'])
                        {
                            $level = $info['level']+1;
                        }
                    }
                }

                // 下一个阶梯返佣截止时间
                $temp = ($level > 1 && $level <= $count && !empty($info)) ? $info['add_time']+$interval_time : '';
                $time = (!empty($temp) && $temp >= time()) ? date('Y-m-d H:i:s', $temp) : '';

                // 当返佣规则
                $current = [
                    'rate'  => intval($config['appoint_goods_ladder_config']['rate'][$level-1]),
                    'price' => floatval($config['appoint_goods_ladder_config']['price'][$level-1]),
                ];

                // 实际返佣值
                $profit = ($current['price'] > 0) ? $current['price'].'π' : $current['rate'].'%';

                // 下一个返佣规则
                if($level > 1 && $level <= $count)
                {
                    $is_valid = 1;
                    $msg = '继续分享在截止时间前('.$time.')让更多人购买助力、获得（'.$profit.'）高返佣！';
                } else {
                    $is_valid = 0;
                    $msg = '分享让更多人购买助力、获得('.$profit.')丰厚返佣！';
                }
                return [
                    'level'     => $level,
                    'count'     => $count,
                    'time'      => $time,
                    'current'   => $current,
                    'msg'       => $msg,
                    'is_valid'  => $is_valid,
                ];
            }
        }
        return [];
    }

    /**
     * 获取H5地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-01-23
     * @desc    description
     * @param   [array]          $config [当前插件配置]
     */
    public static function H5Url($config)
    {
        return empty($config['h5_url']) ? MyC('common_app_h5_url') : $config['h5_url'];
    }
}
?>