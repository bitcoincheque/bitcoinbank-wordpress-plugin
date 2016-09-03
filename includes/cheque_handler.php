<?php
/**
 * Bitcoin Bank cheque handler library.
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

require_once('accounting.php');

class ChequeHandlerClass extends AccountingClass
{
    public function __construct()
    {
        parent::__construct();
    }

    public function IssueCheque(
        $bank_user_id_int,
        $account_id_int,
        $amount_int,
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
        $cheque =null;

        if(SanitizePositiveInteger($bank_user_id_int)
            and SanitizePositiveInteger($account_id_int)
            and SanitizePositiveInteger($amount_int)
            and SanitizePositiveInteger($expire_seconds_int)
            and SanitizePositiveInteger($escrow_seconds_int)
            and SanitizeTextAlpanumeric($reference_str)
            and SanitizeTextAlpanumeric($receiver_name_str)
            and SanitizeTextAlpanumeric($receiver_address_str)
            and SanitizeTextAlpanumeric($receiver_url_str)
            and SanitizeTextAlpanumeric($receiver_email_str)
            and SanitizeTextAlpanumeric($business_no_str)
            and SanitizeTextAlpanumeric($reg_country_str)
            and SanitizeTextAlpanumeric($lock_address_str)
            and SanitizeTextAlpanumeric($memo_str)
        )
        {
            $bank_user_id = new UserIdTypeClass($bank_user_id_int);
            if(SanitizeBankUserId($bank_user_id))
            {
                $account_id = new AccountIdTypeClass($account_id_int);
                if(SanitizeAccountId($account_id))
                {
                    if($this->IsBankUserAccountOwner($bank_user_id, $account_id))
                    {
                        $amount                  = new ValueTypeClass($amount_int);
                        $issue_datetime          = $this->DB_GetCurrentTimeStamp();
                        $cheque_account_id       = $this->GetChequeEscrollAccount();
                        $debit_transaction_type  = new TransactionDirTypeClass('ADD');
                        $credit_transaction_type = new TransactionDirTypeClass('CHEQUE');

                        $transaction_id = $this->MakeTransaction($account_id, $cheque_account_id, $issue_datetime, $amount, $credit_transaction_type, $debit_transaction_type);

                        if(SanitizeTransactionId($transaction_id))
                        {
                            $expire_datetime  = new DateTimeTypeClass($issue_datetime->GetSeconds() + $expire_seconds_int);
                            $escrow_datetime  = new DateTimeTypeClass($issue_datetime->GetSeconds() + $escrow_seconds_int);
                            $reference        = new TextTypeClass($reference_str);
                            $receiver_name    = new NameTypeClass($receiver_name_str);
                            $receiver_address = new TextTypeClass($receiver_address_str);
                            $receiver_url     = new TextTypeClass($receiver_url_str);
                            $receiver_email   = new TextTypeClass($receiver_email_str);
                            $business_no      = new TextTypeClass($business_no_str);
                            $reg_country      = new TextTypeClass($reg_country_str);
                            $lock_address     = new TextTypeClass($lock_address_str);
                            $memo             = new TextTypeClass($memo_str);
                            $user_name        = new NameTypeClass($username);

                            $cheque = $this->CreateCheque($account_id, $issue_datetime, $expire_datetime, $escrow_datetime, $amount, $reference, $receiver_name, $receiver_address, $receiver_url, $receiver_email, $business_no, $reg_country, $lock_address, $memo, $user_name);

                            if(SanitizeCheque($cheque))
                            {
                                /* TODO Failed to create cheque, maybe need to clean up transaction */
                            }
                        }
                    }
                }
            }
        }

        return $cheque;
    }
    
    public function ValidateCheque($cheque_id_int, $access_code_str, $hash_str)
    {
        $result = 'ERRORS';

        $cheque_id = new ChequeIdTypeClass($cheque_id_int);
        $access_code = new TextTypeClass($access_code_str);
        $hash = new TextTypeClass($hash_str);

        if (SanitizeChequeId($cheque_id))
        {
            if(SanitizeText($access_code))
            {
                if(SanitizeText($hash))
                {
                    $my_cheque      = $this->DB_GetChequeData($cheque_id);
                    if(!is_null($my_cheque))
                    {
                        $my_access_code = $my_cheque->GetAccessCode();
                        $my_hash        = $my_cheque->GetHash();

                        if(($access_code->GetString() == $my_access_code->GetString()) and ($hash->GetString() == $my_hash))
                        {
                            $result = 'OK';
                        }
                        else
                        {
                            $result = 'Ivanlid access';
                        }
                    }
                    else
                    {
                        $result = 'Invalid cheque serial no.';
                    }
                }
            }
        }

        return $result;
    }

    public function GetBankUserIdOfWpUser($wp_user_id_int)
    {
        $bank_user_id_int = -1;

        if(SanitizePositiveInteger($wp_user_id_int))
        {
            $wp_user_id = new WpUserIdTypeClass($wp_user_id_int);
            if(!is_null($wp_user_id))
            {
                $bank_user_data = $this->GetBankUserDataFromWpUser($wp_user_id);
                $bank_user_id = $bank_user_data->GetBankUserId();
                $bank_user_id_int = $bank_user_id->GetInt();
            }
        }

        return $bank_user_id_int;
    }

    public function IsWpUserAccountOwner($wp_user_id, $account_id)
    {
        $result = false;

        if(SanitizeWpUserId($wp_user_id) and SanitizeAccountId($account_id))
        {
            $bank_user_data = $this->GetBankUserDataFromWpUser($wp_user_id);
            if(!empty($bank_user_data))
            {
                $bank_user_id = $bank_user_data->GetBankUserId();

                $result = $this->IsBankUserAccountOwner($bank_user_id, $account_id);
            }
        }

        return $result;
    }

    public function GetAccountInfoListFromWpUser($wp_user_id)
    {
        $account_info_list = array();

        $wp_id = new WpUserIdTypeClass($wp_user_id);

        $bank_user_data = $this->GetBankUserDataFromWpUser($wp_id);
        if(!empty($bank_user_data))
        {
            $bank_user_id = $bank_user_data->GetBankUserId();
            $account_info_list = $this->GetAccountInfoList($bank_user_id);
        }

        return $account_info_list;
    }

    public function GetUsersAccountBalance($account_id)
    {
        $balance = null;

        if(SanitizeAccountId($account_id))
        {
            $balance =  $this->GetAccountBalance($account_id);
        }

        return $balance;
    }

    public function GetBankUserDataFromWpUser($wp_user_id)
    {
        $bank_user_data = NULL;

        if(SanitizeWpUserId($wp_user_id))
        {
            $bank_user_data = parent::GetBankUserDataFromWpUser($wp_user_id);
        }

        return $bank_user_data;
    }

    public function GetTransactionListForCurrentUser($wp_user_id, $account_id)
    {
        $transaction_records_list = array();

        if(SanitizeWpUserId($wp_user_id) and SanitizeAccountId($account_id))
        {
            $bank_user_data = $this->GetBankUserDataFromWpUser($wp_user_id);
            if(!empty($bank_user_data))
            {
                $bank_user_id = $bank_user_data->GetBankUserId();

                if($this->IsBankUserAccountOwner($bank_user_id, $account_id))
                {
                    $transaction_records_list = $this->GetTransactionList($account_id);
                }
            }
        }

        return $transaction_records_list;
    }

}

