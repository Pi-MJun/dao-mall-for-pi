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
use app\service\ResourcesService;
use app\service\RegionService;
use app\plugins\distribution\service\BaseService;

/**
 * 分销 - 取货点服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class ExtractionService
{
    /**
     * 订单发货 取货码处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-03
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function OrderExtractionSuccessHandle($params = [])
    {
        $data = [
            'status'    => 1,
            'upd_time'  => time(),
        ];
        if(Db::name('PluginsDistributionUserSelfExtractionOrder')->where(['order_id'=>intval($params['order_id'])])->update($data))
        {
            return DataReturn('取货点订单更新成功', 0);
        }
        return DataReturn('取货点订单更新失败', -100);
    }

    /**
     * 获取有效的取货点列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ExtractionList($params = [])
    {
        $data = Db::name('PluginsDistributionUserSelfExtraction')->where(['status'=>1])->select()->toArray();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                $v = array_merge($v, self::DataHandle($v));
            }
        }
        return DataReturn('处理成功', 0, $data);
    }

    /**
     * 获取用户取货点数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function ExtractionData($user_id)
    {
        $data = Db::name('PluginsDistributionUserSelfExtraction')->where(['user_id'=>intval($user_id)])->find();
        return DataReturn('处理成功', 0, self::DataHandle($data));
    }

    /**
     * 取货点数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-03
     * @desc    description
     * @param   [arrat]          $data [需要处理的数据]
     */
    public static function DataHandle($data)
    {
        if(!empty($data))
        {
            // logo
            $data['logo'] = ResourcesService::AttachmentPathViewHandle($data['logo']);

            // 地区
            $data['province_name'] = RegionService::RegionName($data['province']);
            $data['city_name'] = RegionService::RegionName($data['city']);
            $data['county_name'] = RegionService::RegionName($data['county']);
        }
        return $data;
    }

    /**
     * 用户取货点保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-02
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ExtractionSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'alias',
                'checked_data'      => '16',
                'is_checked'        => 1,
                'error_msg'         => '别名格式最多 16 个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '2,16',
                'error_msg'         => '联系人格式 2~16 个字符之间',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'tel',
                'error_msg'         => '联系电话不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'province',
                'error_msg'         => '省不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'city',
                'error_msg'         => '城市不能为空',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'county',
                'error_msg'         => '区/县不能为空',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'address',
                'checked_data'      => '1,80',
                'error_msg'         => '详细地址格式 1~80 个字符之间',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 联系电话格式校验
        if(!CheckMobile($params['tel']) && !CheckTel($params['tel']))
        {
            return DataReturn('联系电话格式有误', -1);
        }

        // 获取地址信息
        $temp = self::ExtractionData($params['user']['id']);

        // 其它附件
        $data_fields = ['logo'];
        $attachment = ResourcesService::AttachmentParams($params, $data_fields);
        if($attachment['code'] != 0)
        {
            return $attachment;
        }
        
        // 操作数据
        $data = [
            'logo'          => $attachment['data']['logo'],
            'alias'         => $params['alias'],
            'name'          => $params['name'],
            'tel'           => $params['tel'],
            'province'      => intval($params['province']),
            'city'          => intval($params['city']),
            'county'        => intval($params['county']),
            'address'       => $params['address'],
            'lng'           => isset($params['lng']) ? floatval($params['lng']) : 0,
            'lat'           => isset($params['lat']) ? floatval($params['lat']) : 0,
        ];

        // 坐标转换
        if($data['lng'] > 0 && $data['lat'] > 0 && in_array(APPLICATION_CLIENT_TYPE, MyConfig('shopxo.coordinate_transformation')))
        {
            $coordinate = (new \base\GeoTransUtil())->GcjToBd($data['lng'], $data['lat']);
            $data['lng'] = $coordinate['lng'];
            $data['lat'] = $coordinate['lat'];
        }

        // 添加/更新数据
        Db::startTrans();
        if(empty($temp['data']))
        {
            $data['status'] = 0;
            $data['user_id'] = $params['user']['id'];
            $data['add_time'] = time();
            if(Db::name('PluginsDistributionUserSelfExtraction')->insertGetId($data) > 0)
            {
                Db::commit();
                return DataReturn('申请成功', 0);
            } else {
                Db::rollback();
                return DataReturn('申请失败', -100);
            }
        } else {
            // 编辑重新走审核流程
            $data['status'] = 0;
            $data['upd_time'] = time();
            if(Db::name('PluginsDistributionUserSelfExtraction')->where(['user_id'=>$params['user']['id']])->update($data))
            {
                Db::commit();
                return DataReturn('更新成功', 0);
            } else {
                Db::rollback();
                return DataReturn('更新失败', -100);
            }
        }
    }

    /**
     * 自提订单添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-03
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ExtractionInsert($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'order_id',
                'error_msg'         => '订单id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id',
                'error_msg'         => '取货点用户id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'self_extraction_id',
                'error_msg'         => '取货点id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 操作数据
        $data = [
            'user_id'               => $params['user_id'],
            'order_id'              => $params['order_id'],
            'self_extraction_id'    => $params['self_extraction_id'],
            'status'                => 0,
            'add_time'              => time(),
        ];

        // 添加
        if(Db::name('PluginsDistributionUserSelfExtractionOrder')->insertGetId($data) > 0)
        {
            return DataReturn('添加成功', 0);
        }
        return DataReturn('取货点订单添加失败', -100);
    }

    /**
     * 取货点审核
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ExtractionAudit($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '数据id有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'type',
                'checked_data'      => [1,2],
                'error_msg'         => '操作类型有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        if($params['type'] == 0)
        {
            // 请求参数
            $p = [
                [
                    'checked_type'      => 'length',
                    'key_name'          => 'fail_reason',
                    'checked_data'      => '2,200',
                    'is_checked'        => 1,
                    'error_msg'         => '拒绝原因格式 2~200 个字符之间',
                ],
            ];
            $ret = ParamsChecked($params, $p);
            if($ret !== true)
            {
                return DataReturn($ret, -1);
            }
        }

        // 开始处理
        $data = [
            'status'        => intval($params['type']),
            'fail_reason'   => empty($params['fail_reason']) ? '' : $params['fail_reason'],
            'upd_time'      => time(),
        ];
        if(Db::name('PluginsDistributionUserSelfExtraction')->where(['id'=>intval($params['id'])])->update($data))
        {
            return DataReturn('操作成功', 0);
        }
        return DataReturn('操作失败', -100);
    }

    /**
     * 取货点解约
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ExtractionRelieve($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '数据id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 开始处理
        $data = [
            'status'        => 3,
            'fail_reason'   => '管理员解约',
            'upd_time'      => time(),
        ];
        if(Db::name('PluginsDistributionUserSelfExtraction')->where(['id'=>intval($params['id'])])->update($data))
        {
            return DataReturn('操作成功', 0);
        }
        return DataReturn('操作失败', -100);
    }

    /**
     * 取货点删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ExtractionDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '数据id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 开始删除
        if(Db::name('PluginsDistributionUserSelfExtraction')->where(['id'=>intval($params['id'])])->delete())
        {
            return DataReturn('删除成功', 0);
        }
        return DataReturn('删除失败或数据不存在', -100);
    }

    /**
     * 取货点切换保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-14
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function ExtractionSwitchSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'isset',
                'key_name'          => 'id',
                'error_msg'         => '数据id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 操作数据
        $data = [
            'user_id'       => $params['user']['id'],
            'address_key'   => intval($params['id']),
            'address_oldid' => empty($params['value']) ? 0 : intval($params['value']),
        ];

        // 获取数据
        $status = false;
        $info = self::UserCustomExtractionAddress($data['user_id']);
        if(empty($info))
        {
            $data['add_time'] = time();
            if(Db::name('PluginsDistributionCustomExtractionAddress')->insertGetId($data) > 0)
            {
                $status = true;
            }
        } else {
            $data['upd_time'] = time();
            if(Db::name('PluginsDistributionCustomExtractionAddress')->where(['id'=>$info['id']])->update($data))
            {
                $status = true;
            }
        }
        if($status)
        {
            return DataReturn('操作成功', 0);
        }
        return DataReturn('操作失败', -1);
    }

    /**
     * 获取用户自定义地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-14
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function UserCustomExtractionAddress($user_id)
    {
        return Db::name('PluginsDistributionCustomExtractionAddress')->where(['user_id'=>$user_id])->find();
    }
}
?>