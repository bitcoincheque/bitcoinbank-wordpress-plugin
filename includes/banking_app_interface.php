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

namespace BCF_BitcoinBank;

require_once ('cheque_handler.php');
require_once ('email_cheque.php');


class BankingAppInterface
{
    protected $has_username = false;
    protected $has_password = false;
    protected $wp_user_id = null;
    protected $user_logged_in = false;

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
                if(wp_check_password($password, $wp_user->data->user_pass, $wp_user->ID))
                {
                    $this->wp_user_id = $wp_user->ID;
                    $this->user_logged_in = true;
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
            $cheque_handler    = new ChequeHandlerClass();
            $account_data_list = $cheque_handler->GetAccountInfoListFromWpUser($this->wp_user_id);

            if( ! empty($account_data_list))
            {
                $account_list = [];

                foreach($account_data_list as $account_data)
                {
                    $listed_account_id = $account_data->GetAccountId();
                    $account_name      = $account_data->GetAccountName();
                    $balance           = $cheque_handler->GetUsersAccountBalance($listed_account_id);
                    $currency          = $account_data->GetCurrency()->GetString();

                    $account = array(
                        'account_id' => $listed_account_id->GetString(),
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
            $cheque_handler = new ChequeHandlerClass();

            $wp_user_id = new WpUserIdTypeClass($this->wp_user_id);
            $account_id = new AccountIdTypeClass($input_data['account']);

            $transaction_records_list = $cheque_handler->GetTransactionListForCurrentUser($wp_user_id, $account_id);
            $balance                  = $cheque_handler->GetUsersAccountBalance($account_id);
            $account_data             = $cheque_handler->GetAccountDataFromWpUser($wp_user_id, $account_id);
            $currency                 = $account_data->GetCurrency()->GetString();

            $transactions = array();
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

            $response_data = $this->FormatOkBaseResponse();
            $response_data['acount']        = $account_id->GetString();
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

    public function RequestCheque($input_data)
    {
        if($this->user_logged_in)
        {
            $wp_user_id = new WpUserIdTypeClass($this->wp_user_id);
            $account_id = new AccountIdTypeClass($input_data['account']);

            $cheque_handler    = new ChequeHandlerClass();
            if($cheque_handler->IsWpUserAccountOwner($wp_user_id, $account_id))
            {
                $payment_request = DecodeAndVerifyPaymentFile($input_data['payment_request']);

                $amount           = SanitizeInputInteger($payment_request['amount']);
                $currency_str     = SanitizeInputText($payment_request['currency']);
                $paylink_str      = SanitizeInputText($payment_request['paylink']);
                $receiver_name    = SanitizeInputText($payment_request['receiver_name']);
                $receiver_address = SanitizeInputText($payment_request['receiver_address']);
                $receiver_url     = SanitizeInputText($payment_request['receiver_url']);
                $receiver_email   = SanitizeInputText($payment_request['receiver_email']);
                $business_no      = SanitizeInputText($payment_request['business_no']);
                $reg_country      = SanitizeInputText($payment_request['reg_country']);
                $lock             = SanitizeInputText($payment_request['receiver_wallet']);
                $min_expire_sec   = SanitizeInputInteger($payment_request['min_expire_sec']);
                $max_escrow_sec   = SanitizeInputInteger($payment_request['max_escrow_sec']);
                $reference_str    = SanitizeInputText($payment_request['ref']);
                $memo             = SanitizeInputText($payment_request['description']);

                $bank_user_data = $cheque_handler->GetBankUserDataFromWpUser($wp_user_id);
                $bank_user_id = $bank_user_data->GetBankUserId();
                $user_name = $bank_user_data->GetName();

                $cheque = $cheque_handler->IssueCheque(
                    $bank_user_id->GetInt(),
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
                    $user_name->GetString());

                if( ! is_null($cheque))
                {
                    $cheque_data = $cheque->GetDataArrayPublicData();
                    $cheque_file = EncodeAndSignBitcoinCheque($cheque_data);

                    $response_data = $this->FormatOkBaseResponse();
                    $response_data['ver'] = '1';
                    $response_data['cheque'] = $cheque_file;
                    $response_data['status'] = 'UNCLAIMED';
                }
                else
                {
                    $response_data = $this->FormatErrorResponse('Error drawing cheque.');
                }
            }
            else
            {
                $response_data = $this->FormatErrorResponse('Invalid account.');
            }
        }
        else
        {
            $response_data = $this->FormatErrorResponseInvalidUser('');
        }

        return $response_data;
    }

    public function DrawCheque($input_data)
    {
        if($this->user_logged_in)
        {
            $wp_user_id = new WpUserIdTypeClass($this->wp_user_id);
            $account_id = new AccountIdTypeClass($input_data['account']);

            $cheque_handler    = new ChequeHandlerClass();
            if($cheque_handler->IsWpUserAccountOwner($wp_user_id, $account_id))
            {
                $receiver_address = '';
                $receiver_url     = '';
                $receiver_email   = '';
                $business_no      = '';
                $reg_country      = '';
                $min_expire_sec   = 172800; // 2 days
                $max_escrow_sec   = 172800; // 2 days
                $reference_str    = '';

                $bank_user_data = $cheque_handler->GetBankUserDataFromWpUser($wp_user_id);
                $bank_user_id = $bank_user_data->GetBankUserId();
                $user_name = $bank_user_data->GetName();

                $cheque = $cheque_handler->IssueCheque(
                    $bank_user_id->GetInt(),
                    $input_data['account_int'],
                    $input_data['amount_int'],
                    $input_data['$currency_str'],
                    $min_expire_sec,
                    $max_escrow_sec,
                    $reference_str,
                    $input_data['receivers_name'],
                    $receiver_address,
                    $receiver_url,
                    $receiver_email,
                    $business_no,
                    $reg_country,
                    $input_data['$lock'],
                    $input_data['$memo'],
                    $user_name->GetString());

                if( ! is_null($cheque))
                {
                    //error_log('Issued   cheque:' . $cheque_json);
                    //error_log($cheque_json);

                    $cheque_data = $cheque->GetDataArrayPublicData();
                    $cheque_file = EncodeAndSignBitcoinCheque($cheque_data);

                    $response_data = $this->FormatOkBaseResponse();
                    $response_data['ver'] = '1';
                    $response_data['cheque'] = $cheque_file;
                    $response_data['status'] = 'UNCLAIMED';

                    $email = new EmailCheque($input_data['bank_send_to'], $cheque_data['cheque_id'], $cheque_data['access_code']);
                    $email->SetReceiverName($input_data['receivers_name']);
                    $email->SetMessage($input_data['memo']);

                    $current_user = get_user_by('ID', $this->wp_user_id);
                    $from_email = $current_user->user_email;
                    $email->SetFromAddress($from_email);

                    if($input_data['cc_me']==1)
                    {
                        $email->AddCopyAddress($from_email);
                    }

                    $sent_ok = $email->Send();

                    if(!$sent_ok)
                    {
                        $response_data = $this->FormatErrorResponse('Could not send e-mail.');
                        $response_data['ver'] = '1';
                        $response_data['cheque'] = $cheque_file;
                        $response_data['status'] = 'UNCLAIMED';
                    }
                }
                else
                {
                    $response_data = $this->FormatErrorResponse('Error drawing cheque.');
                }
            }
            else
            {
                $response_data = $this->FormatErrorResponse('Invalid account.');
            }
        }
        else
        {
            $response_data = $this->FormatErrorResponseInvalidUser('');
        }

        return $response_data;
    }

}

