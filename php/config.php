<?php
/**
 * @author Leon J
 * @since 2017/5/27
 */
return array_merge(
	[
		//别忘了复制最后两个等号
		'BIZS' => [
			'chuanyizhushouapp' => 'MzIyNTAwNjUyMg=='
		] ,
		'listHandler' => function ( $biz , $list )
		{
			print_r( $list );
		} ,
		'getLastMsgId' => function ( $biz )
		{
			return 0;
		} ,
		'doCrawler' => function ( $serv , $redis , $name , $biz )
		{
			if( $redis->zScore( 'bizs' , $name ) < strtotime( 'today' ) )
			{
				$serv->task( [ $name , $biz ] );
				if( $redis->hlen( 'cookie' ) )
				{
					$redis->zAdd( 'bizs' , time() , $name );
				}
			}
		} ,
		'task_worker_num' => 5 ,
		'interval' => 5000,
	] ,
	json_decode( file_get_contents( '../config.json' ) , true )
);