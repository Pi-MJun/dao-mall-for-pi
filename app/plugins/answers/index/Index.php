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
namespace app\plugins\answers\index;

use app\service\PluginsService;
use app\service\AnswerService;
use app\service\UserService;
use app\service\SeoService;
use app\service\GoodsService;
use app\plugins\answers\service\Service;

/**
 * 问答 - 前端独立页面入口
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class Index
{
    /**
     * 首页入口
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-07
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public function index($params = [])
    {
        // 基础数据
        $base = PluginsService::PluginsData('answers', ['images', 'images_bottom']);
        MyViewAssign('plugins_answers_data', isset($base['data']) ? $base['data'] : []);

        // 幻灯片
        $data_params = [
            'where'     => ['is_enable'=>1],
        ];
        $slider = Service::SlideList($data_params);
        MyViewAssign('plugins_answers_slider', isset($slider['data']) ? $slider['data'] : []);

        // 商品数据
        $goods = Service::GoodsList();
        MyViewAssign('plugins_answers_goods_list', $goods['data']['goods']);

        // 最新问答内容
        $middle_new_page_number = isset($base['data']['middle_new_page_number']) ? intval($base['data']['middle_new_page_number']) : 15;
        $answer = Service::AnswerList(['n'=>$middle_new_page_number]);
        MyViewAssign('plugins_answers_middle_answer_list', $answer['data']);

        // 推荐问答
        if(!empty($base['data']['category_ids']))
        {
            $answers = Service::AnswerList(['n'=>100, 'category_ids'=> $base['data']['category_ids']]);
            MyViewAssign('plugins_answers_rc_list', $answers['data']);
        } else {
            MyViewAssign('plugins_answers_rc_list', []);
        }

        // 最新商品
        if(!empty($base['data']['home_new_goods_number']))
        {
            $number = max(0, isset($base['data']['home_new_goods_number']) ? intval($base['data']['home_new_goods_number']) : 0);
            if($number > 0)
            {
                $where = [
                    ['is_delete_time', '=', 0],
                    ['is_shelves', '=', 1],
                ];
                $goods = GoodsService::GoodsList(['where'=>$where, 'field'=>'id,title,images,min_price', 'n'=>$number]);
                MyViewAssign('plugins_new_goods_list', $goods['data']);
            }
        }

        // 浏览器标题
        $seo_name = empty($base['data']['application_name']) ? '问答' : $base['data']['application_name'];
        MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($seo_name, 1));

        return MyView('../../../plugins/view/answers/index/index/index');
    }

    /**
     * 详情入口
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-07
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public function detail($params = [])
    {
        // 基础数据
        $base = PluginsService::PluginsData('answers', ['images', 'images_bottom']);
        MyViewAssign('plugins_answers_data', isset($base['data']) ? $base['data'] : []);

        // 商品数据
        $goods = Service::GoodsList();
        MyViewAssign('plugins_answers_goods_list', $goods['data']['goods']);

        // 推荐问答
        if(!empty($base['data']['category_ids']))
        {
            $answers = Service::AnswerList(['n'=>100, 'category_ids'=> $base['data']['category_ids']]);
            MyViewAssign('plugins_answers_rc_list', $answers['data']);
        } else {
            MyViewAssign('plugins_answers_rc_list', []);
        }

        // 获取问答数据
        $detail = Service::AnswerRow($params);
        MyViewAssign('plugins_answers_detail', $detail);

        // 浏览次数
        if($detail['code'] == 0 && !empty($detail['data']['id']))
        {
            AnswerService::AnswerAccessCountInc(['answer_id'=>$detail['data']['id']]);
        }

        // 浏览器标题
        if(!empty($detail['data']['title']))
        {
            MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($detail['data']['title']));
        } else if(!empty($detail['data']['content']))
        {
            MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle($detail['data']['content']));
        }

        return MyView('../../../plugins/view/answers/index/index/detail');
    }

    /**
     * 搜索
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-03-11
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public function search($params = [])
    {
        if(input('post.answers_keywords'))
        {
            $answers_keywords = str_replace(['?', ' ', '+', '-'], '', trim(input('post.answers_keywords')));
            return MyRedirect(PluginsHomeUrl('answers', 'index', 'search', ['answers_keywords'=>$answers_keywords]));
        } else {
            // 基础数据
            $base = PluginsService::PluginsData('answers', ['images', 'images_bottom']);
            MyViewAssign('plugins_answers_data', isset($base['data']) ? $base['data'] : []);

            // 商品数据
            $goods = Service::GoodsList();
            MyViewAssign('plugins_answers_goods_list', $goods['data']['goods']);

            // 推荐问答
            if(!empty($base['data']['category_ids']))
            {
                $answers = Service::AnswerList(['n'=>100, 'category_ids'=> $base['data']['category_ids']]);
                MyViewAssign('plugins_answers_rc_list', $answers['data']);
            } else {
                MyViewAssign('plugins_answers_rc_list', []);
            }

            // 获取搜索数据
            // 分页
            $number = isset($base['data']['search_page_number']) ? intval($base['data']['search_page_number']) : 28;

            // 条件
            $keywords_arr = empty($params['answers_keywords']) ? [] : ['keywords'=>$params['answers_keywords']];
            $where = Service::AnswerListWhere(array_merge($params, $keywords_arr));

            // 获取总数
            $total = AnswerService::AnswerTotal($where);

            // 分页
            $page_params = array(
                    'number'    =>  $number,
                    'total'     =>  $total,
                    'where'     =>  $params,
                    'page'      =>  isset($params['page']) ? intval($params['page']) : 1,
                    'url'       =>  PluginsHomeUrl('answers', 'index', 'search'),
                );
            $page = new \base\Page($page_params);
            MyViewAssign('page_html', $page->GetPageHtml());

            // 获取列表
            $data_params = array(
                'm'         => $page->GetPageStarNumber(),
                'n'         => $number,
                'where'     => $where,
                'field'     => 'id,title,content,add_time,is_reply',
            );
            $data = AnswerService::AnswerList($data_params);
            MyViewAssign('plugins_answers_data_list', $data['data']);

            // 参数
            MyViewAssign('params', $params);

            // 浏览器标题
            MyViewAssign('home_seo_site_title', SeoService::BrowserSeoTitle('问答搜索', 1));

            return MyView('../../../plugins/view/answers/index/index/search');
        }
    }

    /**
     * 提问
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-02-07T08:21:54+0800
     * @param    [array]          $params [输入参数]
     */
    public function answer($params = [])
    {
        $params = input('post.');
        $params['user'] = UserService::LoginUserInfo();
        return AnswerService::AnswerSave($params);
    }
}
?>