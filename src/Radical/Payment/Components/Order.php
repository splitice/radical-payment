<?php
namespace Radical\Payment\Components;

class Order implements IOrder
{
    /**
     * Currency of the order
     *
     * @var string
     */
    private $currency = 'USD';

    /**
     * The cost of the order
     *
     * @var float
     */
	private $ammount;

    /**
     * The Item Name
     *
     * @var string
     */
    private $name;

    /**
     * The Item ID?
     *
     * @var mixed
     */
    private $item;

    /**
     * The Order ID
     *
     * @var mixed
     */
    private $id;

    /**
     * Additional Data
     *
     * @var array
     */
    public $additional = array();
	
	function __construct($ammount){
		$this->ammount = $ammount;
	}

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return float
     */
    public function getAmmount()
    {
        return $this->ammount;
    }

    /**
     * @param float $ammount
     */
    public function setAmmount($ammount)
    {
        $this->ammount = $ammount;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param mixed $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    /**
     * @return array
     */
    public function getAdditional()
    {
        return $this->additional;
    }

    /**
     * @param array $additional
     */
    public function setAdditional($additional)
    {
        $this->additional = $additional;
    }
	
	function getId(){
        //If this gateway doesnt give IDs to transactions, then generate an id
		if($this->id === null){
			return time().rand(0, PHP_INT_MAX);
		}
		
		return $this->id;
	}

    function setId($id){
        $this->id = $id;
    }
}