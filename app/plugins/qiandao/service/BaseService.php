<?php
namespace app\plugins\qiandao\service;

use think\facade\Db;
use think\facade\Hook;
use app\service\UserService;
use app\service\MessageService;
use app\service\ResourcesService;
use app\service\IntegralService;
use app\service\PluginsService;

/**
 * 签到服务层
 */
class BaseService
{
	// 签到周期
    public static $zhouqi = [
		1 => array('id' => 1, 'name' => '1天', 'checked' => true),
        3 => array('id' => 3, 'name' => '3天'),
		7 => array('id' => 7, 'name' => '7天'),
		14 => array('id' => 14, 'name' => '14天'),
		21 => array('id' => 21, 'name' => '21天'),
		28 => array('id' => 28, 'name' => '28天'),
	];
	
	// 奖励类型
    public static $get_type = [
		0=> array('id' => '0', 'name' => '积分'),
		1=> array('id' => '1', 'name' => '抽奖机会'),
		2=> array('id' => '2', 'name' => '优惠券'),
	];
	
	// 基础数据附件字段
    public static $base_config_attachment_field = ['picurl', 'picurl_pc', 'picurl_ad'];
	
	/**
     * 基础配置信息保存
     * @param   [array]          $params [输入参数]
     */
    public static function BaseConfigSave($params = [])
    {
        return PluginsService::PluginsDataSave(['plugins'=>'qiandao', 'data'=>$params]);
    }

    /**
     * 基础配置信息
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        return PluginsService::PluginsData('qiandao', self::$base_config_attachment_field, $is_cache);
    }
	
	/**
     * 系统优惠券搜索
     * @param   [array]          $params [输入参数]
     */
    public static function CouponSearchList($params = [])
    {
        // 返回数据
        $result = [
            'page_total'    => 0,
            'page_size'     => 11,
            'page'          => max(1, isset($params['page']) ? intval($params['page']) : 1),
            'total'         => 0,
            'data'          => [],
        ];

        // 条件
        $where = [
            ['is_enable', '=', 1],
        ];

        // 关键字
        if(!empty($params['keywords']))
        {
            $where[] = ['name|desc', 'like', '%'.$params['keywords'].'%'];
        }

        // 获取会员总数
        $result['total'] = CallPluginsServiceMethod('coupon', 'CouponService', 'CouponTotal', $where);

        // 获取会员列表
        if($result['total'] > 0)
        {
            // 基础参数
            $field = '*';
            $order_by = 'id desc';

            // 分页计算
            $m = intval(($result['page']-1)*$result['page_size']);
            $users = CallPluginsServiceMethod('coupon', 'CouponService', 'CouponList', ['where'=>$where, 'm'=>$m, 'n'=>$result['page_size'], 'field'=>$field, 'order_by'=>$order_by]);

            $result['data'] = $users['data'];
            $result['page_total'] = ceil($result['total']/$result['page_size']);
        }
        return DataReturn('处理成功', 0, $result);
    }
	
	/**
     * 获取最新的用户信息
     * @param   [array]          $params [输入参数]
     */
    public static function GetUserInfo($params = [])
    {
        $user = [];
		if(!empty($params['user_id'])){
			$user = Db::name('User')->field('id,avatar,integral')->where(['id'=>$params['user_id']])->find();
		}
		return DataReturn('ok', 0, $user);
    }
	
	/**
     * 数据初始化
     */
	public static function Init()
	{
		$ret = self::BaseConfig(false);
        if($ret['code'] == 0 && empty($ret['data']))
        {
			$data = [
				'picurl'   				=> '/static/upload/images/plugins_qiandao/2020/07/08/1594186781976168.png',
				'picurl_ad'   			=> '/static/upload/images/plugins_qiandao/2020/07/08/1594186781976117.png',
				'bg_color'              => '#c80d01',
			];
			self::BaseConfigSave($data);
		}
	}
	
	/**
     * 列表条件
     * @param   [array]          $params [输入参数]
     */
    public static function QianDaoListWhere($params = [])
    {
        // 用户类型
        $user_type = isset($params['user_type']) ? $params['user_type'] : 'user';

        // 条件初始化
        $where = [];

        // id
        if(!empty($params['id']))
        {
            $where[] = ['id', '=', intval($params['id'])];
        }
		
		// 奖励类型：0积分，1抽奖机会
		if(!empty($params['type']))
        {
            $where[] = ['type', '=', intval($params['type'])];
        }

        // 用户类型
        if(isset($params['user_type']) && $params['user_type'] == 'user')
        {
            // 用户id
            if(!empty($params['user']))
            {
                $where[] = ['user_id', '=', $params['user']['id']];
            }
        }else{
			if(!empty($params['keywords']))
            {
				$users = Db::name('User')->where('username|nickname|mobile', 'like', '%'.$params['keywords'].'%')->column('id');
				if(!empty($users)){
					$where[] = ['user_id', 'in', $users];
				}else{
					$where[] = ['user_id', '=', 0];//搜不到返回一个空结果
				}
            }
		}

        // 是否更多条件
        if(isset($params['is_more']) && $params['is_more'] == 1)
        {
            // 时间
            if(!empty($params['time_start']))
            {
                $where[] = ['add_time', '>', strtotime($params['time_start'])];
            }
            if(!empty($params['time_end']))
            {
                $where[] = ['add_time', '<', strtotime($params['time_end'])];
            }
        }
        return $where;
    }

    /**
     * 总数
     * @param   [array]          $where [条件]
     */
    public static function QianDaoTotal($where = [])
    {
        return (int) Db::name('PluginsYxQiandao')->where($where)->count();
    }
	
	/**
     * 列表
     * @param   [array]          $params [输入参数]
     */
    public static function QianDaoList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];
        $user_type = isset($params['user_type']) ? $params['user_type'] : 'user';

        // 获取签到列表
        $data = Db::name('PluginsYxQiandao')->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // 用户信息
                if(isset($v['user_id']))
                {
                   $v['user'] = UserService::GetUserViewInfo($v['user_id']);
                }
				
                // 奖励说明
				if($v['type'] == 0){
					$msg = '获得'.$v['num'].'积分';
				}elseif($v['type'] == 1){
					$msg = '获得'.$v['num'].'次抽奖机会';
				}else{
					$msg = '获得优惠券一张';
				}
                $v['type_name'] = $msg;

                // 创建时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = date('Y-m-d', $v['add_time']);
                $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            }
        }

        return DataReturn('success', 0, $data);
    }
	
	/**
     * 最近的一条签到记录
     * @param   [array]          $params [输入参数]
     */
    public static function LastQianDaoInfo($params = [])
    {
		$data = Db::name('PluginsYxQiandao')->where('user_id', intval($params['user_id']))->order('add_time desc')->find();
		if(!empty($data))
		{
			// 今日
			$today = [
				mktime(0, 0, 0),
				time()
			];
			$today_str = mktime(0, 0, 0);
			$yestoday_str = mktime(0, 0, 0) - 86400;
		
			// 今日是否已经签到
			$data['is_yestoday'] = 0;
			if($data['add_time'] >= $today_str){
				$data['is_today'] = 1;
			}else{
				$data['is_today'] = 0;
				
				// 看最后一条记录是否是昨天的
				if($data['add_time'] >= $yestoday_str && $data['add_time'] < $today_str){
					$data['is_yestoday'] = 1;
				}else{
					// 如果最后一条记录不是昨天的，说明断签了，则重头开始计算连签
					$data['xu'] = 0;
				}
			}
		}else{
			// 没有记录的情况下，证明从未签到过
			$data['is_today'] = 0;
			$data['xu'] = 0;
			$data['is_yestoday'] = 0;
		}
		
		// 附带传值签到规则+背景图片
		$qiandaobg = $guize = $bgcolor = $qiandao_ad = '';
		$zhouqi = 0;
		$is_score_flow = false;
		$ret = self::BaseConfig();
        if($ret['code'] == 0)
        {
			if(!empty($ret['data']['picurl'])){
				$qiandaobg = ResourcesService::AttachmentPathViewHandle($ret['data']['picurl']);
			}
			if(!empty($ret['data']['picurl_ad'])){
				$qiandao_ad = ResourcesService::AttachmentPathViewHandle($ret['data']['picurl_ad']);
			}
			// 标签处理，兼容小程序rich-text
			if(!empty($ret['data']['guize'])){
				$guize = ResourcesService::ContentStaticReplace($ret['data']['guize'], 'get');
				$guize = ResourcesService::ApMiniRichTextContentHandle($guize);
			}
			$zhouqi = $ret['data']['zhouqi'];
			$bgcolor = !empty($ret['data']['bg_color']) ? $ret['data']['bg_color'] : '#ffffff';
			if(isset($ret['data']['is_score_flow']) && intval($ret['data']['is_score_flow']) > 0){
				$is_score_flow = true;
			}
		}
		$data['qiandaobg'] = $qiandaobg;
		$data['guize'] = $guize;
		$data['zhouqi'] = $zhouqi;
		$data['bgcolor'] = $bgcolor;
		$data['qiandao_ad'] = $qiandao_ad;
		$data['is_score_flow'] = $is_score_flow;
		
		$data['ad01'] = ResourcesService::AttachmentPathViewHandle('/static/plugins/images/qiandao/1.jpg');
		$data['ad02'] = ResourcesService::AttachmentPathViewHandle('/static/plugins/images/qiandao/2.jpg');
		$data['ad03'] = ResourcesService::AttachmentPathViewHandle('/static/plugins/images/qiandao/3.jpg');
		$data['ad04'] = ResourcesService::AttachmentPathViewHandle('/static/plugins/images/qiandao/4.jpg');
		
		// 大转盘插件是否存在
		$ret = self::PluginsExist('dazhuanpan');
		if($ret['code'] != 0)
		{
			$data['ischoujiang'] = true;//存在
		}else{
			$data['ischoujiang'] = false;//不存在
		}
		// 积分商城插件是否存在
		$ret = self::PluginsExist('scorestore');
		if($ret['code'] != 0)
		{
			$data['isscorestore'] = true;//存在
		}else{
			$data['isscorestore'] = false;//不存在
		}
		// 另外一个积分商城插件是否存在
		$ret = self::PluginsExist('points');
		if($ret['code'] != 0)
		{
			$data['ispoints'] = true;//存在
		}else{
			$data['ispoints'] = false;//不存在
		}

        return DataReturn('success', 0, $data);
    }
	
	/**
     * 签到
     * @param   [array]          $params [输入参数]
     */
    public static function QianDao($params = [])
    {
		$ret = PluginsService::PluginsData('qiandao');
        if($ret['code'] != 0)
        {
            MyViewAssign('zhouqi', BaseService::$zhouqi);
            return DataReturn($ret['msg'], 10);
        }
		
		// 得到最新的一条信息
		$data = self::LastQianDaoInfo($params);
		if(intval($data['data']['is_today']) > 0){
			return DataReturn('今日已签', 10);
		}
		
		// 签到周期
		if(!isset($ret['data']['zhouqi'])){
			return DataReturn('插件未设置', 10);
		}
		
		$zhouqi = intval($ret['data']['zhouqi']);
		
		// 开签
		$user_id = $params['user_id'];
		$type = 0;//0类型是积分 1是特殊，跟插件原值不一致，故而下面的type值需要做减1处理，这样才对应
		$xu = intval($data['data']['xu']);//连签的第几天，0是一天没有
		
		// 计算连续签到，就要看最后一条记录是否是昨天的，如此才能连续
		if($xu < $zhouqi && intval($data['data']['is_yestoday']) > 0){
			$num = $ret['data']['jiangli']['jiang_value'][$xu];
			$type = intval($ret['data']['jiangli']['jiang_type'][$xu])-1;
		}else{
			// 重新计算
			$xu = 0;
			$num = $ret['data']['jiangli']['jiang_value'][0];
			$type = intval($ret['data']['jiangli']['jiang_type'][0])-1;
		}
        $ret = self::QianDaoHistoryAdd($user_id, $type, $num, $xu);
		if($ret['code'] != 0){
			return $ret;
		}
		
		// 完善提示
		$msg = '';
		if($type == 0){
			$msg = '恭喜获得'.$num.'积分';
		}elseif($type == 1){
			$msg = '恭喜获得'.$num.'次抽奖机会';
		}else{
			$msg = '恭喜获得优惠券一张';
		}
        return DataReturn('签到成功，'.$msg, 0);
    }
	
	/**
     * 签到记录添加
     */
    public static function QianDaoHistoryAdd($user_id = 0, $type=0, $num = 0, $xu = 0)
    {
        // 添加
        $data = [
            'user_id'           => intval($user_id),
            'type'              => intval($type),
            'num'               => intval($num),
            'xu'           	    => intval($xu) + 1,
            'add_time'          => time(),
        ];

        // 添加
		$qdid = Db::name('PluginsYxQiandao')->insertGetId($data);
        if($qdid > 0)
        {
			// 如果是抽奖类型，则给抽奖机会累加到用户的机会字段去
			if($type == 1){
				if(!Db::name('User')->where(['id'=>$user_id])->inc('cannum', $num)->update()){
					return DataReturn('赠送抽奖机会失败', -10);
				}
			}elseif($type == 2){
				// 优惠券，直接发到位
				$params_send['user_ids'] = [$user_id];
				$params_send['coupon_id'] = $num;
				$res = CallPluginsServiceMethod('coupon', 'CouponService', 'CouponSend', $params_send);
				if($res['code'] != 0){
					return $res;
				}else{
					// 发完，发个站内消息
					MessageService::MessageAdd($user_id, '恭喜获得优惠券', '您参加签到活动得到一张优惠券，请到我的优惠券中查看', '每日签到', $qdid);
				}
			}else{
				// 用户积分添加
				$user_integral = Db::name('User')->where(['id'=>$user_id])->value('integral');
				if(!Db::name('User')->where(['id'=>$user_id])->inc('integral', $num)->update())
				{
					return DataReturn('用户积分赠送失败', -10);
				}

				// 积分日志
				IntegralService::UserIntegralLogAdd($user_id, $user_integral, $num, '每日签到完成赠送', 1);
			}
			
            return DataReturn('签到记录添加成功', 0);
        }
        return DataReturn('签到记录添加失败', -10);
    }
	
	/**
     * 返回-1表示应用已经安装，返回0，则表明未安装
     */
    public static function PluginsExist($plugins)
    {
        // 应用是否存在
        if(is_dir(APP_PATH.'plugins'.DS.$plugins))
        {
			// 应用存在，再检查插件是否开启
			$ret = Db::name('Plugins')->where(['plugins'=>$plugins, 'is_enable'=>1])->find();
			if(empty($ret)){
				return DataReturn('['.$plugins.']插件未开启', 0);
			}
			
            return DataReturn('应用存在且正常开启['.$plugins.']', -1);
        }

        return DataReturn('不存在', 0);
    }
}
?>