<?php
namespace CryptoPay\NZBanks;
use SilverStripe\ORM\DataObject;
use NeilNZ\NZBankAccountValidation\Validator;
use CryptoPay\NZBanks\Bank;

/**
 * DataObject representation of a New Zealand bank account
 */
class NZBankAccount extends DataObject
{
    private static $table_name = 'NZBankAccount';
    private static $singular_name = 'Bank Account';
    private static $plural_name = 'Bank Accounts';

    /**
     * Database fields:
     * - AccountNumber: Padded version of a New Zealand bank account, including dashes
     *       Bank ID (maximum 2 digits)
     *       Bank branch (maximum 4 digits)
     *       Account base number (maximum 8 digits)
     *       Account suffix (maximum 4 digits).
     * - More info:
     * - https://www.ird.govt.nz/-/media/Project/IR/PDF/2020RWTNRWTSpecificationDocumentv10.pdf
     * - See "Bank account number validation"
     *
     *
     */
    private static $db = [
        'AccountNumber' => 'Varchar(21)'
    ];

    /**
     * Has One fields:
     *  - Bank: points back to the bank which controls the account
     */
    private static $has_one = [
        'Bank' => Bank::class
    ];

    /**
     * Summary fields for display purposes
     */
    private static $summary_fields = [
        'AccountNumber' => 'Account number',
        'Bank.BankName' => 'Bank',
        'Bank.BranchInformation' => 'Branch',
        'Bank.City' => 'City'
    ];

    /**
     * Display the bank account number instead of the record ID
     *
     * @return void
     */
    public function getTitle()
    {
        return $this->AccountNumber;
    }

    /**
     * CMS Fields
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('BankID');
        return $fields;
    }

    /**
     * We must store the fully padded version of the bank account
     * It must identify a New Zealand bank
     * Finally, it must pass the checksum validation
     */
    public function validate()
    {
        $valid = parent::validate();

        $accountNumber = NZBankAccount::prettify($this->AccountNumber);
        $bank = Bank::identify($accountNumber);

        if (!$accountNumber) {
            $valid->addError('Bank account number format not accepted.');
        }

        if (!($bank && $bank->ID)) {
            $valid->addError('Invalid bank account: bank identity.');
        }

        if (!$this->validateAccountNumber()) {
            $valid->addError('Invalid bank account: checksum failed.');
        }


        return $valid;
    }

    /**
     * Validate the bank account number
     */
    public function validateAccountNumber()
    {
        $accountNumber = $this->AccountNumber;
        list($bank, $branch, $account, $suffix) = explode('-', $accountNumber);

        return Validator::validate($bank, $branch, $account, $suffix);
    }

    /**
    * Check that the provided value contains alphanumeric, spaces, or a dash
    * @todo: technically allows a-ZA-Z0-9-~*()+=[]{} but that regex sucks
    * @param string value contents of a banking reference field
    * @return boolean
    **/
    public static function validate_reference_field($value)
    {
        return (bool) preg_match('/^[a-zA-Z0-9\-\ \/]{0,12}$/', (string) $value);
    }

    /**
     * The number is validated at this point
     * Before writing, format the number and assign it to the identified bank
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if ($this->AccountNumber) {
            $accountNumber = NZBankAccount::prettify($this->AccountNumber);
            $bank = Bank::identify($accountNumber);

            if ($bank && $bank->ID) {
                $this->BankID = $bank->ID;
            }
            $this->AccountNumber = $accountNumber;
        }
    }

    /**
     * Convert New Zealand dollars into cents
     * @todo this probably needs bcmul
     * @param float dollars, will be rounded up to 2 decimal points
     * @return int whole cents as an integer
     */
    public static function dollars_to_cents($dollars)
    {
        $dollars = round($dollars, 2);
        $cents = $dollars * 100;

        return (int) $cents;
    }

    /**
     * Convert New Zealand cents into dollars
     * @param int cents
     * @return float dollars
     */
    public static function cents_to_dollars($cents)
    {
        $cents = (int) $cents;
        $dollars = bcdiv($cents, 100, 2);
        return $dollars;
    }

    /**
    * "Prettify" the bank account information prior to displaying it
    *
    * @return string normalized bank account number in the following format:
    *         BankNumber-BranchNumber-Account-Suffix
    *         - OR -
    *         null if bank cannot be identified
    *
    */
    public static function prettify($accountNumber, $delimiter = '-')
    {

        $parts = preg_split('/[^0-9]/', $accountNumber);
        if (count($parts) === 4) {
            //IRD requires components to be zero-padded on left to max length
            $bankID = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $bankBranch = str_pad($parts[1], 4, '0', STR_PAD_LEFT);
            $bankAccount = str_pad($parts[2], 8, '0', STR_PAD_LEFT);
            $bankSuffix  = str_pad($parts[3], 4, '0', STR_PAD_LEFT);

            return sprintf("%s$delimiter%s$delimiter%s$delimiter%s", $bankID, $bankBranch, $bankAccount, $bankSuffix);
        }

        return null;
    }

    /**
     * Here we find an existing bank account, or create a new one for identification
     * Bank accounts are not currently assigned to members, but will be if internal payments are ever allowed
     * This method is to check that more than one account is not being used unless necessary
     * - on some occasions members may share accounts, such as a joint account
     * @todo allow has_many relationship with Member
     * @param string account number, which may contain dashes
     * @return NZBankAccount
     */
    public static function find_or_make($accountNumber)
    {
        $account = NZBankAccount::prettify($accountNumber);
        $bankAccount = NZBankAccount::get()->find('AccountNumber', $account);
        if (!($bankAccount && $bankAccount->ID)) {
            $bankAccount = NZBankAccount::create();
            $bankAccount->AccountNumber = $account;
            $bankAccount->write();
        }
        return $bankAccount;
    }
}
