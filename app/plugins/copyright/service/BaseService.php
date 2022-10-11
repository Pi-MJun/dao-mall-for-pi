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
namespace app\plugins\copyright\service;

use think\facade\Db;
use app\service\ConfigService;
use app\service\PluginsService;
use app\service\AppMiniService;

/**
 * 版权修改 - 基础服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class BaseService
{
    // 基础数据附件字段
    public static $base_config_attachment_field = [];

    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        $ret = PluginsService::PluginsData('copyright', self::$base_config_attachment_field, $is_cache);

        // 空值处理
        if(empty($ret['data']))
        {
            $ret['data'] = [];
        }

        // 后端站点名称
        if(empty($ret['data']['admin_site_name']))
        {
            $ret['data']['admin_site_name'] = 'ShopXO';
        }

        // web端底部版权信息
        if(empty($ret['data']['web_bottom_powered']))
        {
            $ret['data']['web_bottom_powered'] = 'Powered by <a href="http://shopxo.net/" title="ShopXO电商系统" target="_blank"><span class="b">Shop</span><span class="o">XO</span></a>';
        } else {
            $ret['data']['web_bottom_powered'] = htmlspecialchars_decode($ret['data']['web_bottom_powered']);
        }
        $ret['data']['web_bottom_powered'] = explode("\n", $ret['data']['web_bottom_powered']);

        // 小程序端底部版权信息
        if(empty($ret['data']['appmini_bottom_powered']))
        {
            $ret['data']['appmini_bottom_powered'] = "<template name=\"copyright\">\n&nbsp;&nbsp;&nbsp;&nbsp;<view class=\"copyright\">\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<view class=\"text\">Powered by ShopXO ".APPLICATION_VERSION."</view>\n&nbsp;&nbsp;&nbsp;&nbsp;</view>\n</template>";
        } else {
            $ret['data']['appmini_bottom_powered'] = htmlspecialchars_decode($ret['data']['appmini_bottom_powered']);
        }
        $ret['data']['appmini_bottom_powered'] = explode("\n", $ret['data']['appmini_bottom_powered']);

        return $ret;
    }

    /**
     * 基础配置信息保存
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        // 保存配置信息
        $ret = PluginsService::PluginsDataSave(['plugins'=>'copyright', 'data'=>$params], self::$base_config_attachment_field);
        if($ret['code'] == 0)
        {
            $ret = self::CopyrightHandle();
        }
        return $ret;
    }

    /**
     * 版权修改处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-13
     * @desc    description
     */
    public static function CopyrightHandle()
    {
        // 获取配置信息
        $base = self::BaseConfig();
        if(empty($base) || empty($base['data']))
        {
            return DataReturn('请先填写配置', -1);
        }

        // web端
        $ret = self::CopyrightWebHandle($base['data']);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 小程序端
        $ret = self::CopyrightAppMiniHandle($base['data']);
        if($ret['code'] != 0)
        {
            return $ret;
        }

        // 更新配置信息缓存
        ConfigService::ConfigInit(1);

        return DataReturn('操作成功', 0);
    }

    /**
     * 小程序端版权处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-13
     * @desc    description
     * @param   [array]          $config [插件数据]
     */
    public static function CopyrightAppMiniHandle($config)
    {
        // 小程序平台+文件后缀名
        $appmini_list = [
            'weixin'    => 'wxml',
            'alipay'    => 'axml',
            'baidu'     => 'swan',
            'qq'        => 'qml',
            'toutiao'   => 'ttml',
        ];

        // 小程序目录位置
        $dir = ROOT.'sourcecode';

        // 更新内容
        $content = str_replace('&nbsp;', ' ', implode("\n", $config['appmini_bottom_powered']));

        // 循环处理
        foreach($appmini_list as $k=>$v)
        {
            // 文件位置
            $theme = MyC(AppMiniService::DefaultThemeKey(['application_name'=>$k]), 'default', true);
            $file = $dir.DS.$k.DS.$theme.DS.'pages'.DS.'common'.DS.'copyright.'.$v;

            // 文件是否存在
            if(file_exists($file))
            {
                // 是否有权限
                if(!is_writable($file))
                {
                    return DataReturn('文件没写权限['.$file.']', -1);
                }

                // 更改内容
                if(file_put_contents($file, $content) === false)
                {
                    return DataReturn('文件更改失败['.$file.']', -1);
                }
            }
        }

        return DataReturn('success', 0);
    }

    /**
     * web端版权处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-04-13
     * @desc    description
     * @param   [array]          $config [插件数据]
     */
    public static function CopyrightWebHandle($config)
    {
        // 后端站点名称
        $data = [
            'value'     => $config['admin_site_name'],
            'upd_time'  => time(),
        ];
        if(Db::name('Config')->where(['only_tag'=>'admin_theme_site_name'])->update($data) === false)
        {
            return DataReturn('后端站点名称更改失败', -1);
        }

        // web端底部版权信息
        $data = [
            'value'     => $content = implode("\n", $config['web_bottom_powered']),
            'upd_time'  => time(),
        ];
        if(Db::name('Config')->where(['only_tag'=>'home_theme_footer_bottom_powered'])->update($data) === false)
        {
            return DataReturn('web端底部版权信息更改失败', -1);
        }

        return DataReturn('success', 0);
    }
}
?>