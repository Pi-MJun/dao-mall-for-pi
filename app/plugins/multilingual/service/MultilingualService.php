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
namespace app\plugins\multilingual\service;

/**
 * 多语言 - 语言配置服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class MultilingualService
{
    /**
     * 获取语言id作为key返回数据
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-01-29
     * @desc    description
     */
    public static function MultilingualColumnList()
    {
        $ret = self::MultilingualList();
        return array_column($ret['data'], null, 'id');
    }

    /**
     * 数据列表
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params  [输入参数]
     */
    public static function MultilingualList($params = [])
    {
        $data = [
            [
                'id'            => 1,
                'code'          => 'zh',
                'name'          => '简体中文',
                'is_default'    => 1,
            ],
            [
                'id'            => 2,
                'code'          => 'cht',
                'name'          => '繁体中文',
            ],
            [
                'id'            => 3,
                'code'          => 'en',
                'name'          => '英文',
            ],
            [
                'id'            => 4,
                'code'          => 'ru',
                'name'          => '俄语',
            ],
            [
                'id'            => 5,
                'code'          => 'kor',
                'name'          => '韩语',
            ],
            [
                'id'            => 6,
                'code'          => 'th',
                'name'          => '泰语',
            ],
            [
                'id'            => 7,
                'code'          => 'yue',
                'name'          => '粤语',
            ],
            [
                'id'            => 8,
                'code'          => 'jp',
                'name'          => '日语',
            ],
            [
                'id'            => 9,
                'code'          => 'de',
                'name'          => '德语',
            ],
            [
                'id'            => 10,
                'code'          => 'nl',
                'name'          => '荷兰语',
            ],
            [
                'id'            => 11,
                'code'          => 'vie',
                'name'          => '越南语',
            ],
            [
                'id'            => 12,
                'code'          => 'it',
                'name'          => '意大利语',
            ],
            [
                'id'            => 13,
                'code'          => 'spa',
                'name'          => '西班牙语',
            ],
            [
                'id'            => 14,
                'code'          => 'fra',
                'name'          => '法语',
            ],
        ];
        return DataReturn('处理成功', 0, $data);
    }
}
?>