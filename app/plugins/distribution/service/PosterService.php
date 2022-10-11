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
namespace app\plugins\distribution\service;

use app\service\ResourcesService;
use app\service\UserService;
use app\service\SystemService;
use app\service\AppMiniUserService;
use app\plugins\distribution\service\BaseService;

/**
 * 分销 - 海报服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class PosterService
{
    // 海报宽度基准数
    private static $benchmark_width = 300;

    /**
     * 用户海报
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-06T21:09:38+0800
     * @param    [array]         $params [输入参数]
     */
    public static function UserPoster($params = [])
    {
        // 是否刷新
        $is_refresh = isset($params['is_refresh']) ? (boolean)$params['is_refresh'] : false;

        // 用户信息
        if(empty($params['user']))
        {
            $user = UserService::LoginUserInfo();
            if(empty($user))
            {
                return DataReturn('请先登录', -400);
            }
        } else {
            $user = $params['user'];
        }

        // 系统类型
        $system_type = SystemService::SystemTypeValue();

        // 海报地址
        $poster_path = 'static'.DS.'upload'.DS.'images'.DS.'plugins_distribution'.DS.'poster'.DS.$system_type.DS.APPLICATION_CLIENT_TYPE.DS.date('Y', $user['add_time']).DS.date('m', $user['add_time']).DS.date('d', $user['add_time']).DS;
        $poster_filename = date('YmdHis', $user['add_time']).$user['id'].'.png';
        $poster_dir = ROOT.'public'.DS.$poster_path.$poster_filename;

        // 已存在则直接返回
        if(file_exists($poster_dir) && $is_refresh === false)
        {
            return DataReturn('海报创建成功', 0, ResourcesService::AttachmentPathViewHandle(DS.$poster_path.$poster_filename));
        }

        // gd函数是否支持
        if(!function_exists('imagettftext'))
        {
            return DataReturn('imagettftext函数不支持', -1);
        }
        if(!function_exists('imagettfbbox'))
        {
            return DataReturn('imagettfbbox函数不支持', -1);
        }

        // 基础配置
        $base = BaseService::BaseConfig();
        if($base['code'] != 0)
        {
            return $base;
        }

        // 海报配置信息
        $poster = BaseService::PosterData(['is_handle_data'=>1]);
        if(empty($poster['data']))
        {
            return DataReturn('海报未配置', -1);
        }

        // 海报背景
        $backdrop = (substr($poster['data']['backdrop_old'], 0, 4) == 'http') ? $poster['data']['backdrop_old'] : ROOT.'public'.$poster['data']['backdrop_old'];
        $bg = @imagecreatefromstring(RequestGet($backdrop));
        $bg_width = imagesx($bg);
        
        // 头像大小计算
        $avatar_width = empty($poster['data']['avatar_width']) ? 100 : $bg_width/(self::$benchmark_width/$poster['data']['avatar_width']);
        
        // 头像资源
        $av = @imagecreatefromstring(RequestGet($user['avatar']));
        
        // 调整大小
        $av = self::ImagesResize($user['avatar'], $av, $avatar_width, $avatar_width);

        // 收圆
        if($poster['data']['avatar_border_style'] == 1)
        {
            $av = self::ImagesAppearance($av, 'radius');
        } elseif($poster['data']['avatar_border_style'] == 2)
        {
            $av = self::ImagesAppearance($av, 'circle');
        }

        // 头像宇海报合并
        $bg = self::ImagesMerge($bg, $av, $avatar_width, $avatar_width, $poster['data']['avatar_left'], $poster['data']['avatar_top']);

        // 二维码创建
        $qrcode = self::UserShareQrcodeCreate($user['id'], $user['add_time'], $base['data'], $is_refresh, $system_type);
        if($qrcode['code'] != 0)
        {
            return $qrcode;
        }

        // 二维码大小计算
        $qrcode_width = empty($poster['data']['qrcode_width']) ? 100 : $bg_width/(self::$benchmark_width/$poster['data']['qrcode_width']);
        
        // 二维码资源
        $av = @imagecreatefromstring(RequestGet($qrcode['data']));

        // 调整大小
        $av = self::ImagesResize($qrcode['data'], $av, $qrcode_width, $qrcode_width);

        // 收圆
        if($poster['data']['qrcode_border_style'] == 1)
        {
            $av = self::ImagesAppearance($av, 'radius');
        } elseif($poster['data']['qrcode_border_style'] == 2)
        {
            $av = self::ImagesAppearance($av, 'circle');
        }

        // 二维码宇海报合并
        $bg = self::ImagesMerge($bg, $av, $qrcode_width, $qrcode_width, $poster['data']['qrcode_left'], $poster['data']['qrcode_top']);

        // 用户名
        $bg = self::StringMerge($bg, $user['user_name_view'], $poster['data']['nickname_color'],  $poster['data']['nickname_auto_center'], $poster['data']['nickname_left'], $poster['data']['nickname_top']);

        // 目录不存在则创建
        if(\base\FileUtil::CreateDir(ROOT.'public'.DS.$poster_path) !== true)
        {
            return DataReturn('海报目录创建失败', -10);
        }

        // 存储图片
        imagepng($bg, $poster_dir);
        if(file_exists($poster_dir))
        {
            // 刷新缓存时间
            MySession('user_poster_images_ver', date('YmdHis'));
            
            return DataReturn('海报创建成功', 0, ResourcesService::AttachmentPathViewHandle(DS.$poster_path.$poster_filename));
        }
        return DataReturn('海报创建失败', -100);
    }

    /**
     * 用户分享二维码生成
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-04-19
     * @desc    description
     * @param   [int]          $user_id         [用户id]
     * @param   [int]          $user_add_time   [用户创建时间]
     * @param   [array]        $config          [插件配置信息]
     * @param   [boolean]      $is_refresh      [是否刷新]
     * @param   [string]       $system_type     [系统类型]
     */
    public static function UserShareQrcodeCreate($user_id, $user_add_time, $config, $is_refresh = false, $system_type = '')
    {
        if(!empty($user_id) && !empty($user_add_time))
        {
            // 系统类型
            if(empty($system_type))
            {
                $system_type = SystemService::SystemTypeValue();
            }

            // 自定义路径和名称
            $path = 'static'.DS.'upload'.DS.'images'.DS.'plugins_distribution'.DS.'qrcode'.DS.$system_type.DS.APPLICATION_CLIENT_TYPE.DS.date('Y', $user_add_time).DS.date('m', $user_add_time).DS.date('d', $user_add_time).DS;
            $filename = date('YmdHis', $user_add_time).$user_id.'.png';

            // 二维码处理参数
            $params = [
                'path'      => DS.$path,
                'filename'  => $filename,
                'dir'       => ROOT.'public'.DS.$path.$filename,
            ];

            // 目录不存在则创建
            if(\base\FileUtil::CreateDir(ROOT.'public'.DS.$path) !== true)
            {
                return DataReturn('二维码目录创建失败', -1);
            }

            // 不存在则创建
            if(!file_exists($params['dir']) || $is_refresh === true)
            {
                // 根据客户端类型生成不同的二维码
                switch(APPLICATION_CLIENT_TYPE)
                {
                    // 微信小程序
                    case 'weixin' :
                        $ret = self::UserCreateMiniWechatQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // QQ小程序
                    case 'qq' :
                        $ret = self::UserCreateMiniQQQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // 支付宝小程序
                    case 'alipay' :
                        $ret = self::UserCreateMiniAlipayQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // 头条小程序
                    case 'toutiao' :
                        $ret = self::UserCreateMiniToutiaoQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // 百度小程序
                    case 'baidu' :
                        $ret = self::UserCreateMiniBaiduQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // 快手小程序
                    case 'kuaishou' :
                        $ret = self::UserCreateMiniKuaishouQrcode($params, $user_id, $config);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                        break;

                    // 默认
                    default :
                        // url/logo
                        $params['content'] = self::UserShareUrl($user_id, $config);
                        $params['logo'] = empty($config['default_qrcode_logo']) ? '' : $config['default_qrcode_logo'];

                        // 创建二维码
                        $ret = (new \base\Qrcode())->Create($params);
                        if($ret['code'] != 0)
                        {
                            return $ret;
                        }
                }
            }
            return DataReturn('处理成功', 0, ResourcesService::AttachmentPathViewHandle($params['path'].$params['filename']));
        }
        return DataReturn('用户id有误', -100);
    }

    /**
     * 获取用户分享url地址
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-08T21:08:47+0800
     * @param    [int]          $user_id        [用户id]
     * @param    [array]        $config         [插件配置信息]
     */
    public static function UserShareUrl($user_id, $config)
    {
        // 当前站点地址
        $url = __MY_URL__;

        // h5端地址处理
        if(APPLICATION_CLIENT_TYPE == 'h5')
        {
            $temp = BaseService::H5Url($config);
            if(!empty($temp))
            {
                $url = $temp;
            }
        }

        return $url.'?referrer='.UserService::UserReferrerEncryption($user_id);
    }

    /**
     * 手机端首页页面地址
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2022-02-21
     * @desc    description
     * @param   [array]          $config [插件配置]
     */
    public static function MobileHomePage($config)
    {
        return empty($config['mobile_home_page']) ? 'pages/index/index' : $config['mobile_home_page'];
    }

    /**
     * 特殊字符处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-09-30
     * @desc    description
     * @param   [string]          $string [字符串]
     */
    public static function StringToEntities($string)
    {
        $len = strlen($string);
        $buf = '';
        for($i=0; $i<$len; $i++)
        {
            if (ord($string[$i]) <= 127)
            {
                $buf .= $string[$i];
            } else if (ord ($string[$i]) <192)
            {
                //unexpected 2nd, 3rd or 4th byte
                $buf .= "&#xfffd";
            } else if (ord ($string[$i]) <224)
            {
                //first byte of 2-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($string[$i + 0]) & 31) << 6) +
                    (ord($string[$i + 1]) & 63)
                );
                $i += 1;
            } else if (ord ($string[$i]) <240)
            {
                //first byte of 3-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($string[$i + 0]) & 15) << 12) +
                    ((ord($string[$i + 1]) & 63) << 6) +
                    (ord($string[$i + 2]) & 63)
                );
                $i += 2;
            } else {
                //first byte of 4-byte seq
                $buf .= sprintf("&#%d;",
                    ((ord($string[$i + 0]) & 7) << 18) +
                    ((ord($string[$i + 1]) & 63) << 12) +
                    ((ord($string[$i + 2]) & 63) << 6) +
                    (ord($string[$i + 3]) & 63)
                );
                $i += 3;
            }
        }
        return $buf;
    }

    /**
     * 字符串合并
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-08T18:25:39+0800
     * @param    [resource]          $bg                    [背景图像资源]
     * @param    [string]            $string                [被合并字符串]
     * @param    [string]            $nickname_color        [颜色]
     * @param    [string]            $nickname_auto_center  [是否自动计算居中位置]
     * @param    [int]               $string_left           [被合并字符串左侧距离]
     * @param    [int]               $string_top            [被合并字符串顶部距离]
     */
    public static function StringMerge($bg, $string, $nickname_color = '#666', $nickname_auto_center = 1, $string_left = null, $string_top = null)
    {
        // 背景图像尺寸
        $bg_width = imagesx($bg);
        $bg_height = imagesy($bg);
        $font_size = 22;

        // 字符串宽度计算
        $p = imagettfbbox($font_size, 0, BaseService::$font_path, self::StringToEntities($string));
        $string_width = $p[2]-$p[0];

        // 十六进制转RGB
        $rgb = HexToRgb($nickname_color);

        // 字符串颜色
        $color = imagecolorallocate($bg, $rgb['r'], $rgb['g'], $rgb['b']);

        // 未配置或者居中则计算字符串居中
        if($string_left === null || $nickname_auto_center == 1)
        {
            $string_left = ($bg_width-$string_width)/2;

        // 设置左侧距离则根据海报宽度计算距离
        } else {
            $string_left = $bg_width/(self::$benchmark_width/$string_left);
        }

        // 顶部距离
        if($string_top > 0) {
            $string_top = $bg_height/(((self::$benchmark_width/$bg_width)*$bg_height)/$string_top);
        }
        $string_top += 30;

        // 字体路径
        $font_path = BaseService::$font_path;

        // 生成文本
        imagettftext($bg, $font_size, 0, $string_left, $string_top, $color, $font_path, self::StringToEntities($string));
        return $bg;
    }

    /**
     * 图像合并
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-06T22:10:49+0800
     * @param    [resource]          $bg         [背景图像资源]
     * @param    [resource]          $img        [被合并图像资源]
     * @param    [int]               $img_width  [被合并图像宽度]
     * @param    [int]               $img_height [被合并图像高度]
     * @param    [int]               $img_left   [被合并图像左侧距离]
     * @param    [int]               $img_top    [被合并图像顶部距离]
     * @param    [int]               $opacity    [透明度（默认100）]
     */
    public static function ImagesMerge($bg, $img, $img_width, $img_height, $img_left = null, $img_top = null, $opacity = 100)
    {
        // 背景图像尺寸
        $bg_width = imagesx($bg);
        $bg_height = imagesy($bg);

        // 左侧距离计算
        // 未设置则取中间数值
        if($img_left === null)
        {
            $img_left = ($bg_width/2)-($img_width/2);

        // 设置左侧距离则根据海报宽度计算距离
        } elseif($img_left > 0) {
            $img_left = $bg_width/(self::$benchmark_width/$img_left);
        }

        // 顶部距离
        if($img_top > 0) {
            $img_top = $bg_height/(((self::$benchmark_width/$bg_width)*$bg_height)/$img_top);
        }

        // 合并操作
        imagecopymerge($bg, $img, $img_left, $img_top, 0, 0, $img_width, $img_height, $opacity);
        return $bg;
    }

    /**
     * 圆形图片
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-06T21:54:44+0800
     * @param    [resource]            $img    [图像资源]
     * @param    [string]              $type   [外观类型（圆形 circle, 圆角 radius）]
     */
    public static function ImagesAppearance($img, $type = 'circle')
    {
        $c = imagecolorallocate($img, 255, 0, 0);
        $w = imagesx($img);
        $h = imagesy($img);
        $tw = ($type == 'circle') ? $w : $w+(0.41*$w);
        $th = ($type == 'circle') ? $h : $h+(0.41*$h);
        imagearc($img, $w/2, $h/2, $tw, $th, 0, 360, $c);
        imagefilltoborder($img, 0, 0, $c, $c);
        imagefilltoborder($img, $w, 0, $c, $c);
        imagefilltoborder($img, 0, $h, $c, $c);
        imagefilltoborder($img, $w, $h, $c, $c);
        imagecolortransparent($img, $c);
        return $img;
    }

    /**
     * 图像大小改变
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-07T00:40:21+0800
     * @param    [string]              $filename    [图像地址（远程地址）]
     * @param    [resource]            $img         [图像资源]
     * @param    [int]                 $width       [设定的宽度]
     * @param    [int]                 $height      [设定的高度]
     */
    public static function ImagesResize($filename, $img, $width, $height)
    {
        // 获取后缀名
        $ext = explode('.', $filename);
        $ext = strtolower($ext[count($ext)-1]);

        // 原始尺寸
        $w = imagesx($img);
        $h = imagesy($img);

        // 创建彩色背景
        $thumb = imagecreatetruecolor($width, $height);

        // png背景设置为白色
        if($ext == 'png')
        {
            imagefilledrectangle($thumb, 0, 0, $width, $height, imagecolorallocate($thumb, 255, 255, 255));
        }
        imagecopyresampled($thumb, $img, 0, 0, 0, 0, $width, $height, $w, $h);
        return $thumb; 
    }

    /**
     * 用户海报刷新
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-08T23:35:26+0800
     * @param    [array]       $params [输入参数]
     */
    public static function UserPosterRefresh($params = [])
    {
        $ret = self::UserPoster(['is_refresh'=>true]);
        if($ret['code'] == 0)
        {
            return DataReturn('刷新成功', 0, $ret['data']);
        }
        return $ret;
    }

    /**
     * 快手小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniKuaishouQrcode($params, $user_id, $config)
    {
        // 配置信息
        $appid = AppMiniUserService::AppMiniConfig('common_app_mini_kuaishou_appid');

        // 二维码内容
        $url = 'kwai://miniapp?appId='.$appid.'&KSMP_source=011012&KSMP_internal_source=011012&path='.urlencode(self::MobileHomePage($config).'?referrer='.$user_id);
        $params['content'] = $url;

        // 创建二维码
        $ret = (new \base\Qrcode())->Create($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }
        return DataReturn('获取成功', 0);
    }

    /**
     * 微信小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniWechatQrcode($params, $user_id, $config)
    {
        // 配置信息
        $appid = AppMiniUserService::AppMiniConfig('common_app_mini_weixin_appid');
        $appsecret = AppMiniUserService::AppMiniConfig('common_app_mini_weixin_appsecret');
        if(empty($appid) || empty($appsecret))
        {
            return DataReturn('微信小程序密钥未配置', -1);
        }

        // 请求参数
        $wx_params = [
            'page'  => self::MobileHomePage($config),
            'scene' => 'referrer='.$user_id,
            'width' => 300,
        ];
        $obj = new \base\Wechat($appid, $appsecret);
        $ret = $obj->MiniQrCodeCreate($wx_params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 保存二维码
        if(@file_put_contents($params['dir'], $ret['data']) !== false)
        {
            return DataReturn('获取成功', 0);
        }
        return DataReturn('二维码保存失败', -1);
    }

    /**
     * QQ小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniQQQrcode($params, $user_id, $config)
    {
        // 配置信息
        $appid = AppMiniUserService::AppMiniConfig('common_app_mini_qq_appid');

        // 二维码内容
        $url = 'https://m.q.qq.com/a/p/'.$appid.'?s='.urlencode(self::MobileHomePage($config).'?referrer='.$user_id);
        $params['content'] = $url;

        // 创建二维码
        $ret = (new \base\Qrcode())->Create($params);
        if($ret['code'] != 0)
        {
            return $ret;
        }
        return DataReturn('获取成功', 0);
    }

    /**
     * 支付宝小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniAlipayQrcode($params, $user_id, $config)
    {
        // 配置信息
        $appid = AppMiniUserService::AppMiniConfig('common_app_mini_alipay_appid');
        if(empty($appid))
        {
            return DataReturn('支付宝小程序密钥未配置', -1);
        }

        // 请求参数
        $request_params = [
            'appid' => $appid,
            'page'  => self::MobileHomePage($config),
            'scene' => 'referrer='.$user_id,
            'width' => 300,
        ];
        $ret = (new \base\Alipay())->MiniQrCodeCreate($request_params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 保存二维码
        if(@file_put_contents($params['dir'], RequestGet($ret['data'])) !== false)
        {
            return DataReturn('获取成功', 0);
        }
        return DataReturn('二维码保存失败', -1);
    }

    /**
     * 头条小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniToutiaoQrcode($params, $user_id, $config)
    {
        // 配置信息
        $config = [
            'appid'     => AppMiniUserService::AppMiniConfig('common_app_mini_toutiao_appid'),
            'secret'    => AppMiniUserService::AppMiniConfig('common_app_mini_toutiao_appsecret'),
        ];
        if(empty($config['appid']) || empty($config['secret']))
        {
            return DataReturn('头条小程序密钥未配置', -1);
        }

        // 请求参数
        $request_params = [
            'page'  => self::MobileHomePage($config),
            'scene' => 'referrer='.$user_id,
            'width' => 300,
        ];

        $ret = (new \base\Toutiao($config))->MiniQrCodeCreate($request_params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 保存二维码
        if(@file_put_contents($params['dir'], $ret['data']) !== false)
        {
            return DataReturn('获取成功', 0);
        }
        return DataReturn('二维码保存失败', -1);
    }

    /**
     * 百度小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]           $params  [二维码相关参数]
     * @param    [int]             $user    [用户id]
     * @param    [array]           $config  [插件配置]
     */
    private static function UserCreateMiniBaiduQrcode($params, $user_id, $config)
    {
        // 配置信息
        $config = [
            'appid'     => AppMiniUserService::AppMiniConfig('common_app_mini_baidu_appid'),
            'key'       => AppMiniUserService::AppMiniConfig('common_app_mini_baidu_appkey'),
            'secret'    => AppMiniUserService::AppMiniConfig('common_app_mini_baidu_appsecret'),
        ];
        if(empty($config['appid']) || empty($config['key']) || empty($config['secret']))
        {
            return DataReturn('百度小程序密钥未配置', -1);
        }

        // 请求参数
        $request_params = [
            'page'  => self::MobileHomePage($config),
            'scene' => 'referrer='.$user_id,
            'width' => 300,
        ];

        $ret = (new \base\Baidu($config))->MiniQrCodeCreate($request_params);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 保存二维码
        if(@file_put_contents($params['dir'], $ret['data']) !== false)
        {
            return DataReturn('获取成功', 0);
        }
        return DataReturn('二维码保存失败', -1);
    }
}
?>