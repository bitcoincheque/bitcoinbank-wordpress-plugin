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

require_once('accounting.php');
require_once('data_types.php');

class UserHandlerClass extends AccountingClass
{
    public function __construct()
    {
        parent::__construct();

        $this->CheckBankUserExistAndCreateNewBankUser();        
    }

    public function IsCurrentUserAccountOwner($account_id)
    {
        $result = false;

        if(SanitizeAccountId($account_id))
        {
            $result = false;
            if (is_user_logged_in())
            {
                $account_owner_id = $this->GetAccountOwner($account_id);

                if($account_owner_id != null)
                {
                    $current_user = wp_get_current_user();
                    $wp_id = new WpUserIdTypeClass($current_user->ID);
                    $bank_user_id = $this->GetBankUserIdFromWpUser($wp_id);

                    if ($account_owner_id->GetInt() == $bank_user_id->GetInt())
                    {
                        $result = true;
                    }
                }
            }
        }
        return $result;
    }
    

    public function GetAccountListForCurrentUser()
    {
        $account_id_list = array();

        if(is_user_logged_in())
        {
            $current_user = wp_get_current_user();
            $bank_user_id = $this->GetBankUserIdFromWpUser($current_user->ID);
            if($bank_user_id > 0)
            {
                $account_id_list = $this->GetAccountIdList($bank_user_id);
            }
        }

        return $account_id_list;
    }

    public function GetAccountInfoListCurrentUser()
    {
        $account_info_list = array();

        if(is_user_logged_in())
        {
            $current_user = wp_get_current_user();
            $wp_id = new WpUserIdTypeClass($current_user->ID);

            $bank_user_data = $this->GetBankUserDataFromWpUser($wp_id);
            if(!empty($bank_user_data))
            {
                $bank_user_id = $bank_user_data->GetBankUserId();
                $account_info_list = $this->GetAccountInfoList($bank_user_id);
            }
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
    
    public function GetTransactionListForCurrentUser($account_id)
    {
        $transaction_records_list = array();

        if(is_user_logged_in())
        {
            if(SanitizeAccountId( $account_id ))
            {
                if ( $this->IsCurrentUserAccountOwner( $account_id ) )
                {
                    $transaction_records_list = $this->GetTransactionList( $account_id );
                }
            }
        }

        return $transaction_records_list;
    }

    function MakeTransactionToAccount($account_id_from, $account_id_to, $amount)
    {
        $transaction_id = null;

        if(SanitizeAccountId($account_id_from) and SanitizeAccountId($account_id_to) and SanitizeAmount($amount))
        {
            $to_account_owner = $this->GetAccountOwner($account_id_to);
            if(!is_null($to_account_owner))
            {
                if($to_account_owner->HasValidData())
                {
                    if ($this->IsCurrentUserAccountOwner($account_id_from))
                    {
                        $timestamp = $this->DB_GetCurrentTimeStamp();
                        $transaction_type_withdraw = new TransactionDirTypeClass('WITHDRAW');
                        $transaction_type_add = new TransactionDirTypeClass('ADD');
                        $transaction_id = $this->MakeTransaction(
                            $account_id_from,
                            $account_id_to,
                            $timestamp,
                            $amount,
                            $transaction_type_withdraw,
                            $transaction_type_add);
                    }
                }
            }
        }

        return $transaction_id;
    }

    public function GetChequeListCurrentUser($issuer_account_id)
    {
        $cheque_records_list = array();

        if(SanitizeAccountId($issuer_account_id))
        {
            if ($this->IsCurrentUserAccountOwner($issuer_account_id))
            {
                $cheque_records_list = $this->GetChequeList($issuer_account_id);
            }
        }

        return $cheque_records_list;
    }


    public function CheckBankUserExistAndCreateNewBankUser()
    {
        $result = false;

        if(is_user_logged_in())
        {
            /* First, check if Wordpress user has a bank user id */
            $current_user = wp_get_current_user();

            $wp_id = new WpUserIdTypeClass($current_user->ID);

            $bank_user_data = $this->GetBankUserDataFromWpUser($wp_id);
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
                    $account_info_list = $this->GetAccountInfoList($bank_user_id);
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

    public function IssueCheque($issuer_account_id, $amount, $expire_seconds, $escrow_seconds, $receiver_name, $lock_address, $reference)
    {
        $cheque =null;

        if(SanitizeAccountId($issuer_account_id)
        and SanitizeAmount($amount)
        and SanitizePositiveInteger($expire_seconds)
        and SanitizePositiveIntegerOrZero($escrow_seconds)
        and SanitizeName($receiver_name)
        and SanitizeText($lock_address)
        and SanitizeText($reference)
        )
        {
            if($this->IsCurrentUserAccountOwner($issuer_account_id))
            {
                $bank_user_id = $this->GetAccountOwner($issuer_account_id);

                $bank_user_data = $this->DB_GetBankUserData($bank_user_id);
                $user_name = $bank_user_data->GetName();

                $account_data = $this->GetAccountData($issuer_account_id);
                if(!is_null($account_data))
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
                            null,
                            null,
                            null,
                            null,
                            null,
                            $lock_address,
                            null,
                            $user_name);

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

    public function ClaimCheque($cheque_id, $access_code)
    {
        $result = false;

        if(SanitizeChequeId($cheque_id) and SanitizeText($access_code))
        {
            $cheque = $this->DB_GetChequeData($cheque_id);

            $result = $this->ChangeChequeState($cheque, 'CHEQUE_EVENT_CLAIM');
        }

        return $result;
    }

    public function SetCurrentUserData($name_str, $country_str)
    {
        $result = false;

        if(is_user_logged_in())
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

        if(is_user_logged_in())
        {
            $current_user = wp_get_current_user();
            $wp_user = new WpUserIdTypeClass($current_user->ID);
            $bank_user_data = $this->GetBankUserDataFromWpUser($wp_user);
        }

        return $bank_user_data;
    }
}