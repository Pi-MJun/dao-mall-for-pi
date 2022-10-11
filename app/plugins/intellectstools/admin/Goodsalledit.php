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

use app\plugins\intellectstools\admin\Common;
use app\plugins\intellectstools\service\BaseService;
use app\plugins\intellectstools\service\GoodsAllEditService;

/**
 * 智能工具箱 - 商品Excel导入修改
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class GoodsAllEdit extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-04
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 首页信息
        $ret = BaseService::BaseConfig();
        if($ret['code'] == 0)
        {
            // 静态数据
            $goods_export_fields = BaseService::$goods_export_fields;
            MyViewAssign('goods_export_key_type', BaseService::$goods_export_key_type);
            MyViewAssign('goods_export_fields', $goods_export_fields);
            MyViewAssign('data_colon_join', BaseService::$data_colon_join);
            MyViewAssign('data_semicolon_join', BaseService::$data_semicolon_join);

            // 字段类型组合
            $goods_export_fields_list = [];
            foreach($goods_export_fields as $v)
            {
                $goods_export_fields_list[$v['type']][] = $v['title'];
            }
            MyViewAssign('goods_export_fields_list', $goods_export_fields_list);

            MyViewAssign('data', $ret['data']);
            return MyView('../../../plugins/view/intellectstools/admin/goodsalledit/index');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 模板下载
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-06
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Download($params = [])
    {
        $ret = GoodsAllEditService::TemplateDownload($params);
        if(isset($ret['code']) && $ret['code'] != 0)
        {
            MyViewAssign('msg', $ret['msg']);
            return MyView('public/tips_error');
        }
        return $ret;
    }

    /**
     * 文件上传
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-07
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Upload($params = [])
    {
        return GoodsAllEditService::DataUpload($params);
    }
}
?>