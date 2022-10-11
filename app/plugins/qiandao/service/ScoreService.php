<?php
namespace app\plugins\qiandao\service;

use think\facade\Db;
use app\service\UserService;
use app\service\MessageService;
use app\service\ResourcesService;
use app\service\IntegralService;

/**
 * 积分互赠服务层
 */
class ScoreService
{
	/**
     * 系统主商城的会员搜索
     * @param   [array]          $params [输入参数]
     */
    public static function UserSearchList($params = [])
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
            ['is_delete_time', '=', 0],
        ];
		
		// 会员ID
        if(!empty($params['user_id']))
        {
            $where[] = ['id', '=', intval($params['user_id'])];
        }

        // 关键字
        if(!empty($params['keywords']))
        {
			// 查询状态
            $keywords_status = false;

            // 用户表查询
            $oids = Db::name('User')->where([['id', '=', intval($params['keywords'])]])->column('id');
            if(!empty($oids))
            {
                $where[] = ['id', 'in', $oids];
                $keywords_status = true;
            }

            // 其他查询
            if($keywords_status === false)
            {
                $where[] = ['nickname|username|mobile|email', 'like', '%'.$params['keywords'].'%'];
                $keywords_status = true;
            }
        }

        // 会员等级id
        if(!empty($params['category_id']))
        {
            $where[] = ['plugins_user_level', '=', $params['category_id']];
        }

        // 获取会员总数
        $result['total'] = UserService::UserTotal($where);

        // 获取会员列表
        if($result['total'] > 0)
        {
            // 基础参数
            $field = 'avatar,status,nickname,username,mobile,email,id';
            $order_by = 'id desc';

            // 分页计算
            $m = intval(($result['page']-1)*$result['page_size']);
            $users = UserService::UserList(['where'=>$where, 'm'=>$m, 'n'=>$result['page_size'], 'field'=>$field, 'order_by'=>$order_by]);

            $result['data'] = $users['data'];
            $result['page_total'] = ceil($result['total']/$result['page_size']);
        }
        return DataReturn('处理成功', 0, $result);
    }
	
	/**
     * 列表条件
     * @param   [array]          $params [输入参数]
     */
    public static function ScoreWhere($params = [])
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
        if(isset($params['status']) && !empty($params['user']))
        {
			// 0是我赠出去的，我是赠送者
			if(intval($params['status']) == 0){
				$where[] = ['fromwho', '=', $params['user']['id']];
			}else{
				// 1表示我是接收者
				$where[] = ['towho', '=', $params['user']['id']];
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
    public static function ScoreTotal($where = [])
    {
        return (int) Db::name('PluginsYxQiandaoSend')->where($where)->count();
    }
	
	/**
     * 列表
     * @param   [array]          $params [输入参数]
     */
    public static function ScoreList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];
        $user_type = isset($params['user_type']) ? $params['user_type'] : 'user';

        // 获取列表
        $data = Db::name('PluginsYxQiandaoSend')->where($where)->limit($m, $n)->order($order_by)->select()->toArray();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // 用户信息
                if(isset($v['towho']))
                {
                   $v['user_towho'] = UserService::GetUserViewInfo($v['towho']);
                }
				if(isset($v['fromwho']))
                {
                   $v['user_fromwho'] = UserService::GetUserViewInfo($v['fromwho']);
                }

                // 创建时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = date('Y-m-d', $v['add_time']);
                $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            }
        }

        return DataReturn('success', 0, $data);
    }
	
	/**
     * 赠送积分
     * @param   [array]          $params [输入参数]
     */
    public static function Sendscore($params = [])
    {
		// 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'num',
                'error_msg'         => '赠送积分数额有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'user_id_new',
                'error_msg'         => '赠送对象信息有误',
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
		
		$num = intval($params['num']);
		$user_id = intval($params['user_id_new']);
		
		if($user_id == intval($params['user']['id'])){
			return DataReturn('不能转增给自己', 10);
		}
		
		// 查询用户自己的积分
		$user = Db::name('User')->field('id,avatar,integral')->where(['id'=>intval($params['user']['id'])])->find();
		if($user['integral'] < $num){
			return DataReturn('积分数额不足', 10);
		}
		
		// 开始赠送
		$user_integral = Db::name('User')->where(['id'=>$user_id])->value('integral');
		if(Db::name('User')->where(['id'=>$user_id])->inc('integral', $num)->update())
		{
			// 积分日志
			IntegralService::UserIntegralLogAdd($user_id, $user_integral, $num, '有人转增积分给你', 1);
		
			// 赠送成功后，自己的积分要扣减
			Db::name('User')->where(['id'=>$user['id']])->dec('integral', $num)->update();
			
			// 积分日志
			IntegralService::UserIntegralLogAdd($user['id'], $user['integral'], $num, '你将积分转增他人', 0);
			
			// 写记录
			$data = [
				'towho'     => $user_id,
				'fromwho'   => $user['id'],
				'num'       => $num,
				'add_time'  => time()
			];
			$sid = Db::name('PluginsYxQiandaoSend')->insertGetId($data);
			return DataReturn('转增成功', 0, $sid);
		}
		return DataReturn('转增失败', 10);
    }
}
?>