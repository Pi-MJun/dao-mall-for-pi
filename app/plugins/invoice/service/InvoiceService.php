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
namespace app\plugins\invoice\service;

use think\facade\Db;
use app\service\UserService;
use app\service\ResourcesService;
use app\plugins\invoice\service\BaseService;

/**
 * 发票 - 开票管理服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class InvoiceService
{
    /**
     * 数据列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params  [输入参数]
     */
    public static function InvoiceList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;

        // 获取订单
        $data = Db::name('PluginsInvoice')->where($where)->field($field)->limit($m, $n)->order($order_by)->select()->toArray();
        return DataReturn('处理成功', 0, self::DataHandle($data, $params));
    }

    /**
     * 数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-18
     * @desc    description
     * @param   [array]          $data      [数据]
     * @param   [array]          $params    [输入参数]
     */
    public static function DataHandle($data, $params = [])
    {
        if(!empty($data))
        {
            // 字段列表
            $keys = ArrayKeys($data);
            
            // 用户列表
            if(in_array('user_id', $keys) && isset($params['is_public']) && $params['is_public'] == 0)
            {
                $user_list = UserService::GetUserViewInfo(array_column($data, 'user_id'));
            }

            foreach($data as &$v)
            {
                // 用户信息
                if(isset($v['user_id']))
                {
                    if(isset($params['is_public']) && $params['is_public'] == 0)
                    {
                        $v['user'] = (!empty($user_list) && is_array($user_list) && array_key_exists($v['user_id'], $user_list)) ? $user_list[$v['user_id']] : [];
                    }
                }

                // 发票附件
                if(isset($v['electronic_invoice']))
                {
                    $v['electronic_invoice'] = empty($v['electronic_invoice']) ? [] : json_decode($v['electronic_invoice'], true);
                    if(!empty($v['electronic_invoice']))
                    {
                        foreach($v['electronic_invoice'] as &$vi)
                        {
                            $vi['url'] = ResourcesService::AttachmentPathViewHandle($vi['url']);
                        }
                    }
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
        return $data;
    }

    /**
     * 订单总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function InvoiceTotal($where = [])
    {
        return (int) Db::name('PluginsInvoice')->where($where)->count();
    }

    /**
     * 保存数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function InvoiceSaveBaseDataHandle($params = [])
    {
        $total_price = 0;
        $business_ids = empty($params['ids']) ? '' : (is_array($params['ids']) ? implode(',', $params['ids']) : urldecode($params['ids']));
        $business_type = empty($params['type']) ? 0 : intval($params['type']);

        // 首次计算总额
        if(empty($params['id']) && !empty($business_ids))
        {
            // 插件配置信息
            $base = BaseService::BaseConfig();

            // 是否下单即可开票
            $is_order_submit_invoice = (isset($base['data']['is_order_submit_invoice']) && $base['data']['is_order_submit_invoice'] == 1) ? 1 : 0;

            // 计算总额
            switch($business_type)
            {
                // 订单开票
                case 0 :
                    $where = BaseService::UserInvoiceOrderWhere($params);
                    $where[] = ['id', 'in', $business_ids];
                    $order = Db::name('Order')->field('id,pay_price,total_price,refund_price')->where($where)->select()->toArray();
                    if(!empty($order))
                    {
                        $price_field = ($is_order_submit_invoice == 1) ? 'total_price' : 'pay_price';
                        $invoice_price = array_sum(array_column($order, $price_field));
                        $refund_price = array_sum(array_column($order, 'refund_price'));
                        $total_price = $invoice_price-$refund_price;
                        $business_ids = implode(',', array_column($order, 'id'));
                    }
                    break;
            }
        } else {
            $total_price = isset($params['total_price']) ? $params['total_price'] : 0;
        }
        return [
            'total_price'   => $total_price,
            'business_ids'  => $business_ids,
            'business_type' => $business_type,
        ];
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function InvoiceSave($params = [])
    {
        // 参数校验
        $ret = self::InvoiceSaveParamsCheck($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 数据
        $data = [
            'invoice_type'      => intval($params['invoice_type']),
            'apply_type'        => intval($params['apply_type']),
            'invoice_content'   => empty($params['invoice_content']) ? '' : $params['invoice_content'],
            'invoice_title'     => $params['invoice_title'],
            'invoice_code'      => empty($params['invoice_code']) ? '' : $params['invoice_code'],
            'email'             => empty($params['email']) ? '' : $params['email'],
            'name'              => empty($params['name']) ? '' : $params['name'],
            'tel'               => empty($params['tel']) ? '' : $params['tel'],
            'address'           => empty($params['address']) ? '' : $params['address'],
            'invoice_bank'      => empty($params['invoice_bank']) ? '' : $params['invoice_bank'],
            'invoice_account'   => empty($params['invoice_account']) ? '' : $params['invoice_account'],
            'invoice_tel'       => empty($params['invoice_tel']) ? '' : $params['invoice_tel'],
            'invoice_address'   => empty($params['invoice_address']) ? '' : $params['invoice_address'],
            'user_note'         => empty($params['user_note']) ? '' : $params['user_note'],
            'status'            => 0,
        ];

        // 开启事务
        Db::startTrans();

        // 捕获异常
        try {
            if(empty($params['id']))
            {
                // 基础数据计算
                $save_base_data = self::InvoiceSaveBaseDataHandle($params);
                if(empty($save_base_data['total_price']))
                {
                    return DataReturn('开票金额必须大于0', -1);
                }
                if(empty($save_base_data['business_ids']))
                {
                    return DataReturn('业务id不能为空', -1);
                }
                $business_ids = array_unique(explode(',', $save_base_data['business_ids']));

                // 业务id是否存在
                $count = (int) Db::name('PluginsInvoice')->alias('pi')->join('plugins_invoice_value piv', 'pi.id=piv.invoice_id')->where(['pi.business_type'=>$save_base_data['business_type'], 'piv.business_id'=>$business_ids])->count();
                if($count > 0)
                {
                    return DataReturn('业务id已开票、不可重复操作', -1);
                }

                // 不可编辑的字段数据
                $data['user_id'] = $params['user']['id'];
                $data['business_type'] = $save_base_data['business_type'];
                $data['total_price'] = $save_base_data['total_price'];
                $data['add_time'] = time();
                $invoice_id = Db::name('PluginsInvoice')->insertGetId($data);
                if($invoice_id <= 0)
                {
                    throw new \Exception('添加失败');
                }

                // 业务id添加
                $business_data = [];
                foreach($business_ids as $bid)
                {
                    $business_data[] = [
                        'business_id'   => $bid,
                        'invoice_id'    => $invoice_id,
                        'add_time'      => time(),
                    ];
                }
                if(Db::name('PluginsInvoiceValue')->insertAll($business_data) < count($business_data))
                {
                    throw new \Exception('业务id添加失败');
                }
            } else {
                // 只能编辑待审核和已拒绝数据
                $info = Db::name('PluginsInvoice')->where(['id'=>intval($params['id'])])->find();
                if(empty($info))
                {
                    return DataReturn('数据不存在', -1);
                }
                if(!in_array($info['status'], [0,3]))
                {
                    return DataReturn('数据状态不可操作['.BaseService::$invoice_status_list[$info['status']]['name'].']', -1);
                }

                $data['upd_time'] = time();
                if(!Db::name('PluginsInvoice')->where(['id'=>intval($params['id'])])->update($data))
                {
                    throw new \Exception('编辑失败');
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
     * 保存参数校验
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function InvoiceSaveParamsCheck($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'in',
                'key_name'          => 'invoice_type',
                'checked_data'      => array_column(BaseService::CanInvoiceTypeList(), 'id'),
                'error_msg'         => '发票类型范围值有误',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'apply_type',
                'checked_data'      => array_column(BaseService::$apply_type_list, 'id'),
                'error_msg'         => '申请类型范围值有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'invoice_title',
                'checked_data'      => '200',
                'error_msg'         => '请填写发票抬头、最多200个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'invoice_content',
                'checked_data'      => '230',
                'is_checked'        => 1,
                'error_msg'         => '发票内容最多230个字符',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'user_note',
                'checked_data'      => '230',
                'is_checked'        => 1,
                'error_msg'         => '备注最多230个字符',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 参数判断处理
        $p = [];

        // 选择专用发票、申请类型必须是企业
        if($params['invoice_type'] == 2 && $params['apply_type'] != 1)
        {
            return DataReturn('专用发票申请类型必须为企业', -1);
        }

        // 申请类型为企业
        if($params['apply_type'] == 1)
        {
            $p[] = [
                'checked_type'      => 'length',
                'key_name'          => 'invoice_code',
                'checked_data'      => '160',
                'error_msg'         => '请填写企业统一社会信用代码或纳税识别号、最多160个字符',
            ]; 
        }

        // 增值税普通电子发票
        if($params['invoice_type'] == 0)
        {
           $p[] = [
                'checked_type'      => 'length',
                'key_name'          => 'email',
                'checked_data'      => '0,60',
                'error_msg'         => '电子邮箱、最多60个字符',
            ]; 
        }

        // 增值税普通纸质发票
        // 增值税专业纸质发票
        if(in_array($params['invoice_type'], [1,2]))
        {
            $p[] = [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '2,30',
                'error_msg'         => '收件人姓名格式 2~30 个字符之间',
            ];
            $p[] = [
                'checked_type'      => 'length',
                'key_name'          => 'tel',
                'checked_data'      => '6,30',
                'error_msg'         => '请填写收件人电话 6~15 个字符',
            ];
            $p[] = [
                'checked_type'      => 'length',
                'key_name'          => 'address',
                'checked_data'      => '1,230',
                'error_msg'         => '请填写收件人地址、最多230个字符',
            ];
        }
        
        // 增值税专业纸质发票
        if($params['invoice_type'] == 2)
        {
            $p[] = [
                'checked_type'      => 'length',
                'key_name'          => 'invoice_bank',
                'checked_data'      => '200',
                'error_msg'         => '请填写企业开户行名称、最多200个字符',
            ];
            $p[] = [
                'checked_type'      => 'length',
                'key_name'          => 'invoice_account',
                'checked_data'      => '160',
                'error_msg'         => '请填写企业开户帐号、最多160个字符',
            ];
            $p[] = [
                'checked_type'      => 'length',
                'key_name'          => 'invoice_tel',
                'checked_data'      => '6,15',
                'error_msg'         => '请填写企业联系电话 6~15 个字符',
            ];
            $p[] = [
                'checked_type'      => 'length',
                'key_name'          => 'invoice_address',
                'checked_data'      => '230',
                'error_msg'         => '请填写企业注册地址、最多230个字符',
            ];
        }
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 用户信息
        if(empty($params['id']) && empty($params['user']))
        {
            return DataReturn('用户信息有误', -1);
        }

        return DataReturn('success', 0);
    }

    /**
     * 审核
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function InvoiceAudit($params = [])
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
                'key_name'          => 'type',
                'checked_data'      => array_column(BaseService::$audit_type_list, 'id'),
                'error_msg'         => '操作类型范围值有误',
            ],
        ];
        // 拒绝必须填写原因
        if(!isset($params['type']) && $params['type'] == 0)
        {
            $p[] = [
                'checked_type'      => 'length',
                'key_name'          => 'refuse_reason',
                'checked_data'      => '230',
                'error_msg'         => '请填写拒绝原因、最多230个字符',
            ];
        }
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 状态是否可以操作
        $info = Db::name('PluginsInvoice')->where(['id'=>intval($params['id'])])->find();
        if(empty($info))
        {
            return DataReturn('数据不存在', -1);
        }
        if($info['status'] != 0)
        {
            return DataReturn('数据状态不可操作['.BaseService::$invoice_status_list[$info['status']]['name'].']', -1);
        }

        // 开始处理
        $data = [
            'upd_time'  => time(),
        ];
        if($params['type'] == 0)
        {
            $data['status'] = 3;
            $data['refuse_reason'] = empty($params['refuse_reason']) ? '' : $params['refuse_reason'];
        } else {
            $data['status'] = 1;
        }
        $where = ['id'=>intval($params['id']), 'status'=>0];
        if(!Db::name('PluginsInvoice')->where($where)->update($data))
        {
            return DataReturn('审核失败', -100);
        }
        return DataReturn('审核成功', 0);
    }

    /**
     * 开具发票
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-07-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function InvoiceIssue($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 状态是否可以操作
        $info = Db::name('PluginsInvoice')->where(['id'=>intval($params['id'])])->find();
        if(empty($info))
        {
            return DataReturn('数据不存在', -1);
        }
        if(!in_array($info['status'], [1,2]))
        {
            return DataReturn('数据状态不可操作['.BaseService::$invoice_status_list[$info['status']]['name'].']', -1);
        }

        // 数据校验
        if($info['invoice_type'] == 0)
        {
            $p = [
                [
                    'checked_type'      => 'empty',
                    'key_name'          => 'electronic_invoice',
                    'error_msg'         => '请上传发票附件',
                ],
                [
                    'checked_type'      => 'is_array',
                    'key_name'          => 'electronic_invoice',
                    'error_msg'         => '发票附件必须数组格式',
                ],
            ];
        } else {
            $p = [
                [
                    'checked_type'      => 'length',
                    'key_name'          => 'express_name',
                    'checked_data'      => '60',
                    'error_msg'         => '快递名称格式最多60个字符',
                ],
                [
                    'checked_type'      => 'length',
                    'key_name'          => 'express_number',
                    'checked_data'      => '60',
                    'error_msg'         => '快递单号格式最多60个字符',
                ],
            ];
        }
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 存储数据
        $data = [
            'status'    => 2,
            'upd_time'  => time(),
        ];
        if($info['invoice_type'] == 0)
        {
            foreach($params['electronic_invoice'] as &$v)
            {
                $v['url'] = ResourcesService::AttachmentPathHandle($v['url']);
            }
            $data['electronic_invoice'] = json_encode($params['electronic_invoice'], JSON_UNESCAPED_UNICODE);
        } else {
            $data['express_name'] = $params['express_name'];
            $data['express_number'] = $params['express_number'];
        }
        if(!Db::name('PluginsInvoice')->where(['id'=>$info['id']])->update($data))
        {
            return DataReturn('操作失败', -100);
        }
        return DataReturn('操作成功', 000);
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
    public static function InvoiceDelete($params = [])
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

        // 开启事务
        Db::startTrans();

        // 捕获异常
        try {
            // 基础条件
            $where = [
                ['id', 'in', $params['ids']],
            ];
            // 用户操作
            if(!empty($params['user']))
            {
                $where[] = ['user_id', '=', $params['user']['id']];
            }
            $status_arr = Db::name('PluginsInvoice')->where($where)->column('status');
            if(empty($status_arr))
            {
                throw new \Exception('数据为空');
            }
            // 1待开票、2已开票 不可操作删除
            if(in_array(1, $status_arr) || in_array(2, $status_arr))
            {
                throw new \Exception('数据不可操作删除');
            }

            // 删除操作
            if(!Db::name('PluginsInvoice')->where($where)->delete())
            {
                throw new \Exception('删除失败');
            }
            if(!Db::name('PluginsInvoiceValue')->where([['invoice_id', 'in', $params['ids']]])->delete())
            {
                throw new \Exception('业务id删除失败');
            }

            // 完成
            Db::commit();
            return DataReturn('删除成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 订单状态改变处理,状态为取消|关闭时释放积分
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-12-21
     * @desc    description
     * @param   [int]          $order_id [订单id]
     */
    public static function OrderStatusChangeHandle($order_id)
    {
        // 获取发票id
        $invoice = Db::name('PluginsInvoice')->alias('pi')->join('plugins_invoice_value piv', 'pi.id=piv.invoice_id')->where(['pi.business_type'=>0, 'pi.status'=>[0,1,3], 'piv.business_id'=>$order_id])->field('pi.id,pi.status')->find();
        if(!empty($invoice))
        {
            // 获取业务id
            $business_ids = Db::name('PluginsInvoiceValue')->where(['invoice_id'=>$invoice['id']])->column('business_id');

            // 一个业务id直接关闭、多个业务id重新计算发票数据
            if(count($business_ids) == 1)
            {
                Db::name('PluginsInvoice')->where(['id'=>$invoice['id']])->update(['status'=>4, 'upd_time'=>time()]);
            } else {
                // 重新计算发票金额
                $save_base_data = self::InvoiceSaveBaseDataHandle([
                    'type'          => 0,
                    'ids'           => $business_ids,
                    'is_not_oid'    => 0,
                ]);
                // 计算为空则直接关闭开票订单
                if(empty($save_base_data['total_price']) || empty($save_base_data['business_ids']))
                {
                    Db::name('PluginsInvoice')->where(['id'=>$invoice['id']])->update(['status'=>4, 'upd_time'=>time()]);
                } else {
                    // 更新金额
                    $upd_data = [
                        'total_price'   => $save_base_data['total_price'],
                        'upd_time'      => time(),
                    ];
                    // 如果已审核过则需要重新审核
                    if($invoice['status'] == 1)
                    {
                        $upd_data['status'] = 0;
                    }
                    Db::name('PluginsInvoice')->where(['id'=>$invoice['id']])->update($upd_data);

                    // 业务id删除后重新添加
                    $business_ids = array_unique(explode(',', $save_base_data['business_ids']));
                    $business_data = [];
                    foreach($business_ids as $bid)
                    {
                        $business_data[] = [
                            'business_id'   => $bid,
                            'invoice_id'    => $invoice['id'],
                            'add_time'      => time(),
                        ];
                    }
                    Db::name('PluginsInvoiceValue')->where(['invoice_id'=>$invoice['id']])->delete();
                    Db::name('PluginsInvoiceValue')->insertAll($business_data);
                }
            }
        }
    }
}
?>