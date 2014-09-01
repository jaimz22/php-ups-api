<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/1/2014
 * @time: 12:42 PM
 */

namespace Ups\Entity;


use Ups\Entity\PaymentInformation\Prepaid;

class PaymentInformation
{
	/**
	 * @var Prepaid
	 */
	protected $prepaid;
	/**
	 * @var @deprecated
	 */
	protected $billThirdParty;
	/**
	 * @var @deprecated
	 */
	protected $freightCollect;
	/**
	 * @var @deprecated
	 */
	protected $consigneeBilled;

	/**
	 * @return Prepaid
	 */
	public function getPrepaid()
	{
		return $this->prepaid;
	}

	/**
	 * @param Prepaid $prepaid
	 */
	public function setPrepaid($prepaid)
	{
		$this->prepaid = $prepaid;
	}
}