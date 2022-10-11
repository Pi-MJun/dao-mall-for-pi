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
namespace app\plugins\answers\admin;

use app\service\PluginsService;
use app\plugins\answers\service\Service;
use app\service\GoodsService;

/**
 * 问答 - 后台管理
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Admin
{
    /**
     * 首页
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 基础数据
        $base = PluginsService::PluginsData('answers', ['images', 'images_bottom']);
        MyViewAssign('data', isset($base['data']) ? $base['data'] : []);

        // 幻灯片
        $data_params = [
            'where'     => ['is_enable'=>1],
        ];
        $slider = Service::SlideList($data_params);
        MyViewAssign('slider', isset($slider['data']) ? $slider['data'] : []);

        // 商品数据
        $goods = Service::GoodsList();
        MyViewAssign('goods_list', $goods['data']['goods']);
        
        return MyView('../../../plugins/view/answers/admin/admin/index');
    }

    /**
     * 基础信息编辑页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function baseinfo($params = [])
    {
        $ret = PluginsService::PluginsData('answers', ['images', 'images_bottom']);
        if($ret['code'] == 0)
        {
            // 是否
            $is_whether_list =  [
                0 => array('id' => 0, 'name' => '否', 'checked' => true),
                1 => array('id' => 1, 'name' => '是'),
            ];

            MyViewAssign('is_whether_list', $is_whether_list);
            MyViewAssign('data', $ret['data']);

            // 获取推荐问答
            if(!empty($ret['data']['category_ids']))
            {
                $answers = Service::AnswerList(['n'=>100, 'field'=>'id,content as title,add_time', 'category_ids'=> $ret['data']['category_ids']]);
                MyViewAssign('answers_rc_list', $answers['data']);
            } else {
                MyViewAssign('answers_rc_list', []);
            }

            return MyView('../../../plugins/view/answers/admin/admin/baseinfo');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 基础数据保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function basesave($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'answers', 'data'=>$params], ['images', 'images_bottom']);
    }

    /**
     * 幻灯片页面
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function slider($params = [])
    {
        $ret = Service::SlideList();
        if($ret['code'] == 0)
        {
            MyViewAssign('data_list', $ret['data']);
            return MyView('../../../plugins/view/answers/admin/admin/slider');
        } else {
            return $ret['msg'];
        }
    }

    /**
     * 幻灯片编辑
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function sliderinfo($params = [])
    {
        // 数据
        $data = [];
        if(!empty($params['id']))
        {
            $data_params = array(
                'where'     => ['id'=>intval($params['id'])],
            );
            $ret = Service::SlideList($data_params);
            $data = empty($ret['data'][0]) ? [] : $ret['data'][0];
        }
        MyViewAssign('data', $data);
        
        return MyView('../../../plugins/view/answers/admin/admin/sliderinfo');
    }

    /**
     * 幻灯片保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function slidersave($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return Service::SlideSave($params);
    }

    /**
     * 幻灯片删除
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-15T11:03:30+0800
     * @param    [array]          $params [输入参数]
     */
    public function sliderdelete($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return Service::SlideDelete($params);
    }

    /**
     * 幻灯片状态更新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-01-12T22:23:06+0800
     * @param    [array]          $params [输入参数]
     */
    public function sliderstatusupdate($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 开始处理
        return Service::SlideStatusUpdate($params);
    }


    /**
     * 推荐商品编辑编辑
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function goodsinfo($params = [])
    {
        // 商品数据
        $goods = Service::GoodsList();
        MyViewAssign('goods', $goods['data']);

        // 商品分类
        MyViewAssign('goods_category_list', GoodsService::GoodsCategoryAll());
        
        return MyView('../../../plugins/view/answers/admin/admin/goodsinfo');
    }

    /**
     * 商品搜索
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function goodssearch($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 搜索数据
        return Service::GoodsSearchList($params);
    }

    /**
     * 商品保存
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function goodssave($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 搜索数据
        return Service::GoodsSave($params);
    }

    /**
     * 问答搜索
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function answerssearch($params = [])
    {
        // 是否ajax请求
        if(!IS_AJAX)
        {
            return $this->error('非法访问');
        }

        // 问答内容
        $params['n'] = 100;
        $params['field'] = 'id,content as title,add_time';
        return Service::AnswerList($params);
    }
}
?>