<?php
/**
 * @author Leon J
 * @since 2017/5/27
 */

require 'vendor/autoload.php';

$BIZS = [
	'chuanyizhushouapp' => 'MzIyNTAwNjUyMg=='
];

$http = new swoole_http_server( "127.0.0.1" );

$http->set(
	array(
		'task_worker_num' => 5 , // 设置启动2个task进程
		'worker_num' => 1 ,
	)
);

$http->on(
	'task' ,
	function ( $serv , $task_id , $from_id , $data )
	{
		list( $name, $biz ) = $data;
		$redis = new Redis();
		$redis->connect( '127.0.0.1' );
		$redis->setOption( Redis::OPT_PREFIX , 'wechat_proxy_' );
		
		$deadTime = strtotime( '-2 hours' );
		$dropWxuins = $redis->zRangeByScore( 'cookie_update' , 0 , $deadTime );
		$redis->zRemRangeByScore( 'cookie_update' , 0 , $deadTime );
		$redis->hDel( 'cookie' , ...$dropWxuins );
		
		$availableCookies = $redis->hGetAll( 'cookie' );
		$wxuins = $redis->zRevRange( 'cookie_update' , 0 , -1 , true );
		
		$redis->close();
		
		uksort( $availableCookies , cmpDec( $wxuins ) );
		
		//todo get last msgId
		$lastMsgId = 0;
		
		$curl = new Curl\Curl();
		$curl->setHeader( 'Host' , 'mp.weixin.qq.com' );
		$curl->setHeader(
			'User-Agent' ,
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36'
		);
		$curl->setReferer( pageLink( $biz ) );
		//		$curl->setOpt( CURLOPT_PROXY , 'http://127.0.0.1:8888' );
	
		
		foreach( $availableCookies as $wxuin => $cookieStr )
		{
			$curl->setOpt(
				CURLOPT_COOKIE ,
				urldecode( http_build_query( json_decode( $cookieStr , true ) , '' , '; ' ) )
			);
			
			$result = fetchList( $curl , $name , $biz , $wxuin , $lastMsgId ,
				function ( $list )
				{
					print_r($list);
				}
			);
			
			if( $result )
			{
				break;
			}
			randSleep();
		}
		
		$curl->close();
	}
);

$http->on(
	'workerStart' ,
	function ( $serv ) use ( $BIZS )
	{
		if( $serv->taskworker )
		{
			return;
		}
		
		$redis = new Redis();
		$redis->pconnect( '127.0.0.1' );
		$redis->setOption( Redis::OPT_PREFIX , 'wechat_proxy_' );
		
		$serv->tick(
			5000 ,
			function ( $id ) use ( $serv , $BIZS , $redis )
			{
				foreach( $BIZS as $name => $biz )
				{
					if( $redis->zScore( 'bizs' , $name ) < strtotime( 'today' ) )
					{
						$serv->task( [$name , $biz] );
						$redis->zAdd( 'bizs' , time() , $name );
					}
				}
			}
		);
	}
);

$http->on(
	'finish' ,
	function ()
	{
	}
);

$http->on(
	'request' ,
	function ()
	{
	}
);

$http->start();
