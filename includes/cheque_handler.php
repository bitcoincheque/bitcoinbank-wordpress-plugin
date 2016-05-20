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

    public function IssueCheque($issuer_account_id, $account_password, $amount, $expire_seconds, $escrow_seconds, $reference)
    {
        $cheque =null;

        if(SanitizeAccountId($issuer_account_id) 
            and SanitizePositiveInteger($expire_seconds) 
            and SanitizePositiveInteger($escrow_seconds)
            and SanitizeText($reference))
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
                        
                        $cheque = $this->CreateCheque($issuer_account_id, $issue_datetime, $expire_datetime, $escrow_datetime, $amount, $reference);
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
}

