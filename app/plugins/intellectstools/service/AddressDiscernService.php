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

/**
 * 智能工具箱 - 地址识别服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2021-05-07
 * @desc    description
 */
class AddressDiscernService
{
    /**
     * 智能解析入口
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-04-09
     * @desc    description
     * @param   [string]        $params['address'] [地址信息]
     * @param   [int]           $params['is_user'] [是否解析用户信息]
     */
    public static function Run($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'address',
                'error_msg'         => '地址信息为空',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否解析用户信息
        $is_user = (isset($params['is_user']) && $params['is_user'] == 1) ? 1 : 0;
        if($is_user == 1)
        {
            $result = self::Decompose($params['address']);
        } else {
            $result['addr'] = $params['address'];
        }

        $fuzz = self::Fuzz($result['addr']);
        $parse = self::Parse($fuzz['a1'], $fuzz['a2'], $fuzz['a3']);
        if(empty($parse))
        {
            return DataReturn('识别失败', -1);
        }

        $result['province'] = $parse['province'];
        $result['city'] = $parse['city'];
        $result['county'] = $parse['county'];
        $result['province_name'] = $parse['province_name'];
        $result['city_name'] = $parse['city_name'];
        $result['county_name'] = $parse['county_name'];

        $result['address'] = ($fuzz['street']) ? $fuzz['street'] : $parse['street'];
        $result['address'] = str_replace([$result['county'], $result['city'], $result['province']], ['', '', ''], $result['address']);

        return DataReturn('操作成功', 0, $result);
    }

    /**
     * 分离手机号(座机)，身份证号，姓名等用户信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-04-10
     * @desc    description
     * @param   [string]          $string [地址信息]
     */
    public static function Decompose($string)
    {
        $compose = [];

        $search = ['收货地址', '详细地址', '地址', '收货人', '收件人', '收货', '所在地区', '邮编', '电话', '手机号码','身份证号码', '身份证号', '身份证', '：', ':', '；', ';', '，', ',', '。'];
        $replace = [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '];
        $string = str_replace($search, $replace, $string);
        $string = preg_replace('/\s{1,}/', ' ', $string);
        $string = preg_replace('/0-|0?(\d{3})-(\d{4})-(\d{4})/', '$1$2$3', $string);
        preg_match('/\d{18}|\d{17}X/i', $string, $match);
        if($match && $match[0])
        {
            $compose['idn'] = strtoupper($match[0]);
            $string = str_replace($match[0], '', $string);
        }

        preg_match('/\d{7,11}|\d{3,4}-\d{6,8}/', $string, $match);
        if($match && $match[0])
        {
            $compose['tel'] = $match[0];
            $string = str_replace($match[0], '', $string);
        }

        preg_match('/\d{6}/', $string, $match);
        if($match && $match[0])
        {
            $compose['postcode'] = $match[0];
            $string = str_replace($match[0], '', $string);
        }

        $string = trim(preg_replace('/ {2,}/', ' ', $string));
        $split_arr = explode(' ', $string);
        if(count($split_arr) > 1)
        {
            $compose['name'] = $split_arr[0];
            foreach($split_arr as $value)
            {
                if(strlen($value) < strlen($compose['name']))
                {
                    $compose['name'] = $value;
                }
            }
            $string = trim(str_replace($compose['name'], '', $string));
        }

        $compose['addr'] = $string;
        return $compose;
    }

    /**
     * 根据统计规律分析出二三级地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-04-10
     * @desc    description
     * @param   [string]          $addr [地址信息]
     */
    public static function Fuzz($addr)
    {
        $addr_origin = $addr;
        $addr = str_replace([' ', ','], ['', ''], $addr);
        $addr = str_replace('自治区', '省', $addr);
        $addr = str_replace('自治州', '州', $addr);

        $addr = str_replace('小区', '', $addr);
        $addr = str_replace('校区', '', $addr);

        $a1 = '';
        $a2 = '';
        $a3 = '';
        $street = '';

        if(mb_strpos($addr, '县') !== false && mb_strpos($addr, '县') < floor((mb_strlen($addr) / 3) * 2) || (mb_strpos($addr, '区') !== false && mb_strpos($addr, '区') < floor((mb_strlen($addr) / 3) * 2)) || mb_strpos($addr, '旗') !== false && mb_strpos($addr, '旗') < floor((mb_strlen($addr) / 3) * 2))
        {
            if(mb_strstr($addr, '旗'))
            {
                $deep3_keyword_pos = mb_strpos($addr, '旗');
                $a3 = mb_substr($addr, $deep3_keyword_pos - 1, 2);
            }
            if(mb_strstr($addr, '区'))
            {
                $deep3_keyword_pos = mb_strpos($addr, '区');
                if(mb_strstr($addr, '市'))
                {
                    $city_pos = mb_strpos($addr, '市');
                    $zone_pos = mb_strpos($addr, '区');
                    $a3 = mb_substr($addr, $city_pos + 1, $zone_pos - $city_pos);
                } else {
                    $a3 = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                }
            }
            if(mb_strstr($addr, '县'))
            {
                $deep3_keyword_pos = mb_strpos($addr, '县');

                if(mb_strstr($addr, '市'))
                {
                    $city_pos = mb_strpos($addr, '市');
                    $zone_pos = mb_strpos($addr, '县');
                    $a3 = mb_substr($addr, $city_pos + 1, $zone_pos - $city_pos);
                } else {
                    if(mb_strstr($addr, '自治县'))
                    {
                        $a3 = mb_substr($addr, $deep3_keyword_pos - 6, 7);
                        if(in_array(mb_substr($a3, 0, 1), ['省', '市', '州']))
                        {
                            $a3 = mb_substr($a3, 1);
                        }
                    } else {
                        $a3 = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    }
                }
            }
            $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
        } else {
            if(mb_strripos($addr, '市'))
            {
                if(mb_substr_count($addr, '市') == 1)
                {
                    $deep3_keyword_pos = mb_strripos($addr, '市');
                    $a3 = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
                } else if(mb_substr_count($addr, '市') >= 2)
                {
                    $deep3_keyword_pos = mb_strripos($addr, '市');
                    $a3 = mb_substr($addr, $deep3_keyword_pos - 2, 3);
                    $street = mb_substr($addr_origin, $deep3_keyword_pos + 1);
                }
            } else {
                $a3 = '';
                $street = $addr;
            }
        }

        if(mb_strpos($addr, '市') || mb_strstr($addr, '盟') || mb_strstr($addr, '州'))
        {
            if($tmp_pos = mb_strpos($addr, '市'))
            {
                $a2 = mb_substr($addr, $tmp_pos - 2, 3);
            } else if($tmp_pos = mb_strpos($addr, '盟'))
            {
                $a2 = mb_substr($addr, $tmp_pos - 2, 3);
            } else if($tmp_pos = mb_strpos($addr, '州'))
            {

                if($tmp_pos = mb_strpos($addr, '自治州'))
                {
                    $a2 = mb_substr($addr, $tmp_pos - 4, 5);
                } else {
                    $a2 = mb_substr($addr, $tmp_pos - 2, 3);
                }
            }
        }
        // 直辖市的情况、区县是否重复包含市名称
        if(!empty($a2) && !empty($a3))
        {
            $a3 = str_replace($a2, '', $a3);
        }

        return [
            'a1'        => $a1,
            'a2'        => $a2,
            'a3'        => $a3,
            'street'    => $street,
        ];
    }

    /**
     * 智能解析出省市区+街道地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-04-10
     * @desc    description
     * @param   [string]          $a1 [国家数据]
     * @param   [string]          $a2 [省市级数据]
     * @param   [string]          $a3 [区级数据]
     */
    public static function Parse($a1, $a2, $a3)
    {
        $result = [];
        if($a3 != '')
        {
            $a1_data = Db::name('Region')->where(['is_enable'=>1, 'level'=>1])->column('id,pid,name', 'id');
            $a2_data = Db::name('Region')->where(['is_enable'=>1, 'level'=>2])->column('id,pid,name', 'id');
            $a3_data = Db::name('Region')->where(['is_enable'=>1, 'level'=>3])->column('id,pid,name', 'id');

            $area3_matches = array();
            foreach($a3_data as $id => $v)
            {
                if(mb_strpos($v['name'], $a3) !== false)
                {
                    $area3_matches[$id] = $v;
                }
            }

            if($area3_matches && count($area3_matches) > 1)
            {
                if($a2)
                {
                    foreach($a2_data as $id => $v)
                    {
                        if(mb_strpos($v['name'], $a2) !== false)
                        {
                            $area2_matches[$id] = $v;
                        }
                    }

                    if($area2_matches)
                    {
                        foreach($area3_matches as $id => $v)
                        {

                            if(isset($area2_matches[$v['pid']]))
                            {
                                $result['city'] = $area2_matches[$v['pid']]['id'];
                                $result['city_name'] = $area2_matches[$v['pid']]['name'];

                                $result['county'] = $v['id'];
                                $result['county_name'] = $v['name'];

                                $sheng_id = $area2_matches[$v['pid']]['pid'];
                                if(!empty($sheng_id))
                                {
                                    $result['province'] = $a1_data[$sheng_id]['id'];
                                    $result['province_name'] = $a1_data[$sheng_id]['name'];
                                }
                            }
                        }
                    }
                } else {
                    $result['street'] = $a3;
                }
            } else if($area3_matches && count($area3_matches) == 1)
            {
                foreach($area3_matches as $id => $v)
                {
                    $city_id = $v['pid'];
                    $result['county'] = $v['id'];
                    $result['county_name'] = $v['name'];
                }
                $city = $a2_data[$city_id];
                $province = $a1_data[$city['pid']];

                $result['province'] = $province['id'];
                $result['province_name'] = $province['name'];
                $result['city'] = $city['id'];
                $result['city_name'] = $city['name'];
            } else if(empty($area3_matches) && $a2 == $a3)
            {
                foreach($a2_data as $id => $v)
                {
                    if(mb_strpos($v['name'], $a2) !== false)
                    {
                        $area2_matches[$id] = $v;
                        $sheng_id = $v['pid'];
                        $result['city'] = $v['id'];
                        $result['city_name'] = $v['name'];
                    }
                }
                if(!empty($sheng_id))
                {
                    $result['province'] = $a1_data[$sheng_id]['id'];
                    $result['province_name'] = $a1_data[$sheng_id]['name'];
                }
            }

            // 缺失字段补全
            $arr = [
                'province'      => 0,
                'city'          => 0,
                'county'        => 0,
                'province_name' => '',
                'city_name'     => '',
                'county_name'   => '',
                'street'        => ''
            ];
            foreach($arr as $k=>$v)
            {
                if(!array_key_exists($k, $result))
                {
                    $result[$k] = $v;
                }
            }
        }

        return $result;
    }
}
?>