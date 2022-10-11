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
use app\service\GoodsService;
use app\service\SystemService;
use app\service\AppMiniUserService;
use app\plugins\distribution\service\BaseService;

/**
 * 分销 - 商品海报服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class PosterGoodsService
{
    /**
     * 微信小程序商品海报生成
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-16T21:59:43+0800
     * @param    [array]            $params [输入参数]
     */
    public static function GoodsCreateMiniWechat($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'goods_id',
                'error_msg'         => '商品id有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 是否刷新
        $is_refresh = isset($params['is_refresh']) ? (boolean)$params['is_refresh'] : false;

        // 用户信息
        $user = UserService::LoginUserInfo();
        if(empty($user))
        {
            return DataReturn('请先登录', -400);
        }

        // 商品信息
        $goods_params = [
            'where' => [
                ['id', '=', intval($params['goods_id'])],
                ['is_delete_time', '=', 0],
                ['is_shelves', '=', 1],
            ],
            'field' => 'id,title,simple_desc,images,price,original_price,add_time,upd_time',
            'm'     => 0,
            'n'     => 1,
        ];
        $goods = GoodsService::GoodsList($goods_params);
        if(empty($goods['data'][0]))
        {
            return DataReturn('商品不存在', -400);
        } else {
            $goods = $goods['data'][0];
            $goods['add_time'] = is_int($goods['add_time']) ? $goods['add_time'] : strtotime($goods['add_time']);
            $goods['upd_time'] = is_int($goods['upd_time']) ? $goods['upd_time'] : strtotime($goods['upd_time']);
        }

        // 基础配置
        $base = BaseService::BaseConfig();
        if($base['code'] != 0)
        {
            return $base;
        }

        // 系统类型
        $system_type = SystemService::SystemTypeValue();

        // 获取商品二维码
        $qrcode = self::UserCreateGoodsMiniQrcode($goods, $user, $base['data'], $system_type);
        if($qrcode['code'] != 0)
        {
            return $qrcode;
        }

        // 海报地址
        $poster_path = 'static'.DS.'upload'.DS.'images'.DS.'plugins_distribution'.DS.'poster_goods'.DS.$system_type.DS.APPLICATION_CLIENT_TYPE.DS.date('Y', $goods['add_time']).DS.date('m', $goods['add_time']).DS.date('d', $goods['add_time']).DS;
        $poster_filename = date('YmdHis', $goods['upd_time']).$goods['id'].$user['id'].'.png';
        $poster_dir = ROOT.'public'.DS.$poster_path.$poster_filename;

        // 已存在则直接返回
        if(file_exists($poster_dir) && $is_refresh === false)
        {
            return DataReturn('商品海报创建成功', 0, ResourcesService::AttachmentPathViewHandle(DS.$poster_path.$poster_filename));
        }

        // gd函数是否支持
        if(!function_exists('imagettftext'))
        {
            return DataReturn('imagettftext函数不支持', -1);
        }

        // 基础配置
        $poster_config = BaseService::PosterGoodsData();
        if($poster_config['code'] != 0)
        {
            return $poster_config;
        } else {
            $poster_config = $poster_config['data'];
        }

        // 字体路径
        $font_path = BaseService::$font_path;

        // 目录不存在则创建
        if(\base\FileUtil::CreateDir(ROOT.'public'.DS.$poster_path) !== true)
        {
            return DataReturn('商品海报目录创建失败', -10);
        }

        // 商品标题颜色
        if(empty($poster_config['goods_title_text_color']))
        {
            $goods_title_text_color = '0,0,0';
        } else {
            $rgb = HexToRgb($poster_config['goods_title_text_color']);
            $goods_title_text_color = $rgb['r'].','.$rgb['g'].','.$rgb['b'];
        }

        // 底部左侧文本颜色
        if(empty($poster_config['bottom_left_text_color']))
        {
            $bottom_left_text_color = '0,0,0';
        } else {
            $rgb = HexToRgb($poster_config['bottom_left_text_color']);
            $bottom_left_text_color = $rgb['r'].','.$rgb['g'].','.$rgb['b'];
        }

        // 底部右侧文本颜色
        if(empty($poster_config['bottom_right_text_color']))
        {
            $bottom_right_text_color = '255,0,0';
        } else {
            $rgb = HexToRgb($poster_config['bottom_right_text_color']);
            $bottom_right_text_color = $rgb['r'].','.$rgb['g'].','.$rgb['b'];
        }

        // 商品图片
        // 远程则下载图片
        $is_goods_images_remote = false;
        if(substr($goods['images_old'], 0, 4) == 'http')
        {
            // 获取图片二进制
            $path = ROOT.'runtime'.DS.'data'.DS.'plugins_distribution'.DS;
            $filename = date('YmdHis', $goods['upd_time']).$goods['id'].$user['id'].'.jpg';
            $res = RequestGet($goods['images_old']);
            if(empty($res))
            {
                return DataReturn('商品主图远程下载失败', -11);
            }

            // 目录不存在则创建
            if(\base\FileUtil::CreateDir($path) !== true)
            {
                return DataReturn('商品临时目录创建失败', -1);
            }

            // 存储文件
            $goods_images_path = $path.$filename;
            if(file_put_contents($goods_images_path, $res) === false)
            {
                return DataReturn('商品主图远程下载失败', -12);
            }
            $is_goods_images_remote = true;
        } else {
            $goods_images_path = ROOT.'public'.$goods['images_old'];
        }

        // 静态资源路径
        $static_path = ROOT.'public'.DS.'static'.DS.'plugins'.DS.'images'.DS.'distribution'.DS;

        // 背景图片
        $background_path = $static_path.'poster-goods-background.jpg';

        // 线条
        $line_path = $static_path.'poster-goods-line.jpg';

        // 二维码地址
        $qrcode = ROOT.'public'.$qrcode['data'];

        // 货币符号
        $currency_symbol = ResourcesService::CurrencyDataSymbol();

        // 配置信息
        $config = [
            'background' =>  $background_path,   //背景图
            'image' => [
                [
                    'url' => $qrcode,   //二维码地址
                    'is_yuan' => true,   //true图片圆形处理
                    'stream' => 0,
                    'left' => 570,   //小于0为小平居中
                    'top' => 1060,
                    'right' => 0,
                    'width' => 300,   //图像宽
                    'height' => 300,   //图像高
                    'opacity' => 100,   //透明度
                ],
                [
                    'url' => $goods_images_path,   //商品主图
                    'is_yuan' => false,
                    'stream' => 0,
                    'left' => 0,
                    'top' => 0,
                    'right' => 0,
                    'width' => 900,
                    'height' => 900,
                    'opacity' => 100,
                ],
                [
                    'url' => $line_path,   //线条
                    'is_yuan' => false,
                    'stream' => 0,
                    'left' => 0,
                    'top' => 1500,
                    'right' => 0,
                    'width' => 900,
                    'height' => 1,
                    'opacity' => 100,
                ],
            ],
            'text' => [
                [
                    'text' => $goods['title'],   //文字内容
                    'left' => 20,   //小于0为小平居中
                    'top' => 960,
                    'font_size' => 28,   //字号
                    'font_color' => $goods_title_text_color,   //字体颜色
                    'angle' => 0,   // 旋转
                    'width' => 880,   // 空或者小于0则取背景宽度默认值
                    'font_path' => $font_path,   //字体文件
                ],
                [
                    'text' => '优惠价',
                    'left' => 20,
                    'top' => 1120,
                    'font_size' => 28,
                    'font_color' => '85,85,85',
                    'angle' => 0,
                    'font_path' => $font_path,
                ],
                [
                    'text' => $currency_symbol.$goods['price'],
                    'left' => 130,
                    'top' => 1120,
                    'font_size' => 28,
                    'font_color' => '204,0,0,1',
                    'angle' => 0,
                    'font_path' => $font_path,
                ],
                [
                    'text' => empty($poster_config['bottom_left_text']) ? '长按识别或保存到相册' : $poster_config['bottom_left_text'],
                    'left' => 60,
                    'top' => 1565,
                    'font_size' => 28,
                    'font_color' => $bottom_left_text_color,
                    'angle' => 0,
                    'font_path' => $font_path,
                ],
                [
                    'text' => empty($poster_config['bottom_right_text']) ? '扫码购买' : $poster_config['bottom_right_text'],
                    'left' => 600,
                    'top' => 1565,
                    'font_size' => 28,
                    'font_color' => $bottom_right_text_color,
                    'angle' => 0,
                    'font_path' => $font_path,
                ],
            ],
        ];

        // 商品原价
        if(!empty($goods['original_price']) && $goods['original_price'] != '0.00')
        {
            $config['text'][] = [
                    'text' => '原价'.$currency_symbol.$goods['original_price'],
                    'left' => 20,
                    'top' => 1180,
                    'font_size' => 24,
                    'font_color' => '169,169,169',
                    'angle' => 0,
                    'font_path' => $font_path,
                ];
        }

        // 商品简述
        if(!empty($goods['simple_desc']))
        {
            if(empty($poster_config['goods_simple_text_color']))
            {
                $goods_simple_text_color = '169,169,169';
            } else {
                $rgb = HexToRgb($poster_config['goods_simple_text_color']);
                $goods_simple_text_color = $rgb['r'].','.$rgb['g'].','.$rgb['b'];
            }
            $config['text'][] = [
                    'text' => $goods['simple_desc'],
                    'left' => 20,
                    'top' => 1260,
                    'font_size' => 26,
                    'font_color' => $goods_simple_text_color,
                    'angle' => 0,
                    'width' => 520,
                    'font_path' => $font_path,
                ];
        }
        
        // 生成商品海报
        $status = self::CreatePoster($config, $poster_dir);

        // 远程图片则删除本地临时图片
        if($is_goods_images_remote == true)
        {
            \base\FileUtil::UnlinkFile($goods_images_path);
        }

        // 返回数据
        if($status)
        {
            return DataReturn('商品海报创建成功', 0, ResourcesService::AttachmentPathViewHandle(DS.$poster_path.$poster_filename));
        }
        return DataReturn('商品海报创建失败', -100);
    }

    /**
     * 生成商品海报
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-08-16
     * @desc    description
     * @param   [array]           $config   [参数,包括图片和文字]
     * @param   [string]          $filename [生成海报文件名,不传此参数则不生成文件,直接输出图片]
     */
    private static function CreatePoster($config = [], $filename = '')
    {
        // 默认图片
        $image_default = [
            'left' => 0,
            'top' => 0,
            'right' => 0,
            'bottom' => 0,
            'width' => 100,
            'height' => 100,
            'opacity' => 100
        ];

        // 默认文本
        $text_default = [
            'text' => '',
            'left' => 0,
            'top' => 0,
            'font_size' => 32, //字号
            'font_color' => '255,255,255', //字体颜色
            'angle' => 0,
        ];

        // 海报最底层得背景
        $background = $config['background'];

        // 背景
        $background_info = getimagesize($background);
        $background_fun = 'imagecreatefrom' . image_type_to_extension($background_info[2], false);
        $background = $background_fun($background);
        $background_width = imagesx($background);
        $background_height = imagesy($background);
        $image_res = imageCreatetruecolor($background_width, $background_height);
        $color = imagecolorallocate($image_res, 0, 0, 0);
        imagefill($image_res, 0, 0, $color);
        imagecopyresampled($image_res, $background, 0, 0, 0, 0, imagesx($background), imagesy($background), imagesx($background), imagesy($background));

        // 处理图片
        if(!empty($config['image']))
        {
            foreach($config['image'] as $key=>$val)
            {
                $val = array_merge($image_default, $val);
                $info = getimagesize($val['url']);
                $ext = (is_array($info) && isset($info[2])) ? image_type_to_extension($info[2], false) : 'jpeg';
                $function = 'imagecreatefrom'.$ext;

                // 如果传的是字符串图像流
                if($val['stream'])
                {
                    $info = getimagesizefromstring($val['url']);
                    $function = 'imagecreatefromstring';
                }
                $res = @$function($val['url']);
                $res_width = $info[0];
                $res_height = $info[1];

                // 建立画板 ，缩放图片至指定尺寸
                $canvas = imagecreatetruecolor($val['width'], $val['height']);
                imagefill($canvas, 0, 0, $color);

                // 如果是透明的gif或png做透明处理
                $ext = pathinfo($val['url']);
                if(array_key_exists('extension', $ext))
                {
                    if($ext['extension'] == 'gif' || $ext['extension'] == 'png')
                    {
                        // 颜色透明
                        imageColorTransparent($canvas, $color);
                    }
                }
                // 关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
                imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'], $res_width, $res_height);

                // 如果left小于-1我这做成了计算让其水平居中
                if ($val['left'] < 0) {
                    $val['left'] = ceil($background_width - $val['width']) / 2;
                }
                $val['top'] = $val['top'] < 0 ? $background_height - abs($val['top']) - $val['height'] : $val['top'];

                // 放置图像
                // 左，上，右，下，宽度，高度，透明度
                imagecopymerge($image_res, $canvas, $val['left'], $val['top'], $val['right'], $val['bottom'], $val['width'], $val['height'], $val['opacity']);
            }
        }
        // 处理文字
        if(!empty($config['text']))
        {
            foreach($config['text'] as $key=>$val)
            {
                $val = array_merge($text_default, $val);
                list($r, $g, $b) = explode(',', $val['font_color']);
                $font_color = imagecolorallocate($image_res, $r, $g, $b);

                // 如果left小于-1我这做成了计算让其水平居中
                $text_width = empty($val['width']) ? 440 : $val['width'];
                $text = self::TextAutoWrap($val['font_size'], 0, $val['font_path'], $val['text'], $text_width);
                if($val['left'] < 0)
                {
                    // 文字水平居中实质
                    $font_box = imagettfbbox($val['font_size'], 0, $val['font_path'], self::TextToUtf8($text));

                    // 计算文字的水平位置
                    $val['left'] = ceil(($background_width - $font_box[2]) / 2);

                }
                $val['top'] = $val['top'] < 0 ? $background_height - abs($val['top']) : $val['top'];
                imagettftext($image_res, $val['font_size'], $val['angle'], $val['left'], $val['top'], $font_color, $val['font_path'], self::TextToUtf8($text));
            }
        }
        //生成图片
        if(!empty($filename))
        {
            //保存到本地
            $res = imagejpeg($image_res, $filename, 100);
            imagedestroy($image_res);
            return $res;
        } else {
            //在浏览器上显示
            header("Content-type:image/png");
            imagejpeg($image_res);
            imagedestroy($image_res);
        }
    }

    /**
     * 文本长度计算
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-16T21:07:08+0800
     * @param    [int]                   $fontsize  [字体大小]
     * @param    [int]                   $angle     [是否旋转角度]
     * @param    [string]                $font_path [字体路径]
     * @param    [string]                $string    [字符串]
     * @param    [int]                   $width     [字符宽度]
     */
    private static function TextAutoWrap($fontsize, $angle, $font_path, $string, $width)
    {
        // 这几个变量分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度
        $content = '';

        // 将字符串拆分成一个个单字 保存到数组 letter 中
        $letter = [];
        for($i = 0; $i < mb_strlen($string, 'utf-8'); $i++)
        {
            $letter[] = mb_substr($string, $i, 1, 'utf-8');
        }

        foreach($letter as $l)
        {
            $temp_str =  self::TextToUtf8($content . ' ' . $l);
            $temp_box = imagettfbbox($fontsize, $angle, $font_path, $temp_str);

            // 判断拼接后的字符串是否超过预设的宽度
            if(($temp_box[2] > $width) && ($content !== ''))
            {
                $content .= "\n";
            }
            $content .= $l;
        }
        return $content;
    }

    /**
     * 文本编码转换
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [string]           $text      [文本信息]
     */
    public static function TextToUtf8($text)
    {
        return mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8');
    }

    /**
     * 获取小程序二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]                $goods          [商品信息]
     * @param    [array]                $user           [用户信息]
     * @param    [array]                $config         [插件配置信息]
     * @param    [string]               $system_type    [系统类型]
     */
    public static function UserCreateGoodsMiniQrcode($goods, $user, $config, $system_type)
    {
        // 商品二维码地址
        $path = 'static'.DS.'upload'.DS.'images'.DS.'plugins_distribution'.DS.'poster_goods_qrcode'.DS.$system_type.DS.APPLICATION_CLIENT_TYPE.DS.date('Y', $goods['add_time']).DS.date('m', $goods['add_time']).DS.date('d', $goods['add_time']).DS;
        $filename = date('YmdHis', $goods['add_time']).$goods['id'].$user['id'].'.jpg';
        $qrcode_dir = ROOT.'public'.DS.$path.$filename;

        // 已存在二维码则直接返回
        if(file_exists($qrcode_dir))
        {
            return DataReturn('获取成功', 0, DS.$path.$filename);
        }

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

        // 根据客户端类型生成不同的二维码
        switch(APPLICATION_CLIENT_TYPE)
        {
            // 微信小程序
            case 'weixin' :
                $ret = self::CreateMiniWechatQrcode($goods, $user, $params);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
                break;

            // QQ小程序
            case 'qq' :
                $ret = self::CreateMiniQQQrcode($goods, $user, $params);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
                break;

            // 支付宝小程序
            case 'alipay' :
                $ret = self::UserCreateMiniAlipayQrcode($goods, $user, $params);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
                break;

            // 头条小程序
            case 'toutiao' :
                $ret = self::UserCreateMiniToutiaoQrcode($goods, $user, $params);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
                break;

            // 百度小程序
            case 'baidu' :
                $ret = self::UserCreateMiniBaiduQrcode($goods, $user, $params);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
                break;

            // 快手小程序
            case 'kuaishou' :
                $ret = self::CreateMiniKuaishouQrcode($goods, $user, $params);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
                break;

            // 默认
            default :
                // url/logo
                $params['content'] = self::UserGoodsShareUrl($goods, $user, $config, $params);
                $params['logo'] = empty($config['default_qrcode_logo']) ? '' : $config['default_qrcode_logo'];

                // 创建二维码
                $ret = (new \base\Qrcode())->Create($params);
                if($ret['code'] != 0)
                {
                    return $ret;
                }
        }
        return DataReturn('获取成功', 0, DS.$path.$filename);
    }

    /**
     * 获取用户分享url地址
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-06-08T21:08:47+0800
     * @param    [array]                $goods      [商品信息]
     * @param    [array]                $user       [用户信息]
     * @param    [array]                $config     [插件配置信息]
     * @param    [array]                $params     [二维码相关参数]
     */
    public static function UserGoodsShareUrl($goods, $user, $config, $params)
    {
        // 分享信息
        $referrer = UserService::UserReferrerEncryption($user['id']);

        // web端商品url地址
        $url = MyUrl('index/goods/index', ['id'=>$goods['id'], 'referrer'=>$referrer]);

        // h5端地址处理
        if(APPLICATION_CLIENT_TYPE == 'h5')
        {
            $temp = BaseService::H5Url($config);
            if(!empty($temp))
            {
                $url = $temp.'pages/goods-detail/goods-detail?id='.$goods['id'].'&referrer='.$referrer;
            }
        }

        return $url;
    }

    /**
     * 快手小程序获取二维码
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-17T00:27:35+0800
     * @param    [array]                $goods      [商品信息]
     * @param    [array]                $user       [用户信息]
     * @param    [array]                $params     [二维码相关参数]
     */
    private static function CreateMiniKuaishouQrcode($goods, $user, $params)
    {
        // 配置信息
        $appid = AppMiniUserService::AppMiniConfig('common_app_mini_kuaishou_appid');

        // 二维码内容
        $url = 'kwai://miniapp?appId='.$appid.'&KSMP_source=011012&KSMP_internal_source=011012&path='.urlencode('pages/goods-detail/goods-detail?id='.$goods['id'].'&referrer='.$user['id']);
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
     * @param    [array]                $goods      [商品信息]
     * @param    [array]                $user       [用户信息]
     * @param    [array]                $params     [二维码相关参数]
     */
    private static function CreateMiniWechatQrcode($goods, $user, $params)
    {
        // 配置信息
        $appid = AppMiniUserService::AppMiniConfig('common_app_mini_weixin_appid');
        $appsecret = AppMiniUserService::AppMiniConfig('common_app_mini_weixin_appsecret');

        // 请求参数
        $wx_params = [
            'page'  => 'pages/goods-detail/goods-detail',
            'scene' => 'id='.$goods['id'].'&referrer='.$user['id'],
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
     * @param    [array]                $goods      [商品信息]
     * @param    [array]                $user       [用户信息]
     * @param    [array]                $params     [二维码相关参数]
     */
    private static function CreateMiniQQQrcode($goods, $user, $params)
    {
        // 配置信息
        $appid = AppMiniUserService::AppMiniConfig('common_app_mini_qq_appid');

        // 二维码内容
        $url = 'https://m.q.qq.com/a/p/'.$appid.'?s='.urlencode('pages/goods-detail/goods-detail?id='.$goods['id'].'&referrer='.$user['id']);
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
     * @param    [array]                $goods      [商品信息]
     * @param    [array]                $user       [用户信息]
     * @param    [array]                $params     [二维码相关参数]
     */
    private static function UserCreateMiniAlipayQrcode($goods, $user, $params)
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
            'page'  => 'pages/goods-detail/goods-detail',
            'scene' => 'id='.$goods['id'].'&referrer='.$user['id'],
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
     * @param    [array]                $goods      [商品信息]
     * @param    [array]                $user       [用户信息]
     * @param    [array]                $params     [二维码相关参数]
     */
    private static function UserCreateMiniToutiaoQrcode($goods, $user, $params)
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
            'page'  => 'pages/goods-detail/goods-detail',
            'scene' => 'id='.$goods['id'].'&referrer='.$user['id'],
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
     * @param    [array]                $goods      [商品信息]
     * @param    [array]                $user       [用户信息]
     * @param    [array]                $params     [二维码相关参数]
     */
    private static function UserCreateMiniBaiduQrcode($goods, $user, $params)
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
            'page'  => 'pages/goods-detail/goods-detail',
            'scene' => 'id='.$goods['id'].'&referrer='.$user['id'],
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