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
        $issuer_account_id, 
        $account_password, 
        $amount, 
        $expire_seconds, 
        $escrow_seconds, 
        $reference,
        $receiver_name,
        $receiver_address,
        $receiver_url,
        $receiver_email,
        $business_no,
        $reg_country,
        $receiver_wallet,
        $description)
    {
        $cheque =null;

        if(SanitizeAccountId($issuer_account_id) 
            and SanitizePositiveInteger($expire_seconds) 
            and SanitizePositiveInteger($escrow_seconds)
            and SanitizeText($reference)
            and SanitizeName($receiver_name)
            and SanitizeText($receiver_address)
            and SanitizeText($receiver_url)
            and SanitizeText($receiver_email)
            and SanitizeText($business_no)
            and SanitizeText($reg_country)
            and SanitizeText($receiver_wallet)
            and SanitizeText($description)
        )
        {
            $account_data = $this->GetAccountData($issuer_account_id);
            if(!is_null($account_data))
            {
                if($account_data->CheckPassword($account_password))
                {
                    $issue_datetime = $this->DB_GetCurrentTimeStamp();
                    $cheque_account_id = $this->GetChequeEscrollAccount();
                    $debit_transaction_type = new TransactionDirTypeClass('ADD');
                    $credit_transaction_type = new TransactionDirTypeClass('CHEQUE');

                    $transaction_id = $this->MakeTransaction($issuer_account_id, $cheque_account_id, $issue_datetime, $amount, $credit_transaction_type, $debit_transaction_type);

                    if(!is_null($transaction_id))
                    {
                        $expire_datetime = new DateTimeTypeClass($issue_datetime->GetSeconds() + $expire_seconds);
                        $escrow_datetime = new DateTimeTypeClass($issue_datetime->GetSeconds() + $escrow_seconds);
                        
                        $cheque = $this->CreateCheque(
                            $issuer_account_id, 
                            $issue_datetime, 
                            $expire_datetime, 
                            $escrow_datetime, 
                            $amount, 
                            $reference, 
                            $receiver_name,
                            $receiver_address,
                            $receiver_url,
                            $receiver_email,
                            $business_no,
                            $reg_country,
                            $receiver_wallet,
                            $description);
                        
                        if(is_null($cheque))
                        {
                            /* TODO Failed to create cheque, maybe need to clean up transaction */
                        }
                    }
                }
            }
        }

        return $cheque;
    }
    
    public function ValidateCheque($cheque_data_array, $claim)
    {
        $result = 'Undefined error.';

        $validate_cheque = new ChequeDataClass();
        if($validate_cheque->SetDataFromDbRecord($cheque_data_array))
        {
            $validate_cheque_id = $validate_cheque->GetChequeId();

            $my_cheque = $this->DB_GetChequeData($validate_cheque_id);
            if(!empty($my_cheque))
            {
                $result = $my_cheque->CompareCheque($cheque_data_array);
                if($result == 'OK' and $claim)
                {
                    $this->ChangeChequeState($my_cheque, 'CHEQUE_EVENT_CLAIM');
                }
            }
            else
            {
                error_log('ValidateCheque bug');
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

