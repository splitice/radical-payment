<?php
namespace Radical\Payment\WebInterface;


class StandardWebInterface implements IWebInterface {
    private $url;
    function __construct($url){
        $this->url = $url;
    }
    function payment_build_url($action){
        $base = $this->url;
        if(strpos($base,'?') !== false){
            $base .= "&";
        }else{
            $base .= '?';
        }
        return $base.'action='.urlencode($action);
    }
    function payment_get_action(){
        return isset($_GET['action'])?$_GET['action']:null;
    }
}