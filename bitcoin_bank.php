<?php
/**
 * Bitcoin Bank plugin for Wordpress.
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

/*
Plugin Name: Bitcoin Bank
Plugin URI: http://www.bitcoincheque.org
Description: This Wordpress plugin is a Bitcoin Bank to demonstrate the usage of "Bitcoin Cheques".
Version: 0.0.1
Author: Bitcoin Cheque Foundation
Author URI: http://www.bitcoincheque.org
License: GNU LGPLv3
License URI: license.txt
Text Domain: bcf_bitcoinbank
*/

namespace BCF_BitcoinBank;

require_once('includes/user_handler.php');
require_once('includes/cheque_handler.php');
require_once('includes/html_table.php');


function SanitizeInputText($text)
{
    $text = str_replace('<', '&lt;', $text);
    $text = str_replace('>', '&gt;', $text);
    //$text = str_replace('"', '&quot;', $text);

    return $text;
}

function SanitizeInputInteger($text)
{
    $value = intval($text);
    return $value;
}

function ProcessAjaxValidateCheque()
{
    $cheque_handler = new ChequeHandlerClass();

    $cheque_base64_uri_encoded = SanitizeInputText($_REQUEST['cheque']);
    $cheque_base64 = urldecode($cheque_base64_uri_encoded);
    $cheque_json = base64_decode($cheque_base64);
    $cheque = json_decode($cheque_json, true);

    $result_msg = $cheque_handler->ValidateCheque($cheque, true);

    //error_log('cheque valid error:' . $result_msg);
    if($result_msg == 'OK')
    {
        $response_data = array(
            'serial_no' => $cheque['serial_no'],
            'status'    => 'VALID',
            'errors'    => $result_msg
        );
    }
    else
    {
        $response_data = array(
            'serial_no' => $cheque['serial_no'],
            'status'    => 'INVALID',
            'errors'    => $result_msg
        );
    }

    echo json_encode($response_data);
    die();
}

function ProcessAjaxRequestCheque()
{
    $amount_val = intval(SanitizeInputText($_REQUEST['amount']));
    $currency_str = SanitizeInputText($_REQUEST['currency']);
    $paylink_str = SanitizeInputText($_REQUEST['paylink']);
    $receiver_name = SanitizeInputText($_REQUEST['receiver_name']);
    $receiver_address = SanitizeInputText($_REQUEST['receiver_address']);
    $receiver_url = SanitizeInputText($_REQUEST['receiver_url']);
    $receiver_email = SanitizeInputText($_REQUEST['receiver_email']);
    $business_no = SanitizeInputText($_REQUEST['business_no']);
    $reg_country = SanitizeInputText($_REQUEST['reg_country']);
    $receiver_wallet = SanitizeInputText($_REQUEST['receiver_wallet']);
    $min_expire_sec = SanitizeInputText($_REQUEST['min_expire_sec']);
    $max_escrow_sec = SanitizeInputText($_REQUEST['max_escrow_sec']);
    $reference_str = SanitizeInputText($_REQUEST['ref']);
    $account_id_val = intval(SanitizeInputText($_REQUEST['account']));
    $account_password_str = SanitizeInputText($_REQUEST['passwd']);
    $description = SanitizeInputText($_REQUEST['description']);

    if($currency_str != 'BTC')
    {
        echo 'Unsupported curreny';
        die();
    }

    $issuer_account_id= new AccountIdTypeClass($account_id_val);
    $account_password = new PasswordTypeClass($account_password_str);
    $amount           = new ValueTypeClass($amount_val);
    $reference        = new TextTypeClass($reference_str);
    $receiver_name    = new NameTypeClass($receiver_name);
    $receiver_address = new TextTypeClass($receiver_address);
    $receiver_url     = new TextTypeClass($receiver_url);
    $receiver_email   = new TextTypeClass($receiver_email);
    $business_no      = new TextTypeClass($business_no);
    $reg_country      = new TextTypeClass($reg_country);
    $receiver_wallet  = new TextTypeClass($receiver_wallet);
    $description      = new TextTypeClass($description);

    $expire_seconds = 300;
    $escrow_seconds = 600;

    $cheque_handler = new ChequeHandlerClass();

    $cheque = $cheque_handler->IssueCheque(
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
        $description
    );

    if(!is_null($cheque))
    {
        //error_log('Issued   cheque:' . $cheque_json);
        //error_log($cheque_json);

        $response_data = array(
            'result'    => 'OK',
            'ver'       => '1',
            'cheque'    => $cheque->GetBase64(),
            'hash'      => '84784uj3489ryfj',
            'status'    => 'UNCLAIMED'
        );

        echo json_encode($response_data);
    }
    else
    {
        $response_data = array(
            'result'    => 'ERROR',
            'msg'       => 'Unknown error'
        );

        echo json_encode($response_data);
    }

    die();
}

function ProcessAjaxGetAccountList()
{
    if(!empty($_REQUEST['username'])
        and (!empty($_REQUEST['password'])))
    {
        $username = SanitizeInputText($_REQUEST['username']);
        $password = SanitizeInputText($_REQUEST['password']);

        $wp_user = get_user_by( 'login', $username );
        if($wp_user != false)
        {
            if(wp_check_password( $password, $wp_user->data->user_pass, $wp_user->ID))
            {
                $wp_user_id = $wp_user->ID;

                $cheque_handler    = new ChequeHandlerClass();
                $account_data_list = $cheque_handler->GetAccountInfoListFromWpUser($wp_user_id);

                if( ! empty($account_data_list))
                {
                    $account_list = [];

                    foreach($account_data_list as $account_data)
                    {
                        $listed_account_id = $account_data->GetAccountId();
                        $account_name      = $account_data->GetAccountName();
                        $balance           = $cheque_handler->GetUsersAccountBalance($listed_account_id);

                        $account = array(
                            'account_id' => $listed_account_id->GetString(),
                            'name'       => $account_name->GetString(),
                            'balance'    => $balance->GetString(),
                            'currency'   => 'BTC'
                        );

                        $account_list[] = $account;
                    }

                    if( ! empty($account_list))
                    {
                        $response_data = array(
                            'result' => 'OK',
                            'list'   => $account_list
                        );
                    }
                    else
                    {
                        $response_data = array(
                            'result' => 'ERROR',
                            'msg'    => 'User has no account.'
                        );
                    }

                }
                else
                {
                    $response_data = array(
                        'result' => 'ERROR',
                        'msg'    => 'User has no account.'
                    );
                }
            }
            else
            {
                $response_data = array(
                    'result' => 'ERROR',
                    'msg'    => 'Wrong password.'
                );
            }
        }
        else
        {
            $response_data = array(
                'result'    => 'ERROR',
                'msg'       => 'Invalid username.'
            );
        }
    }
    else
    {
        $response_data = array(
            'result'    => 'ERROR',
            'msg'       => 'Invalid request.'
        );
    }

    echo json_encode($response_data);
    die();
}

function ProcessAjaxGetAccountDetails()
{
    if(!empty($_REQUEST['account']))
    {
        $account = SanitizeInputInteger($_REQUEST['account']);

        $response_data = array(
            'result'    => 'OK',
            'acount'    => $account,
            'name'      => 'Name',
            'balance'    => 0
        );
    }
    else
    {
        $response_data = array(
            'result'    => 'ERROR',
            'msg'       => 'Request error.'
        );
    }

    echo json_encode($response_data);
    die();
}

function ProcessAjaxGetTransactionList()
{
    if(!empty($_REQUEST['username'])
       and (!empty($_REQUEST['password']))
       and (!empty($_REQUEST['account'])))
    {
        $username_str = SanitizeInputText($_REQUEST['username']);
        $password_str = SanitizeInputText($_REQUEST['password']);
        $account_int = SanitizeInputInteger($_REQUEST['account']);

        $wp_user = get_user_by('login', $username_str);
        if($wp_user != false)
        {
            if(wp_check_password($password_str, $wp_user->data->user_pass, $wp_user->ID))
            {
                $wp_user_id_val = $wp_user->ID;

                $cheque_handler    = new ChequeHandlerClass();

                $wp_user_id = new WpUserIdTypeClass($wp_user_id_val);
                $account_id= new AccountIdTypeClass($account_int);

                $transaction_records_list = $cheque_handler->GetTransactionListForCurrentUser($wp_user_id, $account_id);
                $balance = $cheque_handler->GetUsersAccountBalance($account_id);

                $transactions = array();
                $count=0;
                foreach (array_reverse($transaction_records_list) as $transaction_record)
                {
                    $transaction = array(
                        'id'       => $transaction_record->GetTransactionId()->GetString(),
                        'datetime' => $transaction_record->GetDateTime()->GetString(),
                        'type'     => $transaction_record->GetTransactionType()->GetString(),
                        'amount'   => $transaction_record->GetTransactionAmount()->GetString(),
                        'balance'  => $transaction_record->GetTransactionBalance()->GetString()
                    );

                    $transactions[] = $transaction;

                    $count++;
                    if($count == 25) { break;}
                }

                $response_data = array(
                    'result'       => 'OK',
                    'acount'         => $account_id->GetString(),
                    'transactions' => $transactions,
                    'balance'      => $balance->GetString(),
                    'currency'     => 'BTC'
                );
            }
            else
            {
                $response_data = array(
                    'result' => 'ERROR',
                    'msg'    => 'Wrong password.'
                );
            }
        }
        else
        {
            $response_data = array(
                'result'    => 'ERROR',
                'msg'       => 'Invalid username.'
            );
        }
    }
    else
    {
        $response_data = array(
            'result'    => 'ERROR',
            'msg'       => 'Invalid request.'
        );
    }

    echo json_encode($response_data);
    die();
}

function MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected, $currency)
{
    $html = '<select name="select_account">';
    foreach ( $account_data_list as $account_data )
    {
        $listed_account_id      = $account_data->GetAccountId();
        $account_id_str         = $listed_account_id->GetString();
        $account_id_formatedstr = $listed_account_id->GetFormatedString();
        $account_name           = $account_data->GetAccountName();
        $account_name_str       = $account_name->GetString();
        $balance = $user_handler->GetUsersAccountBalance($listed_account_id);
        $account_balance_str    = GetFormattedCurrency($balance->GetInt(), $currency, true );

        $select = '';
        if($account_selected != null)
        {
            if ( $listed_account_id->GetInt() == $account_selected->GetInt() )
            {
                $select = 'selected="1" ';
            }
        }

        $html .= '<option '.$select.'value="' . $account_id_str . '">' . $account_id_formatedstr . ' / ' . $account_name_str . ' / ' . $account_balance_str . '</option>';
    }
    $html .= '</select>';
    return $html;
}

function MakeHtmlFormSelectAccount($user_handler, $account_data_list, $account_selected, $currency)
{

    $html = '<form name="bcf_withdraw_form">';
    $html .= '<table style="border-style:none;" width="100%"><tr>';
    $html .= '<td style="border-style:none;" width="30%">My accounts:</td>';

    $html .= '<td style="border-style:none;">';

    $html .= MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected, $currency);

    $html .= '</td>';

    $html .= '<td style="border-style:none;"><a href="transactions"><input type="submit" value="Select"/></a></td>';
    $html .= '</tr></table>';
    $html .= '</form>';

    return $html;
}

function ListUserTransactions($atts)
{
    if (is_user_logged_in()) {
        $currency = 'uBTC';

        if ( ! empty( $_REQUEST['select_account'] ) ) {
            $show_account_str = SanitizeInputText( $_REQUEST['select_account'] );
            $account_selected = new AccountIdTypeClass(0);
            $account_selected->SetDataFromString($show_account_str);
        }
        else
        {
            $account_selected = null;
        }

        $user_handler = new UserHandlerClass();
        $account_data_list = $user_handler->GetAccountInfoListCurrentUser();



        if ( $account_selected == null ) {
            $account_data = $account_data_list[0];
            $account_selected = $account_data->GetAccountId();
        }
        $transaction_records_list = $user_handler->GetTransactionListForCurrentUser($account_selected);

        $html_select_account_form = MakeHtmlFormSelectAccount($user_handler, $account_data_list, $account_selected, $currency);

        $html_table = new HtmlTableClass();
        $html_table->AddLineItem('Trans.Id');
        $html_table->AddLineItem('Date/time');
        $html_table->AddLineItem('Type');
        $html_table->AddLineItem('Add');
        $html_table->AddLineItem('Withdraw');
        $html_table->AddLineItem('Balance');
        $html_table->RowFeed();

        foreach (array_reverse($transaction_records_list) as $transaction)
        {
            $id = $transaction->GetTransactionId();
            $datetime = $transaction->GetDateTime()->GetString();
            $type = $transaction->GetTransactionType()->GetString();
            $amount = $transaction->GetTransactionAmount()->GetInt();
            $balance = $transaction->GetTransactionBalance()->GetInt();

            $id_str = $id->GetString();
            $amount_str = GetFormattedCurrency($amount, $currency, false);
            $balance_str = GetFormattedCurrency($balance, $currency, true);

            $html_table->AddLineItem($id_str);
            $html_table->AddLineItem($datetime);
            $html_table->AddLineItem($type);
            if ($amount > 0)
            {
                $html_table->AddLineItem($amount_str);
                $html_table->AddLineItem('');
                $html_table->AddLineItem($balance_str);
            } 
            else if ($amount < 0)
            {
                $html_table->AddLineItem('');
                $html_table->AddLineItem($amount_str);
                $html_table->AddLineItem($balance_str);
            }
            else
            {
                $html_table->AddLineItem('');
                $html_table->AddLineItem('');
                $html_table->AddLineItem($balance_str);
            }
            $html_table->RowFeed();
        }

        $output = $html_select_account_form . $html_table->GetHtmlTable();
    }
    else
    {
        $output = 'You must sign in to see your transactions.<br>';
    }

    return $output;
}

function Withdraw()
{
    if(is_user_logged_in())
    {
        $currency = 'BTC';

        if ( ! empty( $_REQUEST['select_account'] ) ) {
            $show_account_str = SanitizeInputText( $_REQUEST['select_account'] );
            $account_selected = new AccountIdTypeClass(0);
            $account_selected->SetDataFromString($show_account_str);
        }
        else
        {
            $account_selected = null;
        }

        $user_handler = new UserHandlerClass();

        $withdraw_output = '';
        if(!empty($_REQUEST['select_account'])) {
            $from_account_id_str = SanitizeInputText( $_REQUEST['select_account'] );
            $to_account_id_str = intval(SanitizeInputText($_REQUEST['depost_account']));
            $amount_str = intval(SanitizeInputText($_REQUEST['amount']));

            $from_account_id = new AccountIdTypeClass(intval($from_account_id_str));
            $to_account_id = new AccountIdTypeClass(intval($to_account_id_str));
            $amount = new ValueTypeClass(intval($amount_str));

            $transaction_id = $user_handler->MakeTransactionToAccount($from_account_id, $to_account_id, $amount);
            if (!is_null($transaction_id))
            {
                $withdraw_output .= 'Transfered OK.<br>';
                $withdraw_output .= GetFormattedCurrency($amount->GetInt(), $currency, true) . ' sent from account '. $from_account_id->GetFormatedString() . ' to ' . $to_account_id->GetFormatedString() . '<br>';
                $my_transaction_id = $transaction_id->GetInt() - 1; // Making a internal transfer creats two transactions records in database. My record was the previous one.
                $withdraw_output .= '(Transaction ID = ' . $my_transaction_id . ')<br>';
            }
            else
            {
                $withdraw_output .= 'Transfer ERROR!<br>';
                $withdraw_output .= 'Unable to send '. GetFormattedCurrency($amount->GetInt(), $currency, true) . ' from account '. $from_account_id->GetFormatedString() . ' to ' . $to_account_id->GetFormatedString() . '<br>';
                $withdraw_output .= '<br>';
            }
        }
        else
        {
            $withdraw_output .= '<br>';
            $withdraw_output .= '<br>';
            $withdraw_output .= '<br>';
        }

        $account_data_list = $user_handler->GetAccountInfoListCurrentUser();

        $output = '<form name="bcf_withdraw_form">';
        $output .= '<table style="border-style:none;" width="100%"><tr>';
        $output .= '<td style="border-style:none;" width="30%">From my account:</td>';

        $output .= '<td style="border-style:none;">';

        $output .= MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected, $currency);

        $output .= '</td>';

        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;">To account:</td><td style="border-style:none;"><input type="text" value="" id="bcf_bitcoinbank_deposit_account" name="depost_account" /></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;">Amount:</td><td style="border-style:none;"><input type="text" id="bcf_bitcoinbank_withdraw_amount" name="amount" /></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;"></td><td style="border-style:none;"><a href="withdraw"><input type="submit" value="Withdraw"/></a></td>';
        $output .= '</tr></table>';
        $output .= '</form>';

        $output .= '<br>';

    }
    else
    {
        $output = 'You must log in to make withdrawal.<br>';
    }


    return $output . $withdraw_output;
}

function ChequeDetails()
{
    $currency = 'BTC';

    if ( !empty( $_REQUEST['cheque_no'] ) and  !empty( $_REQUEST['access_code'] ) )
    {
        $cheque_id_val   = SanitizeInputInteger($_REQUEST['cheque_no']);
        $access_code_val = SanitizeInputText($_REQUEST['access_code']);

        $cheque_id   = new ChequeIdTypeClass($cheque_id_val);
        $access_code = new TextTypeClass($access_code_val);

        $user_handler = new UserHandlerClass();
        $cheque       = $user_handler->GetCheque($cheque_id, $access_code);

        if($cheque != null)
        {
            $html_table = new HtmlTableClass();

            $html_table->AddLineItem('Cheque No.:');
            $html_table->AddLineItem($cheque->GetChequeId()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Value:');
            $html_table->AddLineItem($cheque->GetValue()->GetFormattedCurrencyString($currency, true));
            $html_table->RowFeed();

            $html_table->AddLineItem('Cheque status:');
            $html_table->AddLineItem($cheque->GetChequeState()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Issuer Name:');
            $html_table->AddLineItem($cheque->GetIssuerName()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Issuer Address');
            $html_table->AddLineItem($cheque->GetIssuerAddress()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Issue date/time:');
            $html_table->AddLineItem($cheque->GetIssueDateTime()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Expire date/time:');
            $html_table->AddLineItem($cheque->GetExpireDateTime()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Escrow date/time:');
            $html_table->AddLineItem($cheque->GetEscrowDateTime()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Fixed fee:');
            $html_table->AddLineItem($cheque->GetFixedFee()->GetFormattedCurrencyString($currency, true));
            $html_table->RowFeed();

            $html_table->AddLineItem('Collection fee');
            $html_table->AddLineItem($cheque->GetCollectionFee()->GetFormattedCurrencyString($currency, true));
            $html_table->RowFeed();

            $html_table->AddLineItem('Will be stamped:');
            $html_table->AddLineItem($cheque->GetStamp()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Collection url:');
            $html_table->AddLineItem($cheque->GetCollectUrl()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s name:');
            $html_table->AddLineItem($cheque->GetReceiverName()->GetString());
            $html_table->RowFeed();

            $address = $cheque->GetReceiverAddress()->GetString();
            $address = str_replace("\r\n", '<br>', $address);
            $html_table->AddLineItem('Receiver\'s address:');
            $html_table->AddLineItem($address);
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s web site:');
            $html_table->AddLineItem($cheque->GetReceiverUrl()->GetString(), $cheque->GetReceiverUrl()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s e-mail:');
            $html_table->AddLineItem($cheque->GetReceiverEmail()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s business no.:');
            $html_table->AddLineItem($cheque->GetReceiverBusinessNo()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s registration country:');
            $html_table->AddLineItem($cheque->GetReceiverRegCountry()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s Wallet:');
            $html_table->AddLineItem($cheque->GetReceiverWallet()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s reference:');
            $html_table->AddLineItem($cheque->GetReceiverReference()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Attached user\'s reference:');
            $html_table->AddLineItem($cheque->GetUserReference()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Attached user\'s name:');
            $html_table->AddLineItem($cheque->GetUserName()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Attached user\'s address:');
            $html_table->AddLineItem($cheque->GetUserAddress()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Access Code:');
            $html_table->AddLineItem($cheque->GetAccessCode()->GetString());
            $html_table->RowFeed();

            $html_table2 = new HtmlTableClass();
            $html_table2->AddLineItem('Account No.:');
            $html_table2->AddLineItem($cheque->GetOwnerAccountId()->GetString());
            $html_table2->RowFeed();

            $html_table2->AddLineItem('Description:');
            $html_table2->AddLineItem($cheque->GetDescription()->GetString());
            $html_table2->RowFeed();

            $png_url = site_url() . '/wp-admin/admin-ajax.php?action=bcf_bitcoinbank_get_cheque_png&cheque_no=' . strval($cheque_id_val) . '&access_code=' . $access_code_val;

            $output = '<a href="' . $png_url . '"><img src="' . $png_url . '" height="300" width="800" alt="Loading cheque image..."/></a>';
            $output .= '<p>';
            $output .= '<h3>Details for Cheque No. ' . strval($cheque_id_val) . '</h3>';
            $output .= '<b>Public data:</b><br>This information is included in cheque and sent to cheque receiver.';
            $output .= $html_table->GetHtmlTable();
            $output .= '<b>Private data:</b><br>Information not included in the cheque.';
            $output .= $html_table2->GetHtmlTable();

            $output .= '<p>';

            $output .= '<br>';
            $output .= '<br>';
            $output .= '<br>';
            $output .= '<hr>';
            $output .= '<h3>Developer\'s details</h3>';
            $output .= '<b>Bitcoin Cheque File (Base64 encoding of the JSON format):</b>';
            $html_table3   = new HtmlTableClass();
            $html_table3->AddLineItem($cheque->GetBase64(), '', 'style="word-wrap:break-word; overflow-wrap:nowrap; hyphens:none;"');
            $html_table3->RowFeed();
            $output .= '<p>';
            $output .= $html_table3->GetHtmlTable('style="table-layout: fixed; width: 100%"');

            $output .= '<b>Bitcoin Cheque in JSON format:</b>';
            $filtered_text = SanitizeInputText($cheque->GetJson());
            $html_table4   = new HtmlTableClass();
            $html_table4->AddLineItem($filtered_text, '', 'style="word-wrap:break-word; overflow-wrap:nowrap; hyphens:none;"');
            $html_table4->RowFeed();
            $output .= '<p>';
            $output .= $html_table4->GetHtmlTable('style="table-layout: fixed; width: 100%"');
        }
        else
        {
            $output = 'Invalid cheque request.';
        }
    }

    return $output;
}


function ListUserCheques()
{
    $currency = 'uBTC';

    if (is_user_logged_in())
    {
        if( ! empty($_REQUEST['select_account']))
        {
            $show_account_str = SanitizeInputText($_REQUEST['select_account']);
            $account_selected = new _AccountIdTypeClass(0);
            $account_selected->SetDataFromString($show_account_str);
        }
        else
        {
            $account_selected = null;
        }

        $user_handler      = new UserHandlerClass();
        $account_data_list = $user_handler->GetAccountInfoListCurrentUser();


        if($account_selected == null)
        {
            $account_data     = $account_data_list[0];
            $account_selected = $account_data->GetAccountId();
        }
        $cheque_list = $user_handler->GetChequeListCurrentUser($account_selected);

        $html_select_account_form = MakeHtmlFormSelectAccount($user_handler, $account_data_list, $account_selected, $currency);

        $html_table = new HtmlTableClass();
        $html_table->AddLineItem('Cheque No.');
        $html_table->AddLineItem('Issue<br>Date/Time');
        $html_table->AddLineItem('Expire<br>Date/Time');
        $html_table->AddLineItem('Escrow<br>Date/Time');
        $html_table->AddLineItem('State');
        $html_table->AddLineItem('Amount');
        $html_table->RowFeed();

        foreach(array_reverse($cheque_list) as $cheque)
        {
            $cheque_id       = $cheque->GetChequeId();
            $access_code     = $cheque->GetAccessCode();
            $issue_datetime  = $cheque->GetIssueDateTime();
            $expire_datetime = $cheque->GetExpireDateTime();
            $escrow_datetime = $cheque->GetEscrowDateTime();
            $state           = $cheque->GetChequeState();
            $amount          = $cheque->GetValue();
            $details_link    = site_url() . '/index.php/cheque-details?cheque_no=' . $cheque_id->GetString() . '&access_code=' . $access_code->GetString();

            $html_table->AddLineItem($cheque_id->GetString(), $details_link);
            $timestamp_str = str_replace(' ', '<br>', $issue_datetime->GetString());
            $html_table->AddLineItem($timestamp_str);
            $timestamp_str = str_replace(' ', '<br>', $expire_datetime->GetString());
            $html_table->AddLineItem($timestamp_str);
            $timestamp_str = str_replace(' ', '<br>', $escrow_datetime->GetString());
            $html_table->AddLineItem($timestamp_str);
            $html_table->AddLineItem($state->GetString());
            $html_table->AddLineItem(GetFormattedCurrency($amount->GetInt(), $currency, true));

            $html_table->RowFeed();
        }

        $output = $html_select_account_form;
        $output .= 'Cheques draw from account ' . $account_selected->GetString() . ':<br>';
        $output .= $html_table->GetHtmlTable();
    }
    else
    {
        $output = 'You must sign in to see your cheques.<br>';
    }

    return $output;
}

function DrawCheque()
{
    if (is_user_logged_in())
    {
        $user_handler = new UserHandlerClass();
        $currency = 'uBTC';

        if( !empty($_REQUEST['select_account'])
            and !empty($_REQUEST['amount'])
            and !empty($_REQUEST['expired_days'])
        )
        {
            $selected_account_int = SanitizeInputInteger($_REQUEST['select_account']);

            if(!empty($_REQUEST['receiver_name']))
            {
                $receiver_name_str = SanitizeInputText($_REQUEST['receiver_name']);
            }
            else
            {
                $receiver_name_str = '';
            }

            if(!empty($_REQUEST['receiver_email']))
            {
                $receiver_email_str = SanitizeInputText($_REQUEST['receiver_email']);
            }
            else
            {
                $receiver_email_str = '';
            }


            $amount_int = SanitizeInputInteger($_REQUEST['amount']);

            $expire_days = SanitizeInputInteger($_REQUEST['expired_days']);

            if(!empty($_REQUEST['memo']))
            {
                $memo_str = SanitizeInputText($_REQUEST['memo']);
            }
            else
            {
                $memo_str = '';
            }

            $account_id = new AccountIdTypeClass($selected_account_int);
            $amount = new ValueTypeClass($amount_int);
            $receiver_name = new NameTypeClass($receiver_name_str);
            $lock_address = new TextTypeClass($receiver_email_str);
            $expire_seconds = $expire_days * 24 * 3600;
            $escrow_seconds = 0;
            $memo = new TextTypeClass($memo_str);

            $cheque = $user_handler->IssueCheque($account_id, $amount, $expire_seconds, $escrow_seconds, $receiver_name, $lock_address, $memo);
            $cheque_id_str = $cheque->GetChequeId()->GetString();
            $access_code_str = $cheque->GetAccessCode()->GetString();

            $png_url = site_url() . '/wp-admin/admin-ajax.php?action=bcf_bitcoinbank_get_cheque_png&cheque_no='. $cheque_id_str . '&access_code=' . $access_code_str;

            $output = '<a href="'.$png_url.'"><img src="'. $png_url . '" height="300" width="800" alt="Loading cheque image..."/></a>';
            $output .= '<br>';
            $output .= '<h3>Send cheque to the receiver</h3>';
            $output .= '<b>Let us send the cheque by e-mail</b>';

            $output .= '<form name="bcf_withdraw_form">';
            $output .= '<table style="border-style:none;" width="100%"><tr>';
            $output .= '<tr>';
            $output .= '<td style="border-style:none;">Send to email * :</td><td style="border-style:none;"><input type="text" value="'.$receiver_email_str.'" name="send_email" /></td>';
            $output .= '</tr><tr>';
            $output .= '<td style="border-style:none;">Personal message to receiver added in the e-mail:</td><td style="border-style:none;"><textarea rows="4" cols="50" name="message">I send you this cheque. You must follow the link below in order to claim it.</textarea></td>';
            $output .= '</tr><tr>';
            $output .= '<td style="border-style:none;">Copy to you:</td><td style="border-style:none;"><input type="text" value="" name="copy_email" /></td>';
            $output .= '</tr><tr>';
            $output .= '<td style="border-style:none;"></td><td style="border-style:none;"><a href="withdraw"><input type="submit" value="Send e-mail"></td>';
            $output .= '</tr></table>';
            $output .= '<input type="hidden" name="cheque_no" value="'.$cheque_id_str.'">';
            $output .= '<input type="hidden" name="access_code" value="'.$access_code_str.'">';
            $output .= '<input type="hidden" name="receiver_name" value="'.$receiver_name_str.'">';
            $output .= '</form>';
            $output .= '* Required information.';
            $output .= '<br>';
            $output .= '<br>';
            $output .= '<b>Or copy the below and send it yourself</b>';

        }
        else if( !empty($_REQUEST['send_email'])
            and !empty($_REQUEST['cheque_no'])
                and !empty($_REQUEST['access_code']))
        {
            $send_email_str = SanitizeInputText($_REQUEST['send_email']);
            $cheque_id_val = SanitizeInputInteger($_REQUEST['cheque_no']);
            $access_code_str = SanitizeInputText($_REQUEST['access_code']);

            $cheque_id   = new ChequeIdTypeClass($cheque_id_val);
            $access_code = new TextTypeClass($access_code_str);

            $cheque = $user_handler->GetCheque($cheque_id, $access_code);

            if($cheque != null)
            {
                if( ! empty($_REQUEST['message']))
                {
                    $message = SanitizeInputText($_REQUEST['message']);
                }
                else
                {
                    $message = '';
                }

                if( ! empty($_REQUEST['receiver_name_str']))
                {
                    $receiver_name_str = SanitizeInputText($_REQUEST['receiver_name_str']);
                }
                else
                {
                    $receiver_name_str = '';
                }

                $png_url     = site_url() . '/wp-admin/admin-ajax.php?action=bcf_bitcoinbank_get_cheque_png&cheque_no=' . $cheque_id_val . '&access_code=' . $access_code_str;
                $claim_url   = site_url() . '/index.php/claim-cheque/';
                $collect_url = $claim_url . '?cheque_no=' . $cheque_id_val . '&access_code=' . $access_code_str;

                $body = '<p></p><b>Hello';
                if($message)
                {
                    $body .= $receiver_name_str;
                }
                $body .= ',</b></p>';
                if($message)
                {
                    /* Strip out non utf-8 characters */
                    $message = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $message);

                    $body .= '<p>' . $message . '</p>';
                    $body .= '<p>To collect the money click on the cheque picture or copy the link below into your web browser.</p>';
                }
                else
                {
                    $body .= '<p>You have received a Bitcoin Cheque. To collect the money click on the cheque picture or copy the link below into your web browser.</p>';
                }

                $body .= '<p><a href="' . $collect_url . '"><img src="' . $png_url . '" height="300" width="800" alt="Loading cheque image..."/></a></p>';

                $body .= '<p><a href="' . $collect_url . '">' . $collect_url . '</a></p>';

                $body .= '<p>Or you can click on this link and enter the Cheque No. and Access Code:</p>';
                $body .= '<p><a href="' . $claim_url . '">' . $claim_url . '</a></p>';
                $body .= 'Cheque No.: ' . $cheque_id_val;
                $body .= '<br>Access Code.: ' . $access_code_str;

                $body .= '<p>This Bitcoin Cheque has been issued by</p>';

                $body .= '<p><b>What is Bitcoin?</b><br>Bitcoin is a consensus network that enables a new payment system and a completely digital money. It is the first decentralized peer-to-peer payment network that is powered by its users with no central authority or middlemen. From a user perspective, Bitcoin is pretty much like cash for the Internet.</p>';
                $body .= '<p><b>What is Bitcoin Cheques?</b><br>A Bitcoin Cheque is a new method for sending Bitcoins. The Bitcoin Cheque is a promiss that the issuing bank will pay a certain amount to a receiver. You can read more about Bitcoin Cheque here at <a href="http://www.bitcoincheque.org">www.bitcoincheque.org</a></p>';

                $subject = 'You have received a Bitcoin Cheque';

                $headers = array('Content-Type: text/html; charset=UTF-8');

                if(wp_mail($send_email_str, $subject, $body, $headers))
                {
                    $output = 'E-mail successfully sent to ' . $send_email_str;
                }
                else
                {
                    $output = 'Error. Could not send e-mail.';
                }
            }
            else
            {
                $output = 'Error. Invalid cheque data.';
            }
        }
        else
        {
            $account_selected = null;

            $account_data_list = $user_handler->GetAccountInfoListCurrentUser();

            $output = '<form name="bcf_withdraw_form">';
            $output .= '<table style="border-style:none;" width="100%"><tr>';
            $output .= '<td style="border-style:none;" width="30%">From my account:</td>';

            $output .= '<td style="border-style:none;">';

            $output .= MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected, $currency);

            $output .= '</td>';

            $output .= '</tr><tr>';
            $output .= '<td style="border-style:none;">Name of receiver:</td><td style="border-style:none;"><input type="text" value="" id="bcf_bitcoinbank_deposit_account" name="receiver_name" /></td>';
            $output .= '</tr><tr>';
            $output .= '<td style="border-style:none;">Receiver\'s e-mail:</td><td style="border-style:none;"><input type="text" id="bcf_bitcoinbank_withdraw_amount" name="receiver_email" /></td>';
            $output .= '</tr><tr>';
            $output .= '<td style="border-style:none;">Amount * :</td><td style="border-style:none;"><input type="text" id="bcf_bitcoinbank_withdraw_amount" name="amount" /></td>';
            $output .= '</tr><tr>';
            $output .= '<td style="border-style:none;">Expired in days * :</td><td style="border-style:none;"><input type="text" id="bcf_bitcoinbank_withdraw_amount" name="expired_days" value="2"/></td>';
            $output .= '</tr><tr>';
            $output .= '<td style="border-style:none;">Memo:</td><td style="border-style:none;"><input type="text" id="bcf_bitcoinbank_withdraw_amount" name="memo" /></td>';
            $output .= '</tr><tr>';
            $output .= '<td style="border-style:none;"></td><td style="border-style:none;"><a href="withdraw"><input type="submit" value="Create cheque"/></a></td>';
            $output .= '</tr></table>';
            $output .= '</form>';
            $output .= '* Required information.';
            $output .= '<br>';
        }

    }
    else
    {
        $output = 'You must be logged in to draw cheques.';
    }

    return $output;
}

function ClaimCheque()
{
    if( !empty($_REQUEST['cheque_no'])
    and !empty($_REQUEST['access_code']))
    {
        $cheque_id_val = SanitizeInputInteger($_REQUEST['cheque_no']);
        $access_code_str = SanitizeInputText($_REQUEST['access_code']);

        $png_url = site_url() . '/wp-admin/admin-ajax.php?action=bcf_bitcoinbank_get_cheque_png&cheque_no='. strval($cheque_id_val) . '&access_code=' . $access_code_str;

        $output = '<a href="'.$png_url.'"><img src="'. $png_url . '" height="300" width="800" alt="Loading cheque image..."/></a>';
        $output .= '<p>';

        $cheque_id   = new ChequeIdTypeClass($cheque_id_val);
        $access_code = new TextTypeClass($access_code_str);

        $user_handler = new UserHandlerClass();
        $cheque = $user_handler->GetCheque($cheque_id, $access_code);

        if($cheque != null)
        {
            $cheque_state = $cheque->GetChequeState();

            if($cheque_state->GetString() == 'UNCLAIMED')
            {
                $locked_address = $cheque->GetReceiverWallet()->GetString();
                $bitcoin_address_readonly = '';
                if(preg_match('/[@]/', $locked_address))
                {
                    /* Lock address is an e-mail */
                    $lock_type ='email';
                    $lock_email = $locked_address;
                    $lock_bitcoin_address = '';
                }
                else
                {
                    /* Lock address is wallet address */
                    $lock_type ='bitcoin_address';
                    $lock_bitcoin_address          = $locked_address;
                    $bitcoin_address_readonly = ' readonly="readonly"';
                }

                if( empty($_REQUEST['bitcoin_address']))
                {
                    $output .= '<h3>Status: This check is unclaimed</h3>';

                    if($lock_type == 'bitcoin_address')
                    {
                        $output .= 'This cheque drawer has locked this cheque to a specific bitcoin wallet address. You are not allowed to change this address.';
                    }
                    elseif(($lock_type == 'email'))
                    {
                        $output .= 'Enter the bitcoin address to transfere money to.';
                    }
                    $output .= '<form name="bcf_profile_form">';
                    $output .= '<table style="border-style:none;" width="100%"><tr>';
                    $output .= '<td style="border-style:none;">Bitcoin Address:</td><td style="border-style:none;"><input type="text" value="' . $lock_bitcoin_address . '"' . $bitcoin_address_readonly . ' name="bitcoin_address"/></td>';
                    $output .= '</tr><tr>';
                    $output .= '<td style="border-style:none;"></td><td style="border-style:none;"><a href=""><input type="submit" value="Collect money"/></a></td>';
                    $output .= '</tr></table>';
                    $output .= '<input type="hidden" name="cheque_no" value="' . $cheque_id_val . '">';
                    $output .= '<input type="hidden" name="access_code" value="' . $access_code_str . '">';
                    $output .= '</form>';
                }
                else
                {
                    if( empty($_REQUEST['confirm']))
                    {
                        $bitcoin_address_str = SanitizeInputText($_REQUEST['bitcoin_address']);

                        if($lock_type == 'bitcoin_address')
                        {
                            if($user_handler->ClaimCheque($cheque_id, $access_code) == true)
                            {
                                $output .= '<h3>Cashing ok</h3>';
                                $output .= 'Cheque money sent to ' . $lock_bitcoin_address;
                            }
                            else
                            {
                                $output .= '<h3>Cashing error</h3>';
                                $output .= 'An error occured when collecting money.';
                            }
                        }
                        elseif(($lock_type == 'email'))
                        {
                            $lock_email_confirmed = false;
                            if(is_user_logged_in())
                            {
                                $current_user = wp_get_current_user();
                                if($current_user->user_email == $lock_email)
                                {
                                    $lock_email_confirmed = true;
                                }
                            }

                            if($lock_email_confirmed)
                            {
                                if($user_handler->ClaimCheque($cheque_id, $access_code) == true)
                                {
                                    $output .= '<h3>Cashing ok</h3>';
                                    $output .= 'Cheque money sent to ' . $bitcoin_address_str;
                                }
                                else
                                {
                                    $output .= '<h3>Cashing error</h3>';
                                    $output .= 'An error occured when collecting money.';
                                }
                            }
                            else
                            {
                                $output .= '<h3>Cashing on hold - confirm your e-mail address</h3>';
                                $output .= '<p>This cheque can only been claimd by owner of e-mail address ' . $lock_email . '</p>';

                                $claim_url = site_url() . '/index.php/claim-cheque/?cheque_no=' . $cheque_id_val . '&access_code=' . $access_code_str . '&bitcoin_address='. $bitcoin_address_str .'&confirm=1';

                                $body = '<p></p><b>Hello';
                                $body .= '<p>You must confirm your e-mail address by clicking this link:</p>';
                                $body .= '<p><a href="' . $claim_url . '">' . $claim_url . '</a></p>';

                                $subject = 'Confirm your e-mail';

                                $headers = array('Content-Type: text/html; charset=UTF-8');

                                if(wp_mail($lock_email, $subject, $body, $headers))
                                {
                                    $output .= '<p>An e-mail with an confirm link has been sent to this address. Open that e-mail and click the confirm link in order to transfere the money.</p>';
                                }
                                else
                                {
                                    $output .= 'Error. Could not send e-mail.';
                                }

                            }
                        }
                    }
                    else
                    {
                        $bitcoin_address_str = SanitizeInputText($_REQUEST['bitcoin_address']);
                        $confirm_str = SanitizeInputText($_REQUEST['confirm']);

                        if($user_handler->ClaimCheque($cheque_id, $access_code) == true)
                        {
                            $output .= '<h3>Cashing ok</h3>';
                            $output .= '<p>E-mail has been confirmed</p>';
                            $output .= '<p>Cheque money sent to ' . $bitcoin_address_str . '</p>';
                        }
                        else
                        {
                            $output .= '<h3>Cashing error</h3>';
                            $output .= 'An error occured when collecting money.';
                        }

                    }
                }
            }
            else
            {
                $output .= '<h3>Status: Claimed</h3>';

                $output .= 'This check has been claimed and its money has been collected.';
            }
        }
    }
    else
    {
        $output = '<h3>Enter cheque details</h3>';

        $output = '<form name="bcf_profile_form">';
        $output .= '<table style="border-style:none;" width="100%"><tr>';
        $output .= '<td style="border-style:none;">Cheque No.:</td><td style="border-style:none;"><input type="text" value="" name="cheque_no"/></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;">Access Code:</td><td style="border-style:none;"><input type="text" value="" name="access_code" /></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;"></td><td style="border-style:none;"><a href=""><input type="submit" value="Claim cheque"/></a></td>';
        $output .= '</tr></table>';
        $output .= '</form>';
    }

    return $output;
}

function ProcessAjaxCreatePngCheque()
{
    $currency = 'BTC';

    if ( ! empty( $_REQUEST['cheque_no'] ) )
    {
        $cheque_id_val = SanitizeInputInteger($_REQUEST['cheque_no']);
        $access_code_val = SanitizeInputText($_REQUEST['access_code']);

        $cheque_id = new ChequeIdTypeClass($cheque_id_val);
        $access_code = new TextTypeClass($access_code_val);

        $user_handler = new UserHandlerClass();
        $cheque = $user_handler->GetCheque($cheque_id, $access_code);

        if(!is_null($cheque))
        {
            header("Content-type: image/png");

            $filename = plugin_dir_path(__FILE__) . 'bank_logo.png';

            $filename = plugin_dir_path(__FILE__) . 'cheque_template2.png';

            $im = imagecreatefrompng($filename);

            $black = imagecolorallocate($im, 0, 0, 0);

            //imagestring($im, 10, 20, 20, 'Bitcoin Demo Bank', $black);
            //imagestring($im, 10, 20, 40, 'www.bitcoindemobank.com', $black);

            imagestring($im, 10, 20, 100, 'Pay to : ' . $cheque->GetReceiverName()->GetString(), $black);
            imagestring($im, 10, 20, 120, 'Locked : ' . $cheque->GetReceiverWallet()->GetString(), $black);
            imagestring($im, 10, 20, 160, 'Paid by: ' . $cheque->GetUserName()->GetString(), $black);
            imagestring($im, 10, 20, 140, 'Memo   : ' . $cheque->GetReceiverReference()->GetString(), $black);

            imagestring($im, 10, 520, 125, $cheque->GetValue()->GetFormattedCurrencyString($currency, true), $black);

            imagestring($im, 10, 490, 170, 'Issue  date: ' . $cheque->GetIssueDateTime()->GetString(), $black);
            imagestring($im, 10, 490, 190, 'Expire date: ' . $cheque->GetExpireDateTime()->GetString(), $black);
            imagestring($im, 10, 490, 210, 'Escrow date: ' . $cheque->GetEscrowDateTime()->GetString(), $black);

            imagestring($im, 10, 20, 275, 'Cheque No.:' . $cheque_id_val . '  Access Code:' . $access_code->GetString() . '  Hash:78fhjrf7y49rfherf67y', $black);

            imagepng($im);

            imagedestroy($im);
        }
        else
        {
            echo 'Invalid cheque';
        }
    }
}

function UserProfile()
{
    if (is_user_logged_in())
    {
        $save_result = false;
        $data_has_been_saved = false;
        $user_handler = new UserHandlerClass();

        if( isset($_REQUEST['full_name'])
        and isset($_REQUEST['country'])
        )
        {
            $name = $_REQUEST['full_name'];
            $country = $_REQUEST['country'];

            $save_result = $user_handler->SetCurrentUserData($name, $country);
            $data_has_been_saved = true;
        }
        else
        {
            $user_data = $user_handler->GetCurrentUserData();
            $name = $user_data->GetName()->GetString();
            $country = $user_data->GetCountry()->GetString();
        }

        $current_user = wp_get_current_user();

        $output = '<form name="bcf_profile_form">';
        $output .= '<table style="border-style:none;" width="100%"><tr>';
        $output .= '<td style="border-style:none;">Login username:</td><td style="border-style:none;"><input type="text" value="' .  $current_user->user_login . '" readonly="readonly" /></td><td style="border-style:none;"><i>Cannot be changed.</i></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;">Login username:</td><td style="border-style:none;"><input type="text" value="' .  $current_user->user_email . '" readonly="readonly" /></td><td style="border-style:none;"><i>Cannot be changed.</i></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;">Name:</td><td style="border-style:none;"><input type="text" value="' . $name . '" name="full_name" /></td><td style="border-style:none;"><i>Name will be included in cheques in the Paid By field.</i></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;">Country:</td><td style="border-style:none;"><input type="text" value="' . $country . '" name="country" /></td><td style="border-style:none;"><i>Optional.</i></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;"></td><td style="border-style:none;"><a href=""><input type="submit" value="Save"/></a></td><td style="border-style:none;"></td>';
        $output .= '</tr></table>';
        $output .= '</form>';

        $output .= '<br>';

        if($data_has_been_saved)
        {
            if($save_result)
            {
                $output .= '<font color="green"><b>User data successfully saved.</b></font>';
            }
            else
            {
                $output .= '<font color="red">><b>Error saving user data.</b></font>';
            }
        }
    }
    else
    {
        $output = 'You must log in to access profile info.';
    }

    return $output;
}

function Payment()
{
    $output = '';

    if(!empty($_REQUEST['request']))
    {
        $request_link = SanitizeInputText($_REQUEST['request']);

        if($request_link != '')
        {
            $api_response = wp_remote_get( $request_link );
            $payment_request = '';

            if(wp_remote_retrieve_response_code($api_response) == 200)
            {
                $payment_request_json = wp_remote_retrieve_body( $api_response );

                if(empty($payment_request_json))
                {
                    echo 'JSON object empty<br>';
                }
                else
                {
                    $payment_request = json_decode($payment_request_json, true);
                    if($payment_request)
                    {
                    }
                }
            }

            if(is_user_logged_in())
            {
                $output .= 'A payment has been requested.';
                $amount = SanitizeInputInteger($payment_request['amount']);
                $currency = SanitizeInputInteger($payment_request['currency']);

                $formated_currency = GetFormattedCurrency($amount, $payment_request['currency'], true, ',');

                $user_handler = new UserHandlerClass();
                $account_data_list = $user_handler->GetAccountInfoListCurrentUser();

                $output .= '<form name="bcf_withdraw_form">';
                $output .= '<table style="border-style:none;" width="100%"><tr>';
                $output .= '<td style="border-style:none;" width="30%">From my account:</td>';

                $output .= '<td style="border-style:none;">';

                $account_selected = null;
                $output .= MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected, $currency);

                $output .= '</td>';

                $output .= '</tr><tr>';
                $output .= '<td style="border-style:none;">Pay to:</td><td style="border-style:none;"><input type="text" value="' . $payment_request['receiver_name'] . '" name="receiver_name" /></td>';
                $output .= '</tr><tr>';
                $output .= '<td style="border-style:none;">Amount:</td><td style="border-style:none;"><input type="text" value="'.$formated_currency.'"/></td>';
                $output .= '</tr><tr>';
                $output .= '<td style="border-style:none;">Description:</td><td style="border-style:none;"><input type="text" value="'.$payment_request['description'].'" name="memo" /></td>';
                $output .= '</tr><tr>';
                $output .= '<td style="border-style:none;"></td><td style="border-style:none;"><a href="withdraw"><input type="submit" value="Make payment"/></a></td>';
                $output .= '</tr></table>';
                $output .= '<input type="hidden" name="lock" value="'.$payment_request['receiver_wallet'].'">';
                $output .= '<input type="hidden" name="amount" value="'.$payment_request['amount'].'">';
                $output .= '<input type="hidden" name="receiver_reference" value="'.$payment_request['ref'].'">';
                $output .= '<input type="hidden" name="paylink" value="'.$payment_request['paylink'].'">';
                $output .= '</form>';

                $output .= '<br>';

            }
            else
            {
                $output .= '<p>You must log in to make the payment.</p>';
                $output .= '<p>If you don\'t have an account, please register first.</p>';
                $output .= '<p>After log-in you will need to return to this page and refresh the it.</p>';
            }

            $output .= '<br>';
            $output .= '<br>';
            $output .= '<br>';
            $output .= '<hr>';
            $output .= '<h3>Developer\'s details</h3>';
            $output .= '<p>Payment request link: ' . $request_link . '</p>';
            $output .= '<p>Payment request json: ' . $payment_request_json . '</p>';

        }
        else
        {
            $output .= 'No payment request in url found.';
        }
    }
    elseif( !empty($_REQUEST['select_account'])
        and !empty ($_REQUEST['amount'])
        and !empty ($_REQUEST['receiver_name'])
        and !empty ($_REQUEST['lock'])
        and !empty ($_REQUEST['amount'])
        and !empty ($_REQUEST['receiver_reference'])
        and !empty ($_REQUEST['paylink']))
    {
        $select_account_val = SanitizeInputInteger($_REQUEST['select_account']);
        $amount_val = SanitizeInputInteger($_REQUEST['amount']);
        $receiver_name_str = SanitizeInputText($_REQUEST['receiver_name']);
        $lock_addr_str = SanitizeInputText($_REQUEST['lock']);
        $receiver_reference_str = SanitizeInputText($_REQUEST['receiver_reference']);
        $paylink_str = SanitizeInputText($_REQUEST['paylink']);
        $expire_days = 2;

        if(is_user_logged_in())
        {
            $account_id = new AccountIdTypeClass($select_account_val);
            $amount = new ValueTypeClass($amount_val);
            $receiver_name = new NameTypeClass($receiver_name_str);
            $lock_address = new TextTypeClass($lock_addr_str);
            $expire_seconds = $expire_days * 24 * 3600;
            $escrow_seconds = 0;
            $receiver_reference = new TextTypeClass($receiver_reference_str);

            $user_handler = new UserHandlerClass();
            $cheque = $user_handler->IssueCheque($account_id, $amount, $expire_seconds, $escrow_seconds, $receiver_name, $lock_address, $receiver_reference);

            if($cheque != null)
            {
                $cheque_id_str   = $cheque->GetChequeId()->GetString();
                $access_code_str = $cheque->GetAccessCode()->GetString();

                $cheque_base64 = $cheque->GetBase64();
                //$cheque_json_encoded = rawurlencode($cheque_json);
                $cheque_base64_url_encoded = urlencode ($cheque_base64);

                $api_url = $paylink_str . '&cheque=' . $cheque_base64_url_encoded;

                $api_response = wp_remote_get( $api_url );
                if(wp_remote_retrieve_response_code($api_response) == 200)
                {
                    $json = wp_remote_retrieve_body( $api_response );

                    if(empty($json))
                    {
                        $output .= 'Error: JSON object empty.<br>';
                    }
                    else
                    {
                        $json_decoded = json_decode($json, true);
                        if($json_decoded)
                        {
                            $return_link = $json_decoded['return_link'];

                            $output .= '<p>Payment OK.</p>';
                            $output .= '<a href="'.$return_link.'"><input type="submit" value="Return to site"/></a>';
                        }
                        else
                        {
                            $output .= 'Error: Can not decode JSON object.<br>';

                        }

                        $output .= '<br><br><h3>Advanced information:</h3>';
                        $output .= 'Get request with cheque:<br> ' . $api_url . '<br><br>';
                        $output .= 'Response back from site:<br> '. $json . '<br>';
                    }
                }

            }
            else
            {
                $output .= 'Payment error.';
            }
        }
        else
        {
            $output .= 'Error. Not logged in.';
        }
    }
    else
    {
        $output .= 'Here you make payments.';
    }

    return $output;
}


function ActivatePlugin()
{
    /* To let it be recreated */
    delete_option( BCF_BITCOINBANK_ADMIN_USER_ID );
    delete_option( BCF_BITCOINBANK_CHEQUE_ESCROW_ACCOUNT_ID );

    DB_CreateOrUpdateDatabaseTables();
    
    $user_handler = new UserHandlerClass();
    $user_handler->CreateAdminBankUser();
}

function DeactivatePlugin()
{
}

/* Add AJAX handlers */
// Note! Need to have both actions below, "nopriv" and other! Otherwise wordpress will not return json object.

// Can be tested i browser: /wp-admin/admin-ajax.php?action=bcf_bitcoinbank_process_ajax_request_cheque&account=3&amount=762&passwd=abc123&ref=47
add_action('wp_ajax_nopriv_bcf_bitcoinbank_process_ajax_request_cheque', 'BCF_BitcoinBank\ProcessAjaxRequestCheque');
add_action('wp_ajax_bcf_bitcoinbank_process_ajax_request_cheque', 'BCF_BitcoinBank\ProcessAjaxRequestCheque');

add_action('wp_ajax_nopriv_bcf_bitcoinbank_process_ajax_validate_cheque', 'BCF_BitcoinBank\ProcessAjaxValidateCheque');
add_action('wp_ajax_bcf_bitcoinbank_process_ajax_validate_cheque', 'BCF_BitcoinBank\ProcessAjaxValidateCheque');

// Can be tested i browser: /wp-admin/admin-ajax.php?action=bcf_bitcoinbank_get_cheque_png
add_action('wp_ajax_nopriv_bcf_bitcoinbank_get_cheque_png', 'BCF_BitcoinBank\ProcessAjaxCreatePngCheque');
add_action('wp_ajax_bcf_bitcoinbank_get_cheque_png', 'BCF_BitcoinBank\ProcessAjaxCreatePngCheque');

add_action('wp_ajax_nopriv_get_account_list', 'BCF_BitcoinBank\ProcessAjaxGetAccountList');
add_action('wp_ajax_get_account_list', 'BCF_BitcoinBank\ProcessAjaxGetAccountList');

add_action('wp_ajax_nopriv_get_account_details', 'BCF_BitcoinBank\ProcessAjaxGetAccountDetails');
add_action('wp_ajax_get_account_details', 'BCF_BitcoinBank\ProcessAjaxGetAccountDetails');

add_action('wp_ajax_nopriv_get_transactions', 'BCF_BitcoinBank\ProcessAjaxGetTransactionList');
add_action('wp_ajax_get_transactions', 'BCF_BitcoinBank\ProcessAjaxGetTransactionList');

/* Add shortcodes */
add_shortcode('bcf_bitcoinbank_list_user_transactions', 'BCF_BitcoinBank\ListUserTransactions');
add_shortcode('bcf_bitcoinbank_withdraw', 'BCF_BitcoinBank\Withdraw');
add_shortcode('bcf_bitcoinbank_list_user_cheques', 'BCF_BitcoinBank\ListUserCheques');
add_shortcode('bcf_bitcoinbank_cheque_details', 'BCF_BitcoinBank\ChequeDetails');
add_shortcode('bcf_bitcoinbank_draw_cheque', 'BCF_BitcoinBank\DrawCheque');
add_shortcode('bcf_bitcoinbank_claim_cheque', 'BCF_BitcoinBank\ClaimCheque');
add_shortcode('bcf_bitcoinbank_profile', 'BCF_BitcoinBank\UserProfile');
add_shortcode('bcf_bitcoinbank_payment', 'BCF_BitcoinBank\Payment');

register_activation_hook(__FILE__, 'BCF_BitcoinBank\ActivatePlugin');
register_deactivation_hook(__FILE__, 'BCF_BitcoinBank\DeactivatePlugin');
