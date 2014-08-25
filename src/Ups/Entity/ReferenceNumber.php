<?php
namespace Ups\Entity;

class ReferenceNumber
{
    public $Number;
    public $Code;
    public $Value;
    public $BarCodeIndicator;

    function __construct($response = null)
    {
        if (null != $response) {
            if (isset($response->BarCodeIndicator)) {
                $this->BarCodeIndicator = $response->BarCodeIndicator;
            }
            if (isset($response->Number)) {
                $this->Number = $response->Number;
            }
            if (isset($response->Code)) {
                $this->Code = $response->Code;
            }
            if (isset($response->Value)) {
                $this->Value = $response->Value;
            }
        }
    }

	/**
	 * @return mixed
	 */
	public function getBarCodeIndicator()
	{
		return $this->BarCodeIndicator;
	}

	/**
	 * @param mixed $BarCodeIndicator
	 */
	public function setBarCodeIndicator($BarCodeIndicator)
	{
		$this->BarCodeIndicator = $BarCodeIndicator;
	}

	/**
	 * @return mixed
	 */
	public function getCode()
	{
		return $this->Code;
	}

	/**
	 * @param mixed $Code
	 */
	public function setCode($Code)
	{
		$this->Code = $Code;
	}

	/**
	 * @return mixed
	 */
	public function getNumber()
	{
		return $this->Number;
	}

	/**
	 * @param mixed $Number
	 */
	public function setNumber($Number)
	{
		$this->Number = $Number;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->Value;
	}

	/**
	 * @param mixed $Value
	 */
	public function setValue($Value)
	{
		$this->Value = $Value;
	}


} 