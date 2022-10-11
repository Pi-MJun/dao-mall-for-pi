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
namespace app\plugins\intellectstools\admin;

use app\service\GoodsService;
use app\service\BrandService;
use app\service\ResourcesService;
use app\plugins\intellectstools\admin\Common;
use app\plugins\intellectstools\service\GoodsInventoryService;
use app\plugins\intellectstools\service\GoodsPriceService;
use app\plugins\intellectstools\service\GoodsBeautifyService;
use app\plugins\intellectstools\service\GoodsNoteService;
use app\plugins\intellectstools\service\GoodsCategoryService;

/**
 * 智能工具箱 - 商品
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class Goods extends Common
{
    /**
     * 库存修改页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function InventoryInfo($params = [])
    {
        $data = [];
        if(!empty($params['id']))
        {
            $ret = GoodsInventoryService::GoodsInventoryData($params);
            $data = empty($ret['data']) ? [] : $ret['data'];
            MyViewAssign('goods_id', intval($params['id']));
        }
        MyViewAssign('data', $data);
        return MyView('../../../plugins/view/intellectstools/admin/goods/inventoryinfo');
    }

    /**
     * 库存保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function InventorySave($params = [])
    {
        return GoodsInventoryService::GoodsInventorySave($params);
    }

    /**
     * 价格修改页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function PriceInfo($params = [])
    {
        $data = [];
        if(!empty($params['id']))
        {
            // 条件
            $where = [
                ['is_delete_time', '=', 0],
                ['id', '=', intval($params['id'])],
            ];

            // 获取数据
            $data_params = [
                'where'     => $where,
                'm'         => 0,
                'n'         => 1,
            ];
            $ret = GoodsService::GoodsList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $data = $ret['data'][0];

                // 获取商品编辑规格
                $specifications = GoodsService::GoodsEditSpecifications($params['id']);
                MyViewAssign('specifications', $specifications);

                // 规格扩展数据
                $goods_spec_extends = GoodsService::GoodsSpecificationsExtends($params);
                MyViewAssign('goods_specifications_extends', $goods_spec_extends['data']);

                // 编辑器文件存放地址
                MyViewAssign('editor_path_type', ResourcesService::EditorPathTypeValue('goods'));
            }
        }
        MyViewAssign('data', $data);
        return MyView('../../../plugins/view/intellectstools/admin/goods/priceinfo');
    }

    /**
     * 商品备注页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function NoteInfo($params = [])
    {
        $goods_data = [];
        if(!empty($params['id']))
        {
            // 条件
            $where = [
                ['is_delete_time', '=', 0],
                ['id', '=', intval($params['id'])],
            ];

            // 获取数据
            $data_params = [
                'where'     => $where,
                'm'         => 0,
                'n'         => 1,
                'field'     => 'id,title,simple_desc,images,price'
            ];
            $ret = GoodsService::GoodsList($data_params);
            if(!empty($ret['data']) && !empty($ret['data'][0]))
            {
                $goods_data = $ret['data'][0];

                // 备注信息
                $note_data = GoodsNoteService::GoodsNoteData($goods_data['id']);
                MyViewAssign('note_data', $note_data);
            }
        }
        MyViewAssign('goods_data', $goods_data);
        return MyView('../../../plugins/view/intellectstools/admin/goods/noteinfo');
    }

    /**
     * 商品备注保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function NoteSave($params = [])
    {
        return GoodsNoteService::GoodsNoteSave($params);
    }

    /**
     * 价格保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function PriceSave($params = [])
    {
        return GoodsPriceService::GoodsPriceSave($params);
    }

    /**
     * 商品数据美化页面
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BeautifyInfo($params = [])
    {
        // 商品分类
        MyViewAssign('goods_category_list', GoodsService::GoodsCategoryAll());

        // 品牌
        MyViewAssign('brand_list', BrandService::CategoryBrand());

        return MyView('../../../plugins/view/intellectstools/admin/goods/beautifyinfo');
    }

    /**
     * 商品数据美化保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function BeautifySave($params = [])
    {
        return GoodsBeautifyService::GoodsBeautifySave($params);
    }

    /**
     * 商品分类批量移动
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function GoodsCategoryMoveSave($params = [])
    {
        return GoodsCategoryService::GoodsCategoryMoveSave($params);
    }
}
?>