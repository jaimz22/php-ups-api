<?php
namespace Ups;

use DOMDocument;
use SimpleXMLElement;
use Exception;
use InvalidArgumentException;
use stdClass;
use Ups\Entity\Address;
use Ups\Entity\Shipment;

/**
 * Package Shipping API Wrapper
 * Based on UPS Developer Guide, dated: 31 Dec 2012
 *
 * @package ups
 */
class Shipping extends Ups
{
    const REQ_VALIDATE = 'validate';
    const REQ_NONVALIDATE = 'nonvalidate';

    /**
     * @var string
     */
    private $shipConfirmEndpoint = '/ShipConfirm';

    /**
     * @var string
     */
    private $shipAcceptEndpoint = '/ShipAccept';

    /**
     * @var string
     */
    private $voidEndpoint = '/Void';

    /**
     * @var string
     */
    private $recoverLabelEndoint = '/LabelRecovery';

    /**
     * Create a Shipment Confirm request (generate a digest)
     *
     * @param string $validation A UPS_Shipping::REQ_* constant (or null)
     * @param stdClass $shipment Shipment data container.
     * @param stdClass|null $labelSpecOpts LabelSpecification data. Optional
     * @param stdClass|null $receiptSpecOpts ReceiptSpecification data. Optional
     * @return stdClass
     * @throws Exception
     */
    public function confirm($validation, $shipment, $labelSpecOpts = null, $receiptSpecOpts = null)
    {
        $request = $this->createConfirmRequest($validation, $shipment, $labelSpecOpts, $receiptSpecOpts);

        $response = $this->request($this->createAccess(), $request, $this->compileEndpointUrl($this->shipConfirmEndpoint));

        if ($response->Response->ResponseStatusCode == 0) {
            throw new Exception(
                "Failure ({$response->Response->Error->ErrorCode}: {$response->Response->Error->ErrorSeverity}): {$response->Response->Error->ErrorDescription}",
                (int)$response->Response->Error->ErrorCode
            );
        } else {
            unset($response->Response);
            return $this->formatResponse($response);
        }
    }

    /**
     * Creates a ShipConfirm request
     *
     * @param string $validation
     * @param Shipment $shipment
     * @param stdClass|null $labelSpecOpts
     * @param stdClass|null $receiptSpecOpts
     * @return string
     */
    private function createConfirmRequest($validation, $shipment, $labelSpecOpts, $receiptSpecOpts)
    {
        $xml = new DOMDocument();
        $xml->formatOutput = true;

        // Page 45
        $container = $xml->appendChild($xml->createElement('ShipmentConfirmRequest'));

        // Page 45
        $request = $container->appendChild($xml->createElement('Request'));

        $node = $xml->importNode($this->createTransactionNode(), true);
        $request->appendChild($node);

        $request->appendChild($xml->createElement('RequestAction', 'ShipConfirm'));
        $request->appendChild($xml->createElement('RequestOption', $validation ? : ''));

        // Page 47
        $shipmentNode = $container->appendChild($xml->createElement('Shipment'));

        if (!is_null($shipment->getDescription())) {
            $shipmentNode->appendChild($xml->createElement('Description', $shipment->getDescription()));
        }

        if (isset($shipment->ReturnService)) {
            $node = $shipmentNode->appendChild($xml->createElement('ReturnService'));

            $node->appendChild($xml->createElement('Code', $shipment->ReturnService->Code));

            if ($shipment->ReturnService->DocumentsOnly) {
                $node->appendChild($xml->createElement('DocumentsOnly'));
            }
        }

        $shipperNode = $shipmentNode->appendChild($xml->createElement('Shipper'));

        $shipperNode->appendChild($xml->createElement('Name', $shipment->getShipper()->getName()));

        if (!is_null($shipment->getShipper()->getAttentionName())) {
            $shipperNode->appendChild($xml->createElement('AttentionName', $shipment->getShipper()->getAttentionName()));
        }

        if (!is_null($shipment->getShipper()->getCompanyDisplayableName())) {
            $shipperNode->appendChild($xml->createElement('CompanyDisplayableName', $shipment->getShipper()->getCompanyDisplayableName()));
        }

        $shipperNode->appendChild($xml->createElement('ShipperNumber', $shipment->getShipper()->getShipperNumber()));

        if (!is_null($shipment->getShipper()->getTaxIdentificationNumber())) {
            $shipperNode->appendChild($xml->createElement('TaxIdentificationNumber', $shipment->getShipper()->getTaxIdentificationNumber()));
        }

        if (!is_null($shipment->getShipper()->getPhoneNumber())) {
            $shipperNode->appendChild($xml->createElement('PhoneNumber', $shipment->getShipper()->getPhoneNumber()));
        }

        if (!is_null($shipment->getShipper()->getFaxNumber())) {
            $shipperNode->appendChild($xml->createElement('FaxNumber', $shipment->getShipper()->getFaxNumber()));
        }

        if (!is_null($shipment->getShipper()->getEmailAddress())) {
            $shipperNode->appendChild($xml->createElement('EMailAddress', $shipment->getShipper()->getEmailAddress()));
        }

        $addressNode = $xml->importNode($this->compileAddressNode($shipment->getShipper()->getAddress()), true);
        $shipperNode->appendChild($addressNode);

        $shipToNode = $shipmentNode->appendChild($xml->createElement('ShipTo'));

		if (!is_null($shipment->getShipTo()->getCompanyName())){
			$shipToNode->appendChild($xml->createElement('CompanyName', $shipment->getShipTo()->getCompanyName()));
		}

		if (!is_null($shipment->getShipTo()->getName())){
			$shipToNode->appendChild($xml->createElement('Name', $shipment->getShipTo()->getName()));
		}

        if (!is_null($shipment->getShipTo()->getAttentionName())) {
            $shipToNode->appendChild($xml->createElement('AttentionName', $shipment->getShipTo()->getAttentionName()));
        }

        if (!is_null($shipment->getShipTo()->getPhoneNumber())) {
            $shipToNode->appendChild($xml->createElement('PhoneNumber', $shipment->getShipTo()->getPhoneNumber()));
        }

        if (!is_null($shipment->getShipTo()->getFaxNumber())) {
            $shipToNode->appendChild($xml->createElement('FaxNumber', $shipment->getShipTo()->getFaxNumber()));
        }

        if (!is_null($shipment->getShipTo()->getEmailAddress())) {
            $shipToNode->appendChild($xml->createElement('EMailAddress', $shipment->getShipTo()->getEmailAddress()));
        }

        $addressNode = $xml->importNode($this->compileAddressNode($shipment->getShipTo()->getAddress()), true);

        if ($shipment->getShipTo()->isResidentialAddress()) {
            $addressNode->appendChild($xml->createElement('ResidentialAddress'));
        }

        if (!is_null($shipment->getShipTo()->getLocationId())) {
            $addressNode->appendChild($xml->createElement('LocationID', strtoupper($shipment->getShipTo()->getLocationId())));
        }

        $shipToNode->appendChild($addressNode);

        if (!is_null($shipment->getShipFrom())) {
            $shipFromNode = $shipmentNode->appendChild($xml->createElement('ShipFrom'));

            $shipFromNode->appendChild($xml->createElement('CompanyName', $shipment->getShipFrom()->getCompanyName()));

            if (!is_null($shipment->getShipFrom()->getAttentionName())) {
                $shipFromNode->appendChild($xml->createElement('AttentionName', $shipment->getShipFrom()->getAttentionName()));
            }

            if (!is_null($shipment->getShipFrom()->getPhoneNumber())) {
                $shipFromNode->appendChild($xml->createElement('PhoneNumber', $shipment->getShipFrom()->getPhoneNumber()));
            }

            if (!is_null($shipment->getShipFrom()->getFaxNumber())) {
                $shipFromNode->appendChild($xml->createElement('FaxNumber', $shipment->getShipFrom()->getFaxNumber()));
            }

            $addressNode = $xml->importNode($this->compileAddressNode($shipment->getShipFrom()->getAddress()), true);
            $shipFromNode->appendChild($addressNode);
        }

        if (isset($shipment->SoldTo)) {
            $soldToNode = $shipmentNode->appendChild($xml->createElement('SoldTo'));

            if ($shipment->SoldTo->Option) {
                $soldToNode->appendChild($xml->createElement('Option', $shipment->SoldTo->Option));
            }

            $soldToNode->appendChild($xml->createElement('CompanyName', $shipment->SoldTo->CompanyName));

            if ($shipment->SoldTo->AttentionName) {
                $soldToNode->appendChild($xml->createElement('AttentionName', $shipment->SoldTo->AttentionName));
            }

            if ($shipment->SoldTo->PhoneNumber) {
                $soldToNode->appendChild($xml->createElement('PhoneNumber', $shipment->SoldTo->PhoneNumber));
            }

            if ($shipment->SoldTo->FaxNumber) {
                $soldToNode->appendChild($xml->createElement('FaxNumber', $shipment->SoldTo->FaxNumber));
            }

            if ($shipment->SoldTo->Address) {
                $addressNode = $xml->importNode($this->compileAddressNode($shipment->SoldTo->Address), true);
                $soldToNode->appendChild($addressNode);
            }
        }

        if (isset($shipment->PaymentInformation)) {
            $paymentNode = $shipmentNode->appendChild($xml->createElement('PaymentInformation'));

            if ($shipment->PaymentInformation->Prepaid) {
                $node = $paymentNode->appendChild($xml->createElement('Prepaid'));
                $node = $node->appendChild($xml->createElement('BillShipper'));

                if ($shipment->PaymentInformation->Prepaid->BillShipper->AccountNumber) {
                    $node->appendChild($xml->createElement('AccountNumber', $shipment->PaymentInformation->Prepaid->BillShipper->AccountNumber));
                } else if ($shipment->PaymentInformation->Prepaid->BillShipper->CreditCard) {
                    $ccNode = $node->appendChild($xml->createElement('CreditCard'));
                    $ccNode->appendChild($xml->createElement('Type', $shipment->PaymentInformation->Prepaid->BillShipper->CreditCard->Type));
                    $ccNode->appendChild($xml->createElement('Number', $shipment->PaymentInformation->Prepaid->BillShipper->CreditCard->Number));
                    $ccNode->appendChild($xml->createElement('ExpirationDate', $shipment->PaymentInformation->Prepaid->BillShipper->CreditCard->ExpirationDate));

                    if ($shipment->PaymentInformation->Prepaid->BillShipper->CreditCard->SecurityCode) {
                        $ccNode->appendChild($xml->createElement('SecurityCode', $shipment->PaymentInformation->Prepaid->BillShipper->CreditCard->SecurityCode));
                    }

                    if ($shipment->PaymentInformation->Prepaid->BillShipper->CreditCard->Address) {
                        $addressNode = $xml->importNode($this->compileAddressNode($shipment->PaymentInformation->Prepaid->BillShipper->CreditCard->Address), true);
                        $ccNode->appendChild($addressNode);
                    }
                }
            } else if ($shipment->PaymentInformation->BillThirdParty) {
                $node = $paymentNode->appendChild($xml->createElement('BillThirdParty'));
                $btpNode = $node->appendChild($xml->createElement('BillThirdPartyShipper'));
                $btpNode->appendChild($xml->createElement('AccountNumber', $shipment->PaymentInformation->BillThirdParty->AccountNumber));

                $tpNode = $btpNode->appendChild($xml->createElement('ThirdParty'));
                $addressNode = $tpNode->appendChild($xml->createElement('Address'));

                if ($shipment->PaymentInformation->BillThirdParty->ThirdParty->Address->PostalCode) {
                    $addressNode->appendChild($xml->createElement('PostalCode', $shipment->PaymentInformation->BillThirdParty->ThirdParty->Address->PostalCode));
                }

                $addressNode->appendChild($xml->createElement('CountryCode', $shipment->PaymentInformation->BillThirdParty->ThirdParty->Address->CountryCode));
            } else if ($shipment->PaymentInformation->FreightCollect) {
                $node = $paymentNode->appendChild($xml->createElement('FreightCollect'));
                $brNode = $node->appendChild($xml->createElement('BillReceiver'));
                $brNode->appendChild($xml->createElement('AccountNumber', $shipment->PaymentInformation->FreightCollect->BillReceiver->AccountNumber));

                if ($shipment->PaymentInformation->FreightCollect->BillReceiver->Address) {
                    $addressNode = $brNode->appendChild($xml->createElement('Address'));
                    $addressNode->appendChild($xml->createElement('PostalCode', $shipment->PaymentInformation->FreightCollect->BillReceiver->Address->PostalCode));
                }
            } else if ($shipment->PaymentInformation->ConsigneeBilled) {
                $paymentNode->appendChild($xml->createElement('ConsigneeBilled'));
            }
//        } else if ($shipment->ItemizedPaymentInformation) {
//            $paymentNode = $shipmentNode->appendChild($xml->createElement('ItemizedPaymentInformation'));
        }

//        if (isset($shipment->GoodsNotInFreeCirculationIndicator)) {
//            $shipmentNode->appendChild($xml->createElement('GoodsNotInFreeCirculationIndicator'));
//        }
//
//        if (isset($shipment->MovementReferenceNumber)) {
//            $shipmentNode->appendChild($xml->createElement('MovementReferenceNumber', $shipment->MovementReferenceNumber));
//        }

        $serviceNode = $shipmentNode->appendChild($xml->createElement('Service'));
        $serviceNode->appendChild($xml->createElement('Code', $shipment->getService()->getCode()));

        if (!is_null($shipment->getService()->getDescription())) {
            $serviceNode->appendChild($xml->createElement('Description', $shipment->getService()->getDescription()));
        }

//        if (isset($shipment->InvoiceLineTotal)) {
//            $node = $shipmentNode->appendChild($xml->createElement('InvoiceLineTotal'));
//
//            if ($shipment->InvoiceLineTotal->CurrencyCode) {
//                $node->appendChild($xml->createElement('CurrencyCode', $shipment->InvoiceLineTotal->CurrencyCode));
//            }
//
//            $node->appendChild($xml->createElement('MonetaryValue', $shipment->InvoiceLineTotal->MonetaryValue));
//        }
//
//        if (isset($shipment->NumOfPiecesInShipment)) {
//            $shipmentNode->appendChild($xml->createElement('NumOfPiecesInShipment', $shipment->NumOfPiecesInShipment));
//        }

        foreach ($shipment->getPackages() as $package) {
            $node = $shipmentNode->appendChild($xml->createElement('Package'));

            $ptNode = $node->appendChild($xml->createElement('PackagingType'));
            $ptNode->appendChild($xml->createElement('Code', $package->getPackagingType()->getCode()));

            if (!is_null($package->getPackagingType()->getDescription())) {
                $ptNode->appendChild($xml->createElement('Description', $package->getPackagingType()->getDescription()));
            }

            $pwNode = $node->appendChild($xml->createElement('PackageWeight'));
            $umNode = $pwNode->appendChild($xml->createElement('UnitOfMeasurement'));

            if (!is_null($package->getPackageWeight()->getUnitOfMeasurement()->getCode())) {
                $umNode->appendChild($xml->createElement('Code', $package->getPackageWeight()->getUnitOfMeasurement()->getCode()));
            }

            if (!is_null($package->getPackageWeight()->getUnitOfMeasurement()->getDescription())) {
                $umNode->appendChild($xml->createElement('Description', $package->getPackageWeight()->getUnitOfMeasurement()->getDescription()));
            }

            $pwNode->appendChild($xml->createElement('Weight', $package->getPackageWeight()->getWeight()));

            if (!is_null($package->getLargePackage())) {
                $node->appendChild($xml->createElement('LargePackageIndicator'));
            }

            if (is_null($package->getReferenceNumber())) {
                $refNode = $node->appendChild($xml->createElement('ReferenceNumber'));

                if (!is_null($package->getReferenceNumber()->getBarCodeIndicator())) {
                    $refNode->appendChild($xml->createElement('BarCodeIndicator', $package->getReferenceNumber()->getBarCodeIndicator()));
                }

                $refNode->appendChild($xml->createElement('Code', $package->getReferenceNumber()->getCode()));
                $refNode->appendChild($xml->createElement('Value', $package->getReferenceNumber()->getValue()));
            }

            if (!is_null($package->getAdditionalHandling())) {
                $node->appendChild($xml->createElement('AdditionalHandling'));
            }
        }

        if ($labelSpecOpts) {
            $labelSpec = $container->appendChild($xml->createElement('LabelSpecification'));

            $node = $labelSpec->appendChild($xml->createElement('LabelPrintMethod'));
            $node->appendChild($xml->createElement('Code', $labelSpecOpts->LabelPrintMethod->Code));

            if (isset($labelSpecOpts->LabelPrintMethod->Description)) {
                $node->appendChild($xml->createElement('Description', $labelSpecOpts->LabelPrintMethod->Description));
            }

            if (isset($labelSpecOpts->HTTPUserAgent)) {
                $labelSpec->appendChild($xml->createElement('HTTPUserAgent', $labelSpecOpts->HTTPUserAgent));
            }

            if (isset($labelSpecOpts->LabelStockSize)) {
                $stock = $labelSpec->appendChild($xml->createElement('LabelStockSize'));

                $stock->appendChild($xml->createElement('Height', $labelSpecOpts->LabelStockSize->Height));
                $stock->appendChild($xml->createElement('Width', $labelSpecOpts->LabelStockSize->Width));
            }

            $node = $labelSpec->appendChild($xml->createElement('LabelImageFormat'));
            $node->appendChild($xml->createElement('Code', $labelSpecOpts->ImageFormat->Code));

            if (isset($labelSpecOpts->ImageFormat->Description)) {
                $node->appendChild($xml->createElement('Description', $labelSpecOpts->ImageFormat->Description));
            }

            if (isset($labelSpecOpts->Instruction)) {
                $node = $labelSpec->appendChild($xml->createElement('Instruction'));
                $node->appendChild($xml->createElement('Code', $labelSpecOpts->Instruction->Code));

                if ($labelSpecOpts->Instruction->Description) {
                    $node->appendChild($xml->createElement('Description', $labelSpecOpts->Instruction->Description));
                }
            }
        }

        if ($receiptSpecOpts) {
            $receiptSpec = $container->appendChild($xml->createElement('ReceiptSpecification'));
            $node = $receiptSpec->appendChild($xml->createElement('ImageFormat'));
            $node->appendChild($xml->createElement('Code', $receiptSpecOpts->ImageFormat->Code));

            if ($receiptSpecOpts->ImageFormat->Description) {
                $node->appendChild($xml->createElement('Description', $receiptSpecOpts->ImageFormat->Description));
            }
        }

        return $xml->saveXML();
    }

    /**
     * Create a Shipment Accept request (generate a shipping label)
     *
     * @param string $shipmentDigest The UPS Shipment Digest received from a ShipConfirm request.
     * @return stdClass
     * @throws Exception
     */
    public function accept($shipmentDigest)
    {
        $request = $this->createAcceptRequest($shipmentDigest);
        $response = $this->request($this->createAccess(), $request, $this->compileEndpointUrl($this->shipAcceptEndpoint));

        if ($response->Response->ResponseStatusCode == 0) {
            throw new Exception(
                "Failure ({$response->Response->Error->ErrorSeverity}): {$response->Response->Error->ErrorDescription}",
                (int)$response->Response->Error->ErrorCode
            );
        } else {
            return $this->formatResponse($response->ShipmentResults);
        }
    }

    /**
     * Creates a ShipAccept request
     *
     * @param string $shipmentDigest
     * @return string
     */
    private function createAcceptRequest($shipmentDigest)
    {
        $xml = new DOMDocument();
        $xml->formatOutput = true;

        $container = $xml->appendChild($xml->createElement('ShipmentAcceptRequest'));
        $request = $container->appendChild($xml->createElement('Request'));

        $node = $xml->importNode($this->createTransactionNode(), true);
        $request->appendChild($node);

        $request->appendChild($xml->createElement('RequestAction', 'ShipAccept'));
        $container->appendChild($xml->createElement('ShipmentDigest', $shipmentDigest));

        return $xml->saveXML();
    }

    /**
     * Void a shipping label / request
     *
     * @param string|array $shipmentData Either the UPS Shipment Identification Number or an array of
     *                                   expanded shipment data [shipmentId:, trackingNumbers:[...]]
     * @return stdClass
     * @throws Exception
     */
    public function void($shipmentData)
    {
        if (is_array($shipmentData) && !isset($shipmentData['shipmentId'])) {
            throw new InvalidArgumentException('$shipmentData parameter is required to contain a key `shipmentId`.');
        }

        $request = $this->createVoidRequest($shipmentData);
        $response = $this->request($this->createAccess(), $request, $this->compileEndpointUrl($this->voidEndpoint));

        if ($response->Response->ResponseStatusCode == 0) {
            throw new Exception(
                "Failure ({$response->Response->Error->ErrorSeverity}): {$response->Response->Error->ErrorDescription}",
                (int)$response->Response->Error->ErrorCode
            );
        } else {
            unset($response->Response);
            return $this->formatResponse($response);
        }
    }

    /**
     * Creates a void shipment request
     *
     * @param string|array $shipmentData
     * @return string
     */
    private function createVoidRequest($shipmentData)
    {
        $xml = new DOMDocument();
        $xml->formatOutput = true;

        $container = $xml->appendChild($xml->createElement('VoidShipmentRequest'));
        $request = $container->appendChild($xml->createElement('Request'));

        $node = $xml->importNode($this->createTransactionNode(), true);
        $request->appendChild($node);

        $request->appendChild($xml->createElement('RequestAction', '1'));

        if (is_string($shipmentData)) {
            $request->appendChild($xml->createElement('ShipmentIdentificationNumber', strtoupper($shipmentData)));
        } else {
            $expanded = $request->appendChild($xml->createElement('ExpandedVoidShipment'));
            $expanded->appendChild($xml->createElement('ShipmentIdentificationNumber', strtoupper($shipmentData['shipmentId'])));

            if (array_key_exists('trackingNumbers', $shipmentData)) {
                foreach ($shipmentData['trackingNumbers'] as $tn) {
                    $expanded->appendChild($xml->createElement('TrackingNumber', strtoupper($tn)));
                }
            }
        }

        return $xml->saveXML();
    }

    /**
     * Recover a shipping label
     *
     * @param string|array $trackingData Either the tracking number or a map of ReferenceNumber data
     *                                   [value:, shipperNumber:]
     * @param array|null $labelSpecification Map of label specification data for this request. Optional.
     *                                       [userAgent:, imageFormat: 'HTML|PDF']
     * @param array|null $labelDelivery All elements are optional. [link:]
     * @param array|null $translate Map of translation data. Optional. [language:, dialect:]
     * @return stdClass
     * @throws Exception|InvalidArgumentException
     */
    public function recoverLabel($trackingData, $labelSpecification = null, $labelDelivery = null, $translate = null)
    {
        if (is_array($trackingData)) {
            if (!isset($trackingData['value'])) {
                throw new InvalidArgumentException('$trackingData parameter is required to contain `value`.');
            }

            if (!isset($trackingData['shipperNumber'])) {
                throw new InvalidArgumentException('$trackingData parameter is required to contain `shipperNumber`.');
            }
        }

        if (!empty($translate)) {
            if (!isset($translateOpts['language'])) {
                $translateOpts['language'] = 'eng';
            }

            if (!isset($translateOpts['dialect'])) {
                $translateOpts['dialect'] = 'US';
            }
        }

        $request = $this->createRecoverLabelRequest($trackingData, $labelSpecification, $labelDelivery, $translate);
        $response = $this->request($this->createAccess(), $request, $this->compileEndpointUrl($this->recoverLabelEndpoint));

        if ($response->Response->ResponseStatusCode == 0) {
            throw new Exception(
                "Failure ({$response->Response->Error->ErrorSeverity}): {$response->Response->Error->ErrorDescription}",
                (int)$response->Response->Error->ErrorCode
            );
        } else {
            unset($response->Response);
            return $this->formatResponse($response);
        }
    }

    /**
     * Creates a label recovery request
     *
     * @param string|array $trackingData
     * @param array|null $labelSpecificationOpts
     * @param array|null $labelDeliveryOpts
     * @param array|null $translateOpts
     * @return string
     */
    private function createRecoverLabelRequest($trackingData, $labelSpecificationOpts = null, $labelDeliveryOpts = null, $translateOpts = null)
    {
        $xml = new DOMDocument();
        $xml->formatOutput = true;

        $container = $xml->appendChild($xml->createElement('LabelRecoveryRequest'));
        $request = $container->appendChild($xml->createElement('Request'));

        $node = $xml->importNode($this->createTransactionNode(), true);
        $request->appendChild($node);

        $request->appendChild($xml->createElement('RequestAction', 'LabelRecovery'));

        if (!empty($labelSpecificationOpts)) {
            $labelSpec = $request->appendChild($xml->createElement('LabelSpecification'));

            if (isset($labelSpecificationOpts['userAgent'])) {
                $labelSpec->appendChild($xml->createElement('HTTPUserAgent', $labelSpecificationOpts['userAgent']));
            }

            if (isset($labelSpecificationOpts['imageFormat'])) {
                $format = $labelSpec->appendChild($xml->createElement('LabelImageFormat'));
                $format->appendChild($xml->createElement('Code', $labelSpecificationOpts['imageFormat']));
            }
        }

        if (!empty($labelDeliveryOpts)) {
            $labelDelivery = $request->appendChild($xml->createElement('LabelDelivery'));
            $labelDelivery->appendChild($xml->createElement('LabelLinkIndicator', $labelDeliveryOpts['link']));
        }

        if (!empty($translateOpts)) {
            $translate = $request->appendChild($xml->createElement('Translate'));
            $translate->appendChild($xml->createElement('LanguageCode', $translateOpts['language']));
            $translate->appendChild($xml->createElement('DialectCode', $translateOpts['dialect']));
            $translate->appendChild($xml->createElement('Code', '01'));
        }

        return $xml->saveXML();
    }

    /**
     * Format the response
     *
     * @param SimpleXMLElement $response
     * @return stdClass
     */
    private function formatResponse(SimpleXMLElement $response)
    {
        return $this->convertXmlObject($response);
    }

	/**
	 * Generates a standard <Address> node for requests
	 *
	 * @param Address $address Address data structure
	 *
	 * @return \DOMNode
	 */
    private function compileAddressNode($address)
    {
        $xml = new DOMDocument;
        $xml->formatOutput = true;

        $node = $xml->appendChild($xml->createElement('Address'));

        $node->appendChild($xml->createElement('AddressLine1', $address->getAddressLine1()));

        if (!is_null($address->getAddressLine2())) {
            $node->appendChild($xml->createElement('AddressLine2', $address->getAddressLine2()));
        }

        if (!is_null($address->getAddressLine3())) {
            $node->appendChild($xml->createElement('AddressLine3', $address->getAddressLine3()));
        }

        $node->appendChild($xml->createElement('City', $address->getCity()));

        if (!is_null($address->getStateProvinceCode())) {
            $node->appendChild($xml->createElement('StateProvinceCode', $address->getStateProvinceCode()));
        }

        if (!is_null($address->getPostalCode())) {
            $node->appendChild($xml->createElement('PostalCode', $address->getPostalCode()));
        }

        if (!is_null($address->getCountryCode())) {
            $node->appendChild($xml->createElement('CountryCode', $address->getCountryCode()));
        }

        return $node->cloneNode(true);
    }
}