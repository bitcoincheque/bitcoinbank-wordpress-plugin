<?php
/**
 * Bitcoin Bank accounting system library.
 * Original written to demonstrate the usage of Bitcoin Cheques.
 *
 * Copyright (C) 2016 Arild Hegvik and Bitcoin Cheque Foundation.
 *
 * GNU LESSER GENERAL PUBLIC LICENSE (GNU LGPLv3)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FO PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace BCF_BitcoinBank;

require_once('db_interface.php');
require_once ('util.php');
require_once('data_types.php');

define ('CHEQUE_EVENT_EXPIRED', 'CHEQUE_EVENT_EXPIRED');
define ('CHEQUE_EVENT_CLAIM',   'CHEQUE_EVENT_CLAIM');
define ('CHEQUE_EVENT_HOLD',    'CHEQUE_EVENT_HOLD');
define ('CHEQUE_EVENT_RELEASE', 'CHEQUE_EVENT_RELEASE');

define ('BCF_BITCOINBANK_ADMIN_USER_ID',            'bcf_bitcoinbank_admin_user_id');
define ('BCF_BITCOINBANK_CHEQUE_ESCROW_ACCOUNT_ID', 'bcf_bitcoinbank_cheque_escrow_account_id');



class AccountingClass extends DatabaseInterfaceClass
{
    protected function __construct()
    {
        parent::__construct();

        $this->ReimburseAllExpiredCheques();
    }

    public function CreateAdminBankUser()
    {
        $admin_bank_user_id_str = get_option( BCF_BITCOINBANK_ADMIN_USER_ID );
        if (!$admin_bank_user_id_str)
        {
            $admin_bank_user = $this->CheckAndCreateAdminBankUser();
            if(!is_null($admin_bank_user))
            {
                $admin_bank_user_id = $admin_bank_user->GetBankUserId();
                $admin_bank_user_id_str = $admin_bank_user_id->GetString();
                add_option(BCF_BITCOINBANK_ADMIN_USER_ID, $admin_bank_user_id_str);
            }
        }

        $cheque_account_id_str = get_option( BCF_BITCOINBANK_CHEQUE_ESCROW_ACCOUNT_ID );
        if (!$cheque_account_id_str)
        {
            $admin_bank_user_id_str = get_option( BCF_BITCOINBANK_ADMIN_USER_ID );
            $admin_bank_user_id_val = intval($admin_bank_user_id_str);
            $admin_bank_user_id = new UserIdTypeClass($admin_bank_user_id_val);

            $account = $this->CheckAndCreateChequeEscrowAccount($admin_bank_user_id);
            if(!is_null($account))
            {
                $account_id     = $account->GetAccountId();
                $account_is_str = $account_id->GetString();
                add_option(BCF_BITCOINBANK_CHEQUE_ESCROW_ACCOUNT_ID, $account_is_str);
            }
        }
    }

    protected function GetBankUserDataFromWpUser($wp_user_id)
    {
        $bank_user_data = NULL;

        if(SanitizeWpUserId($wp_user_id))
        {
            $bank_user_data = $this->DB_GetBankUserDataFromWpUser($wp_user_id);
        }

        return $bank_user_data;
    }

    protected function CreateBankUser($wp_user_id, $name)
    {
        $bank_user = null;
        if(SanitizeWpUserId($wp_user_id) and SanitizeText($name))
        {
            $bank_user = new UserDataClass();
            $bank_user->SetWpUserId($wp_user_id);
            $bank_user->SetName($name);

            $bank_user_id_value = $this->DB_WriteRecord($bank_user);

            if($bank_user_id_value > 0)
            {
                $bank_user_id = new UserIdTypeClass($bank_user_id_value);
                if(!$bank_user->SetBankUserId($bank_user_id))
                {
                    $bank_user = NULL;
                }
            }
            else
            {
                $bank_user = NULL;
            }
        }
        return $bank_user;
    }

    protected function CreateBankAccount($bank_user_id, $password)
    {
        if(SanitizeBankUserId($bank_user_id)
            and SanitizePassword($password))
        {
            $account = new AccountDataClass;
            $account->SetAccountOwner($bank_user_id);
            $account->SetPassword($password);

            $account_id_value = $this->DB_WriteRecord($account);

            if($account_id_value > 0)
            {
                $account_id = new AccountIdTypeClass($account_id_value);
                if($account->SetAccountId($account_id))
                {
                    $transaction = new TransactionDataClass();
                    $transaction->SetAccountId($account_id);
                    $transaction->SetDateTime($this->DB_GetCurrentTimeStamp());
                    $transaction_type = new TransactionDirTypeClass("INITIAL");
                    $transaction->SetTransactionType($transaction_type);

                    $transaction_id_value = $this->DB_WriteRecord($transaction);

                    if($transaction_id_value > 0)
                    {
                        return $account;                    }
                }
            }
        }
        return NULL;
    }

    protected function GetTransactionList($account_id)
    {
        $transaction_list = array();
        if(SanitizeAccountId($account_id))
        {
            $transaction_list = $this->DB_GetTransactionList($account_id);
        }
        return $transaction_list;
    }




    protected function GetBankUserIdFromWpUser($wp_user_id)
    {
        $bank_user_id = NULL;

        if(SanitizeWpUserId($wp_user_id))
        {
            $bank_user_data = $this->DB_GetBankUserDataFromWpUser($wp_user_id);

            if($bank_user_data != null)
            {
                $bank_user_id = $bank_user_data->GetBankUserId();
            }
        }

        return $bank_user_id;
    }
    
    


    protected function GetAccountData($account_id)
    {
        $account_info = NULL;

        if(SanitizeAccountId($account_id))
        {
            $account_info = $this->DB_GetAccountData($account_id);
        }

        return $account_info;
    }

    public function GetAccountInfoList($bank_user_id)
    {
        $account_info_list = array();

        if(SanitizeBankUserId($bank_user_id))
        {
            $account_info_list = $this->DB_GetAccountDataList($bank_user_id);
        }

        return $account_info_list;
    }
    
    protected function GetAccountOwner($account_id)
    {
        $bank_user_id = null;

        if(SanitizeAccountId($account_id))
        {
            $account_data = $this->GetAccountData($account_id);
            if (!empty($account_data))
            {
                $bank_user_id = $account_data->GetOwnersUserId();
            }
        }
        
        return $bank_user_id;
    }

    protected function GetAccountBalance($account_id)
    {
        $balance = -1;

        if(SanitizeAccountId($account_id))
        {
            $balance = $this->DB_GetBalance($account_id);
        }

        return $balance;
    }

    private function WithdrawTransaction($account_id, $datetime, $amount, $transaction_type)
    {
        $transaction_id = 0;

        if(SanitizeAccountId($account_id)
            and SanitizeDateTime($datetime)
            and SanitizeAmount($amount)
            and SanitizeTransactionType($transaction_type))
        {
            if(!empty($this->GetAccountOwner($account_id)))
            {
                if ($transaction_type->IsCreditType())
                {
                    $amount_value = $amount->GetInt();
                    if ($amount_value > 0)
                    {
                        // TODO This must be atomic operation
                        $balance = $this->DB_GetBalance($account_id);
                        $balance_value = $balance->GetInt();

                        //if ($balance_value >= $amount_value)
                        //{
                            $balance_value = $balance_value - $amount_value;
                            $amount_value = 0 - $amount_value;

                            $new_balance = new ValueTypeClass($balance_value);
                            $new_amount = new ValueTypeClass($amount_value);

                            $transaction = new TransactionDataClass();
                            $transaction->SetAccountId($account_id);
                            $transaction->SetDateTime($datetime);
                            $transaction->SetTransactionType($transaction_type);
                            $transaction->SetTransactionAmount($new_amount);
                            $transaction->SetTransactionBalance($new_balance);

                            $transaction_id_val = $this->DB_WriteRecord($transaction);

                            $transaction_id = new TransactionIdTypeClass($transaction_id_val);
                        //}
                    }
                }
            }
        }

        return $transaction_id;
    }

    private function AddTransaction($account_id, $datetime, $amount, $transaction_type)
    {
        $transaction_id = 0;

        if(SanitizeAccountId($account_id)
            and SanitizeDateTime($datetime)
            and SanitizeAmount($amount)
            and SanitizeTransactionType($transaction_type))
        {
            if ($transaction_type->IsDebitType())
            {
                if(!empty($this->GetAccountOwner($account_id)))
                {
                    $amount_value = $amount->GetInt();

                    if ($amount_value > 0)
                    {
                        // TODO This must be atomic operation
                        $balance = $this->DB_GetBalance($account_id);
                        $balance_value = $balance->GetInt();

                        $balance_value = $balance_value + $amount_value;

                        $new_balance = new ValueTypeClass($balance_value);
                        $new_amount = new ValueTypeClass($amount_value);

                        $transaction = new TransactionDataClass();
                        $transaction->SetAccountId($account_id);
                        $transaction->SetDateTime($datetime);
                        $transaction->SetTransactionType($transaction_type);
                        $transaction->SetTransactionAmount($new_amount);
                        $transaction->SetTransactionBalance($new_balance);

                        $transaction_id_val = $this->DB_WriteRecord($transaction);

                        $transaction_id = new TransactionIdTypeClass($transaction_id_val);
                    }
                }
            }
        }
        
        return $transaction_id;
    }

    protected function MakeTransaction($account_id_from, $account_id_to, $datetime, $amount, $debit_type, $credit_type)
    {
        $transaction_id = null;

        if(SanitizeAccountId($account_id_from)
            and SanitizeAccountId($account_id_to)
            and SanitizeDateTime($datetime)
            and SanitizeAmount($amount)
            and SanitizeTransactionType($debit_type)
            and SanitizeTransactionType($credit_type))
        {
            $transaction_id = $this->WithdrawTransaction($account_id_from, $datetime, $amount, $debit_type);
            if(!is_null($transaction_id))
            {
                $transaction_id = $this->AddTransaction($account_id_to, $datetime, $amount, $credit_type);
            }
        }

        return $transaction_id;
    }

    private  function CheckAndCreateAdminBankUser()
    {
        /* The Bank admin has Wordpress user id 0 */
        $wp_user = new WpUserIdTypeClass(0);
        $admin_bank_user = $this->GetBankUserDataFromWpUser($wp_user);

        if (is_null($admin_bank_user))
        {
            /* No bank user for Wordpress user 0, create one */
            $name = new TextTypeClass('Admin');
            $admin_bank_user = $this->CreateBankUser($wp_user, $name);
            if (is_null($admin_bank_user))
            {
                wp_die();
            }
        }

        return $admin_bank_user;
    }

    private  function CheckAndCreateChequeEscrowAccount($bank_user_id)
    {
        $cheque_account_id = null;

        if(SanitizeBankUserId($bank_user_id))
        {
            $cheque_account_list = $this->GetAccountInfoList($bank_user_id);
            $n = count( $cheque_account_list );
            if ( $n > 0 )
            {
                // Admin user has accounts, use the first one for cheque escrow
                $cheque_account_id = $cheque_account_list[0];

            }
            else
            {
                // Admin user has noe accoutns, create one
                $password = new PasswordTypeClass('');
                $cheque_account_id = $this->CreateBankAccount($bank_user_id, $password);
                if (empty($cheque_account_id))
                {
                    wp_die();
                }
            }
        }

        return $cheque_account_id;
    }


    protected function GetChequeEscrollAccount()
    {
        $cheque_account_id = NULL;

        $cheque_account_id_str = get_option(BCF_BITCOINBANK_CHEQUE_ESCROW_ACCOUNT_ID);
        if($cheque_account_id_str != "")
        {
            $cheque_account_id = new AccountIdTypeClass(intval($cheque_account_id_str));
        }

        return $cheque_account_id;
    }


    protected function CreateCheque($issuer_account_id, $issue_datetime, $expire_datetime, $escrow_datetime, $amount, $reference)
    {
        $cheque = null;

        if(SanitizeAccountId($issuer_account_id)
            and SanitizeDateTime($issue_datetime)
            and SanitizeDateTime($expire_datetime)
            and SanitizeDateTime($escrow_datetime)
            and SanitizeAmount($amount)
            and SanitizeText($reference))
        {
            if ($amount->GetInt() > 0)
            {
                $r1 = rand(1, PHP_INT_MAX - 1);
                $r2 = rand(1, PHP_INT_MAX - 1);
                $r = $r1 / $r2;
                $str = strval($r);
                $secret_token_str = str_replace('.', '', $str);
                $secret_token = new TextTypeClass($secret_token_str);

                $state = new ChequeStateTypeClass('UNCLAIMED');
                
                $cheque = new ChequeDataClass();
                $cheque->SetChequeState($state);
                $cheque->SetIssueDateTime($issue_datetime);
                $cheque->SetExpireDateTime($expire_datetime);
                $cheque->SetEscrowDateTime($escrow_datetime);
                $cheque->SetValue($amount);
                $cheque->SetReceiverReference($reference);
                $cheque->SetNounce($secret_token);
                $cheque->SetOwnerAccountId($issuer_account_id);

                $collect_url_str = site_url() . '/wp-admin/admin-ajax.php?action=bcf_bitcoinbank_process_ajax_validate_cheque';
                $collect_url = new TextTypeClass($collect_url_str);
                $cheque->SetCollectUrl($collect_url);

                $cheque_id_value = $this->DB_WriteRecord($cheque);

                if($cheque_id_value > 0)
                {
                    /* Cheque created, add cheque_id */
                    $cheque_id = new ChequeIdTypeClass($cheque_id_value);
                    $cheque->SetChequeId($cheque_id);
                }
                else
                {
                    /* Failed to create cheque, return null */
                    $cheque = null;
                }
            }
        }

        return $cheque;
    }

    /*
    protected function GetChequeRecord($cheque_id)
    {
        $cheque_record = array();

        if(SanitizeChequeId($cheque_id))
        {
            $record_list = $this->DB_GetChequeData($cheque_id);

            if (count($record_list) == 1)
            {
                $cheque_record = $record_list[0];
            }
        }

        return $cheque_record;
    }
    */

    protected function GetCheque($cheque_id)
    {
        if(SanitizeChequeId($cheque_id))
        {
            $cheque_record = $this->GetChequeRecord($cheque_id);
            if(!empty($cheque_record))
            {
                $cheque = new BitCoinChequeClass();
                if ($cheque->SetDataFromArray($cheque_record))
                {
                    return $cheque;
                }
            }
        }
    }

    protected function GetChequeList($issuer_account_id)
    {
        $cheque_list = array();
        if(SanitizeAccountId($issuer_account_id))
        {
            $cheque_list = $this->DB_GetChequeList($issuer_account_id);
        }
        return $cheque_list;
    }

    protected function ReimburseAllExpiredCheques()
    {
        $result = true;
        $current_time = $this->DB_GetCurrentTimeStamp();
        $cheque_account_id = $this->GetChequeEscrollAccount();

        $cheque_state = new ChequeStateTypeClass('UNCLAIMED');

        $cheque_list = $this->DB_GetChequeDataListByState($cheque_state);

        foreach($cheque_list as $cheque)
        {
            if($cheque->HasExpired($current_time))
            {
                $issuer_account_id = $cheque->GetOwnerAccountId();
                $amount = $cheque->GetValue();

                $transaction_type_withdraw = new TransactionDirTypeClass('WITHDRAW');
                $transaction_type_add = new TransactionDirTypeClass('REIMBURSEMENT');

                // TODO This must be atomic operation
                $transaction_id = $this->MakeTransaction($cheque_account_id, $issuer_account_id, $current_time, $amount, $transaction_type_withdraw, $transaction_type_add);

                if(!is_null($transaction_id))
                {
                    $result = $this->ChangeChequeState($cheque, CHEQUE_EVENT_EXPIRED);
                    if ($result == false)
                    {
                        die();
                    }
                }
                // TODO End of atomic operation
            }
        }

        return $result;
    }

    protected function ChangeChequeState($cheque, $event)
    {
        $result = false;
        
        if(SanitizeCheque($cheque))
        {
            $old_state = $cheque->GetChequeState();

            $new_state = $this->ChequeStateMachine($old_state, $event);
            if(!is_null($new_state))
            {
                $cheque->SetChequeState($new_state);

                if($this->DB_UpdateRecord($cheque))
                {
                    $result = true;
                }
            }

        }
        return $result;
    }
            
    private function ChequeStateMachine($old_state, $event)
    {
        $cheque_new_state = null;

        if(SanitizeChequeState($old_state))
        {
            $old_state_str = $old_state->GetString();
            $new_state     = 'BUG';

            if($old_state_str == 'UNCLAIMED')
            {
                if($event == CHEQUE_EVENT_EXPIRED)
                {
                    $new_state = 'EXPIRED';
                }
                else if($event == CHEQUE_EVENT_CLAIM)
                {
                    $new_state = 'CLAIMED';
                }
                else if($event == CHEQUE_EVENT_HOLD)
                {
                    $new_state = '';
                }
                else if($event == CHEQUE_EVENT_RELEASE)
                {
                    $new_state = '';
                }
            }
            //***********************************************************************
            elseif($old_state_str == 'CLAIMED')
            {
                if($event == CHEQUE_EVENT_EXPIRED)
                {
                    $new_state = '';
                }
                else if($event == CHEQUE_EVENT_CLAIM)
                {
                    $new_state = '';
                }
                else if($event == CHEQUE_EVENT_HOLD)
                {
                    $new_state = '';
                }
                else if($event == CHEQUE_EVENT_RELEASE)
                {
                    $new_state = '';
                }
            }
            //***********************************************************************
            elseif($old_state_str == 'EXPIRED')
            {
                if($event == CHEQUE_EVENT_EXPIRED)
                {
                    $new_state = '';
                }
                else if($event == CHEQUE_EVENT_CLAIM)
                {
                    $new_state = '';
                }
                else if($event == CHEQUE_EVENT_HOLD)
                {
                    $new_state = '';
                }
                else if($event == CHEQUE_EVENT_RELEASE)
                {
                    $new_state = '';
                }
            }

            if($new_state == 'BUG')
            {
                error_log('ChequeStateMachine Error: invalid state/event' . $old_state_str . '/' . $event);
            }

            $cheque_new_state = new ChequeStateTypeClass($new_state);
        }

        return $cheque_new_state;
    }

}

function FormatedLongCurrency($value, $fractional_length, $decimal_mark)
{
    if($value >= 0) {
        $formatter = '%1$0' . intval($fractional_length + 1) . 'd';
    }
    else {
        $formatter = '%1$0' . intval( $fractional_length + 2 ) . 'd';
    }
    $str = sprintf($formatter, $value);
    $str = strrev($str);
    $right_part = substr($str, 0, $fractional_length);
    $left_part = substr($str, $fractional_length);
    $str = $right_part . $decimal_mark . $left_part;
    $str = strrev($str);
    return $str;
}

function GetFormattedCurrency($value, $currency, $include_currency_text=false, $decimal_mark=',')
{
    if(SanitizeInteger($value))
    {
        if($currency == 'BTC' )
        {
            $str = FormatedLongCurrency($value, 8, $decimal_mark);
            if($include_currency_text)
            {
                $str .= ' BTC';
            }
        }
        elseif($currency == 'mBTC' )
        {
            $str = FormatedLongCurrency($value, 5, $decimal_mark);
            if($include_currency_text)
            {
                $str .= ' mBTC';
            }
        }
        elseif($currency == 'uBTC' )
        {
            $str = FormatedLongCurrency($value, 2, $decimal_mark);
            if($include_currency_text)
            {
                $str .= ' uBTC';
            }
        }
        else
        {
            $str = 'Error: Unknown currency';
        }
    }
    else
    {
        $str = 'Error in numbers.';
    }
    return $str;
}
