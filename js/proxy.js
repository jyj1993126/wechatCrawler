var redis = require( 'redis' );
var config = require( '../config.json' );
var redisClient = redis.createClient( config );
var setCookie = require( 'set-cookie-parser' );

module.exports = {
    summary : 'keep cookie to redis' ,
    *beforeSendResponse( requestDetail , responseDetail ) {
        if ( requestDetail.url.indexOf( 'mp.weixin.qq.com' ) !== -1 ) {
            var set_cookie = responseDetail.response.header['Set-Cookie'];
            if ( set_cookie != undefined ) {
                var cookies = setCookie( set_cookie );
                var cookieObj = {};
                cookies.forEach( function ( e ) {
                    cookieObj[e.name] = e.value;
                } );
                if ( cookieObj.wap_sid2 != undefined && cookieObj.wxuin != undefined ) {
                    redisClient.hset( 'cookie' , cookieObj.wxuin , JSON.stringify( cookieObj ) );
                    redisClient.zadd( 'cookie_update' , Date.parse( new Date() ) / 1000 , cookieObj.wxuin );
                }
            }
        }
    } ,
};
