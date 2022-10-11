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
namespace app\plugins\brand\index;

use app\service\SeoService;
use app\plugins\brand\index\Common;
use app\plugins\brand\service\BaseService;

/**
 * 品牌 - 首页
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-10
 * @desc    description
 */
class Index extends Common
{
    /**
     * 首页
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-10
     * @desc    description
     * @param   [array]           $params [输入参数]
     */
    public function Index($params = [])
    {
        // 品牌分类
        $brand_category_list = BaseService::BrandCategoryList();
        MyViewAssign('brand_category_list', $brand_category_list);

        // 品牌列表
        $brand_list = BaseService::BrandList();
        MyViewAssign('brand_list', $brand_list);

        // seo
        $seo_title = empty($this->base_data['seo_title']) ? '品牌' : $this->base_data['seo_title'];
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($seo_title, 2));
        if(!empty($this->base_data['seo_keywords']))
        {
            MyViewAssign('home_seo_site_keywords', $this->base_data['seo_keywords']);
        }
        $seo_desc = empty($this->base_data['seo_desc']) ? (empty($this->base_data['describe']) ? '' : $this->base_data['describe']) : $this->base_data['seo_desc'];
        if(!empty($seo_desc))
        {
            MyViewAssign('home_seo_site_description', $seo_desc);
        }

        return MyView('../../../plugins/view/brand/index/index/index');
    }
}
?>