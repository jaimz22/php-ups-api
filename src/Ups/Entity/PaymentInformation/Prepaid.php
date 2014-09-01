<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/1/2014
 * @time: 1:19 PM
 */

namespace Ups\Entity\PaymentInformation;


use Ups\Entity\PaymentInformation\Prepaid\BillShipper;

class Prepaid
{
	/**
	 * @var BillShipper
	 */
	private $billShipper;

	/**
	 * @return BillShipper
	 */
	public function getBillShipper()
	{
		return $this->billShipper;
	}

	/**
	 * @param BillShipper $billShipper
	 */
	public function setBillShipper($billShipper)
	{
		$this->billShipper = $billShipper;
	}

}