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
namespace app\plugins\intellectstools\service;

use think\facade\Db;
use app\service\GoodsService;
use app\service\WarehouseGoodsService;
use app\plugins\intellectstools\service\BaseService;

/**
 * 智能工具箱 - 数据导入服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class GoodsAllEditService
{
    // 品牌id
    public static $brand_ids;

    // 地区id
    public static $place_origin_ids;

    // 仓库id
    public static $warehouse_ids;

    /**
     * 模板下载
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function TemplateDownload($params = [])
    {
        // 参数校验
        $p = [
            [
                'checked_type'      => 'in',
                'key_name'          => 'export_key_type',
                'checked_data'      => array_keys(BaseService::$goods_export_key_type),
                'error_msg'         => '规格主键类型有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'export_field',
                'error_msg'         => '请至少选择一个可修改字段',
            ],
            [
                'checked_type'      => 'is_array',
                'key_name'          => 'export_field',
                'error_msg'         => '可修改字段有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'export_title',
                'error_msg'         => '请至少选择一个可修改字段名称',
            ],
            [
                'checked_type'      => 'is_array',
                'key_name'          => 'export_title',
                'error_msg'         => '可修改字段名称有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据组装
        $export_key_type = $params['export_key_type'];
        $title = [
            $export_key_type => [
                'name'  => BaseService::$goods_export_key_type[$export_key_type]['title'].BaseService::$data_colon_join.$export_key_type,
                'type'  => 'string',
            ],
        ];
        foreach($params['export_field'] as $v)
        {
            if(isset($params['export_title'][$v]) && isset(BaseService::$goods_export_fields[$v]))
            {
                $title[$v] = [
                    'name'  => $params['export_title'][$v].BaseService::$data_colon_join.$v,
                    'type'  => 'string',
                ];
            }
        }

        // Excel驱动导出数据
        $excel = new \base\Excel(['filename'=>'商品批量修改模板', 'title'=>$title]);
        return $excel->Export();
    }

    /**
     * 数据上传
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function DataUpload($params = [])
    {
        // 获取导入数据
        $ret = (new \base\Excel())->Import();
        if($ret['code'] != 0)
        {
            return $ret;
        }
        if(empty($ret['data']) || empty($ret['data']['title']) || empty($ret['data']['data']))
        {
            return DataReturn('导入数据为空', -1);
        }

        // 数据处理
        $title = [];
        $field = [];
        $data = $ret['data']['data'];
        foreach($ret['data']['title'] as $v)
        {
            if(!empty($v))
            {
                $temp = explode(BaseService::$data_colon_join, $v);
                if(count($temp) == 2)
                {
                    $title[] = $temp[0];
                    $field[] = $temp[1];
                } else {
                    return DataReturn('导入数据标题有误', -1);
                }
            }
        }

        // 数据处理
        return self::ExportDataHandle($title, $field, $data);
    }

    /**
     * 导入数据处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-08
     * @desc    description
     * @param   [array]          $title [标题]
     * @param   [array]          $field [字段]
     * @param   [array]          $data  [数据]
     */
    public static function ExportDataHandle($title, $field, $data)
    {
        // 开启事务
        Db::startTrans();

        // 捕获异常
        try {
            $goods_base = [];
            $base_data = BaseService::$goods_export_fields;
            foreach($data as $k=>$v)
            {
                // 是否为空或者非数组
                if(empty($v) || !is_array($v))
                {
                    continue;
                }

                // 行提示信息
                $first_msg = '第'.($k+1).'行、';

                $base = [];
                $spec = [];
                $where = [];
                foreach($v as $ks=>$vs)
                {
                    // 字段匹配
                    // 字段不存在基础配置中则跳出当前循环
                    if(!isset($field[$ks]) || !isset($field[$ks]))
                    {
                        break;
                    }
                    $f = $field[$ks];

                    // 第一个字段匹配商品id
                    if($ks == 0)
                    {
                        $where = [$f=>htmlspecialchars($vs)];
                        $where['goods_id'] = Db::name('GoodsSpecBase')->where($where)->value('goods_id');
                        if(empty($where['goods_id']))
                        {
                            throw new \Exception($first_msg.'商品规格数据不存在['.$vs.']');
                        }
                        continue;
                    }

                    // 是否自定义值处理方法
                    if(!empty($base_data[$f]['method']))
                    {
                        $method = $base_data[$f]['method'];
                        if(method_exists(__CLASS__, $method))
                        {
                            $ret = self::$method($vs);
                            if($ret['code'] != 0)
                            {
                                throw new \Exception($first_msg.$ret['msg'].'['.$vs.']');
                            }
                            $vs = $ret['data'];
                        }
                    }

                    // 数据处理类型
                    $type = isset($base_data[$f]['type']) ? $base_data[$f]['type'] : 'base';

                    // 数据值处理
                    if(in_array($type, ['base', 'spec']))
                    {
                        $data_type = isset($base_data[$f]['data_type']) ? $base_data[$f]['data_type'] : 'string';
                        switch($data_type)
                        {
                            // 整数
                            case 'int' :
                                $vs = intval($vs);
                                break;

                            // 浮点数
                            case 'float' :
                                $vs = PriceNumberFormat($vs);
                                break;

                            // 默认字符串类型处理
                            default :
                                $vs = htmlspecialchars(trim($vs));
                        }
                    }

                    // 数据处理
                    switch($type)
                    {
                        // 基础字段
                        case 'base' :
                            $base[$f] = $vs;
                            break;

                        // 商品规格
                        case 'spec' :
                            $spec[$f] = $vs;
                            break;

                        // 商品规格库存
                        case 'inventory' :
                            $ret = self::GoodsSpecInventoryHandle($vs, $where);
                            if($ret['code'] != 0)
                            {
                                throw new \Exception($first_msg.$ret['msg']);
                            }
                            break;

                        // 商品分类
                        case 'category' :
                            $ret = self::GoodsCategoryHandle($vs, $where);
                            if($ret['code'] != 0)
                            {
                                throw new \Exception($first_msg.$ret['msg']);
                            }
                            break;

                        // 商品参数
                        case 'parameters' :
                            $ret = self::GoodsParametersHandle($vs, $where);
                            if($ret['code'] != 0)
                            {
                                throw new \Exception($first_msg.$ret['msg']);
                            }
                            break;
                    }
                }

                // 商品基础信息放入
                if(!empty($base) && !isset($goods_base[$where['goods_id']]))
                {
                    $goods_base[$where['goods_id']] = $base;
                }

                // 规格更新
                if(!empty($spec))
                {
                    if(Db::name('GoodsSpecBase')->where($where)->update($spec) === false)
                    {
                        throw new \Exception($first_msg.'商品规格更新失败');
                    }

                    // 存在价格则更新商品价格基础数据
                    if(array_key_exists('price', $spec) || array_key_exists('original_price', $spec))
                    {
                        $ret = GoodsService::GoodsSaveBaseUpdate($where['goods_id'], $where['goods_id']);
                        if($ret['code'] != 0)
                        {
                            throw new \Exception($first_msg.$ret['msg']);
                        }
                    }
                }
            }

            // 商品基础更新
            if(!empty($goods_base))
            {
                foreach($goods_base as $gid=>$gv)
                {
                    $gv['upd_time'] = time();
                    if(Db::name('Goods')->where(['id'=>$gid])->update($gv) === false)
                    {
                        throw new \Exception($first_msg.'、商品更新失败');
                    }
                }
            }

            // 完成
            Db::commit();
            return DataReturn('批量更新成功', 0);
        } catch(\Exception $e) {
            Db::rollback();
            return DataReturn($e->getMessage(), -1);
        }
    }

    /**
     * 商品规格库存处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-08
     * @desc    description
     * @param   [array]          $value [数据值]
     * @param   [array]          $where [条件]
     */
    public static function GoodsSpecInventoryHandle($value, $where)
    {
        if(!empty($value))
        {
            $value = explode(BaseService::$data_colon_join, $value);
            if(count($value) != 2)
            {
                return DataReturn('库存格式有误', -1);
            }

            // 获取仓库
            if(!isset(self::$warehouse_ids[$value[0]]))
            {
                self::$warehouse_ids[$value[0]] = (int) Db::name('Warehouse')->where(['name'=>$value[0]])->value('id');
            }
            if(empty(self::$warehouse_ids[$value[0]]))
            {
                return DataReturn('仓库不存在['.$value[0].']', -1);
            }
            $warehouse_id = self::$warehouse_ids[$value[0]];

            // 获取规格基础id
            $base_id = Db::name('GoodsSpecBase')->where($where)->value('id');
            if(empty($base_id))
            {
                return DataReturn('规格数据不存在', -1);
            }

            // 获取规格信息
            $spec_value = Db::name('GoodsSpecValue')->where(['goods_spec_base_id'=>$base_id])->order('id asc')->column('value');
            $md5_key = md5(empty($spec_value) ? 'default' : implode('', $spec_value));

            // 库存商品不存在则增加
            $warehouse_goods = Db::name('WarehouseGoods')->where(['warehouse_id'=>$warehouse_id, 'goods_id'=>$where['goods_id']])->find();
            if(empty($warehouse_goods))
            {
                $warehouse_goods_data = [
                    'warehouse_id'  => $warehouse_id,
                    'goods_id'      => $where['goods_id'],
                ];
                $ret = WarehouseGoodsService::WarehouseGoodsAdd($warehouse_goods_data);
                if($ret['code'] != 0)
                {
                    return DataReturn('仓库商品添加失败', -1);
                }
                $warehouse_goods_id = Db::name('WarehouseGoods')->where($warehouse_goods_data)->value('id');
            } else {
                $warehouse_goods_id = $warehouse_goods['id'];
            }

            // 仓库商品规格数据、不存在则添加
            $warehouse_goods_spec_where = [
                'warehouse_id'          => $warehouse_id,
                'goods_id'              => $where['goods_id'],
                'md5_key'               => $md5_key,
            ];
            $temp = Db::name('WarehouseGoodsSpec')->where($warehouse_goods_spec_where)->find();
            if(empty($temp))
            {
                $spec_arr = [];
                $spec = GoodsService::GoodsSpecificationsActual($where['goods_id']);
                if(!empty($spec['title']) && !empty($spec['value']))
                {
                    $temp_value_arr = array_column($spec['value'], 'value', 'base_id');
                    if(array_key_exists($base_id, $temp_value_arr))
                    {
                        $temp_arr = explode(GoodsService::$goods_spec_to_string_separator, $temp_value_arr[$base_id]);
                        foreach($temp_arr as $sk=>$sv)
                        {
                            if(array_key_exists($sk, $spec['title']))
                            {
                                $spec_arr[] = [
                                    'type'  => $spec['title'][$sk],
                                    'value' => $sv,
                                ];
                            }
                        }
                    }
                }
                $warehouse_goods_spec_data =[
                    'warehouse_goods_id'    => $warehouse_goods_id,
                    'warehouse_id'          => $warehouse_id,
                    'goods_id'              => $where['goods_id'],
                    'md5_key'               => $md5_key,
                    'spec'                  => empty($spec_arr) ? '' : json_encode($spec_arr, JSON_UNESCAPED_UNICODE),
                    'inventory'             => intval($value[1]),
                    'add_time'              => time(),
                ];
                if(Db::name('WarehouseGoodsSpec')->insertGetId($warehouse_goods_spec_data) <= 0)
                {
                    return DataReturn('仓库商品规格库存添加失败', -1);
                }
            } else {
                $data = [
                    'inventory' => $value[1],
                ];
                if(Db::name('WarehouseGoodsSpec')->where($warehouse_goods_spec_where)->update($data) === false)
                {
                    return DataReturn('仓库商品规格库存更新失败', -1);
                }
            }

            // 同步商品库存
            $ret = WarehouseGoodsService::GoodsSpecInventorySync($where['goods_id']);
            if($ret['code'] != 0)
            {
                return $ret;
            }
        }
        return DataReturn('规格库存处理成功', 0);
    }

    /**
     * 商品参数处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-08
     * @desc    description
     * @param   [array]          $value [数据值]
     * @param   [array]          $where [条件]
     */
    public static function GoodsParametersHandle($value, $where)
    {
        if(!empty($value))
        {
            $value = explode(BaseService::$data_semicolon_join, $value);
            if(!empty($value))
            {
                $data = [];
                $type_arr = ['全部'=>0, '详情'=>1, '基础'=>2];
                foreach($value as $v)
                {
                    $temp = explode(BaseService::$data_colon_join, $v);
                    $count = count($temp);

                    // 指定名称、值
                    if($count == 2)
                    {
                        $data[] = [
                            'type'      => $type_arr['全部'],
                            'name'      => $temp[0],
                            'value'     => $temp[1],
                            'goods_id'  => $where['goods_id'],
                        ];
                    }

                    // 指定类型、名称、值
                    if($count == 3)
                    {
                        if(isset($type_arr[$temp[0]]))
                        {
                            $data[] = [
                                'type'      => $type_arr[$temp[0]],
                                'name'      => $temp[1],
                                'value'     => $temp[2],
                                'goods_id'  => $where['goods_id'],
                            ];
                        }
                    }
                }

                if(!empty($data))
                {
                    foreach($data as $v)
                    {
                        $temp_where = ['name'=>$v['name'], 'goods_id'=>$v['goods_id']];
                        $temp = Db::name('GoodsParams')->where($temp_where)->find();
                        if(empty($temp))
                        {
                            $v['add_time'] = time();
                            if(Db::name('GoodsParams')->insertGetId($v) <= 0)
                            {
                                return DataReturn('商品参数添加失败', -1);
                            }
                        } else {
                            if(Db::name('GoodsParams')->where($temp_where)->update($v) === false)
                            {
                                return DataReturn('商品参数更新失败', -1);
                            }
                        }
                    }
                }
            }
        }
        return DataReturn('规格参数处理成功', 0);
    }

    /**
     * 商品分类处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-08
     * @desc    description
     * @param   [array]          $value [数据值]
     * @param   [array]          $where [条件]
     */
    public static function GoodsCategoryHandle($value, $where)
    {
        if(!empty($value))
        {
            $value = explode(BaseService::$data_semicolon_join, $value);
            if(!empty($value))
            {
                $category = Db::name('GoodsCategory')->where(['name'=>$value])->column('name', 'id');
                if(empty($category))
                {
                    return DataReturn('商品分类不存在('.implode(',', $value).')', -1);
                }
                if(count($category) != count($value))
                {
                    foreach($value as $v)
                    {
                        if(!in_array($v, $category))
                        {
                            return DataReturn('商品分类不存在('.$v.')', -1);
                        }
                    }
                }
                // 添加分类
                return GoodsService::GoodsCategoryInsert(array_keys($category), $where['goods_id']);
            }
        }
        return DataReturn('商品分类添加成功', 0);
    }

    /**
     * 商品类型处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-08
     * @desc    description
     * @param   [array]          $value [数据值]
     * @param   [array]          $where [条件]
     */
    public static function GoodsSiteTypeHandle($value)
    {
        $index = -1;
        if(!empty($value))
        {
            $index = array_search($value, array_column(MyConst('common_site_type_list'), 'name'));
            if($index === false)
            {
                return DataReturn('商品类型不存在', -1);
            }
        }
        return DataReturn('success', 0, $index);
    }

    /**
     * 是否处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-08
     * @desc    description
     * @param   [string]          $value [数据值]
     */
    public static function IsTextHandle($value)
    {
        return DataReturn('success', 0, ($value == '是') ? 1 : 0);
    }

    /**
     * 获取地区id
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-08
     * @desc    description
     * @param   [string]          $value [数据值]
     */
    public static function GoodsPlaceOriginHandle($value)
    {
        if(!isset(self::$place_origin_ids[$value]))
        {
            self::$place_origin_ids[$value] = (int) Db::name('Region')->where(['name'=>$value])->value('id');
        }
        if(empty(self::$place_origin_ids[$value]))
        {
            return DataReturn('地区不存在', -1);
        }
        return DataReturn('success', 0, self::$place_origin_ids[$value]);
    }

    /**
     * 获取品牌id
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-08
     * @desc    description
     * @param   [string]          $value [数据值]
     */
    public static function GoodsBrandHandle($value)
    {
        if(!isset(self::$brand_ids[$value]))
        {
            self::$brand_ids[$value] = (int) Db::name('Brand')->where(['name'=>$value])->value('id');
        }
        if(empty(self::$brand_ids[$value]))
        {
            return DataReturn('品牌不存在', -1);
        }
        return DataReturn('success', 0, self::$brand_ids[$value]);
    }
}
?>