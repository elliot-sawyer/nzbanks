<?php
namespace CryptoPay\NZBanks;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
/**
 * Task for downloading the most recent register of active banks from Payments NZ
 * This list is generally updated once a month
 * If the URL changes, it can be set using the Config API
 */
class DownloadBankRegisterInformation extends BuildTask
{

    /**
     * This is the URL to the banks register. It is a text file with approximately 3000 records
     */
    private static $bank_register_source = "https://www.paymentsnz.co.nz/documents/6/";

    /**
     * Temp variable for storing the text source
     */
    private $bankRegisterSource = null;


    /**
     * Run the task. This accepts no parameters
     */
    public function run($request)
    {
        //download the payload from Payments NZ
        if ($this->downloadTextFromPaymentsNZ()) {
            //parse the payload
            $this->parseBankRegister();
        }
    }

    /**
     * Download the register of banks from Payments NZ
     *
     * @return string txt file representing the bank branch register
     */
    private function downloadTextFromPaymentsNZ()
    {

        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $this->config()->bank_register_source);
        $txt = (string) $response->getBody();
        $this->bankRegisterSource = $txt;
        return $this->bankRegisterSource;
    }

    /**
     * The bank register is a spreadsheet masquerading as a text file.
     * As such, it contains rows of "columns" that are an exact width to suit the register
     * The schema has been mapped on the Bank model's DB columns.
     * Instead of repeating the work, we infer it from the DB schema
     *
     * The field definitions are available below
     * @link https://www.paymentsnz.co.nz/documents/7/Bank-Branch-Definitions.docx
     * @return array
     */
    private function getFieldLengthsFromDatabase()
    {
        $localSchema = Bank::getSchema()->fieldSpecs(Bank::class);
        foreach ($localSchema as $column => $field) {
            //from schema definition, these are always Varchar(##)
            //size is thus anything in field without a number
            $this->schema[$column] = preg_replace('/[^0-9]/', '', $field);
            //in case someone removed the number on varchar field
            if (empty($this->schema[$column])) {
                $this->schema[$column] = Bank::create()->dbObject($column)->size;
            }
            //if it's still empty, just skip it
            if (empty($this->schema[$column])) {
                continue;
            }
        }

        return $this->schema;
    }

    /**
     * Parse the bank register into the local database, using the Bank model
     * This will only add new banks - it does not update existing ones or delete closed ones.
     * This is to preserve the integrity of existing records
     *
     * @todo Bank branches are rarely, if ever, edited. This task should allow overwrites
     */
    private function parseBankRegister()
    {
        //get the schema
        $schema = $this->getFieldLengthsFromDatabase();

        //get the existing banks
        $existingBanks = Bank::get()->Sort(['BankNumber ASC', 'BranchNumber ASC'])->map('Prefix', 'ID')->toArray();
        $count = 0;

        //break the file into rows
        $contents = explode("\n", $this->bankRegisterSource);

        //foreach row...
        foreach ($contents as $id => $bank) {
            if ($id === 0) {
                continue; //skip header
            }
            //we need to parse each line by known lengths, starting with an array of chars
            $bankChars = str_split($bank);
            $bankDetails = null;
            //use array_splice to remove $size characters from the array
            //join and trim the result to obtain the token
            foreach ($schema as $column => $size) {
                $bankDetails[$column] = trim(
                    join(
                        array_splice(
                            $bankChars,
                            0,
                            $size
                        )
                    )
                );
            }

            //check if the bank already exists
            $prefix = $bankDetails['BankNumber'].$bankDetails['BranchNumber'];

            //if it does not exist, create the record.
            if (empty($existingBanks[$prefix])) {
                $bank = Bank::create($bankDetails);
                $bank->write();
                $count++;
            }
        }

        DB::alteration_message($count." records written");
    }
}
