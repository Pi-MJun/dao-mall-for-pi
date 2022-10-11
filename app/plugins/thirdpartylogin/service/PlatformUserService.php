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
namespace app\plugins\thirdpartylogin\service;

use think\facade\Db;
use app\service\UserService;
use app\plugins\thirdpartylogin\service\BaseService;

/**
 * 第三方登录 - 平台用户服务层
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2020-09-04
 * @desc    description
 */
class PlatformUserService
{
    /**
     * 用户登录信息处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [string]         $platform       [平台类型]
     * @param   [array]          $data           [用户授权信息]
     * @param   [array]          $plugins_config [插件配置信息]
     */
    public static function PlatformUserLoginHandle($platform, $data, $plugins_config)
    {
        // 当前登录用户
        $login_user = UserService::LoginUserInfo();

        // 获取用户信息
        $user_id = 0;
        $platform_user_id = 0;
        $platform_user = self::OpenPlatformUserInfo($platform, $data);
        if(!empty($platform_user))
        {
            // 平台用户id
            $platform_user_id = $platform_user['id'];

            // 当前用户已登录
            if(empty($login_user))
            {
                // 赋值原始平台用户状态
                $data['status'] = $platform_user['status'];

                // 是否存在绑定用户、并确认用户是否存在、确保已绑定用户存在、避免已被删除
                if(!empty($platform_user['user_id']))
                {
                    // 用户是否存在
                    $user = UserService::UserInfo('id', $platform_user['user_id'], 'id');
                    if(empty($user))
                    {
                        $data['user_id'] = 0;
                        $data['status'] = ($platform_user['status'] == 1) ? 2 : 0;
                    } else {
                        $user_id = $user['id'];
                    }
                }
            } else {
                // 更新信息
                $data['status'] = 1;
                $data['user_id'] = $login_user['id'];

                // 用户id
                $user_id = $login_user['id'];
            }

            // openid 不一致的时候不更新用户数据
            // 存在unionid用户就不会再写入用户信息，插件用户登录表一个平台对应一条unionid数据
            if(!empty($platform_user['openid']) && $platform_user['openid'] != $data['openid'])
            {
                unset($data['openid']);
            }

            // 用户存在则更新用户数据
            $data['upd_time'] = time();
            if(!Db::name('PluginsThirdpartyloginUser')->where(['id'=>$platform_user['id']])->update($data))
            {
                return DataReturn('平台用户更新失败', -1);
            }
        } else {
            // 当前用户已登录
            $status = 0;
            if(!empty($login_user))
            {
                $status = 1;
                $user_id = $login_user['id'];
            }

            // 平台用户添加
            $platform_insert = self::PlatformUserInsert($data, $platform, $user_id, $status);
            if($platform_insert['code'] != 0)
            {
                return $platform_insert;
            }
            $data['status'] = $status;
            $platform_user_id = $platform_insert['data'];

            // 重新获取添加的用户信息
            $platform_user = Db::name('PluginsThirdpartyloginUser')->where(['id'=>$platform_user_id])->find();
        }

        // 用户状态正常、是否存在绑定用户
        if(isset($data['status']) && $data['status'] == 1 && !empty($user_id))
        {
            // 用户状态校验
            $check = UserService::UserStatusCheck('id', $user_id);
            if($check['code'] != 0)
            {
                return $check;
            }

            // 用户登录成功处理
            return self::UserLoginSuccessHandle($platform, $user_id, $data);
        }

        // 用户状态正常、openid或unionid是否存在用户表中
        $ret = self::SystemUserInfo($platform, $data);
        if($ret['code'] == 0)
        {
            // 用户状态校验
            $user_id = $ret['data'];
            $check = UserService::UserStatusCheck('id', $user_id);
            if($check['code'] != 0)
            {
                return $check;
            }

            // 绑定用户
            Db::name('PluginsThirdpartyloginUser')->where(['id'=>$platform_user_id])->update([
                'user_id'   => $user_id,
                'status'    => 1,
                'upd_time'  => time(),
            ]);

            // 用户登录成功处理
            return self::UserLoginSuccessHandle($platform, $user_id, $data);
        }

        // 是否开启强制绑定帐号
        if(isset($plugins_config['is_force_bind_user']) && $plugins_config['is_force_bind_user'] == 1)
        {
            // 平台名称
            $platform_user['platform_name'] = BaseService::PlatformTypeName($plugins_config, $platform_user['platform']);

            // 根据终端返回数据格式
            if(BaseService::GetApplicationClientType() == 'pc')
            {
                // 缓存用户绑定信息
                MySession(BaseService::$bind_platform_user_key, $platform_user_id);
                return DataReturn('需要绑定账号', 0, MyUrl('index/user/logininfo'));
            } else {
                $platform_user['is_force_bind_user'] = 1;
                return DataReturn('需要绑定账号', 0, $platform_user);
            }
        }

        // 直接写入数据库并登录返回
        $user_insert = self::UserInsert($platform, $data);
        if($user_insert['code'] != 0)
        {
            return $user_insert;
        }

        // 更新平台用户关联id
        $platform_bind = self::PlatformUserBind($platform_user_id, $user_insert['data']);
        if($platform_bind['code'] != 0)
        {
            return $platform_bind;
        }

        // 用户状态校验
        $check = UserService::UserStatusCheck('id', $user_insert['data']);
        if($check['code'] != 0)
        {
            return $check;
        }

        // 用户登录成功处理
        return self::UserLoginSuccessHandle($platform, $user_insert['data'], $data);
    }

    /**
     * 用户登陆成功处理
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-11-13
     * @desc    description
     * @param   [string]       $platform    [平台类型]
     * @param   [int]          $user_id     [用户id]
     * @param   [array]        $data        [用户授权信息]
     */
    public static function UserLoginSuccessHandle($platform, $user_id, $data)
    {
        // openid、unionid同步到用户表处理
        if(in_array($platform, ['weixin', 'qq']))
        {
            // 获取用户信息
            $user = UserService::UserInfo('id', $user_id, 'id,weixin_openid,weixin_unionid,weixin_web_openid,qq_openid,qq_unionid');
            if(!empty($user))
            {
                // openid
                if(!empty($data['openid']))
                {
                    // 微信环境、微信端仅手机环境存在用户表web字段
                    if($platform == 'weixin' && IsWeixinEnv() && empty($user['weixin_web_openid']))
                    {
                        Db::name('User')->where(['id'=>$user_id])->update(['weixin_web_openid'=>$data['openid']]);
                    }
                }

                // unionid
                if(!empty($data['unionid']) && empty($user[$platform.'_unionid']))
                {
                    Db::name('User')->where(['id'=>$user_id])->update([$platform.'_unionid'=>$data['unionid']]);
                }
            }
        }

        // 用户登录session纪录
        if(UserService::UserLoginRecord($user_id))
        {
            // 根据平台处理不同登陆逻辑
            if(BaseService::GetApplicationClientType() == 'pc')
            {
                // 用户cookie设置
                self::SetUserCookie($user_id);

                // 返回跳转地址
                return DataReturn('登录成功', 0,  BaseService::BackRedirectUrl());
            } else {
                // 生成token和用户信息返回
                return DataReturn('登录成功', 0, UserService::AppUserInfoHandle($user_id));
            }
        }
        return DataReturn('登录失败', -100);
    }

    /**
     * 系统用户是否存在对应信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-10-27
     * @desc    description
     * @param   [string]         $platform [平台类型]
     * @param   [array]          $data     [用户授权信息]
     */
    public static function SystemUserInfo($platform, $data)
    {
        // 目前仅微信和QQ存在在用户表存在相关字段
        if(in_array($platform, ['weixin', 'qq']))
        {
            // openid
            if(!empty($data['openid']))
            {
                $openid_field = '';
                if($platform == 'weixin')
                {
                    // 微信环境、微信端仅手机环境存在用户表web字段
                    if(IsWeixinEnv())
                    {
                        $openid_field = 'weixin_web_openid';
                    }
                } else {
                    $openid_field = $platform.'_openid';
                }
                if(!empty($openid_field))
                {
                    $user = UserService::UserInfo($openid_field, $data['openid'], 'id');
                    if(!empty($user))
                    {
                        return DataReturn('success', 0, $user['id']);
                    }
                }
            }

            // unionid
            if(!empty($data['unionid']))
            {
                $user = UserService::UserInfo($platform.'_unionid', $data['unionid'], 'id');
                if(!empty($user))
                {
                    return DataReturn('success', 0, $user['id']);
                }
            }
        }
        return DataReturn('无相关用户', -1);
    }

    /**
     * 用户添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [string]         $platform       [平台类型]
     * @param   [array]          $data           [用户授权信息]
     */
    public static function UserInsert($platform, $data)
    {
        // 根据手机、邮箱先查询用户
        if(!empty($data['mobile']))
        {
            $user = UserService::UserInfo('mobile', $data['mobile']);
        }
        if(!empty($data['email']) && empty($user))
        {
            $user = UserService::UserInfo('email', $data['email']);
        }

        // 用户信息、不存在则添加
        $user_id = 0;
        if(!empty($user))
        {
            $user_id = $user['id'];
        } else {
            // 需要添加的用户信息
            $insert_data = [
                'nickname'  => isset($data['nickname']) ? $data['nickname'] : '',
                'avatar'    => isset($data['avatar']) ? $data['avatar'] : '',
                'mobile'    => isset($data['mobile']) ? $data['mobile'] : '',
                'email'     => isset($data['email']) ? $data['email'] : '',
                'gender'    => isset($data['gender']) ? intval($data['gender']) : 0,
                'province'  => isset($data['province']) ? $data['province'] : '',
                'city'      => isset($data['city']) ? $data['city'] : '',
                'add_time'  => time(),
            ];
            // 微信登陆处理openid和unionid
            if(in_array($platform, ['weixin', 'qq']))
            {
                // openid处理
                if(!empty($data['openid']))
                {
                    if($platform == 'weixin')
                    {
                        // openid仅微信环境存储到用户表 weixin_web_openid
                        if(IsWeixinEnv())
                        {
                            $insert_data['weixin_web_openid'] = $data['openid'];
                        }
                    } else {
                        $insert_data[$platform.'_openid'] = $data['openid'];
                    }
                }

                // 是否存在unionid
                if(!empty($data['unionid']))
                {
                    $insert_data[$platform.'_unionid'] = $data['unionid'];
                }
            }

            // 添加用户
            $ret = UserService::UserInsert($insert_data);
            if($ret['code'] != 0)
            {
                return $ret;
            }
            $user_id = $ret['data']['user_id'];
        }
        return DataReturn('添加成功', 0, $user_id);
    }

    /**
     * 平台用户绑定
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [int]          $platform_user_id [平台用户id]
     * @param   [int]          $user_id          [系统用户id]
     */
    public static function PlatformUserBind($platform_user_id, $user_id)
    {
        $data = [
            'user_id'   => $user_id,
            'status'    => 1,
            'upd_time'  => time(),
        ];
        if(!Db::name('PluginsThirdpartyloginUser')->where(['id'=>$platform_user_id])->update($data))
        {
            return DataReturn('平台用户关联失败', -1);
        }

        // 获取用户判断是否需要更新信息
        $field = 'nickname,mobile,email,avatar,gender,province,city';
        $system_user = UserService::UserInfo('id', $user_id, $field);
        $platform_user = Db::name('PluginsThirdpartyloginUser')->field($field)->where(['id'=>$platform_user_id])->find();
        if(!empty($system_user) && !empty($platform_user))
        {
            // 可以更新的数据
            $update_data = [];
            $field_arr = explode(',', $field);
            foreach($field_arr as $v)
            {
                if(empty($system_user[$v]) && !empty($platform_user[$v]))
                {
                    $update_data[$v] = $platform_user[$v];
                }
            }
            if(!empty($update_data))
            {
                Db::name('User')->where(['id'=>$user_id])->update($update_data);
            }
        }

        return DataReturn('平台用户关联成功', 0);
    }

    /**
     * 平台用户添加
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]         $data     [用户授权数据]
     * @param   [string]        $platform [平台]
     * @param   [int]           $user_id  [用户id]
     * @param   [int]           $status   [绑定状态]
     */
    public static function PlatformUserInsert($data, $platform, $user_id = 0, $status = 0)
    {
        // 添加平台用户
        $data['user_id'] = $user_id;
        $data['status'] = $status;
        $data['add_time'] = time();
        $data['platform'] = $platform;
        $platform_id = Db::name('PluginsThirdpartyloginUser')->insertGetId($data);
        if($platform_id > 0)
        {
            return DataReturn('添加成功', 0, $platform_id);
        }
        return DataReturn('平台用户添加失败', -1);
    }

    /**
     * 用户登录cookie设置
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-02-26
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function SetUserCookie($user_id)
    {
        // 获取用户信息
        $user = UserService::UserInfo('id', intval($user_id), 'id,token,username,nickname,mobile,email,avatar');
        if(!empty($user))
        {
            // 用户信息处理
            $user = UserService::UserHandle($user);

            // 没有token则生成
            if(empty($user['token']))
            {
                // 生成用户token
                if(method_exists(new UserService(), 'CreatedUserToken'))
                {
                    $user['token'] = UserService::CreatedUserToken($user_id);
                } else {
                    $user['token'] = md5(md5($user_id.time()).rand(100, 1000000));
                }

                // 更新用户token
                $data = [
                    'token'     => $user['token'],
                    'upd_time'  => time(),
                ];
                if(!Db::name('User')->where(['id'=>$user_id])->update($data))
                {
                    return false;
                }
            }

            // 设置cookie数据
            cookie('user_info', json_encode($user, JSON_UNESCAPED_UNICODE));
            return true;
        }
        return false;
    }

    /**
     *  开发平台用户信息获取
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [string]          $platform [平台类型]
     * @param   [array]           $data     [用户授权信息（openid,unionid）]
     */
    public static function OpenPlatformUserInfo($platform, $data)
    {
        // 初始化用户
        $user = [];

        // openid
        if(!empty($data['openid']))
        {
            $user = Db::name('PluginsThirdpartyloginUser')->where(['platform'=>$platform, 'openid'=>$data['openid']])->find();
        }

        // unionid
        if(empty($user) && !empty($data['unionid']))
        {
            $user = Db::name('PluginsThirdpartyloginUser')->where(['platform'=>$platform, 'unionid'=>$data['unionid']])->find();
        }

        return $user;
    }
    
    /**
     * 平台用户信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-22
     * @desc    description
     * @param   [int]          $platform_user_id [平台用户id]
     */
    public static function PlatformUserInfo($platform_user_id)
    {
        return empty($platform_user_id) ? [] : Db::name('PluginsThirdpartyloginUser')->where(['id'=>intval($platform_user_id)])->find();
    }

    /**
     * 平台用户列表
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-22
     * @desc    description
     * @param   [int]          $user_id [用户id]
     */
    public static function PlatformUserList($user_id)
    {
        return empty($user_id) ? [] : Db::name('PluginsThirdpartyloginUser')->where(['user_id'=>intval($user_id)])->select()->toArray();
    }

    /**
     * 平台用户解绑
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2021-05-21
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function UnbindHandle($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '操作数据有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 开始处理
        $data = [
            'user_id'   => 0,
            'status'    => 2,
            'upd_time'  => time(),
        ];
        if(Db::name('PluginsThirdpartyloginUser')->where(['id'=>intval($params['id']), 'user_id'=>$params['user']['id']])->update($data))
        {
            return DataReturn('解绑成功', 0);
        }
        return DataReturn('解绑失败', -1);
    }
}
?>