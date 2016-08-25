<?php

namespace Repository\ParcelServiceRepository;

    /**
    * PHP class for UPS Rate API
    * @author Janos Vajda
    */
    class UpsRate {

    /**
     * Access key of UPS Acoount 
     * @var string 
     */
    protected $accessKey = '';

    /**
     * User ID of UPS account
     * @var string
     */
    protected $userId = '';

    /**
     * Password of UPS Account
     * @var type 
     */
    protected $password = '';
    protected $ShipperNumber = ''; //Shipper number
    protected $ShipperZip = ''; //Shipper ZIP

    protected $webServiceLocation = ""; //Location of API
    protected $liveServiceUrl = 'https://www.ups.com/ups.app/xml/Rate'; //Production URL for UPS Rate API
    protected $integrationBaseUrl = 'https://wwwcie.ups.com/ups.app/xml/Rate'; //Test URL for UPS Rate API
    protected $packages = array();
    protected $returnPriceOnly = false;

    /**
     * PHP Class for UPS Rate API
     * @param type $accessKey
     * @param type $userId
     * @param type $password
     * @param type $useIntegration
     */
    public function __construct($accessKey = null, $userId = null, $password = null, $useIntegration = false) {
        $this->accessKey = $accessKey;
        $this->userId = $userId;
        $this->password = $password;

        if ($useIntegration) {
            $this->webServiceLocation = $this->integrationBaseUrl;
        } else {
            $this->webServiceLocation = $this->liveServiceUrl;
        }
    }

    /**
     * It sets type of the resultset
     * @param boolean $returnPriceOnly
     */
    public function setReturnPriceOnly($returnPriceOnly = false) {
        $this->returnPriceOnly = $returnPriceOnly;
    }

    /**
     * Set Shipper number
     * @param string $ShipperNumber
     */
    public function setShipperNumber($ShipperNumber) {
        $this->ShipperNumber = $ShipperNumber;
    }

    /**
     * Set shipper ZIP
     * @param string $ShipperZip
     */
    public function setShipperZip($ShipperZip) {
        $this->ShipperZip = $ShipperZip;
    }

    public function setPackages($packages) {
        $this->packages = $packages;
    }

    /**
     * Generates package sub xml.
     */
    private function generatePackageXML() {
        $xml_template = "";

        foreach ($this->packages as $package) {
            $xml_template.=
                    "<Package>  
                            <PackagingType>  
                                <Code>02</Code>  
                            </PackagingType>  
                            <Dimensions>  
                                <UnitOfMeasurement>  
                                    <Code>IN</Code>  
                                </UnitOfMeasurement>  
                                <Length>{$package['packageLength']}</Length>  
                                <Width>{$package['packageWidth']}</Width>  
                                <Height>{$package['packageHeight']}</Height>  
                            </Dimensions>  
                            <PackageWeight>  
                                <UnitOfMeasurement>  
                                    <Code>LBS</Code>  
                                </UnitOfMeasurement>  
                                <Weight>{$package['packageWeight']}</Weight>  
                            </PackageWeight>  
                        </Package>";
        }
        return $xml_template;
    }

    /**
     * Get UPS Service code
     * @param string $ServiceCode
     * @return string
     */
    private function GetServiceCode($ServiceCode = 'GND') {
        switch (strtoupper($ServiceCode)) {
            case '1DM':
                $ServiceCode = '14';
                break;
            case '1DA':
                $ServiceCode = '01';
                break;
            case '1DAPI':
                $ServiceCode = '01';
                break;
            case '1DP':
                $ServiceCode = '13';
                break;
            case '2DM':
                $ServiceCode = '59';
                break;
            case '2DA':
                $ServiceCode = '02';
                break;
            case '3DS':
                $strServiceCode = '12';
                break;
            case 'GND':
                $ServiceCode = '03';
                break;
            case 'GNDRES':
                $ServiceCode = '03';
                break;
            case 'GNDCOM':
                $ServiceCode = '03';
                break;
            case 'STD':
                $ServiceCode = '11';
                break;
            case 'XPR':
                $ServiceCode = '07';
                break;
            case 'XDM':
                $ServiceCode = '54';
                break;
            case 'XPD':
                $ServiceCode = '08';
                break;
            default:
                $ServiceCode = '03';
                break;
        }
        return $ServiceCode;
    }

    /**
     * Calls UPS API
     * @param type $strDestinationZip
     * @param type $strServiceShortName
     * @param type $strPackageLength
     * @param type $strPackageWidth
     * @param type $strPackageHeight
     * @param type $strPackageWeight
     * @param type $boolReturnPriceOnly
     * @return \SimpleXMLElement
     */
    public function GetShippingRateOrObject($strDestinationZip, $strServiceShortName = 'GND', $boolReturnPriceOnly = true) {
        $strServiceCode = $this->GetServiceCode($strServiceShortName);

        $packageXML = $this->generatePackageXML();

        $xml = "<?xml version=\"1.0\"?>  
        <AccessRequest xml:lang=\"en-US\">  
            <AccessLicenseNumber>{$this->accessKey}</AccessLicenseNumber>  
            <UserId>{$this->userId}</UserId>  
            <Password>{$this->password}</Password>  
        </AccessRequest>  
        <?xml version=\"1.0\"?>  
        <RatingServiceSelectionRequest xml:lang=\"en-US\">  
            <Request>  
                <TransactionReference>  
                    <CustomerContext>Bare Bones Rate Request</CustomerContext>  
                    <XpciVersion>1.0001</XpciVersion>  
                </TransactionReference>  
                <RequestAction>Rate</RequestAction>  
                <RequestOption>Rate</RequestOption>  
            </Request>  
            <PickupType>  
                <Code>01</Code>  
            </PickupType>  
            <Shipment>  
                <Shipper>  
                    <Address>  
                        <PostalCode>{$this->ShipperZip}</PostalCode>  
                        <CountryCode>US</CountryCode>  
                    </Address>  
                    <ShipperNumber>{$this->ShipperNumber}</ShipperNumber>  
                </Shipper>  
                <ShipTo>  
                    <Address>  
                        <PostalCode>{$strDestinationZip}</PostalCode>  
                        <CountryCode>US</CountryCode>  
                        <ResidentialAddressIndicator/>  
                    </Address>  
                </ShipTo>  
                <ShipFrom>  
                    <Address>  
                        <PostalCode>{$this->ShipperZip}</PostalCode>  
                        <CountryCode>US</CountryCode>  
                    </Address>  
                </ShipFrom>  
                <Service>  
                    <Code>{$strServiceCode}</Code>  
                </Service>  
                " . $packageXML . "
            </Shipment>  
        </RatingServiceSelectionRequest>";

        $curl_resource = curl_init($this->webServiceLocation);
        curl_setopt($curl_resource, CURLOPT_HEADER, 0);
        curl_setopt($curl_resource, CURLOPT_POST, 1);
        curl_setopt($curl_resource, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl_resource, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_resource, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl_resource, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl_resource, CURLOPT_POSTFIELDS, $xml);

        $result = curl_exec($curl_resource);

        $result = new \SimpleXMLElement($result);
        
        curl_close($curl_resource);

        if ($this->returnPriceOnly) {
            return (float)$result->RatedShipment->TotalCharges->MonetaryValue;
        } else {
            return $result;
        }
    }

}
