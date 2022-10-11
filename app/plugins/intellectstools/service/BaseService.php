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
use app\service\PluginsService;
use app\service\GoodsService;

/**
 * 智能工具箱 - 基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [];

    // 价格类型
    public static $price_type_list = [
        0 => ['value' => 0, 'field' => 'price', 'name' => '销售价'],
        1 => ['value' => 1, 'field' => 'original_price', 'name' => '原价'],
    ];

    // 调整规则
    public static $modify_price_rules_list = [
        0 => ['value' => 0, 'type' => '+', 'name' => '增加'],
        1 => ['value' => 1, 'type' => '-', 'name' => '减少'],
        2 => ['value' => 2, 'type' => '*', 'name' => '乘以'],
        3 => ['value' => 3, 'type' => '/', 'name' => '除以'],
        4 => ['value' => 4, 'type' => 'fixed', 'name' => '固定'],
    ];

    // 模板导出主键类型
    public static $goods_export_key_type = [
        'coding' => [
            'title'     => '规格编码',
            'field'     => 'coding',
            'type'      => 'string',
        ],
        'barcode' => [
            'title'     => '规格条形码',
            'field'     => 'barcode',
            'type'      => 'string',
        ],
    ];

    // 商品导出字段定义
    public static $goods_export_fields = [
        'title' => [
            'title'     => '标题名称',
            'field'     => 'title',
            'type'      => 'base',
            'data_type' => 'string',
        ],
        'simple_desc' => [
            'title'     => '商品简述',
            'field'     => 'simple_desc',
            'type'      => 'base',
            'data_type' => 'string',
        ],
        'model' => [
            'title'     => '商品型号',
            'field'     => 'model',
            'type'      => 'base',
            'data_type' => 'string',
        ],
        'is_category' => [
            'title'     => '商品分类',
            'field'     => 'is_category',
            'type'      => 'category',
            'data_type' => 'int',
        ],
        'brand_id' => [
            'title'     => '品牌',
            'field'     => 'brand_id',
            'type'      => 'base',
            'data_type' => 'int',
            'method'    => 'GoodsBrandHandle',
        ],
        'place_origin' => [
            'title'     => '生产地',
            'field'     => 'place_origin',
            'type'      => 'base',
            'data_type' => 'int',
            'method'    => 'GoodsPlaceOriginHandle',
        ],
        'inventory_unit' => [
            'title'     => '库存单位',
            'field'     => 'inventory_unit',
            'type'      => 'base',
            'data_type' => 'string',
        ],
        'buy_min_number' => [
            'title'     => '最低起购数量',
            'field'     => 'buy_min_number',
            'type'      => 'base',
            'data_type' => 'int',
        ],
        'buy_max_number' => [
            'title'     => '单次最大购买数量',
            'field'     => 'buy_max_number',
            'type'      => 'base',
            'data_type' => 'int',
        ],
        'site_type' => [
            'title'     => '商品类型',
            'field'     => 'site_type',
            'type'      => 'base',
            'data_type' => 'int',
            'method'    => 'GoodsSiteTypeHandle',
        ],
        'is_deduction_inventory' => [
            'title'     => '是否扣减库存',
            'field'     => 'is_deduction_inventory',
            'type'      => 'base',
            'data_type' => 'int',
            'method'    => 'IsTextHandle',
        ],
        'is_shelves' => [
            'title'     => '是否上下架',
            'field'     => 'is_shelves',
            'type'      => 'base',
            'data_type' => 'int',
            'method'    => 'IsTextHandle',
        ],
        'price' => [
            'title'     => '商品销售价(元)',
            'field'     => 'price',
            'type'      => 'spec',
        ],
        'original_price' => [
            'title'     => '商品原价(元)',
            'field'     => 'original_price',
            'type'      => 'spec',
            'data_type' => 'float',
        ],
        'weight' => [
            'title'     => '商品重量(kg)',
            'field'     => 'weight',
            'type'      => 'spec',
            'data_type' => 'float',
        ],
        'inventory' => [
            'title'     => '商品库存',
            'field'     => 'inventory',
            'type'      => 'inventory',
            'data_type' => 'int',
        ],
        'seo_title' => [
            'title'     => 'SEO标题',
            'field'     => 'seo_title',
            'type'      => 'base',
            'data_type' => 'string',
        ],
        'seo_keywords' => [
            'title'     => 'SEO关键字',
            'field'     => 'seo_keywords',
            'type'      => 'base',
            'data_type' => 'string',
        ],
        'seo_desc' => [
            'title'     => 'SEO描述',
            'field'     => 'seo_desc',
            'type'      => 'base',
            'data_type' => 'string',
        ],
        'parameters' => [
            'title'     => '商品参数',
            'field'     => 'parameters',
            'type'      => 'parameters',
        ],
    ];

    // 数据key=>val分割符
    public static $data_colon_join = '{cn}';

    // 数据段分割符
    public static $data_semicolon_join = '{sn}';

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'intellectstools', 'data'=>$params]);
    }
    
    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        $ret = PluginsService::PluginsData('intellectstools', self::$base_config_attachment_field, $is_cache);

        // 数据为空则赋值空数组
        if(empty($ret['data']))
        {
            $ret['data'] = [];
        }

        // 商品详情页-详情内容顶部提示信息
        $ret['data']['goods_detail_content_top_tips_msg'] = empty($ret['data']['goods_detail_content_top_tips_msg']) ? [] : explode("\n", $ret['data']['goods_detail_content_top_tips_msg']);

        return $ret;
    }

    /**
     * 商品条件
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-08
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public static function GoodsWhere($params = [])
    {
        // 字段名称
        $field = empty($params['field']) ? 'id' : $params['field'];

        // 商品id
        $data = [];
        if(!empty($params['goods_ids']))
        {
            $data = explode(',', $params['goods_ids']);
        }

        // 商品分类
        if(!empty($params['category_id']))
        {
            // 获取所有子级分类id
            $cids = GoodsService::GoodsCategoryItemsIds($params['category_id']);
            if(!empty($cids))
            {
                $gids = Db::name('GoodsCategoryJoin')->where(['category_id'=>$cids])->column('goods_id');
                if(!empty($gids))
                {
                    $data = array_merge($data, $gids);
                }
            }
        }

        // 品牌
        if(!empty($params['brand_id']))
        {
            $gids = Db::name('Goods')->where(['brand_id'=>intval($params['brand_id'])])->column('id');
            if(!empty($gids))
            {
                $data = array_merge($data, $gids);
            }
        }

        // 去重商品id并返回
        $goods_ids = array_unique($data);

        $where_value = empty($goods_ids) ? [$field, '>', 0] : [$field, 'in', $goods_ids];
        return [
            'goods_ids' => $goods_ids,
            'where'     => [
                $where_value
            ],
        ];
    }
}
?>