<?php
namespace Radical\Payment\Components;

interface IOrder
{
    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @param string $currency
     */
    public function setCurrency($currency);

    /**
     * @return float
     */
    public function getAmmount();

    /**
     * @param float $ammount
     */
    public function setAmmount($ammount);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @return mixed
     */
    public function getItem();

    /**
     * @param mixed $item
     */
    public function setItem($item);

    /**
     * @return array
     */
    public function getAdditional();

    /**
     * @param array $additional
     */
    public function setAdditional($additional);

    function getId();

    function setId($id);
}