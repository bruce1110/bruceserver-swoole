<?php
namespace WebIm\error;
/**
 * User: Bruce Qin
 * Date: 2016/6/3
 * Time: 14:16
 */
class WsErr
{
    const E100 = array(
        "code" => 100,
        "msg" => "Successful"
    );
    const E101 = array(
        "code" => 101,
        "msg" => "NnrecogNized Message"
    );
    const E102 = array(
        "code" => 102,
        "msg" => "Repeat Login"
    );
    const E103 = array(
        "code" => 103,
        "msg" => "No Logined"
    );
    const E104 = array(
        "code" => 104,
        "msg" => "Nuknown Message Type"
    );
    const E105 = array(
        "code" => 105,
        "msg" => "Repeat Auth"
    );
    const E106 = array(
        "code" => 106,
        "msg" => "the message max length is " . \WebIm\Server::MESSAGE_MAX_LEN
    );
    const E107 = array(
        "code" => 107,
        "msg" => "Auth  Failed"
    );
}