<?php

require_once ( dirname(__FILE__).'/LogUtil.php' );
require_once ( dirname(__FILE__).'/Util.php' );
require_once ( dirname(__FILE__).'/stripe-php/init.php' );

main();

function main() {
    $m        = new MongoClient( "mongodb://localhost" );
    $db       = $m->revelcare;
    $input    = array_merge( $_POST, $_GET );
    $action   = $input['action'];

    LogUtil::logObj( "INPUT: ", $input );
    switch ( $action ) {
    case 'chargeCustomer':
        $res = chargeCustomer( $db, $input );
        break;
    case 'getPaymentDetails':
        $res = getPaymentDetails( $db, $input );
        break;
    case 'insertUser':
        $res = insertUser( $db, $input );
        break;
    case 'pharmacyList':
        $res = pharmacyList( $db, $input );
        break;
    case 'requestInviteCode':
        $res = requestInviteCode( $db, $input );
        break;
    case 'updateUser':
        $res = updateUser( $db, $input );
        break;
    case 'validateInviteCode':
        $res = validateInviteCode( $db, $input );
        break;
    default:
        $res = [ 'error' => true, 'message' => 'Unknown API call' ];
        break;
    }

    if ( array_key_exists( 'error', $res )) {
        header( 'HTTP/1.0 403 Error: *' );
        header( 'Access-Control-Allow-Origin: *' );
    } else {
        header( 'HTTP/1.0 200 OK' );
        header( 'Access-Control-Allow-Origin: *' );
    }
    echo( json_encode( $res ));
}

function requestInviteCode( $db, $input ) {
    $invite_code = $db->invite_code;
    $cursor      = $invite_code->find();
    $res         = [ 'error' => true, 'message' => 'No valid codes created' ];
    foreach ($cursor as $doc) {
        $res    = $doc;
    }
    return $res;
}

function validateInviteCode( $db, $input ) {
    $invite_code = $db->invite_code;
    $code        = $input['code'];
    $res         = [ 'error' => true, 'message' => 'Code not exist' ];
    if ( $code ) {
        $cursor  = $invite_code->find( [ 'code' => $code ] );
        foreach ($cursor as $doc) {
            $res = $doc;
        }
    }
    return $res;
}

function pharmacyList( $db, $input ) {
    $pharmacy    = $db->pharmacy;
    $res         = [];
    $cursor      = $pharmacy->find();
    foreach ($cursor as $doc) {
        $res[]   = $doc;
    }
    if ( count( $res ) <= 0 ) {
        $res     = [ 'error' => true, 'message' => 'No pharmacies found' ];
    }
    return $res;
}

function insertUser( $db, $input ) {
    $user        = $db->user;
    $usr         = $input;
    $usr['email_address'] = strtolower( $usr['email_address'] );
    $testUser    = $user->findOne( ['email_address' => $usr['email_address'] ] );
    if ( $testUser ) {
        return [ 'error' => true, 'message' => 'A user with this email already exists' ];
    }
    unset( $usr['action'] );
    $password             = $usr['password'];
    $usr['salt']          = Util::generateRandomString();
    $usr['password']      = hash( 'sha256', $password . $usr['salt'] );
    $user->insert( $usr );
    unset( $usr['password'] );
    unset( $usr['salt'] );
    return $usr;
}

function updateUser( $db, $input ) {
    $user        = $db->user;
    $usr         = $input;
    $id          = new MongoId( $input['id'] );
    $testUser    = $user->findOne( ['_id' => $id ] );
    if ( ! $testUser ) {
        return [ 'error' => true, 'message' => 'No user found' ];
    }
    unset( $usr['id'] );
    unset( $usr['action'] );
    $user->update( ['_id' => $id ], [ '$set' => $usr ] );
    unset( $usr['password'] );
    unset( $usr['salt'] );
    return $usr;
}

function getPaymentDetails( $db, $input ) {
    $user        = $db->user;
    $usr         = $input;
    $id          = new MongoId( $input['id'] );
    $testUser    = $user->findOne( ['_id' => $id ] );
    if ( ! $testUser ) {
        return [ 'error' => true, 'message' => 'No user found' ];
    }
    if ( array_key_exists( 'payment_details', $testUser )) {
        return $testUser['payment_details'];
    } else {
        return [];
    }
}

function chargeCustomer( $db, $input ) {
 
}
