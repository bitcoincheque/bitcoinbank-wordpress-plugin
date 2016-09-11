<?php
/**
 * Bank App communication handler
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

/* Description
 * ===========
 * This class provides bank functionality for the Banking App interface. It serves as a intermediate level between the
 * high-level Banking App interface and lower-level bank account functions.
 *
 * All functions in Banking App inerface that need access to the bank account, must work its way through this handler.
 *
 * The handler will ensure that only the bank users are only allowed to access their own accounts.
 */


namespace BCF_BitcoinBank;


require_once('accounting.php');


class BankingAppHandler extends AccountingClass
{
    protected $bank_user_id_obj = null;

    public function __construct($bank_user_id = null)
    {
        parent::__construct();

        if(!is_null($bank_user_id))
        {
            $this->bank_user_id_obj = $bank_user_id;
        }
    }

    public function GetUserBankUserData()
    {
        $account_data_list = array();

        if(!is_null($this->bank_user_id_obj))
        {
            $account_data_list = $this->GetBankUserData($this->bank_user_id_obj);
        }

        return $account_data_list;
    }

    public function GetUserAccountDataList()
    {
        $account_data_list = array();

        if(!is_null($this->bank_user_id_obj))
        {
            $account_data_list = $this->GetAccounDataList($this->bank_user_id_obj);
        }

        return $account_data_list;
    }

    public function GetUserAccountData($account_id)
    {
        $account_data = null;

        if(!is_null($this->bank_user_id_obj))
        {
            $account_id_obj = new AccountIdTypeClass($account_id);

            if($this->IsBankUserAccountOwner($this->bank_user_id_obj, $account_id_obj))
            {
                $account_data = $this->GetAccountData($account_id_obj);
            }
        }

        return $account_data;
    }

    public function GetUsersAccountBalance($account_id)
    {
        $balance = null;

        if(!is_null($this->bank_user_id_obj))
        {
            $account_id_obj = new AccountIdTypeClass($account_id);

            if($this->IsBankUserAccountOwner($this->bank_user_id_obj, $account_id_obj))
            {
                $balance = $this->GetAccountBalance($account_id_obj);
            }
        }

        return $balance;
    }

    public function GetUserAccountTransactionList($account_id)
    {
        $transaction_records_list = array();

        if(!is_null($this->bank_user_id_obj))
        {
            $account_id_obj = new AccountIdTypeClass($account_id);

            if($this->IsBankUserAccountOwner($this->bank_user_id_obj, $account_id_obj))
            {
                $transaction_records_list = $this->GetTransactionList($account_id_obj);
            }
        }

        return $transaction_records_list;
    }


    public function IssueAccountCheque(
        $account_id_int,
        $amount_int,
        $currency_str,
        $expire_seconds_int,
        $escrow_seconds_int,
        $reference_str,
        $receiver_name_str,
        $receiver_address_str,
        $receiver_url_str,
        $receiver_email_str,
        $business_no_str,
        $reg_country_str,
        $lock_address_str,
        $memo_str,
        $username)
    {
        $cheque = null;

        if(!is_null($this->bank_user_id_obj))
        {
            $account_id_obj = new AccountIdTypeClass($account_id_int);

            if($this->IsBankUserAccountOwner($this->bank_user_id_obj, $account_id_obj))
            {
                if(SanitizePositiveInteger($account_id_int) and SanitizePositiveInteger($amount_int) and SanitizePositiveInteger($expire_seconds_int) and SanitizePositiveInteger($escrow_seconds_int) and SanitizeTextAlpanumeric($currency_str) and SanitizeTextAlpanumeric($reference_str) and SanitizeTextAlpanumeric($receiver_name_str) and SanitizeTextAlpanumeric($receiver_address_str) and SanitizeTextAlpanumeric($receiver_url_str) and SanitizeTextAlpanumeric($receiver_email_str) and SanitizeTextAlpanumeric($business_no_str) and SanitizeTextAlpanumeric($reg_country_str) and SanitizeTextAlpanumeric($lock_address_str) and SanitizeTextAlpanumeric($memo_str))
                {
                    $amount            = new ValueTypeClass($amount_int);
                    $currency          = new CurrencyTypeClass($currency_str);
                    $issue_datetime    = $this->DB_GetCurrentTimeStamp();
                    $expire_datetime   = new DateTimeTypeClass($issue_datetime->GetSeconds() + $expire_seconds_int);
                    $escrow_datetime   = new DateTimeTypeClass($issue_datetime->GetSeconds() + $escrow_seconds_int);
                    $reference         = new TextTypeClass($reference_str);
                    $receiver_name     = new NameTypeClass($receiver_name_str);
                    $receiver_address  = new TextTypeClass($receiver_address_str);
                    $receiver_url      = new TextTypeClass($receiver_url_str);
                    $receiver_email    = new TextTypeClass($receiver_email_str);
                    $business_no       = new TextTypeClass($business_no_str);
                    $reg_country       = new TextTypeClass($reg_country_str);
                    $lock_address      = new TextTypeClass($lock_address_str);
                    $memo              = new TextTypeClass($memo_str);
                    $user_name         = new NameTypeClass($username);

                    $cheque = $this->IssueCheque(
                        $account_id_obj,
                        $issue_datetime,
                        $expire_datetime,
                        $escrow_datetime,
                        $amount,
                        $currency,
                        $reference,
                        $receiver_name,
                        $receiver_address,
                        $receiver_url,
                        $receiver_email,
                        $business_no,
                        $reg_country,
                        $lock_address,
                        $memo,
                        $user_name);
                }
            }
        }

        return $cheque;
    }


}