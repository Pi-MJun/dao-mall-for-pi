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
use app\service\UserService;
use app\service\RegionService;
use app\service\GoodsService;
use app\service\WarehouseService;
use app\service\OrderService;
use app\service\DomainService;
use app\layout\service\BaseLayout;
use app\plugins\shop\service\BaseService;
use app\plugins\shop\service\ShopGoodsService;
use app\plugins\wallet\service\WalletService;

/**
 * 多商户 - 店铺服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class ShopService
{
    /**
     * 搜索店铺列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function SearchList($params = [])
    {
        // 返回格式
        $result = [
            'page_total'    => 0,
            'total'         => 0,
            'data'          => [],
        ];

        // 分页
        $result['page'] = max(1, isset($params['page']) ? intval($params['page']) : 1);
        $result['page_size'] = empty($params['page_size']) ? 20 : min(intval($params['page_size']), 100);
        $result['page_start'] = intval(($result['page']-1)*$result['page_size']);
        
        // 搜索条件
        $where = self::SearchWhereHandle($params);

        // 有效期条件
        // 仅搜索无限期和有效期内的数据
        $expire_where = self::ShopExpireTimeWhere();

        // 排序
        if(!empty($params['order_by_field']) && !empty($params['order_by_type']) && $params['order_by_field'] != 'default')
        {
            $order_by = $params['order_by_field'].' '.$params['order_by_type'];
        } else {
            $order_by = 'id desc';
        }

        // 获取总数
        $result['total'] = self::ShopTotal($where, $expire_where);

        // 存在总数则查询数据
        if($result['total'] > 0)
        {
            // 获取列表
            $data_params = [
                'where'             => $where,
                'm'                 => $result['page_start'],
                'n'                 => $result['page_size'],
                'field'             => '*',
                'order_by'          => $order_by,
                'expire_where'      => $expire_where,
                'is_goods_count'    => 1,
                'is_sales_count'    => 1,
                'is_shop_favor'     => 1,
            ];
            $ret = self::ShopList($data_params);

            // 返回数据
            $result['data'] = $ret['data'];
            $result['page_total'] = ceil($result['total']/$result['page_size']);
        }
        return DataReturn('处理成功', 0, $result);
    }

    /**
     * 店铺有效期条件
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-08
     * @desc    description
     */
    public static function ShopExpireTimeWhere()
    {
        return '`expire_time` = 0 OR `expire_time` >= '.time();
    }

    /**
     * 获取应用商店列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-17
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function ShopList($params)
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $expire_where = (empty($params['expire_where']) || !is_string($params['expire_where'])) ? '`id`>0' : $params['expire_where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : trim($params['order_by']);
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        // 获取列表
        $data = Db::name('PluginsShop')->where($where)->whereRaw($expire_where)->field($field)->order($order_by)->limit($m, $n)->select()->toArray();
        return DataReturn('处理成功', 0, self::DataHandle($data, $params));
    }

    /**
     * 获取总数
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-17
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function ShopTotal($where, $expire_where = '')
    {
        $expire_where = (empty($expire_where) || !is_string($expire_where)) ? '`id`>0' : $expire_where;
        return (int) Db::name('PluginsShop')->where($where)->whereRaw($expire_where)->count();
    }

    /**
     * 搜索条件处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function SearchWhereHandle($params = [])
    {
        $where = [
            ['status', '=', 2],
        ];

        // 分类
        $category_id = empty($params['category_id']) ? (empty($params['cid']) ? '' : intval($params['cid'])) : intval($params['category_id']);
        if(!empty($category_id))
        {
            $where[] = ['category_id', '=', $category_id];
        }

        // 关键字
        $keywords = empty($params['keywords']) ? (empty($params['wd']) ? '' : $params['wd']) : $params['keywords'];
        if(!empty($keywords))
        {
            $where[] = ['name|describe', 'like', '%'.$keywords.'%'];
        }

        return $where;
    }

    /**
     * 数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-29
     * @desc    description
     * @param   [array]          $data      [数据]
     * @param   [array]          $params    [输入参数]
     */
    public static function DataHandle($data, $params = [])
    {
        if(!empty($data) && is_array($data) && count(array_filter($data)) > 0)
        {
            // 用户类型
            $user_type = empty($params['user_type']) ? 'user' : $params['user_type'];

            // 插件配置信息
            if(empty($params['base_config']))
            {
                $base = BaseService::BaseConfig();
                $config = $base['data'];
            } else {
                $config = $params['base_config'];
            }

            // 地区
            $region_ids = array_unique(array_merge(array_column($data, 'province'), array_column($data, 'city'), array_column($data, 'county')));
            $region = empty($region_ids) ? [] : RegionService::RegionName($region_ids);

            // 店铺分类
            $category = Db::name('PluginsShopCategory')->where(['id'=>array_column($data, 'category_id')])->column('name', 'id');

            // 静态数据值
            $status_list = array_column(BaseService::$plugins_shop_status_list, 'name', 'value');
            $week_list = array_column(BaseService::$plugins_week_list, 'name', 'value');
            $settle_type = array_column(BaseService::$plugins_settle_type, 'name', 'value');
            $auth_type = array_column(BaseService::$plugins_auth_type_list, 'name', 'value');
            $data_model = array_column(BaseService::$plugins_shop_data_model_list, 'name', 'value');
            $bond_list = array_column(BaseService::$plugins_shop_bond_status_list, 'name', 'value');

            // 是否需要商品总数
            $is_goods_count = (isset($params['is_goods_count']) && $params['is_goods_count']) == 1 ? 1 : 0;

            // 是否需要累计销售总数
            $is_sales_count = (isset($params['is_sales_count']) && $params['is_sales_count']) == 1 ? 1 : 0;

            // 是否需要店铺收藏总数
            $is_shop_favor = (isset($params['is_shop_favor']) && $params['is_shop_favor']) == 1 ? 1 : 0;

            // 是否保留布局数据（由于数据太大、默认去掉）
            $is_layout_config = (isset($params['is_layout_config']) && $params['is_layout_config'] == 1) ? 1 : 0;

            foreach($data as &$v)
            {
                // 非管理员和商家则移除隐私字段数据
                if(!in_array($user_type, ['admin', 'shop']))
                {
                    unset($v['status'],
                        $v['notice_mobile'],
                        $v['notice_email'],
                        $v['contacts_name'],
                        $v['contacts_tel'],
                        $v['company_name'],
                        $v['company_number'],
                        $v['company_license'],
                        $v['more_prove'],
                        $v['idcard_name'],
                        $v['idcard_number'],
                        $v['idcard_front'],
                        $v['idcard_back'],
                        $v['settle_type'],
                        $v['settle_rate'],
                        $v['is_user_settle'],
                        $v['expire_time'],
                        $v['fail_reason']);
                }

                // 用户信息
                if(array_key_exists('user_id', $v))
                {
                    $v['user'] = ($user_type == 'admin') ? UserService::GetUserViewInfo($v['user_id']) : [];
                }

                // url
                if(array_key_exists('id', $v))
                {
                    $v['url'] = BaseService::ShopHomeUrl($config, $v);
                }

                // 地区
                if(array_key_exists('province', $v))
                {
                    $v['province_name'] = (!empty($v['province']) && !empty($region) && array_key_exists($v['province'], $region)) ? $region[$v['province']] : '';
                }
                if(array_key_exists('city', $v))
                {
                    $v['city_name'] = (!empty($v['city']) && !empty($region) && array_key_exists($v['city'], $region)) ? $region[$v['city']] : '';
                }
                if(array_key_exists('county', $v))
                {
                    $v['county_name'] = (!empty($v['county']) && !empty($region) && array_key_exists($v['county'], $region)) ? $region[$v['county']] : '';
                }

                // 商品总数
                if($is_goods_count == 1)
                {
                    $v['goods_count'] = Db::name('Goods')->where(['shop_id'=>$v['id']])->count();
                }

                // 累计销量
                if($is_sales_count == 1)
                {
                    $v['goods_sales_count'] = Db::name('Goods')->where(['shop_id'=>$v['id']])->sum('sales_count');
                }

                // 店铺收藏总数
                if($is_shop_favor == 1)
                {
                    $v['shop_favor_count'] = Db::name('PluginsShopFavor')->where(['shop_id'=>$v['id']])->count();
                }

                // 是否保留布局数据
                if($is_layout_config == 0)
                {
                    unset($v['layout_config']);
                }

                // 认证类型
                if(array_key_exists('auth_type', $v))
                {
                    if(array_key_exists($v['auth_type'], $auth_type))
                    {
                        $v['auth_type_name'] = $auth_type[$v['auth_type']];
                        $v['auth_type_msg'] = '已通过'.$v['auth_type_name'].'实名认证';
                    } else {
                        $v['auth_type_name'] = '';
                        $v['auth_type_msg'] = '';
                    }
                }

                // 店铺分类
                if(array_key_exists('category_id', $v))
                {
                    $v['category_name'] = array_key_exists($v['category_id'], $category) ? $category[$v['category_id']] : '';
                }

                // 状态
                if(array_key_exists('status', $v))
                {
                    $v['status_name'] = array_key_exists($v['status'], $status_list) ? $status_list[$v['status']] : '未知';
                }

                // 结算类型
                if(array_key_exists('settle_type', $v))
                {
                    $v['settle_type_name'] = ($v['settle_type'] == -1 || !array_key_exists($v['settle_type'], $settle_type)) ? '' : $settle_type[$v['settle_type']];
                }

                // 数据模式
                if(array_key_exists('data_model', $v))
                {
                    $v['data_model_name'] = ($v['data_model'] == -1 || !array_key_exists($v['data_model'], $data_model)) ? '' : $data_model[$v['data_model']];
                }

                // logo
                if(array_key_exists('logo', $v))
                {
                    $v['logo'] = ResourcesService::AttachmentPathViewHandle($v['logo']);
                }
                if(array_key_exists('logo_long', $v))
                {
                    $v['logo_long'] = ResourcesService::AttachmentPathViewHandle($v['logo_long']);
                }

                // banner
                if(array_key_exists('banner', $v))
                {
                    $v['banner'] = ResourcesService::AttachmentPathViewHandle($v['banner']);
                }

                // 身份证、企业执照、更多材料附件
                if(array_key_exists('idcard_front', $v))
                {
                    $v['idcard_front'] = ResourcesService::AttachmentPathViewHandle($v['idcard_front']);
                }
                if(array_key_exists('idcard_back', $v))
                {
                    $v['idcard_back'] = ResourcesService::AttachmentPathViewHandle($v['idcard_back']);
                }
                if(array_key_exists('company_license', $v))
                {
                    $v['company_license'] = ResourcesService::AttachmentPathViewHandle($v['company_license']);
                }
                if(array_key_exists('more_prove', $v))
                {
                    $v['more_prove'] = empty($v['more_prove']) ? [] : json_decode($v['more_prove'], true);
                }

                // 截止时间
                if(array_key_exists('expire_time', $v))
                {
                    $v['expire_time_text'] = empty($v['expire_time']) ? '永久' : date('Y-m-d', $v['expire_time']);
                }

                // 客服微信二维码
                if(array_key_exists('service_weixin_qrcode', $v))
                {
                    $v['service_weixin_qrcode'] = ResourcesService::AttachmentPathViewHandle($v['service_weixin_qrcode']);
                }

                // 工作日
                if(array_key_exists('open_week', $v))
                {
                    $v['open_week_name'] = ($v['open_week'] == -1 || !array_key_exists($v['open_week'], $week_list)) ? '' : $week_list[$v['open_week']];
                }
                if(array_key_exists('close_week', $v))
                {
                    $v['close_week_name'] = ($v['close_week'] == -1 || !array_key_exists($v['close_week'], $week_list)) ? '' : $week_list[$v['close_week']];
                }

                // 在线时间
                if(array_key_exists('open_time', $v))
                {
                    $v['open_time'] = substr($v['open_time'], 0, 5);
                }
                if(array_key_exists('close_time', $v))
                {
                    $v['close_time'] = substr($v['close_time'], 0, 5);
                }

                // 保证金缴纳状态
                if(array_key_exists('bond_status', $v))
                {
                    if(array_key_exists($v['bond_status'], $bond_list) && isset($v['bond_expire_time']) && ($v['bond_expire_time'] == 0 || $v['bond_expire_time'] > time()))
                    {
                        $v['bond_status_name'] = $bond_list[$v['bond_status']];
                        $bond_price = (isset($v['bond_price']) && $v['bond_price'] > 0) ? PriceBeautify($v['bond_price']).'π' : '';
                        $v['bond_status_msg'] = '已缴纳'.$bond_price.'保证金';
                    } else {
                        $v['bond_status_name'] = '';
                        $v['bond_status_msg'] = '';
                    }
                }
                // 保证金过期时间
                if(array_key_exists('bond_expire_time', $v))
                {
                    $v['bond_expire_time_text'] = empty($v['bond_expire_time']) ? (isset($v['bond_status']) && $v['bond_status'] == 1 ? '永久' : '') : date('Y-m-d H:i:s', $v['bond_expire_time']);
                }
                // 保证金缴纳时间
                if(array_key_exists('bond_pay_time', $v))
                {
                    $v['bond_pay_time'] = empty($v['bond_pay_time']) ? '' : date('Y-m-d H:i:s', $v['bond_pay_time']);
                }

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
     * 店铺信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-18
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ShopSave($params = [])
    {
        // 参数校验
        $ret = self::ShopSaveParamsCheck($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 附件
        $data_fields = ['logo', 'logo_long', 'banner', 'service_weixin_qrcode', 'idcard_front', 'idcard_back', 'company_license'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);

        // 数据
        $data = [
            // 基础信息
            'user_id'               => intval($params['user_id']),
            'logo'                  => $attachment['data']['logo'],
            'logo_long'             => $attachment['data']['logo_long'],
            'banner'                => $attachment['data']['banner'],
            'name'                  => $params['name'],
            'describe'              => $params['describe'],
            'category_id'           => intval($params['category_id']),
            'data_model'            => isset($params['data_model']) ? intval($params['data_model']) : 0,
            'fail_reason'           => empty($params['fail_reason']) ? '' : $params['fail_reason'],

            // seo信息
            'seo_title'             => empty($params['seo_title']) ? '' : $params['seo_title'],
            'seo_keywords'          => empty($params['seo_keywords']) ? '' : $params['seo_keywords'],
            'seo_desc'              => empty($params['seo_desc']) ? '' : $params['seo_desc'],
        ];

        // 获取用户店铺信息
        $where = ['user_id'=>$data['user_id']];
        $info = Db::name('PluginsShop')->where($where)->find();

        // 用户操作类型
        $user_type = isset($params['user_type']) ? $params['user_type'] : 'user';

        // 管理员操作
        if($user_type == 'admin')
        {
            $data['system_type'] = empty($params['system_type_name']) ? SYSTEM_TYPE : $params['system_type_name'];
            $data['settle_type'] = isset($params['settle_type']) ? intval($params['settle_type']) : -1;
            $data['settle_rate'] = isset($params['settle_rate']) ? floatval($params['settle_rate']) : 0;
            $data['is_user_settle'] = (isset($params['is_user_settle']) && $params['is_user_settle'] == 1) ? 1 : 0;
            $data['expire_time'] = empty($params['expire_time']) ? 0 : strtotime($params['expire_time']);

            // 店铺二级域名
            $data['domain'] = empty($params['domain']) ? '' : trim($params['domain']);
            $data['domain_edit_number'] = empty($params['domain_edit_number']) ? 0 : intval($params['domain_edit_number']);

            // 保证金、只能修改过期时间
            $data['bond_expire_time'] = empty($params['bond_expire_time']) ? 0 : strtotime($params['bond_expire_time']);
        }

        // 是否开启配置项
        if(!empty($params['base_config']))
        {
            // 管理员操作或者用户
            // 身份证信息 1.管理员随时可以编辑，2.用户仅非已审核状态下可以操作
            if($user_type == 'admin' || empty($info) || in_array($info['status'], [0,1,3]))
            {
                // 认证类型
                if((isset($params['base_config']['is_auth_fill_info']) && $params['base_config']['is_auth_fill_info'] == 1) || (isset($params['base_config']['is_auth_upload_pic']) && $params['base_config']['is_auth_upload_pic'] == 1))
                {
                    $data['auth_type'] = isset($params['auth_type']) ? intval($params['auth_type']) : -1;
                }

                // 认证信息
                if(isset($params['base_config']['is_auth_fill_info']) && $params['base_config']['is_auth_fill_info'] == 1)
                {
                    // 身份证
                    if($data['auth_type'] == 0)
                    {
                        $data['idcard_name']    = empty($params['idcard_name']) ? '' : $params['idcard_name'];
                        $data['idcard_number']  = empty($params['idcard_number']) ? '' : $params['idcard_number'];

                    // 执照
                    } else {
                        $data['company_name']    = empty($params['company_name']) ? '' : $params['company_name'];
                        $data['company_number']  = empty($params['company_number']) ? '' : $params['company_number'];
                    }
                }
                
                // 认证照片
                if(isset($params['base_config']['is_auth_upload_pic']) && $params['base_config']['is_auth_upload_pic'] == 1)
                {
                    // 身份证
                    if($data['auth_type'] == 0)
                    {
                        $data['idcard_front']   = $attachment['data']['idcard_front'];
                        $data['idcard_back']    = $attachment['data']['idcard_back'];

                    // 执照
                    } else {
                        $data['company_license']    = $attachment['data']['company_license'];
                    }
                }

                // 更多材料附件
                if(isset($params['base_config']['is_auth_upload_more_prove']) && $params['base_config']['is_auth_upload_more_prove'] == 1)
                {
                    $more_prove = [];
                    if(!empty($params['more_prove']))
                    {
                        if(!is_array($params['more_prove']))
                        {
                            $params['more_prove'] = json_decode($params['more_prove'], true);
                        }
                        foreach($params['more_prove'] as $v)
                        {
                            if(!empty($v['url']) && !empty($v['title']))
                            {
                                $v['url'] = ResourcesService::AttachmentPathHandle($v['url']);
                                $more_prove[] = $v;
                            }
                        }
                    }
                    $data['more_prove'] = empty($more_prove) ? '' : json_encode($more_prove, JSON_UNESCAPED_UNICODE);
                }
            }

            // 店铺信息
            if(isset($params['base_config']['is_shop_info']) && $params['base_config']['is_shop_info'] == 1)
            {
                $data['is_extraction']  = (isset($params['is_extraction']) && $params['is_extraction'] == 1) ? 1 : 0;
                $data['contacts_name']   = empty($params['contacts_name']) ? '' : $params['contacts_name'];
                $data['contacts_tel']    = empty($params['contacts_tel']) ? '' : $params['contacts_tel'];
                $data['province']       = isset($params['province']) ? intval($params['province']) : 0;
                $data['city']           = isset($params['city']) ? intval($params['city']) : 0;
                $data['county']         = isset($params['county']) ? intval($params['county']) : 0;
                $data['address']        = empty($params['address']) ? '' : $params['address'];
                $data['lng']            = empty($params['lng']) ? 0 : floatval($params['lng']);
                $data['lat']            = empty($params['lat']) ? 0 : floatval($params['lat']);
            }

            // 客服信息
            if(isset($params['base_config']['is_service_info']) && $params['base_config']['is_service_info'] == 1)
            {
                $data['open_week']             = isset($params['open_week']) ? intval($params['open_week']) : -1;
                $data['close_week']            = isset($params['close_week']) ? intval($params['close_week']) : -1;
                $data['service_weixin_qrcode'] = $attachment['data']['service_weixin_qrcode'];
                $data['service_qq']            = empty($params['service_qq']) ? '' : $params['service_qq'];
                $data['service_tel']           = empty($params['service_tel']) ? '' : $params['service_tel'];
                $data['open_time']             = isset($params['open_time']) ? $params['open_time'] : '00:00:00';
                $data['close_time']            = isset($params['close_time']) ? $params['close_time'] : '00:00:00';
            }

            // 接收通知
            if(isset($params['base_config']['is_notice_info']) && $params['base_config']['is_notice_info'] == 1)
            {
                $data['notice_mobile']  = empty($params['notice_mobile']) ? '' : $params['notice_mobile'];
                $data['notice_email']   = empty($params['notice_email']) ? '' : $params['notice_email'];
            }
        }

        // 开启事务
        Db::startTrans();

        // 捕获异常
        try {
            if(empty($info))
            {
                // 添加店铺
                $data['status'] = 0;
                $data['add_time'] = time();
                $shop_id = Db::name('PluginsShop')->insertGetId($data);
                if($shop_id <= 0)
                {
                    throw new \Exception('店铺添加失败');
                }
            } else {
                // 更新店铺
                $data['upd_time'] = time();
                // 已拒绝改为待提交
                if($info['status'] == 3 && $user_type == 'user')
                {
                    $data['status'] = 0;
                }
                if(!Db::name('PluginsShop')->where($where)->update($data))
                {
                    throw new \Exception('更新失败');
                }
                $shop_id = $info['id'];
            }

            // 店铺仓库保存
            $ret = self::ShopWarehouseSave($shop_id, $params['base_config']);
            if($ret['code'] != 0)
            {
                throw new \Exception($ret['msg']);
            }

            // 二级域名更新
            if((isset($data['domain']) && $data['domain'] != $info['domain']) || !empty($data['domain']))
            {
                // 更新二级域名文件
                $ret = DomainService::DomainUpdate([
                    'dec_domain'    => self::ShopDomainData($shop_id, empty($info) ? '' : $info['domain']),
                    'inc_domain'    => self::ShopDomainData($shop_id, $data['domain']),
                ]);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }
            }

            // 完成
            Db::commit();
            return DataReturn('操作成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 店铺仓库保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-02-19
     * @desc    description
     * @param   [int]          $shop_id [店铺id]
     * @param   [array]        $config  [插件配置信息]
     */
    public static function ShopWarehouseSave($shop_id, $config)
    {
        // 获取店铺
        $info = Db::name('PluginsShop')->where(['id'=>$shop_id])->find();
        if(empty($info))
        {
            return DataReturn('店铺信息不存在', -1);
        }

        // 基础信息
        $suffix = empty($config['warehouse_name_suffix']) ? '' : $config['warehouse_name_suffix'];
        $data = [
            'shop_id'       => $shop_id,
            'shop_user_id'  => $info['user_id'],
            'name'          => $info['name'].$suffix,
            'level'         => 0,
            'is_enable'     => $info['status'] == 2 ? 1 : 0,
        ];

        // 店铺联系信息
        if(isset($config['is_shop_info']) && $config['is_shop_info'] == 1)
        {
            $data['contacts_name'] = $info['contacts_name'];
            $data['contacts_tel'] = $info['contacts_tel'];
            $data['province'] = $info['province'];
            $data['city'] = $info['city'];
            $data['county'] = $info['county'];
            $data['address'] = $info['address'];
            $data['lng'] = $info['lng'];
            $data['lat'] = $info['lat'];
        }

        // 获取店铺仓库、存在更新不存在则添加
        $warehouse = self::ShopWarehouseInfo($shop_id);
        if(empty($warehouse))
        {
            $data['add_time'] = time();
            $warehouse_id = Db::name('Warehouse')->insertGetId($data);
            if($warehouse_id <= 0)
            {
                return DataReturn('店铺仓库添加失败', -1);
            }
        } else {
            // 仓库启用、如果已经删除则恢复
            if($data['is_enable'] == 1 && !empty($warehouse['is_delete_time']))
            {
                $data['is_delete_time'] = 0;
            }
            $data['upd_time'] = time();
            if(!Db::name('Warehouse')->where(['id'=>$warehouse['id']])->update($data))
            {
                return DataReturn('店铺仓库更新失败', -1);
            }

            // 1. 仓库状态与上一次不一致
            // 2. 状态启用、但是删除已恢复，现已去除删除时间
            // 则同步库存
            if($data['is_enable'] != $warehouse['is_enable'] || array_key_exists('is_delete_time', $data))
            {
                // 仓库商品库存同步
                $ret = WarehouseService::WarehouseGoodsInventorySync($warehouse['id']);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
            }
            $warehouse_id = $warehouse['id'];
        }

        return DataReturn('店铺仓库操作成功', 0, $warehouse_id);
    }

    /**
     * 店铺仓库
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-06
     * @desc    description
     * @param   [int]          $shop_id [店铺id]
     */
    public static function ShopWarehouseInfo($shop_id)
    {
        $where = [
            ['shop_id', '=', $shop_id],
        ];
        return Db::name('Warehouse')->where($where)->find();
    }

    /**
     * 用户店铺仓库
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-06
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function UserShopWarehouseInfo($user_id)
    {
        // 获取店铺
        $data_params = [
            'where'     => [
                'user_id' => $user_id
            ],
            'm'         => 0,
            'n'         => 1,
            'user_type' => 'shop',
        ];
        $ret = self::ShopList($data_params);
        $info = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];
        $data = [];
        if(!empty($info))
        {
            // 获取当前店铺仓库
            $data = self::ShopWarehouseInfo($info['id']);
        }
        return $data;
    }

    /**
     * 店铺保存参数校验
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-07
     * @desc    description
     * @param   [array]          $params    [输入参数]
     */
    public static function ShopSaveParamsCheck($params = [])
    {
        // 请求参数
        $week_values = array_column(BaseService::$plugins_week_list, 'value');
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'base_config',
                'error_msg'         => '配置信息为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'logo',
                'error_msg'         => '请上传店铺logo',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'name',
                'error_msg'         => '请填写店铺名称',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '16',
                'error_msg'         => '店铺名称格式最多16个字符',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'describe',
                'error_msg'         => '请填写店铺简介',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'describe',
                'checked_data'      => '230',
                'error_msg'         => '店铺简介格式最多230个字符',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'category_id',
                'error_msg'         => '请选择店铺分类',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'data_model',
                'checked_data'      => array_column(BaseService::$plugins_shop_data_model_list, 'value'),
                'error_msg'         => '数据模式范围值有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'seo_title',
                'checked_data'      => '100',
                'is_checked'        => 1,
                'error_msg'         => 'SEO标题格式 最多100个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'seo_keywords',
                'checked_data'      => '130',
                'is_checked'        => 1,
                'error_msg'         => 'SEO关键字格式 最多130个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'seo_desc',
                'checked_data'      => '230',
                'is_checked'        => 1,
                'error_msg'         => 'SEO描述格式 最多230个字符',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 基础配置
        $p = [];
        if(!empty($params['base_config']))
        {
            // 认证信息、是否存在认类型
            if(isset($params['auth_type']))
            {
                // 认证类型
                $p[] = [
                    'checked_type'      => 'in',
                    'key_name'          => 'auth_type',
                    'checked_data'      => array_column(BaseService::$plugins_auth_type_list, 'value'),
                    'error_msg'         => '认证类型范围值有误',
                ];

                // 是否需要填写认证信息
                if(isset($params['base_config']['is_auth_fill_info']) && $params['base_config']['is_auth_fill_info'] == 1)
                {
                    // 身份证
                    if($params['auth_type'] == 0)
                    {
                        $p[] = [
                            'checked_type'      => 'length',
                            'key_name'          => 'idcard_name',
                            'checked_data'      => '16',
                            'error_msg'         => '身份证姓名格式1~16个字符',
                        ];
                        $p[] = [
                            'checked_type'      => 'length',
                            'key_name'          => 'idcard_number',
                            'checked_data'      => '18',
                            'error_msg'         => '身份证号码格式1~18个字符',
                        ];

                    // 企业
                    } else {
                        $p[] = [
                            'checked_type'      => 'length',
                            'key_name'          => 'company_name',
                            'checked_data'      => '30',
                            'error_msg'         => '企业名称格式1~30个字符',
                        ];
                        $p[] = [
                            'checked_type'      => 'length',
                            'key_name'          => 'company_number',
                            'checked_data'      => '18',
                            'error_msg'         => '企业统一社会信用代码格式1~18个字符',
                        ];
                    }
                }

                // 是否需要上传认证照片
                if(isset($params['base_config']['is_auth_upload_pic']) && $params['base_config']['is_auth_upload_pic'] == 1)
                {
                    // 身份证
                    if($params['auth_type'] == 0)
                    {
                        $p[] = [
                            'checked_type'      => 'empty',
                            'key_name'          => 'idcard_front',
                            'error_msg'         => '请上传身份证人面图片',
                        ];
                        $p[] = [
                            'checked_type'      => 'empty',
                            'key_name'          => 'idcard_back',
                            'error_msg'         => '请上传身份证国微面图片',
                        ];

                    // 企业
                    } else {
                        $p[] = [
                            'checked_type'      => 'empty',
                            'key_name'          => 'company_license',
                            'error_msg'         => '请上传企业执照图片',
                        ];
                    }
                }
            }

            // 是否需要填写店铺信息
            if(isset($params['base_config']['is_shop_info']) && $params['base_config']['is_shop_info'] == 1)
            {
                // 店铺联系信息
                $p[] = [
                    'checked_type'      => 'empty',
                    'key_name'          => 'contacts_name',
                    'error_msg'         => '联系人格式2~16个字符之间',
                ];
                $p[] = [
                    'checked_type'      => 'empty',
                    'key_name'          => 'contacts_tel',
                    'error_msg'         => '联系电话格式有误',
                ];
                $p[] = [
                    'checked_type'      => 'empty',
                    'key_name'          => 'province',
                    'error_msg'         => '请选择省份',
                ];
                $p[] = [
                    'checked_type'      => 'empty',
                    'key_name'          => 'city',
                    'error_msg'         => '请选择城市',
                ];
                $p[] = [
                    'checked_type'      => 'empty',
                    'key_name'          => 'address',
                    'error_msg'         => '详细地址格式1~80个字符之间',
                ];

                // 地理位置
                if(isset($params['base_config']['is_select_position']) && $params['base_config']['is_select_position'] == 1)
                {
                    $p[] = [
                        'checked_type'      => 'empty',
                        'key_name'          => 'lng',
                        'error_msg'         => '请选择地理位置',
                    ];
                    $p[] = [
                        'checked_type'      => 'empty',
                        'key_name'          => 'lat',
                        'error_msg'         => '请选择地理位置',
                    ];
                }
            }

            // 是否需要填写客服信息
            if(isset($params['base_config']['is_service_info']) && $params['base_config']['is_service_info'] == 1)
            {
                $p[] = [
                    'checked_type'      => 'length',
                    'key_name'          => 'service_qq',
                    'checked_data'      => '30',
                    'is_checked'        => 1,
                    'error_msg'         => '客服QQ格式有误、最多30个字符',
                ];
                $p[] = [
                    'checked_type'      => 'length',
                    'key_name'          => 'service_tel',
                    'checked_data'      => '20',
                    'is_checked'        => 1,
                    'error_msg'         => '客服电话格式格式有误、最多20个字符',
                ];
                $p[] = [
                    'checked_type'      => 'in',
                    'key_name'          => 'open_week',
                    'checked_data'      => $week_values,
                    'error_msg'         => '请选择工作日起始',
                ];
                $p[] = [
                    'checked_type'      => 'in',
                    'key_name'          => 'close_week',
                    'checked_data'      => $week_values,
                    'error_msg'         => '请选择工作日结束',
                ];
                $p[] = [
                    'checked_type'      => 'empty',
                    'key_name'          => 'open_time',
                    'error_msg'         => '请选择在线时间起始',
                ];
                $p[] = [
                    'checked_type'      => 'empty',
                    'key_name'          => 'close_time',
                    'error_msg'         => '请选择在线时间结束',
                ];
            }

            // 是否需要填写接收通知
            if(isset($params['base_config']['is_notice_info']) && $params['base_config']['is_notice_info'] == 1)
            {
                $p[] = [
                    'checked_type'      => 'fun',
                    'key_name'          => 'notice_mobile',
                    'checked_data'      => 'CheckMobile',
                    'is_checked'        => 1,
                    'error_msg'         => '接收通知手机号码格式有误、最多11个字符',
                ];
                $p[] = [
                    'checked_type'      => 'fun',
                    'key_name'          => 'notice_email',
                    'checked_data'      => 'CheckEmail',
                    'is_checked'        => 1,
                    'error_msg'         => '接收通知电子邮箱格式有误、最多60个字符',
                ];
            }
        }
        if(!empty($p))
        {
            $ret = ParamsChecked($params, $p);
            if($ret !== true)
            {
                return DataReturn($ret, -1);
            }
        }
        return DataReturn('success', 0);
    }

    /**
     * 状态操作
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-22
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ShopStatusUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'shop_id',
                'error_msg'         => '店铺id有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'opt_type',
                'checked_data'      => [2,3,4],
                'error_msg'         => '操作范围值有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'base_config',
                'error_msg'         => '配置信息为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取店铺
        $where = [
            'id'    => intval($params['shop_id']),
        ];
        $info = Db::name('PluginsShop')->where($where)->find();
        if(empty($info))
        {
            return DataReturn('店铺不存在', -1);
        }

        // 开启事务
        Db::startTrans();

        // 捕获异常
        try {
            // 操作类型，2通过、3拒绝、4关闭
            if($params['opt_type'] == 2)
            {
                // 操作数据值
                $fail_reason = '';
                $is_enable = 1;
            } else {
                // 请求参数
                $p = [
                    [
                        'checked_type'      => 'length',
                        'key_name'          => 'msg',
                        'checked_data'      => '200',
                        'error_msg'         => '原因格式 最多200个字符',
                    ],
                ];
                $ret = ParamsChecked($params, $p);
                if($ret !== true)
                {
                    throw new \Exception($ret);
                }

                // 操作数据值
                $fail_reason = $params['msg'];
                $is_enable = 0;
            }

            // 更新店铺状态
            $update_data = [
                'fail_reason'   => $fail_reason,
                'status'        => $params['opt_type'],
                'upd_time'      => time(),
            ];
            if(!Db::name('PluginsShop')->where(['id'=>$info['id'],'status'=>$info['status']])->update($update_data))
            {
                throw new \Exception('操作失败');
            }

            // 获取仓库
            $warehouse = self::ShopWarehouseInfo($info['id'], $params['base_config']);
            if(!empty($warehouse))
            {
                // 仓库更新
                $update_data = [
                    'is_enable' => $is_enable,
                    'upd_time'  => time(),
                ];
                if(Db::name('Warehouse')->where(['id'=>$warehouse['id']])->update($update_data) === false)
                {
                    throw new \Exception('店铺仓库更新失败');
                }

                // 仓库商品库存同步
                $ret = WarehouseService::WarehouseGoodsInventorySync($warehouse['id']);
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }
            }

            // 店铺商品处理
            $update_data = [
                'is_shelves'    => ($params['opt_type'] == 2) ? 1 : 0,
                'upd_time'      => time(),
            ];
            if(Db::name('Goods')->where(['shop_id'=>$info['id']])->update($update_data) === false)
            {
                throw new \Exception('店铺商品更新失败');
            }

            // 完成
            Db::commit();
            return DataReturn('操作成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ShopDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'ids',
                'error_msg'         => '店铺id有误',
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

        // 启动事务
        Db::startTrans();

        // 捕获异常
        try {
            // 删除店铺
            if(!Db::name('PluginsShop')->where(['id'=>$params['ids']])->delete())
            {
                throw new \Exception('店铺删除失败');
            }

            // 删除仓库
            $warehouse_ids = Db::name('Warehouse')->where(['shop_id'=>$params['ids']])->column('id');
            if(Db::name('Warehouse')->where(['shop_id'=>$params['ids']])->update(['is_delete_time'=>time(), 'upd_time'=>time()]) === false)
            {
                throw new \Exception('仓库删除失败');
            }
            if(!empty($warehouse_ids))
            {
                foreach($warehouse_ids as $wid)
                {
                    $ret = WarehouseService::WarehouseGoodsInventorySync($wid);
                    if($ret['code'] != 0)
                    {
                        throw new \Exception($ret['msg']);
                    }
                }
            }

            // 删除店铺商品数据
            $goods_ids = Db::name('Goods')->where(['shop_id'=>$params['ids']])->column('id');
            if(!empty($goods_ids))
            {
                // 删除商品操作
                GoodsService::GoodsDeleteHandle($goods_ids);
            }

            // 提交事务
            Db::commit();
            return DataReturn('删除成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 提交审核
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-15
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ShopSubmit($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '数据id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取店铺信息
        $where = [
            'id'        => intval($params['id']),
            'user_id'   => $params['user_id'],
        ];
        $info = Db::name('PluginsShop')->where($where)->field('id,status')->find();
        if(empty($info))
        {
            return DataReturn('店铺信息不存在', -1);
        }
        if(!in_array($info['status'], [0,3]))
        {
            return DataReturn('店铺状态不允许操作', -1);
        }

        // 更新操作
        $data = [
            'status'    => 1,
            'upd_time'  => time(),
        ];
        if(Db::name('PluginsShop')->where($where)->update($data))
        {
            return DataReturn('提交成功', 0);
        }
        return DataReturn('提交失败', -100);
    }

    /**
     * 用户店铺信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-01
     * @desc    description
     * @param   [int|string]   $where_value  [数据id]
     * @param   [int|string]   $where_field  [字段名称（默认user_id）]
     * @param   [string]       $field        [需要读取的字段（默认全部）]
     * @param   [array]        $params       [输入参数]
     */
    public static function UserShopInfo($where_value, $where_field = 'user_id', $field = '*', $params = [])
    {
        $data = Db::name('PluginsShop')->where([$where_field=>$where_value, 'status'=>2])->field($field)->find();
        if(!empty($data))
        {
            $res = self::DataHandle([$data], $params);
            return empty($res[0]) ? [] : $res[0];
        }
        return [];
    }

    /**
     * 获取有效店铺
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-28
     * @desc    description
     * @param   [int]          $shop_id [店铺id]
     * @param   [array]        $config  [插件配置]
     */
    public static function ShopValidInfo($shop_id, $config = [])
    {
        $where = [
            ['id', '=', intval($shop_id)],
            ['status', '=', 2],
        ];
        $expire_where = self::ShopExpireTimeWhere();

        // 获取列表
        $data_params = [
            'm'                 => 0,
            'n'                 => 1,
            'where'             => $where,
            'expire_where'      => $expire_where,
            'is_shop_favor'     => 1,
            'is_layout_config'  => 1,
        ];
        $ret = self::ShopList($data_params);
        $shop = [];
        if(!empty($ret['data']) && !empty($ret['data'][0]))
        {
            $shop = $ret['data'][0];

            // 默认banner
            if(empty($shop['banner']))
            {
                $shop['banner'] = MyConfig('shopxo.attachment_host').BaseService::$default_banner;
            }

            // 客服系统
            $chat = BaseService::ChatUrl($config, $shop['user_id']);
            if(!empty($chat))
            {
                $shop['chat_info'] = $chat;
            }
        }
        return $shop;
    }

    /**
     * 布局保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function LayoutDesignSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 获取店铺信息
        $info = self::UserShopInfo($params['user_id'], 'user_id', 'id,name,status,data_model', ['user_type'=>'shop']);
        if(empty($info))
        {
            return DataReturn('店铺信息不存在', -1);
        }
        if($info['status'] != 2)
        {
            return DataReturn('店铺状态不允许操作['.BaseService::$plugins_shop_status_list[$info['status']]['name'].']', -1);
        }
        if($info['data_model'] != 1)
        {
            return DataReturn('请先切换拖拽设计模式', -1);
        }

        // 布局数据
        $config = empty($params['config']) ? '' : BaseLayout::ConfigSaveHandle($params['config']);
        $data = [
            'layout_config' => $config,
            'upd_time'      => time(),
        ];
        if(Db::name('PluginsShop')->where(['id'=>$info['id']])->update($data))
        {
            return DataReturn('操作成功', 0);
        }
        return DataReturn('操作失败', -1);
    }

    /**
     * 二级域名查询
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function DomainQuery($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'domain',
                'error_msg'         => '请输入二级域名',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'base_config',
                'error_msg'         => '配置信息为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        $params['domain'] = trim($params['domain']);

        // 是否已开启二级域名设置
        if(!isset($params['base_config']['is_shop_second_domain_set']) || $params['base_config']['is_shop_second_domain_set'] != 1)
        {
            return DataReturn('未开启二级域名设置、请联系管理员！', -1);
        }

        // 获取店铺信息
        $info = self::UserShopInfo($params['user_id'], 'user_id', 'id,name,status,domain,domain_edit_number', ['user_type'=>'shop']);
        if(empty($info))
        {
            return DataReturn('店铺信息不存在', -1);
        }
        if($info['status'] != 2)
        {
            return DataReturn('店铺状态不允许操作['.BaseService::$plugins_shop_status_list[$info['status']]['name'].']', -1);
        }
        $can_number = BaseService::ShopSecondDomainCanEditNumber($params['base_config'], $info['domain_edit_number']);
        if($can_number <= 0)
        {
            return DataReturn('店铺二级域名可设置次数已用完！', -1);
        }
        if($params['domain'] == $info['domain'])
        {
            return DataReturn('不能与当前店铺二级域名相同', -1);
        }

        // 二级域名是否禁止
        if(in_array($params['domain'], ['www']) || (!empty($params['base_config']['shop_second_domain_prohibit']) && in_array($params['domain'], explode(',', $params['base_config']['shop_second_domain_prohibit']))))
        {
            return DataReturn('该二级域名不可用、请更换一个再试！', -1);
        }

        // 查询二级域名是否已经存在
        $where = [
            ['domain', '=', $params['domain']],
            ['id', '<>', $info['id']],
        ];
        $count = (int) Db::name('PluginsShop')->where($where)->count();
        if($count > 0)
        {
            return DataReturn('该二级域名已存在、请更换一个再试！', -1);
        }

        $result = [
            'id'            => $info['id'],
            'number'        => $info['domain_edit_number'],
            'shop_domain'   => $info['domain'],
            'params_domain' => $params['domain'],
        ];
        return DataReturn('恭喜您、该二级域名可以使用！', 0, $result);
    }

    /**
     * 二级域名绑定
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function DomainBind($params = [])
    {
        // 查询验证
        $ret = self::DomainQuery($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }
        $info = $ret['data'];

        // 开启事务
        Db::startTrans();

        // 捕获异常
        try {
            // 更新域名
            $data = [
                'domain'                => $info['params_domain'],
                'domain_edit_number'    => $info['number']+1,
                'upd_time'              => time(),
            ];
            if(!Db::name('PluginsShop')->where(['id'=>$info['id']])->update($data))
            {
                throw new \Exception('店铺更新失败');
            }

            // 更新二级域名文件
            $ret = DomainService::DomainUpdate([
                'dec_domain'    => self::ShopDomainData($info['id'], $info['shop_domain']),
                'inc_domain'    => self::ShopDomainData($info['id'], $info['params_domain']),
            ]);
            if($ret['code'] != 0)
            {
                throw new \Exception($ret['msg']);
            }

            // 完成
            Db::commit();
            return DataReturn('操作成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 店铺二级域名数据生成
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-10
     * @desc    description
     * @param   [int]          $shop_id [店铺id]
     * @param   [string]       $domain  [店铺二级域名]
     */
    public static function ShopDomainData($shop_id, $domain)
    {
        return [$domain => 'plugins/index/pluginsname/shop/pluginscontrol/index/pluginsaction/detail/id/'.$shop_id];
    }

    /**
     * 保证金缴纳
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function BondPay($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'base_config',
                'error_msg'         => '配置信息为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否开启保证金缴纳操作
        if(!isset($params['base_config']['is_shop_bond']) || $params['base_config']['is_shop_bond'] != 1)
        {
            return DataReturn('未开启保证金缴纳、请联系管理员！', -1);
        }

        // 获取店铺信息
        $info = self::UserShopInfo($params['user_id'], 'user_id', 'id,user_id,name,status,category_id,bond_status,bond_price,bond_expire_time,bond_pay_time', ['user_type'=>'shop']);
        if(empty($info))
        {
            return DataReturn('店铺信息不存在', -1);
        }
        // 是否已经缴纳过、过期则可以再次缴纳
        if($info['bond_status'] == 1 && ($info['bond_expire_time'] == 0 || $info['bond_expire_time'] > time()))
        {
            return DataReturn('店铺已缴纳保证金、无需重复操作', -1);
        }

        // 开启事务
        Db::startTrans();

        // 捕获异常
        try {
            // 更新数据
            $shop_bond_price = BaseService::ShopBondPrice($params['base_config'], $info);
            $shop_bond_expire_time = empty($params['base_config']['shop_bond_expire_time']) ? 0 : intval($params['base_config']['shop_bond_expire_time']);
            $data = [
                'bond_status'       => 1,
                'bond_price'        => $shop_bond_price,
                'bond_expire_time'  => empty($shop_bond_expire_time) ? 0 : time()+($shop_bond_expire_time*60),
                'bond_pay_time'     => time(),
            ];
            if(!Db::name('PluginsShop')->where(['id'=>$info['id']])->update($data))
            {
                throw new \Exception('店铺更新失败');
            }

            // 钱包变更、金额大于0则更新钱包
            if($shop_bond_price > 0)
            {
                // 用户钱包
                $user_wallet = WalletService::UserWallet($info['user_id']);
                if($user_wallet['code'] != 0)
                {
                    throw new \Exception($user_wallet['msg']);
                }
                if($user_wallet['data']['normal_money'] < $shop_bond_price)
                {
                    throw new \Exception('钱包余额不足、请先充值['.$user_wallet['data']['normal_money'].'<'.$shop_bond_price.']');
                }

                // 钱包变更、有效金额减少
                $ret = WalletService::UserWalletMoneyUpdate($info['user_id'], $shop_bond_price, 0, 'normal_money', 0, '多商户保证金缴纳');
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }

                // 钱包变更、冻结金额增加
                $ret = WalletService::UserWalletMoneyUpdate($info['user_id'], $shop_bond_price, 1, 'frozen_money', 0, '多商户保证金缴纳');
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }
            }

            // 完成
            Db::commit();
            return DataReturn('操作成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 保证金退回
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-06-24
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function BondGoback($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '用户信息有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'base_config',
                'error_msg'         => '配置信息为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否开启保证金退回操作
        if(!isset($params['base_config']['is_shop_bond_goback']) || $params['base_config']['is_shop_bond_goback'] != 1)
        {
            return DataReturn('未开启保证金退回、请联系管理员！', -1);
        }

        // 获取店铺信息
        $info = self::UserShopInfo($params['user_id'], 'user_id', 'id,user_id,name,status,bond_status,bond_price,bond_expire_time,bond_pay_time', ['user_type'=>'shop']);
        if(empty($info))
        {
            return DataReturn('店铺信息不存在', -1);
        }
        // 是否已经缴纳过
        if($info['bond_status'] != 1)
        {
            return DataReturn('店铺未缴纳保证金、无需重复操作', -1);
        }

        // 是否存在有效订单
        if(isset($params['base_config']['is_shop_bond_goback_not_valid_order']) && $params['base_config']['is_shop_bond_goback_not_valid_order'] == 1)
        {
            $where = [
                ['shop_id', '=', $info['id']],
                ['status', 'not in', [4,5,6]],
            ];
            $count = OrderService::OrderTotal($where);
            if($count > 0)
            {
                return DataReturn('店铺有'.$count.'条订单进行中、不可操作！', -1);
            }
        }

        // 有效商品限量
        if(isset($params['base_config']['is_shop_bond_goback_valid_goods_limit']) && $params['base_config']['is_shop_bond_goback_valid_goods_limit'] == 1)
        {
            // 限制总数
            $goods_max_number = empty($params['base_config']['shop_not_pay_bond_can_release_goods_number']) ? 0 : intval($params['base_config']['shop_not_pay_bond_can_release_goods_number']);
            // 已发布总数
            $where = [
                ['shop_id', '=', $info['id']],
                ['is_delete_time', '=', 0],
            ];
            $goods_count = ShopGoodsService::GoodsTotal($where);
            if($goods_count > $goods_max_number)
            {
                return DataReturn('店铺商品超过免费('.$goods_count.'>'.$goods_max_number.')条限制、请先删除部分商品再试！', -1);
            }
        }

        // 开启事务
        Db::startTrans();

        // 捕获异常
        try {
            // 更新数据
            $data = [
                'bond_status'       => 2,
                'bond_price'        => 0,
                'bond_expire_time'  => 0,
                'bond_pay_time'     => 0,
            ];
            if(!Db::name('PluginsShop')->where(['id'=>$info['id']])->update($data))
            {
                throw new \Exception('店铺更新失败');
            }

            // 保证金为0则不操作钱包
            if($info['bond_price'] > 0)
            {
                // 钱包变更、冻结金额减少
                $ret = WalletService::UserWalletMoneyUpdate($info['user_id'], $info['bond_price'], 0, 'frozen_money', 0, '多商户保证金退回');
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }

                // 钱包变更、有效金额增加
                $ret = WalletService::UserWalletMoneyUpdate($info['user_id'], $info['bond_price'], 1, 'normal_money', 0, '多商户保证金退回');
                if($ret['code'] != 0)
                {
                    throw new \Exception($ret['msg']);
                }
            }

            // 完成
            Db::commit();
            return DataReturn('操作成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 获取首页店铺数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-05-15
     * @desc    description
     * @param   [array]          $config [插件配置信息]
     */
    public static function HomeShopList($config)
    {
        // 获取列表
        $home_data_list_number = empty($config['home_data_list_number']) ? 10 : intval($config['home_data_list_number']);
        $ret = self::SearchList(['n'=>$home_data_list_number]);
        return empty($ret['data']) || empty($ret['data']['data']) ? [] : $ret['data']['data'];
    }
}
?>