<?php
/**
 * Bank App communication interface
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
 * This class serves as a interface for the Banking App.
 */

namespace BCF_BitcoinBank;


require_once ('banking_app_handler.php');
require_once ('email_cheque.php');


class BankingAppInterface
{
    private $has_username = false;
    private $has_password = false;
    private $bank_user_id_obj = null;
    private $user_logged_in = false;
    private $wp_user_id = false;

    public function __construct($username=null, $password=null)
    {
        if((!is_null($username)) and (gettype($username) == 'string'))
        {
            if(preg_match('/^[a-zA-Z1-9][a-zA-Z0-9]+$/', $username))
            {
                if($username != '')
                {
                    $this->has_username = true;
                }
            }
        }

        if((!is_null($password)) and (gettype($password) == 'string'))
        {
            if(preg_match('/^[a-zA-Z1-9][a-zA-Z0-9]+$/', $password))
            {
                if($password != '')
                {
                    $this->has_password = true;
                }
            }
        }

        if($this->has_username and $this->has_password)
        {
            $wp_user = get_user_by('login', $username);
            if($wp_user != false)
            {
                $this->wp_user_id = $wp_user->ID;
                if(wp_check_password($password, $wp_user->data->user_pass, $wp_user->ID))
                {
                    $banking_app_handler = new BankingAppHandler();

                    $wp_user_obj = new WpUserIdTypeClass($wp_user->ID);

                    $this->bank_user_id_obj = $banking_app_handler->GetBankUserIdFromWpUser($wp_user_obj);
                    if(!is_null($this->bank_user_id_obj))
                    {
                        $this->user_logged_in = true;
                    }
                }
            }
        }
    }

    private function FormatOkBaseResponse($message='')
    {
        $response_data = array(
            'result'    => 'OK',
            'message'   => $message
        );

        return $response_data;
    }

    private function FormatErrorResponse($error_message)
    {
        $response_data = array(
            'result'    => 'ERROR',
            'message'   => 'Error: ' . $error_message
        );

        return $response_data;
    }

    private function FormatErrorResponseInvalidUser()
    {
        $missing = '';

        if(!$this->has_username)
        {
            $missing .= 'username';
        }
        if(!$this->has_password)
        {
            if($missing != '')
            {
                $missing .= ' and ';
            }
            $missing .= 'password';
        }

        return $this->FormatErrorResponse('Invalid request. Missing ' . $missing);
    }

    public function GetAccountList()
    {
        if($this->user_logged_in)
        {
            $banking_app_handler    = new BankingAppHandler($this->bank_user_id_obj);
            $account_data_list = $banking_app_handler->GetUserAccountDataList();

            if( ! empty($account_data_list))
            {
                $account_list = [];

                foreach($account_data_list as $account_data)
                {
                    $account_id        = $account_data->GetAccountId();
                    $account_name      = $account_data->GetAccountName();
                    $balance           = $banking_app_handler->GetUsersAccountBalance($account_id->GetInt());
                    $currency          = $account_data->GetCurrency()->GetString();

                    $account = array(
                        'account_id' => $account_id->GetString(),
                        'name'       => $account_name->GetString(),
                        'balance'    => $balance->GetString(),
                        'currency'   => $currency
                    );

                    $account_list[] = $account;
                }

                if( ! empty($account_list))
                {
                    $response_data = $this->FormatOkBaseResponse();
                    $response_data['list'] = $account_list;
                }
                else
                {
                    $response_data = $this->FormatErrorResponse('User has no account.');
                }
            }
            else
            {
                $response_data = $this->FormatErrorResponse('User has no account.');
            }
        }
        else
        {
            $response_data = $this->FormatErrorResponseInvalidUser();
        }

        return $response_data;
    }

    public function GetTransactionList($input_data)
    {
        if($this->user_logged_in)
        {
            $banking_app_handler = new BankingAppHandler($this->bank_user_id_obj);

            $account_id = $input_data['account'];

            $transaction_records_list = $banking_app_handler->GetUserAccountTransactionList($account_id);
            $balance      = $banking_app_handler->GetUsersAccountBalance($account_id);
            $account_data = $banking_app_handler->GetUserAccountData($account_id);
            $currency     = $account_data->GetCurrency()->GetString();

            $transactions = array();

            if(!empty($transaction_records_list))
            {
                $count        = 0;
                foreach(array_reverse($transaction_records_list) as $transaction_record)
                {
                    $transaction = array(
                        'id'       => $transaction_record->GetTransactionId()->GetString(),
                        'datetime' => $transaction_record->GetDateTime()->GetString(),
                        'type'     => $transaction_record->GetTransactionType()->GetString(),
                        'amount'   => $transaction_record->GetTransactionAmount()->GetString(),
                        'balance'  => $transaction_record->GetTransactionBalance()->GetString()
                    );

                    $transactions[] = $transaction;

                    $count ++;
                    if($count == 25)
                    {
                        break;
                    }
                }
            }

            $response_data = $this->FormatOkBaseResponse();
            $response_data['acount']        = strval($account_id);
            $response_data['transactions']  = $transactions;
            $response_data['balance']       = $balance->GetString();
            $response_data['currency']      = $currency;
        }
        else
        {
            $response_data = $this->FormatErrorResponseInvalidUser('');
        }

        return $response_data;
    }

    public function GetAccountInfo($input_data)
    {
        if($this->user_logged_in)
        {

            $response_data = array(
                'result'    => 'OK',
                'acount'    => $input_data['$account'],
                'name'      => 'Name',
                'balance'    => 0
            );
        }
        else
        {
            $response_data = $this->FormatErrorResponseInvalidUser('');
        }

        return $response_data;
    }

    private function MakeCheque($banking_app_handler, $input_data)
    {
        $amount           = SanitizeInputInteger($input_data['amount']);
        $min_expire_sec   = SanitizeInputInteger($input_data['min_expire_sec']);
        $max_escrow_sec   = SanitizeInputInteger($input_data['max_escrow_sec']);

        $currency_str     = SanitizeInputText($input_data['currency']);
        $receiver_name    = SanitizeInputText($input_data['receiver_name']);
        $receiver_address = SanitizeInputText($input_data['receiver_address']);
        $receiver_url     = SanitizeInputText($input_data['receiver_url']);
        $receiver_email   = SanitizeInputText($input_data['receiver_email']);
        $business_no      = SanitizeInputText($input_data['business_no']);
        $reg_country      = SanitizeInputText($input_data['reg_country']);
        $lock             = SanitizeInputText($input_data['lock']);
        $reference_str    = SanitizeInputText($input_data['ref']);
        $memo             = SanitizeInputText($input_data['memo']);

        /* Optional data. Set to empty string if payment file has omitted the fields. */
        if(is_null($min_expire_sec)) {$min_expire_sec = 172800;}
        if(is_null($max_escrow_sec)) {$max_escrow_sec = 172800;}

        if(is_null($receiver_name)) {$receiver_name = '';}
        if(is_null($receiver_address)) {$receiver_address = '';}
        if(is_null($receiver_url)) {$receiver_url = '';}
        if(is_null($receiver_email)) {$receiver_email = '';}
        if(is_null($business_no)) {$business_no = '';}
        if(is_null($reg_country)) {$reg_country = '';}
        if(is_null($lock)) {$lock = '';}
        if(is_null($reference_str)) {$reference_str = '';}
        if(is_null($memo)) {$memo = '';}

        $bank_user_data = $banking_app_handler->GetUserBankUserData();
        $user_name      = $bank_user_data->GetName()->GetString();

        $cheque = $banking_app_handler->IssueAccountCheque(
            $input_data['account'],
            $amount,
            $currency_str,
            $min_expire_sec,
            $max_escrow_sec,
            $reference_str,
            $receiver_name,
            $receiver_address,
            $receiver_url,
            $receiver_email,
            $business_no,
            $reg_country,
            $lock,
            $memo,
            $user_name
        );

        return $cheque;
    }

    public function RequestCheque($input_data)
    {
        $response_data = '';

        if($this->user_logged_in)
        {
            $banking_app_handler = new BankingAppHandler($this->bank_user_id_obj);

            $payment_request  = DecodeAndVerifyPaymentFile($input_data['payment_request']);

            foreach($payment_request as $key => $value)
            {
                $input_data[$key] = $value;
            }

            $cheque = $this->MakeCheque($banking_app_handler, $input_data);

            if( ! is_null($cheque))
            {
                $cheque_data = $cheque->GetDataArrayPublicData();
                $cheque_file = EncodeAndSignBitcoinCheque($cheque_data);

                $response_data           = $this->FormatOkBaseResponse();
                $response_data['ver']    = '1';
                $response_data['cheque'] = $cheque_file;
                $response_data['status'] = 'UNCLAIMED';
            }
            else
            {
                $response_data = $this->FormatErrorResponse('Error drawing cheque.');
            }
        }

        return $response_data;
    }

    public function DrawCheque($input_data)
    {
        $response_data = '';

        if($this->user_logged_in)
        {
            $banking_app_handler = new BankingAppHandler($this->bank_user_id_obj);

            $cheque = $this->MakeCheque($banking_app_handler, $input_data);

            if( ! is_null($cheque))
            {
                //error_log('Issued   cheque:' . $cheque_json);
                //error_log($cheque_json);

                $cheque_data = $cheque->GetDataArrayPublicData();
                $cheque_file = EncodeAndSignBitcoinCheque($cheque_data);

                $response_data           = $this->FormatOkBaseResponse();
                $response_data['ver']    = '1';
                $response_data['cheque'] = $cheque_file;
                $response_data['status'] = 'UNCLAIMED';

                $email = new EmailCheque($input_data['bank_send_to'], $cheque_data['cheque_id'], $cheque_data['access_code']);
                $email->SetReceiverName($input_data['receiver_name']);
                $email->SetMessage($input_data['memo']);

                $current_user = get_user_by('ID', $this->wp_user_id);
                $from_email   = $current_user->user_email;
                $email->SetFromAddress($from_email);

                if($input_data['cc_me'] == 1)
                {
                    $email->AddCopyAddress($from_email);
                }

                $sent_ok = $email->Send();

                if( ! $sent_ok)
                {
                    $response_data           = $this->FormatErrorResponse('Could not send e-mail.');
                    $response_data['ver']    = '1';
                    $response_data['cheque'] = $cheque_file;
                    $response_data['status'] = 'UNCLAIMED';
                }
            }
            else
            {
                $response_data = $this->FormatErrorResponse('Error drawing cheque.');
            }
        }

        return $response_data;
    }

}

