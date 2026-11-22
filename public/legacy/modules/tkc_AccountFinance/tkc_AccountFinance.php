<?php
// SUITE-64: hand-written module (no Module Builder, D11). One row per account holds the Finance fields.
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

#[\AllowDynamicProperties]
class tkc_AccountFinance extends Basic
{
    public $new_schema = true;
    public $module_dir = 'tkc_AccountFinance';
    public $object_name = 'tkc_AccountFinance';
    public $table_name = 'tkc_accountfinance';
    public $importable = false;
    public $disable_row_level_security = true;

    public $id;
    public $name;
    public $account_id;
    public $account_name;
    public $rate_angels;
    public $rate_snor;
    public $rate_body;
    public $expected_revenue_current_fy;
    public $expected_revenue_next_fy;
    public $other_financial_terms;

    public function bean_implements($interface)
    {
        return $interface === 'ACL';
    }

    /**
     * Basic requires name. The module has no name field in its layouts: use the account name.
     */
    public function save($check_notify = false)
    {
        if (empty($this->name)) {
            $accountName = '';
            if (!empty($this->account_id)) {
                $account = BeanFactory::getBean('Accounts', $this->account_id);
                $accountName = $account ? (string)$account->name : '';
            }
            $this->name = $accountName !== '' ? $accountName : 'Finance';
        }

        return parent::save($check_notify);
    }
}
