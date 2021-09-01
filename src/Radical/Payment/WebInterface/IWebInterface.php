<?php
namespace Radical\Payment\WebInterface;


interface IWebInterface {
    function payment_build_url($action);
    function payment_get_action();
}