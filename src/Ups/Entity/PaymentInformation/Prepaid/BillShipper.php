<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/1/2014
 * @time: 1:19 PM
 */

namespace Ups\Entity\PaymentInformation\Prepaid;


class BillShipper
{
	private $accountNumber;
	/**
	 * @var
	 * @deprecated
	 */
	private $creditCard;

	/**
	 * @return mixed
	 */
	public function getAccountNumber()
	{
		return $this->accountNumber;
	}

	/**
	 * @param mixed $accountNumber
	 */
	public function setAccountNumber($accountNumber)
	{
		$this->accountNumber = $accountNumber;
	}


} 