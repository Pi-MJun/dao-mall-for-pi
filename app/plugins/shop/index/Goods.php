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
namespace app\plugins\shop\index;

use app\service\SeoService;
use app\service\SystemBaseService;
use app\service\GoodsService;
use app\service\RegionService;
use app\service\BrandService;
use app\service\GoodsParamsService;
use app\plugins\shop\index\Base;
use app\plugins\shop\service\BaseService;
use app\plugins\shop\service\ShopService;
use app\plugins\shop\service\ShopGoodsService;
use app\plugins\shop\service\ShopGoodsCategoryService;
use app\plugins\shop\service\GoodsInventoryService;
use app\plugins\shop\service\MainGoodsSyncService;

/**
 * 多商户 - 商品管理
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-03-31
 * @desc    description
 */
class Goods extends Base
{
    /**
     * 构造方法
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-30
     * @desc    description
     */
    public function __construct($params = [])
    {
        parent::__construct($params);

        // 是否已经登录
        $this->IsLogin();
    }

    /**
     * 列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 总数
        $total = GoodsService::GoodsTotal($this->form_where);

        // 分页
        $page_params = [
            'number'    => $this->page_size,
            'total'     => $total,
            'where'     => $params,
            'page'      => $this->page,
            'url'       => PluginsHomeUrl('shop', 'goods', 'index'),
        ];
        $page = new \base\Page($page_params);

        // 获取数据列表
        $data_params = [
            'where'         => $this->form_where,
            'm'             => $page->GetPageStarNumber(),
            'n'             => $this->page_size,
            'order_by'      => $this->form_order_by['data'],
            'is_category'   => 1,
        ];
        $ret = GoodsService::GoodsList($data_params);

        // 基础参数赋值
        MyViewAssign('params', $params);
        MyViewAssign('page_html', $page->GetPageHtml());
        MyViewAssign('data_list', $ret['data']);

        // 获取店铺数据
        $user_shop = ShopService::UserShopInfo($this->user['id'], 'user_id', '*', ['user_type'=>'shop']);
        MyViewAssign('user_shop', $user_shop);

        // 商品分类
        MyViewAssign('goods_category_list', GoodsService::GoodsCategory(['is_all'=>1]));

        // 店铺商品分类
        $shop_goods_category = ShopGoodsCategoryService::GoodsCategoryAll(['user_id'=>$this->user['id']]);
        MyViewAssign('shop_goods_category', $shop_goods_category['data']);

        // 加载布局管理
        MyViewAssign('is_load_layout_admin', 1);

        // 浏览器名称
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('商品管理', 1));
        return MyView('../../../plugins/view/shop/index/goods/index');
    }
    
    /**
     * 详情
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Detail($params = [])
    {
        if(!empty($params['id']))
        {
            // 条件
            $where = [
                ['is_delete_time', '=', 0],
                ['id', '=', intval($params['id'])],
                ['shop_user_id', '=', $this->user['id']],
            ];

            // 获取列表
            $data_params = [
                'm'                 => 0,
                'n'                 => 1,
                'where'             => $where,
                'is_photo'          => 1,
                'is_content_app'    => 1,
                'is_category'       => 1,
            ];
            $ret = GoodsService::GoodsList($data_params);
            $data = [];
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];
                $data['fictitious_goods_value'] = BaseService::FictitiousGoodsValue($data['id']);

                // 获取商品编辑规格
                $specifications = GoodsService::GoodsEditSpecifications($data['id']);
                MyViewAssign('specifications', $specifications);

                // 获取商品编辑参数
                $parameters = GoodsService::GoodsEditParameters($data['id']);
                MyViewAssign('parameters', $parameters);

                // 商品参数类型
                MyViewAssign('common_goods_parameters_type_list', MyConst('common_goods_parameters_type_list'));
            }
            MyViewAssign('data', $data);
        }

        // 关闭头尾
        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        return MyView('../../../plugins/view/shop/index/goods/detail');
    }

    /**
     * 商品添加/编辑页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function SaveInfo($params = [])
    {
        // 商品信息
        $data = [];
        if(!empty($params['id']))
        {
            // 条件
            $where = [
                ['is_delete_time', '=', 0],
                ['id', '=', intval($params['id'])],
                ['shop_user_id', '=', $this->user['id']],
            ];

            // 获取数据
            $data_params = [
                'where'             => $where,
                'm'                 => 0,
                'n'                 => 1,
                'is_photo'          => 1,
                'is_content_app'    => 1,
                'is_category'       => 1,
            ];
            $ret = GoodsService::GoodsList($data_params);
            if(empty($ret['data'][0]))
            {
                return $this->error('商品信息不存在', PluginsHomeUrl('shop', 'goods', 'index'));
            }
            $data = $ret['data'][0];
            $data['fictitious_goods_value'] = BaseService::FictitiousGoodsValue($data['id']);

            // 获取商品编辑规格
            $specifications = GoodsService::GoodsEditSpecifications($data['id']);
            MyViewAssign('specifications', $specifications);

            // 获取商品编辑参数
            $parameters = GoodsService::GoodsEditParameters($data['id']);
            MyViewAssign('parameters', $parameters);

            // 获取分类层级数据
            $category_level = ShopGoodsService::GoodsCategoryLevel($data['category_ids']);
            MyViewAssign('category_level', $category_level);
        }

        // 地区信息
        MyViewAssign('region_province_list', RegionService::RegionItems(['pid'=>0]));

        // 商品分类
        MyViewAssign('goods_category_list', GoodsService::GoodsCategory(['is_all'=>1]));

        // 品牌分类
        MyViewAssign('brand_list', BrandService::CategoryBrand());

        // 规格扩展数据
        $goods_spec_extends = GoodsService::GoodsSpecificationsExtends($params);
        MyViewAssign('goods_specifications_extends', $goods_spec_extends['data']);

        // 站点类型
        MyViewAssign('common_site_type_list', MyConst('common_site_type_list'));
        // 当前系统设置的站点类型
        MyViewAssign('common_site_type', SystemBaseService::SiteTypeValue());

        // 商品参数类型
        MyViewAssign('common_goods_parameters_type_list', MyConst('common_goods_parameters_type_list'));

        // 商品参数模板
        $data_params = array(
            'm'     => 0,
            'n'     => 0,
            'where' => [
                ['is_enable', '=', 1],
                ['config_count', '>', 0],
            ],
            'field' => 'id,name',
        );
        $template = GoodsParamsService::GoodsParamsTemplateList($data_params);
        MyViewAssign('goods_template_list', $template['data']);

        // 店铺商品分类
        $shop_goods_category = ShopGoodsCategoryService::GoodsCategoryAll(['user_id'=>$this->user['id']]);
        MyViewAssign('shop_goods_category', $shop_goods_category['data']);

        // 获取店铺数据
        $user_shop = ShopService::UserShopInfo($this->user['id'], 'user_id', '*', ['user_type'=>'shop']);
        MyViewAssign('user_shop', $user_shop);

        // 加载布局管理
        MyViewAssign('is_load_layout_admin', 1);

        // 是否拷贝
        MyViewAssign('is_copy', (isset($params['is_copy']) && $params['is_copy'] == 1) ? 1 : 0);

        // 编辑器文件存放地址
        MyViewAssign('editor_path_type', 'plugins_shop-user_goods-'.$this->user['id']);

        // 数据
        unset($params['id'], $params['is_copy']);
        MyViewAssign('data', $data);
        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/shop/index/goods/saveinfo');
    }

    /**
     * 平台商品添加页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function MainAddInfo($params = [])
    {
        // 商品分类
        MyViewAssign('goods_category_list', GoodsService::GoodsCategory(['is_all'=>1]));

        MyViewAssign('params', $params);
        return MyView('../../../plugins/view/shop/index/goods/mainaddinfo');
    }

    /**
     * 保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Save($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error('非法访问');
        }

        // 开始操作
        $params['shop_user_id'] = $this->user['id'];
        $params['base_config'] = $this->base_config;
        return ShopGoodsService::GoodsSave($params);
    }

    /**
     * 状态更新
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function StatusUpdate($params = [])
    {
        // 是否ajax
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始操作
        $params['shop_user_id'] = $this->user['id'];
        $params['base_config'] = $this->base_config;
        return ShopGoodsService::GoodsStatusUpdate($params);
    }

    /**
     * 删除
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Delete($params = [])
    {
        // 是否ajax
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始操作
        $params['shop_user_id'] = $this->user['id'];
        $params['base_config'] = $this->base_config;
        return ShopGoodsService::GoodsDelete($params);
    }

    /**
     * 库存修改页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function InventoryInfo($params = [])
    {
        if(!empty($params['id']))
        {
            $params['user_id'] = $this->user['id'];
            $ret = GoodsInventoryService::GoodsInventoryData($params);
            MyViewAssign('goods_id', intval($params['id']));
            MyViewAssign('data', empty($ret['data']) ? [] : $ret['data']);
        }

        // 关闭头尾
        MyViewAssign('is_header', 0);
        MyViewAssign('is_footer', 0);
        return MyView('../../../plugins/view/shop/index/goods/inventoryinfo');
    }

    /**
     * 库存保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-30
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function InventorySave($params = [])
    {
        $params['user_id'] = $this->user['id'];
        $params['base_config'] = $this->base_config;
        return GoodsInventoryService::GoodsInventorySave($params);
    }

    /**
     * 商品主分类移动保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsCategoryMainMoveSave($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error('非法访问');
        }

        // 开始操作
        $params['shop_user_id'] = $this->user['id'];
        $params['base_config'] = $this->base_config;
        return ShopGoodsService::GoodsCategoryMainMoveSave($params);
    }

    /**
     * 商品店铺分类移动保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsCategoryShopMoveSave($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error('非法访问');
        }

        // 开始操作
        $params['shop_user_id'] = $this->user['id'];
        $params['base_config'] = $this->base_config;
        return ShopGoodsService::GoodsCategoryShopMoveSave($params);
    }

    /**
     * 商品搜索
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-10-16
     * @desc    description
     * @param   [array]           $params [商品搜索]
     */
    public function GoodsSearch($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 搜索数据
        $params['data_type'] = 'main';
        $params['user_id'] = $this->user['id'];
        return BaseService::GoodsSearchList($params);
    }

    /**
     * 平台商品添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-03-31
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function MainAdd($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            $this->error('非法访问');
        }

        // 开始操作
        $params['shop_user_id'] = $this->user['id'];
        $params['base_config'] = $this->base_config;
        return MainGoodsSyncService::GoodsSync($params);
    }
}
?>