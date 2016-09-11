<?php
/**
 * Bitcoin Bank user data class library.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace BCF_BitcoinBank;

use BitWasp\Bitcoin\Amount;

require_once('accounting.php');
require_once('data_types.php');

class UserHandlerClass extends AccountingClass
{
    protected $bank_user_id_obj = null;

    public function __construct()
    {
        parent::__construct();

        $this->CheckBankUserExistAndCreateNewBankUser();


        $current_user = wp_get_current_user();

        $wp_user_obj = new WpUserIdTypeClass($current_user->ID);

        $this->bank_user_id_obj = $this->GetBankUserIdFromWpUser($wp_user_obj);

        if(!is_null($this->bank_user_id_obj))
        {
            $this->user_logged_in = true;
        }
    }

    private function CheckBankUserExistAndCreateNewBankUser()
    {
        $result = false;

        if(is_user_logged_in())
        {
            $current_user = wp_get_current_user();
            $wp_user_obj = new WpUserIdTypeClass($current_user->ID);

            /* First, check if Wordpress user has a bank user id */
            $bank_user_data = $this->GetBankUserDataFromWpUser($wp_user_obj);
            if(is_null($bank_user_data))
            {
                /* WP User has no bank user, create one */
                $wp_user_id = new WpUserIdTypeClass($current_user->ID);

                $name_str   = $current_user->first_name . ' ' . $current_user->last_name;
                $name       = new TextTypeClass($name_str);

                $bank_user_data = $this->CreateBankUser($wp_user_id, $name);
            }

            if(!is_null($bank_user_data))
            {
                $bank_user_id = $bank_user_data->GetBankUserId();
                if(!is_null($bank_user_id))
                {
                    /* Second, create bank account if bank user have non */
                    $account_info_list = $this->GetAccounDataList($bank_user_id);
                    if(count($account_info_list) == 0)
                    {
                        $password = new PasswordTypeClass("");

                        $account = $this->CreateBankAccount($bank_user_id, $password);
                        if( ! is_null($account))
                        {
                            $result = true;
                        }
                    }
                }
            }
            else
            {
                /* Unable to load or create bank user */
            }
        }

        return $result;
    }

    private function BankUserIsLoggedIn()
    {
        if(is_user_logged_in() and (!is_null($this->bank_user_id_obj)))
        {
            return true;
        }

        return false;
    }

    public function GetCurrentUserAccountData($account_id)
    {
        $account_data = NULL;

        $from_account_id_obj = new AccountIdTypeClass($account_id);
        if(SanitizeAccountId($from_account_id_obj))
        {
            $account_data = $this->GetAccountData($from_account_id_obj);
        }

        return $account_data;
    }

     public function GetCurrentUserAccountInfoList()
    {
        $account_info_list = array();

        if($this->BankUserIsLoggedIn())
        {
            $account_info_list = $this->GetAccounDataList($this->bank_user_id_obj);
        }

        return $account_info_list;
    }

    public function GetCurrentUserAccountBalance($account_id)
    {
        $balance = null;

        if($this->BankUserIsLoggedIn())
        {
            $from_account_id_obj = new AccountIdTypeClass($account_id);
            if(SanitizeAccountId($from_account_id_obj))
            {
                $balance = $this->GetAccountBalance($from_account_id_obj);
            }
        }
        
        return $balance;
    }

    public function GetCurrentUserTransactionList($account_id)
    {
        $transaction_records_list = array();

        if($this->BankUserIsLoggedIn())
        {
            $account_id_obj = new AccountIdTypeClass($account_id);

            if(SanitizeAccountId( $account_id_obj ))
            {
                if ( $this->IsBankUserAccountOwner($this->bank_user_id_obj, $account_id_obj) )
                {
                    $transaction_records_list = $this->GetTransactionList( $account_id_obj );
                }
            }
        }

        return $transaction_records_list;
    }

    function MakeCurrentUserAccountTransaction($account_id_from, $account_id_to, $amount, $currency)
    {
        $transaction_id = null;

        if($this->BankUserIsLoggedIn())
        {
            $account_id_from_obj = new AccountIdTypeClass($account_id_from);
            $account_id_to_obj   = new AccountIdTypeClass($account_id_to);
            $amount_obj      = new ValueTypeClass($amount);
            $currency_obj      = new CurrencyTypeClass($currency);

            if(SanitizeAccountId($account_id_from_obj) and SanitizeAccountId($account_id_to_obj) and SanitizeAmount($amount_obj) and SanitizeCurrency($currency_obj))
            {
                $to_account_owner = $this->GetAccountOwner($account_id_to_obj);
                if( ! is_null($to_account_owner))
                {
                    if($to_account_owner->HasValidData())
                    {
                        if($this->IsBankUserAccountOwner($this->bank_user_id_obj, $account_id_from_obj))
                        {
                            $timestamp                 = $this->DB_GetCurrentTimeStamp();
                            $transaction_type_withdraw = new TransactionDirTypeClass('WITHDRAW');
                            $transaction_type_add      = new TransactionDirTypeClass('ADD');

                            $transaction_id            = $this->MakeTransaction(
                                $account_id_from_obj,
                                $account_id_to_obj,
                                $timestamp,
                                $amount_obj,
                                $currency_obj,
                                $transaction_type_withdraw,
                                $transaction_type_add);
                        }
                    }
                }
            }
        }

        return $transaction_id;
    }

    public function MakeCurrentUserIssueCheque(
        $account_id,
        $amount,
        $expire_seconds,
        $escrow_seconds,
        $receiver_name,
        $lock_address,
        $reference,
        $memo)
    {
        $cheque =null;

        if($this->BankUserIsLoggedIn())
        {
            $account_id_obj = new AccountIdTypeClass($account_id);
            $amount_obj   = new ValueTypeClass($amount);

            if($this->IsBankUserAccountOwner($this->bank_user_id_obj, $account_id_obj))
            {

                $bank_user_data = $this->DB_GetBankUserData($this->bank_user_id_obj);
                $user_name_obj = $bank_user_data->GetName();

                $account_data_obj = $this->GetAccountData($account_id_obj);
                if(!is_null($account_data_obj))
                {
                    $currency_obj = $account_data_obj->GetCurrency();

                    $issue_datetime_obj = $this->DB_GetCurrentTimeStamp();
                    $expire_datetime_obj = new DateTimeTypeClass($issue_datetime_obj->GetSeconds() + $expire_seconds);
                    $escrow_datetime_obj = new DateTimeTypeClass($issue_datetime_obj->GetSeconds() + $escrow_seconds);

                    $receiver_name_obj   = new NameTypeClass($receiver_name);
                    $lock_address_obj   = new TextTypeClass($lock_address);
                    $reference_obj   = new TextTypeClass($reference);
                    $memo_obj   = new TextTypeClass($memo);


                    $cheque = $this->IssueCheque(
                        $account_id_obj,
                        $issue_datetime_obj,
                        $expire_datetime_obj,
                        $escrow_datetime_obj,
                        $amount_obj,
                        $currency_obj,
                        $reference_obj,
                        $receiver_name_obj,
                        null,
                        null,
                        null,
                        null,
                        null,
                        $lock_address_obj,
                        $memo_obj,
                        $user_name_obj);
                }
            }
        }

        return $cheque;
    }

    public function GetUserCheque($cheque_id, $access_code)
    {
        $cheque = null;

        $cheque_id_obj   = new ChequeIdTypeClass($cheque_id);
        $access_code_obj = new TextTypeClass($access_code);

        if(SanitizeChequeId($cheque_id_obj) and SanitizeText($access_code_obj))
        {
            $cheque = $this->GetCheque($cheque_id_obj, $access_code_obj);
        }

        return $cheque;
    }

    public function GetCurrentUserChequeList($account_id)
    {
        $cheque_records_list = array();

        if($this->BankUserIsLoggedIn())
        {
            $account_id_obj = new AccountIdTypeClass($account_id);

            if(SanitizeAccountId($account_id_obj))
            {
                if($this->IsBankUserAccountOwner($this->bank_user_id_obj, $account_id_obj))
                {
                    $cheque_records_list = $this->GetChequeList($account_id_obj);
                }
            }
        }

        return $cheque_records_list;
    }


    public function ClaimUserCheque($cheque_id, $access_code)
    {
        $result = false;

        $cheque_id_obj   = new ChequeIdTypeClass($cheque_id);
        $access_code_obj = new TextTypeClass($access_code);

        if(SanitizeChequeId($cheque_id_obj) and SanitizeText($access_code_obj))
        {
            $result = $this->ClaimCheque($cheque_id_obj, $access_code_obj);
        }

        return $result;
    }

    public function SetCurrentUserData($name_str, $country_str)
    {
        $result = false;

        if($this->BankUserIsLoggedIn())
        {
            $name = new NameTypeClass($name_str);
            $country = new TextTypeClass($country_str);
            
            $current_user = wp_get_current_user();
            $wp_user_id = new WpUserIdTypeClass($current_user->ID);
            $bank_user_id = $this->GetBankUserIdFromWpUser($wp_user_id);
            
            $result = $this->SetBankUserData($bank_user_id, $name, $country);
        }

        return $result;
    }

    public function GetCurrentUserData()
    {
        $bank_user_data = null;

        if($this->BankUserIsLoggedIn())
        {
            $current_user = wp_get_current_user();
            $wp_user = new WpUserIdTypeClass($current_user->ID);
            $bank_user_data = $this->GetBankUserDataFromWpUser($wp_user);
        }

        return $bank_user_data;
    }
}