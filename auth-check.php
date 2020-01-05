<?php 
declare(strict_types=1); // strict requirement

function wp_parse_auth_cookie( $cookie ) {
    if ( strlen( $cookie ) < 1 ) {
        return false;
    }
    $cookie_elements = explode( '|', $cookie );
    if ( count( $cookie_elements ) !== 4 ) {
        return false;
    }
 
    list( $username, $expiration, $token, $hmac ) = $cookie_elements;
 
    return compact( 'username', 'expiration', 'token', 'hmac' );
}

function wp_validate_auth_cookie( $connection , $prefix='wp_',$meta_key = '_wp_php_auth_cookie', $cookie = '' ) {
    $cookie_elements = wp_parse_auth_cookie( $cookie );
    if ( ! $cookie_elements ) {
        return false;
    }
 
    $username   = $cookie_elements['username'];
    $hmac       = $cookie_elements['hmac'];
    $token      = $cookie_elements['token'];
    $expired    = $cookie_elements['expiration'];
    $expiration = $cookie_elements['expiration'];
 
    // Allow a grace period for POST and Ajax requests
    // used by us in all cases as most o tese requests will be api calls
        $expired += 3600;// replaced HOUR_IN_SECONDS
 
    // Quick check to see if an honest cookie has expired
    if ( $expired < time() ) {
        return false;
    }
    //$connection='wpdb';//change this once testing is over
    $user = get_user_by_name($connection,$prefix,$meta_key, $cookie );
    return $user;
}
function get_user_by_name($connection,$prefix,$meta_key,$cookie) {
    $sql_query= "select user_id from ".$prefix."usermeta where meta_key='".$meta_key."' and meta_value='".$cookie."'";
    $user_id=DB::connection($connection)->select($sql_query);
    if($user_id){
    $uid=$user_id[0]->user_id;
    $sql_query2= "select meta_value from ".$prefix."usermeta where user_id=$uid and meta_key='wp_capabilities'";
    $user_capabilities_serialize=DB::connection($connection)->select($sql_query2);
    $user_capabilities_unserialize=unserialize($user_capabilities_serialize[0]->meta_value);
    $sql_query3= "select user_email from ".$prefix."users where id=$uid";
    $user_details=DB::connection($connection)->select($sql_query3);
    $user['id']=$user_id[0]->user_id;
    $user['is_admin']=$user_capabilities_unserialize['administrator'];
    $user['user_email']=$user_details[0]->user_email;
    return $user;
    }
    else{
        return false;
    }
}
?>
