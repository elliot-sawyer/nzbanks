<?php
namespace CryptoPay\NZBanks;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

/**
 * Bank model - describes a Bank from data obtained from PaymentsNZ
 * @author  Elliot Sawyer <elliot.sawyer@gmail.com>
 * @license MIT https://github.com/silverstripe-elliot/nzbankvalidator/blob/master/LICENSE
 */

class Bank extends DataObject
{
    private static $indexes = array(
        'BankNumber' => true,
        'BranchNumber' => true
    );

    private static $summary_fields = array(
        'BankNumber' => 'BankNumber',
        "BranchNumber" => 'BranchNumber',
        'BankName' => 'BankName',
        'City' => 'City'
    );

    /**
     * Schema of Payments NZ information
     * Public Info: http://www.paymentsnz.co.nz/clearing-systems/bulk-electronic-clearing-system
     * Public Data: https://www.paymentsnz.co.nz/documents/6/
     * Public Schema: https://www.paymentsnz.co.nz/documents/7/Bank-Branch-Definitions.docx
     * @var array
     */
    private static $db = array(
        'BankNumber' => 'Varchar(2)',
        'BranchNumber' => 'Varchar(4)',
        'NationalClearingCode' => 'Varchar(6)', //Required if BIC Plus Indication Flag is set to Y (Combines bank and branch numbers)
        'BIC' => 'Varchar(11)', //Either 8 or 11 character SWIFT BIC reference, otherwise blank
        'BankName' => 'Varchar(70)',    //full name of bank
        'BranchInformation' => 'Varchar(70)', //branch name
        'City' => 'Varchar(70)', //city/town where branch is located
        'PhysicalAddress1' => 'Varchar(35)',    //The actual location, ie: floor number, building name, street number and street name
        'PhysicalAddress2' => 'Varchar(35)',
        'PhysicalAddress3' => 'Varchar(35)',
        'PhysicalAddress4' => 'Varchar(35)',
        'PostCode' => 'Varchar(15)', //post code of physical address
        'Location' => 'Varchar(90)', //not used
        'CountryName' => 'Varchar(70)', //default New Zealand
        'POBNumber' => 'Varchar(35)', //po box number
        'POBLocation1' => 'Varchar(20)', //po box address excludeing number
        'POBLocation2' => 'Varchar(35)',
        'POBLocation3' => 'Varchar(35)',
        'POBPostCode' => 'Varchar(15)',
        'POBCountry' => 'Varchar(70)',  //post cost of post office box
        'STD' => 'Varchar(4)', //Area code
        'Phone' => 'Varchar(14)', //Phone number excluding Area Code
        'Fax' => 'Varchar(14)', //Fax number excluding Area Code
        'Retail' => 'Varchar(1)', //R = if retail branch. Otherwise blank. (This field is no longer in use)
        'BICPlusIndicator' => 'Varchar(1)', //Y = include on BIC Plus File,N or Null = do not include on BIC Plus File
        'LatestStatus' => 'Varchar(1)' //A = Added/new record,M = Modified,U = Update/no change,C = Closed
    );

    /*
    * Getter for the six digit Bank prefix
    * This is *not* the same as NationalClearingCode, which can be empty
     */
    public function getPrefix()
    {
        return $this->BankNumber.$this->BranchNumber;
    }
    /*
    * Identifies a bank branch by the first six digits of the account number
    * This is not the same as bank account validation, which validates against a checksum
    *
    * @param accountNumber - an NZ Bank account number. All non-digits will be stripped
    *                         but you should attempt to clean up as much as possible
    *                        Only the first six numbers are used, the rest are discarded
    * @return the Bank object or false if it couldn't be identified
     */
    public static function identify($accountNumber)
    {
        $bankAccount = preg_replace("/[^0-9]/", '', $accountNumber);
        $bankNumber = substr($bankAccount, 0, 2);
        $branchNumber = substr($bankAccount, 2, 4);

        $bank = Bank::get()->filter(array(
            'BankNumber' => $bankNumber,
            'BranchNumber' => $branchNumber,
        ))->First();
        return ($bank && $bank->ID) ? $bank : false;
    }

    /*
    * "Prettify" the bank account information prior to displaying it
    * @return String: A normalized bank account number in the following format:
    *         BankNumber-BranchNumber-Account-Suffix
    *         - OR -
    *         null if bank cannot be identified
    *
     */
    public static function prettify($accountNumber)
    {

        $parts = preg_split('/[^0-9]/', $accountNumber);
        if (count($parts) === 4) {
            //IRD requires components to be zero-padded on left to max length
            $bankID = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $bankBranch = str_pad($parts[1], 4, '0', STR_PAD_LEFT);
            $bankAccount = str_pad($parts[2], 8, '0', STR_PAD_LEFT);
            $bankSuffix  = str_pad($parts[3], 4, '0', STR_PAD_LEFT);

            return sprintf("%s-%s-%s-%s", $bankID, $bankBranch, $bankAccount, $bankSuffix);
        }

        return null;
    }

    public function canView($member = null, $context = []) {return true;}
    public function canEdit($member = null, $context = []) {return Permission::check('ADMIN');}
    public function canDelete($member = null, $context = []) {return false;}
    public function canCreate($member = null, $context = []) {return Permission::check('ADMIN');}


}
