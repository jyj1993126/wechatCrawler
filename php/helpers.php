<?php
/**
 * @author Leon J
 * @since 2017/5/27
 */

/**
 * @param $biz
 * @return string
 */
function pageLink( $biz )
{
	return "https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz={$biz}&scene=124#wechat_redirect";
}

/**
 * @param $biz
 * @param int $fromMsgId
 * @return string
 */
function listLink( $biz , $fromMsgId = -1 )
{
	return "https://mp.weixin.qq.com/mp/profile_ext?action=getmsg&__biz={$biz}&f=json&frommsgid={$fromMsgId}&count=10&scene=124&is_ok=1&x5=0&f=json";
}

/**
 * @param $wxuins
 * @return Closure
 */
function cmpDec( $wxuins )
{
	return function ( $a , $b ) use ( $wxuins )
	{
		$val = $wxuins[$a] - $wxuins[$b];
		if( $val > 0 )
		{
			return -1;
		}
		elseif( $val == 0 )
		{
			return 0;
		}
		else
		{
			return 1;
		}
	};
}

function randSleep()
{
	sleep( mt_rand( 50 , 200 ) / 100 );
}

/**
 * @param \Curl\Curl $curl
 * @param $name
 * @param $biz
 * @param $wxuin
 * @param $lastMsgId
 * @param $callback
 * @return bool
 */
function fetchList( $curl ,$name,  $biz , $wxuin , $lastMsgId , $callback )
{
	$result = false;
	$continue = true;
	$fromMsgId = -1;
	while( $continue )
	{
		$list = [];
		$curl->get( listLink( $biz, $fromMsgId ) );
		$rtn = json_decode( $curl->response , true );
		$continue = (bool)$rtn['can_msg_continue'];
		$hasFollow = true;
		
		if( $success = $rtn['ret'] == 0 )
		{
			if( !$result && $success )
			{
				$result = true;
				if( $rtn['msg_count'] == 10 && !$continue )
				{
					$hasFollow = false;
				}
			}
			foreach( json_decode( $rtn['general_msg_list'] , true )['list'] as $msg )
			{
				$fromMsgId = $msg['comm_msg_info']['id'];
				if( $fromMsgId == $lastMsgId )
				{
					break 2;
				}
				$list[] = $msg;
			}
			$callback( $biz, $list );
			randSleep();
		}
		
		if( !$hasFollow )
		{
			echo "账号 : {$wxuin} 可能没有关注公众号 : {$name} \n";
		}
	}
	return $result;
}

function fetchDetail( $curl, $url )
{
	
}
