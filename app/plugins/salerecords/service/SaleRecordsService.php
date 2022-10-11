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
namespace app\plugins\salerecords\service;

use think\facade\Db;
use app\service\UserService;
use app\service\GoodsService;
use app\service\ResourcesService;
use app\service\SystemBaseService;

/**
 * 销售记录 - 数据服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class SaleRecordsService
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-31
     * @desc    description
     * @param   [array]           $plugins_config [插件配置信息]
     * @param   [array]           $params         [输入参数]
     */
    public static function HomeFloorBottom($plugins_config, $params = [])
    {
        $params['n'] = empty($plugins_config['home_bottom_number']) ? 30 : intval($plugins_config['home_bottom_number']);
        return self::SaleRecordsDataList($plugins_config, $params);
    }

    /**
     * 商品详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-31
     * @desc    description
     * @param   [array]           $plugins_config [插件配置信息]
     * @param   [array]           $params         [输入参数]
     */
    public static function GoodsDetailBaseBottom($plugins_config, $params = [])
    {
        $params['n'] = empty($plugins_config['goods_detail_number']) ? 30 : intval($plugins_config['goods_detail_number']);
        return self::SaleRecordsDataList($plugins_config, $params);
    }

    /**
     * 数据列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-31
     * @desc    description
     * @param   [array]           $plugins_config [插件配置信息]
     * @param   [array]           $params         [输入参数]
     */
    public static function SaleRecordsDataList($plugins_config, $params = [])
    {
        // 基础条件
        $where = self::SaleRecordsWhere($plugins_config, $params);

        // 起始值、订单随机范围值
        $m = empty($plugins_config['order_random_max_number']) ? 0 : rand(0, intval($plugins_config['order_random_max_number']));

        // 指定读取数据条数
        $n = empty($params['n']) ? 30 : $params['n'];

        // 读取数据
        $field = 'od.user_id,od.order_id,od.goods_id,od.title,od.images,od.buy_number,o.add_time';
        $data = Db::name('OrderDetail')->alias('od')->join('order o', 'o.id=od.order_id')->where($where)->field($field)->order('o.id desc')->limit($m, $n)->select()->toArray();
        if(!empty($data))
        {
            // 商品信息处理
            $res = GoodsService::GoodsDataHandle($data, ['data_key_field'=>'goods_id']);
            $data = $res['data'];

            // 用户信息处理
            $avatar = SystemBaseService::AttachmentHost().'/static/index/'.strtolower(MyC('common_default_theme', 'default', true)).'/images/default-user-avatar.jpg';
            $user_list = Db::name('User')->where(['id'=>array_column($data, 'user_id')])->column('username,nickname,mobile,email,avatar,province,city', 'id');
            if(!empty($user_list))
            {
                foreach($user_list as &$u)
                {
                    $u = UserService::UserHandle($u);
                    if(empty($u['user_name_view']))
                    {
                        $u['user_name_view'] = RandomString(1).'***'.RandomString(1);
                        if(empty($u['avatar']))
                        {
                            $u['avatar'] = $avatar;
                        }
                    } else {
                        if(stripos('***', $u['user_name_view']) === false)
                        {
                            $u['user_name_view'] = mb_substr($u['user_name_view'], 0, 1, 'utf-8').'***'.mb_substr($u['user_name_view'], -1, null, 'utf-8');
                        }
                    }
                }
            }

            // 商品单位、默认件
            $goods_unit = Db::name('Goods')->where(['id'=>array_column($data, 'goods_id')])->column('inventory_unit', 'id');

            // 提示信息
            $config_tips = empty($plugins_config['goods_detail_tips']) ? ['刚刚'] : explode(',', $plugins_config['goods_detail_tips']);

            foreach($data as &$v)
            {
                // 用户
                $v['user'] = (!empty($user_list) && array_key_exists($v['user_id'], $user_list)) ? $user_list[$v['user_id']] : UserService::UserHandle(['avatar'=>$avatar, 'username'=>RandomString(1).'***'.RandomString(1)]);

                // 单位
                $unit = (!empty($goods_unit) && array_key_exists($v['goods_id'], $goods_unit) && !empty($goods_unit[$v['goods_id']])) ? $goods_unit[$v['goods_id']] : '件';
                $v['unit'] = $unit;

                // 提示信息
                $tips = $config_tips[array_rand($config_tips)];
                $v['tips'] = $v['user']['user_name_view'].$tips.'购买了'.$v['buy_number'].$v['unit'].'！';

                // 时间
                $v['add_time'] = date('m-d H:i', is_int($v['add_time']) ? $v['add_time'] : strtotime($v['add_time']));
            }
        }
        return $data;
    }

    /**
     * 数据条件
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-31
     * @desc    description
     * @param   [array]           $plugins_config [插件配置信息]
     * @param   [array]           $params         [输入参数]
     */
    public static function SaleRecordsWhere($plugins_config, $params)
    {
        $where = [];

        // 订单状态
        if(!empty($plugins_config['order_status']) && is_array($plugins_config['order_status']))
        {
            $where[] = ['o.status', 'in', $plugins_config['order_status']];
        }

        // 商品id
        if(!empty($params['goods_id']))
        {
            $where[] = ['od.goods_id', '=', $params['goods_id']];
        }

        return $where;
    }
}
?>