<?php
/**
 * Bitcoin Bank user interface class.
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

require_once('user_handler.php');
require_once('cheque_handler.php');
require_once('html_table.php');
require_once('payment_data_codec.php');

require_once('email_cheque.php');


class UserInterface
{
    private function DrawPaymentForm($cheque_request)
    {
        $output = '';

        $amount = SanitizeInputInteger($cheque_request['amount']);
        $currency = SanitizeInputInteger($cheque_request['currency']);

        $formated_currency = GetFormattedCurrency($amount, $cheque_request['currency'], false, ',');

        $user_handler = new UserHandlerClass();
        $account_data_list = $user_handler->GetAccountInfoListCurrentUser();

        $output .= '<form name="bcf_withdraw_form">';
        $output .= '<table style="border-style:none;" width="100%"><tr>';
        $output .= '<td style="border-style:none;" width="20%">From my account:</td>';

        $output .= '<td style="border-style:none;" width="50%">';

        $account_selected = null;
        $output .= $this->MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected, $currency);

        $output .= '</td><td style="border-style:none;"></td>';

        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;">Pay to:</td><td style="border-style:none;"><input type="text" style="width:100%;" value="' . $cheque_request['receiver_name'] . '" name="receiver_name" /></td><td style="border-style:none;"></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;">Amount:</td><td style="border-style:none;"><input type="text" style="width:100%;" name="amount" value="' . $formated_currency . '"/></td><td style="border-style:none;">' . $cheque_request['currency'] . '</td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;">Description:</td><td style="border-style:none;"><input type="text" style="width:100%;" value="' . $cheque_request['description'] . '" name="memo" /></td><td style="border-style:none;"></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;"></td><td style="border-style:none;"><a href="withdraw"><input type="submit" value="Make payment"/></a></td><td style="border-style:none;"></td>';
        $output .= '</tr></table>';
        $output .= '<input type="hidden" name="lock" value="'.$cheque_request['receiver_wallet'].'">';
        $output .= '<input type="hidden" name="receiver_reference" value="'.$cheque_request['ref'].'">';
        $output .= '<input type="hidden" name="currency" value="'.$cheque_request['currency'].'">';
        $output .= '<input type="hidden" name="paylink" value="'.$cheque_request['paylink'].'">';
        $output .= '</form>';

        return $output;
    }

    private function MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected)
    {
        $html = '<select name="select_account">';
        foreach ( $account_data_list as $account_data )
        {
            $listed_account_id      = $account_data->GetAccountId();
            $account_id_str         = $listed_account_id->GetString();
            $account_id_formatedstr = $listed_account_id->GetFormatedString();
            $account_name           = $account_data->GetAccountName();
            $account_name_str       = $account_name->GetString();
            $currency               = $account_data->GetCurrency();
            $currency_str           = $currency->GetString();
            $balance = $user_handler->GetUsersAccountBalance($listed_account_id);
            $account_balance_str    = GetFormattedCurrency($balance->GetInt(), $currency_str, true );

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

    private function MakeHtmlFormSelectAccount($user_handler, $account_data_list, $account_selected)
    {

        $html = '<form name="bcf_withdraw_form">';
        $html .= '<table style="border-style:none;" width="100%"><tr>';
        $html .= '<td style="border-style:none;" width="30%">My accounts:</td>';

        $html .= '<td style="border-style:none;">';

        $html .= $this->MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected);

        $html .= '</td>';

        $html .= '<td style="border-style:none;"><a href="transactions"><input type="submit" value="Select"/></a></td>';
        $html .= '</tr></table>';
        $html .= '</form>';

        return $html;
    }

    private function ConvertFloatToIntCurrency($amount_float, $currency_unit)
    {
        switch($currency_unit)
        {
            case 'uBTC': $unit_size = 100; break;
            case 'mBTC': $unit_size = 100000; break;
            case 'BTC': $unit_size  = 100000000; break;
            case 'TestBTC': $unit_size  = 100000000; break;
            default:
                die();
        }

        $amount_float = str_replace(',', '.', $amount_float);
        $amount_float = floatval($amount_float);
        $amount_float *= $unit_size;
        $amount_int = intval($amount_float);
        return $amount_int;
    }

    private function DrawChequePng($cheque_id, $access_code)
    {
        $png_url = site_url() . '/wp-admin/admin-ajax.php?action=bcf_bitcoinbank_get_cheque_png&cheque_no=' . strval($cheque_id) . '&access_code=' . $access_code;
        $output  = '<a href="' . $png_url . '"><img src="' . $png_url . '" height="300" width="800" alt="Loading cheque image..."/></a>';

        return $output;
    }

    public function DrawCheque($input_data)
    {
        if (is_user_logged_in())
        {
            if(is_null($input_data['state']))
            {
                /* No known state, show Draw Cheque page */
                $account_selected = null;

                $user_handler = new UserHandlerClass();
                $account_data_list = $user_handler->GetAccountInfoListCurrentUser();

                $output = '<form name="bcf_withdraw_form">';
                $output .= '<table style="border-style:none;" width="100%"><tr>';
                $output .= '<td style="border-style:none;" width="30%">From my account:</td>';

                $output .= '<td style="border-style:none;">';

                $output .= $this->MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected);

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
                $output .= '<input type="hidden" name="state" value="1">';
                $output .= '</form>';
                $output .= '* Required information.';
                $output .= '<br>';
            }
            else
            {
                if($input_data['state'] == 1)
                {
                    /* Show a send e-mail form */
                    $user_handler = new UserHandlerClass();

                    $selected_account_int = SanitizeInputInteger($input_data['select_account']);

                    $account_id = new AccountIdTypeClass($selected_account_int);
                    $acount_data = $user_handler->GetAccountData($account_id);
                    $currency = $acount_data->GetCurrency()->GetString();

                    if(!is_null($input_data['receiver_name']))
                    {
                        $receiver_name_str = SanitizeInputText($input_data['receiver_name']);
                    }
                    else
                    {
                        $receiver_name_str = '';
                    }

                    if(!is_null($input_data['receiver_email']))
                    {
                        $receiver_email_str = SanitizeInputText($input_data['receiver_email']);
                    }
                    else
                    {
                        $receiver_email_str = '';
                    }

                    $amount_float = SanitizeInputText($input_data['amount']);

                    $amount_int = $this->ConvertFloatToIntCurrency($amount_float, $currency);

                    $expire_days = SanitizeInputInteger($input_data['expired_days']);

                    if(!is_null($input_data['memo']))
                    {
                        $memo_str = SanitizeInputText($input_data['memo']);
                    }
                    else
                    {
                        $memo_str = '';
                    }


                    $amount         = new ValueTypeClass($amount_int);
                    $receiver_name  = new NameTypeClass($receiver_name_str);
                    $lock_address   = new TextTypeClass($receiver_email_str);
                    $expire_seconds = $expire_days * 24 * 3600;
                    $escrow_seconds = 0;
                    $memo           = new TextTypeClass($memo_str);

                    $cheque          = $user_handler->IssueCheque($account_id, $amount, $expire_seconds, $escrow_seconds, $receiver_name, $lock_address, $memo);
                    $cheque_id_str   = $cheque->GetChequeId()->GetString();
                    $access_code_str = $cheque->GetAccessCode()->GetString();

                    $png_url = site_url() . '/wp-admin/admin-ajax.php?action=bcf_bitcoinbank_get_cheque_png&cheque_no=' . $cheque_id_str . '&access_code=' . $access_code_str;

                    $output = '<a href="' . $png_url . '"><img src="' . $png_url . '" height="300" width="800" alt="Loading cheque image..."/></a>';
                    $output .= '<br>';
                    $output .= '<h3>Send cheque to the receiver</h3>';
                    $output .= '<b>Let us send the cheque by e-mail</b>';

                    $output .= '<form name="bcf_withdraw_form">';
                    $output .= '<table style="border-style:none;" width="100%"><tr>';
                    $output .= '<tr>';
                    $output .= '<td style="border-style:none;">Send to email * :</td><td style="border-style:none;"><input type="text" value="' . $receiver_email_str . '" name="send_email" /></td>';
                    $output .= '</tr><tr>';
                    $output .= '<td style="border-style:none;">Personal message to receiver added in the e-mail:</td><td style="border-style:none;"><textarea rows="4" cols="50" name="message">I send you this cheque. You must follow the link below in order to claim it.</textarea></td>';
                    $output .= '</tr><tr>';
                    $output .= '<td style="border-style:none;">Copy to you:</td><td style="border-style:none;"><input type="text" value="" name="copy_email" /></td>';
                    $output .= '</tr><tr>';
                    $output .= '<td style="border-style:none;"></td><td style="border-style:none;"><a href="withdraw"><input type="submit" value="Send e-mail"></td>';
                    $output .= '</tr></table>';
                    $output .= '<input type="hidden" name="cheque_no" value="' . $cheque_id_str . '">';
                    $output .= '<input type="hidden" name="access_code" value="' . $access_code_str . '">';
                    $output .= '<input type="hidden" name="receiver_name" value="' . $receiver_name_str . '">';
                    $output .= '<input type="hidden" name="state" value="2">';
                    $output .= '</form>';
                    $output .= '* Required information.';
                    $output .= '<br>';
                    $output .= '<br>';
                    $output .= '<h3>Or copy the below and send it yourself</h3>';

                    $cheque_data = $cheque->GetDataArray(true);
                    $cheque_file = EncodeAndSignBitcoinCheque($cheque_data);

                    $output .= '<textarea name="payment_request" rows="10" style="width:100%;">' . $cheque_file . '</textarea>';
                }
                elseif($input_data['state'] == 2)
                {
                    if(!is_null($input_data['send_email'])
                       and (!is_null($input_data['cheque_no']))
                           and (!is_null($input_data['access_code'])))
                    {
                        $send_email_str  = SanitizeInputText($input_data['send_email']);
                        $cheque_id_val   = SanitizeInputInteger($input_data['cheque_no']);
                        $access_code_str = SanitizeInputText($input_data['access_code']);

                        $cheque_id   = new ChequeIdTypeClass($cheque_id_val);
                        $access_code = new TextTypeClass($access_code_str);

                        $user_handler = new UserHandlerClass();

                        $cheque = $user_handler->GetCheque($cheque_id, $access_code);

                        if($cheque != null)
                        {
                            $email = new EmailCheque($send_email_str, $cheque_id_val, $access_code_str);

                            if(!is_null($input_data['receiver_name']))
                            {
                                $receiver_name = SanitizeInputText($input_data['receiver_name']);
                                $email->SetReceiverName($receiver_name);
                            }

                            if(!is_null($input_data['message']))
                            {
                                $message = SanitizeInputText($input_data['message']);
                                $email->SetMessage($message);
                            }

                            if(!is_null($input_data['copy_email']))
                            {
                                $cc = SanitizeInputText($input_data['copy_email']);
                                $email->AddCopyAddress($cc);
                            }

                            $current_user = wp_get_current_user();
                            $from_email   = $current_user->user_email;
                            $email->SetFromAddress($from_email);

                            if($email->Send())
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
                }
                else
                {
                    $output = 'Errors in parameters.';

                }
            }
        }
        else
        {
            $output = 'You must be logged in to draw cheques.';
        }

        return $output;
    }

    public function DisplayProfile($input_data)
    {
        if (is_user_logged_in())
        {
            $save_result = false;
            $data_has_been_saved = false;
            $user_handler = new UserHandlerClass();

            if(!is_null($input_data['full_name']) and !is_null($input_data['country'])
            )
            {
                $name = $input_data['full_name'];
                $country = $input_data['country'];

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

    public function Withdraw($input_data)
    {
        if(is_user_logged_in())
        {
            if (!is_null( $input_data['select_account'] ) ) {
                $show_account_str = SanitizeInputText( $input_data['select_account'] );
                $account_selected = new AccountIdTypeClass(0);
                $account_selected->SetDataFromString($show_account_str);
            }
            else
            {
                $account_selected = null;
            }

            $user_handler = new UserHandlerClass();

            $withdraw_output = '';
            if(!is_null($input_data['select_account'])) {
                $from_account_id_str = SanitizeInputText( $input_data['select_account'] );
                $to_account_id_str = intval(SanitizeInputText($input_data['depost_account']));
                $amount_str = intval(SanitizeInputText($input_data['amount']));

                $from_account_id = new AccountIdTypeClass(intval($from_account_id_str));
                $to_account_id = new AccountIdTypeClass(intval($to_account_id_str));
                $amount = new ValueTypeClass(intval($amount_str));

                $currency_from_account = $user_handler->GetAccountData($from_account_id)->GetCurrency()->GetString();
                $currency_to_account = $user_handler->GetAccountData($to_account_id)->GetCurrency()->GetString();

                if($currency_from_account == $currency_to_account)
                {
                    $currency = new CurrencyTypeClass($currency_from_account);

                    $transaction_id = $user_handler->MakeTransactionToAccount($from_account_id, $to_account_id, $amount, $currency);
                    if(!is_null($transaction_id))
                    {
                        $withdraw_output .= 'Transfered OK.<br>';
                        $withdraw_output .= GetFormattedCurrency($amount->GetInt(), $currency_from_account, true) . ' sent from account ' . $from_account_id->GetFormatedString() . ' to ' . $to_account_id->GetFormatedString() . '<br>';
                        $my_transaction_id = $transaction_id->GetInt() - 1; // Making a internal transfer creats two transactions records in database. My record was the previous one.
                        $withdraw_output .= '(Transaction ID = ' . $my_transaction_id . ')<br>';
                    }
                    else
                    {
                        $withdraw_output .= 'Transfer ERROR!<br>';
                        $withdraw_output .= 'Unable to send ' . GetFormattedCurrency($amount->GetInt(), $currency_from_account, true) . ' from account ' . $from_account_id->GetFormatedString() . ' to ' . $to_account_id->GetFormatedString() . '<br>';
                        $withdraw_output .= '<br>';
                    }
                }
                else
                {
                    $withdraw_output .= 'ERROR!<br>';
                    $withdraw_output .= 'Different currencies for receiving accounts (' . $currency_to_account . ')';
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

            $output .= $this->MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected);

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

    function Display_TransactionList($input_data)
    {
        if (is_user_logged_in())
        {
            if(!is_null($input_data['select_account']))
            {
                $show_account_str = strval($input_data['select_account']);
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
            $currency = $account_data->GetCurrency()->GetString();

            $html_select_account_form = $this->MakeHtmlFormSelectAccount($user_handler, $account_data_list, $account_selected);

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

    function DisplayChequeDetails($input_data)
    {
        $request_has_cheque_no=false;
        $request_has_access_code=false;
        $request_has_cheque_file=false;

        $cheque_id_val = -1;
        $access_code_str = '';
        $payment_cheque_file_url_encoded = '';

        $show_cheque_png = false;
        $show_cheque_public_details = false;
        $show_cheque_private_details = false;
        $show_cheque_file = false;
        $show_developers_details = false;
        $show_error_message = false;
        $ask_for_cheque_no_and_access_code = false;
        $ask_for_cheque_file = false;

        $error_message = '';

        $output = '';

        if(!is_null($input_data))
        {
            $cheque_id_val = SanitizeInputInteger($input_data['cheque_no']);
            $request_has_cheque_no=true;
        }

        if(!is_null($input_data['access_code']))
        {
            $access_code_str = SanitizeInputText($input_data['access_code']);
            $request_has_access_code=true;
        }

        if(!is_null($input_data['hash']))
        {
            $payment_cheque_file_url_encoded = SanitizeInputText($input_data['hash']);
            $request_has_cheque_file=true;
        }

        if($request_has_cheque_no and $request_has_access_code)
        {
            $cheque_id   = new ChequeIdTypeClass($cheque_id_val);
            $access_code = new TextTypeClass($access_code_str);

            $user_handler = new UserHandlerClass();
            $cheque       = $user_handler->GetCheque($cheque_id, $access_code);

            if($cheque != null)
            {
                $cheque_public_data = $cheque->GetDataArray(true);

                $show_cheque_png = true;
                $show_cheque_public_details = true;
                $show_cheque_file = true;
                //$show_developers_details = true;

                if(is_user_logged_in())
                {
                    //$cheque_private_data = $cheque->GetDataArray(false);
                    //$show_cheque_private_details = true;
                }
            }
            else
            {
                $error_message = 'Invalid Cheque No. or Access Code.';
                $show_error_message = true;

                $ask_for_cheque_no_and_access_code = true;
                $ask_for_cheque_file = true;
            }
        }
        elseif($request_has_cheque_file)
        {
            $payment_cheque_file = html_entity_decode($payment_cheque_file_url_encoded);
            $cheque_public_data = DecodeAndVerifyPaymentFile($payment_cheque_file);

            $cheque_id_val = $cheque_public_data['cheque_id'];
            $access_code_str = $cheque_public_data['access_code'];

            $show_cheque_png = true;
            $show_cheque_public_details = true;
            //$show_cheque_private_details = true;
            //$show_developers_details = true;
        }
        else
        {
            $error_message = 'Invalid cheque request.';
            $show_error_message = true;
            $ask_for_cheque_no_and_access_code = true;
            $ask_for_cheque_file = true;
        }

        if($show_error_message)
        {
            $output .= $error_message;
        }

        if($show_cheque_png)
        {
            $png_url = site_url() . '/wp-admin/admin-ajax.php?action=bcf_bitcoinbank_get_cheque_png&cheque_no=' . strval($cheque_id_val) . '&access_code=' . $access_code_str;
            $output .= '<a href="' . $png_url . '"><img src="' . $png_url . '" height="300" width="800" alt="Loading cheque image..."/></a>';
        }

        if($show_cheque_public_details)
        {
            $html_table = new HtmlTableClass();

            foreach($cheque_public_data as $key => $field)
            {
                if(gettype($field) == 'string')
                {

                }
                elseif(gettype($field) == 'integer')
                {
                    $field = strval($field);
                }
                else
                {
                    die();
                }

                $html_table->AddLineItem($key);
                $html_table->AddLineItem($field);
                $html_table->RowFeed();
            }


            $output .= '<p>';
            $output .= '<h3>Details for Cheque No. ' . strval($cheque_id_val) . '</h3>';
            $output .= '<b>Public data:</b><br>This information is included in cheque and sent to cheque receiver.';
            $output .= $html_table->GetHtmlTable();
        }

        if($show_cheque_private_details)
        {
            $html_table2 = new HtmlTableClass();
            $html_table2->AddLineItem('Account No.:');
            $html_table2->AddLineItem($cheque->GetOwnerAccountId()->GetString());
            $html_table2->RowFeed();

            $html_table2->AddLineItem('Description:');
            $html_table2->AddLineItem($cheque->GetDescription()->GetString());
            $html_table2->RowFeed();

            $output .= '<b>Private data:</b><br>Information not included in the cheque.';
            $output .= $html_table2->GetHtmlTable();
        }

        if($show_cheque_file)
        {
            $output .= '<h3>Bitcoin Cheque File</h3>';
            $output .= 'This is the Bitcoin Cheque File sent to the receiver.';

            $cheque_data = $cheque->GetDataArray(true);
            $cheque_file = EncodeAndSignBitcoinCheque($cheque_data);
            $output .= '<textarea name="cheque" rows="10" style="width:100%;">'.$cheque_file.'</textarea>';
        }


        if($show_developers_details)
        {
            $output .= '<br>';
            $output .= '<hr>';
            $output .= '<h3>Developer\'s details</h3>';
            $output .= '<b>Bitcoin Cheque in JSON format:</b>';
            $filtered_text = SanitizeInputText($cheque->GetJson());
            $html_table4   = new HtmlTableClass();
            $html_table4->AddLineItem($filtered_text, '', 'style="word-wrap:break-word; overflow-wrap:nowrap; hyphens:none;"');
            $html_table4->RowFeed();
            $output .= '<p>';
            $output .= $html_table4->GetHtmlTable('style="table-layout: fixed; width: 100%"');
        }

        if($ask_for_cheque_no_and_access_code)
        {
            $output .= '<h3>Enter cheque details</h3>';
            $output .= 'Please enter Cheque No. and Access Code:';

            $output .= '<form name="bcf_profile_form">';
            $output .= '<table style="border-style:none;" width="100%"><tr>';
            $output .= '<td style="border-style:none;">Cheque No.:</td><td style="border-style:none;"><input type="text" value="" name="cheque_no"/></td>';
            $output .= '</tr><tr>';
            $output .= '<td style="border-style:none;">Access Code:</td><td style="border-style:none;"><input type="text" value="" name="access_code" /></td>';
            $output .= '</tr><tr>';
            $output .= '<td style="border-style:none;"></td><td style="border-style:none;"><a href=""><input type="submit" value="Look up cheque"/></a></td>';
            $output .= '</tr></table>';
            $output .= '</form>';
            $output .= '<br>';
        }

        if($ask_for_cheque_file)
        {
            $output .= '<h3>Paste Bitcoin Cheque File</h3>';
            $output .= '<p>If you have an Bitcoin Cheque File (looking like a random text starting with the "PAYMENT_CHEQUE", you can paste the text in form below:</p>';
            $output .= '<form>';
            $output .= '<textarea name="cheque" rows="10" style="width:100%;"></textarea>';
            $output .= '<br><br>';
            $output .= '<input type="submit" value="Read Bitcoin Cheque File"/>';
            $output .= '</form>';
        }


        return $output;
    }

    public function DisplayChequeList($input_data)
    {
        if (is_user_logged_in())
        {
            if(!is_null($input_data['select_account']))
            {
                $show_account_str = strval($input_data['select_account']);
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
            $currency = $user_handler->GetAccountData($account_selected)->GetCurrency()->GetString();

            $html_select_account_form = $this->MakeHtmlFormSelectAccount($user_handler, $account_data_list, $account_selected);

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


    public function ClaimCheque($input_data)
    {
        if(!is_null($input_data['cheque_no'])
            and !is_null($input_data['access_code']))
        {
            $cheque_id_val = $input_data['cheque_no'];
            $access_code_str = $input_data['access_code'];

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

                    if( is_null($input_data['bitcoin_address']))
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
                        if( is_null($input_data['confirm']))
                        {
                            $bitcoin_address_str = SanitizeInputText($input_data['bitcoin_address']);

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
                            $bitcoin_address_str = SanitizeInputText($input_data['bitcoin_address']);
                            $confirm_str = SanitizeInputText($input_data['confirm']);

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


    public function DisplayPaymentForm($input_data)
    {
        $output = '';

        if(!is_null($input_data['request']))
        {
            $request_link = SanitizeInputText($input_data['request']);

            if($request_link != '')
            {
                $api_response = wp_remote_get( $request_link );
                $cheque_request = array();

                if(wp_remote_retrieve_response_code($api_response) == 200)
                {
                    $payment_request_json = wp_remote_retrieve_body( $api_response );

                    if(is_null($payment_request_json))
                    {
                        echo 'JSON object empty<br>';
                    }
                    else
                    {
                        $payment_request = json_decode($payment_request_json, true);
                        if(!empty($payment_request))
                        {
                            $payment_request_file = $payment_request['payment_request'];

                            $cheque_request = DecodeAndVerifyPaymentFile($payment_request_file);
                        }
                    }
                }

                if(!empty($cheque_request))
                {
                    if(is_user_logged_in())
                    {
                        $amount = SanitizeInputInteger($cheque_request['amount']);
                        $currency = SanitizeInputText($cheque_request['currency']);
                        $receiver_name = SanitizeInputText($cheque_request['receiver_name']);
                        $description = SanitizeInputText($cheque_request['description']);

                        $formated_currency = GetFormattedCurrency($amount, $currency, true, ',');

                        $output .= '<p>A payment has been requested.</p>';
                        $output .= '<p>';
                        $output .= 'Pay to: ' . $receiver_name . '<br>';
                        $output .= 'Description: ' . $description . '<br>';
                        $output .= 'Price: ' . $formated_currency;
                        $output .= '</p>';
                        $output .= '<p> Your have 3 options.</p>';
                        $output .= '<h3>Option 1: Pay now from you bank account</h3>';
                        $output .= '<p>';
                        $output .= $this->DrawPaymentForm($cheque_request);
                        $output .= '</p>';

                    }
                    else
                    {
                        $output .= '<p>A payment has been requested. Your have 3 options:</p>';
                        $output .= '<h3>Option 1: Pay now from you bank account</h3>';
                        $output .= '<p>';
                        $output .= '<p>You must log in to make the payment.</p>';
                        $output .= '<p>If you don\'t have an account, please register first.</p>';
                        $output .= '<p>After log-in you will need to return to this page and refresh the it.</p>';
                        $output .= '</p>';

                    }

                    $output .= '<br>';

                    $output .= '<h3>Option 2: Download the Bitcoin Banking App</h3>';
                    $output .= '<p>The Bitcoin Banking App allow you to make instant one-click payments.<br>';
                    $output .= 'Note! Currently it is only made available for Chrome browser.</p>';
                    $app_image = site_url() . '/wp-content/plugins/bitcoinbank-wordpress-plugin/img/transactions_small.png';
                    $output .= '<p><a href="https://chrome.google.com/webstore/detail/bitcoin-banking-app/pgloifjeoelfeolhficbcmhdfeepceoc" target="_blank"><img src="'.$app_image.'"/></a></p>';
                    $output .= '<p><a href="https://chrome.google.com/webstore/detail/bitcoin-banking-app/pgloifjeoelfeolhficbcmhdfeepceoc" target="_blank">Go to Chrome Marketplace and download Bitcoin Banking App</a></p>';

                    $output .= '<br>';

                    $output .= '<h3>Option 3: Pay from another bank</h3>';
                    $output .= '<p>If you are already using another bank, you can copy the payment request data text below, and paste it in your prefered bank´s payment page:</p>';
                    $output .= '<textarea rows="13" style="width:100%;">' . $payment_request_file . '</textarea>';


                    $output .= '<br>';
                    $output .= '<br>';
                    $output .= '<br>';
                    $output .= '<br>';
                    $output .= '<h5>Developer\'s details</h5>';
                    $output .= '<p>Payment request link:<br> ' . $request_link . '</p>';
                    $output .= '<p>Payment request json:<br> ' . $payment_request_json . '</p>';
                }
                else
                {
                    $output .= 'Error in payment request.';
                }
            }
            else
            {
                $output .= 'No payment request in url found.';
            }
        }
        else if(!is_null($input_data['select_account'])
                and !is_null ($input_data['amount'])
                    and !is_null ($input_data['receiver_name'])
                        and !is_null ($input_data['lock'])
                            and !is_null ($input_data['amount'])
                                and !is_null ($input_data['currency'])
                                    and !is_null ($input_data['receiver_reference'])
                                        and !is_null ($input_data['paylink']))
        {
            $select_account_val = SanitizeInputInteger($input_data['select_account']);
            $amount_float = SanitizeInputText($input_data['amount']);
            $currency = SanitizeInputText($input_data['currency']);
            $receiver_name_str = SanitizeInputText($input_data['receiver_name']);
            $lock_addr_str = SanitizeInputText($input_data['lock']);
            $receiver_reference_str = SanitizeInputText($input_data['receiver_reference']);
            $paylink_str = SanitizeInputText($input_data['paylink']);
            $expire_days = 2;

            if(is_user_logged_in())
            {
                $amount_val = $this->ConvertFloatToIntCurrency($amount_float, $currency);

                $account_id = new AccountIdTypeClass($select_account_val);
                $amount = new ValueTypeClass($amount_val);
                $receiver_name = new NameTypeClass($receiver_name_str);
                $lock_address = new TextTypeClass($lock_addr_str);
                $expire_seconds = $expire_days * 24 * 3600;
                $escrow_seconds = 0;
                $receiver_reference = new TextTypeClass($receiver_reference_str);

                $user_handler = new UserHandlerClass();
                $cheque = $user_handler->IssueCheque($account_id, $amount, $expire_seconds, $escrow_seconds, $receiver_name, $lock_address, $receiver_reference);

                //$hand = new ChequeHandlerClass();

                if($cheque != null)
                {
                    $payment_cheque_file = EncodeAndSignBitcoinCheque($cheque->GetDataArray(true));
                    $payment_cheque_file_url_encoded = urlencode ($payment_cheque_file);
                    $api_url = $paylink_str;


                    $args = array(
                        'body' => array(
                            'action'    => 'send_payment_cheque',
                            'cheque'    => $payment_cheque_file_url_encoded
                        )
                    );

                    $response = wp_remote_post( $api_url, $args );

                    if(is_wp_error($response))
                    {
                        $output .= 'Error: Illegal response from receiver.<br>';
                    }
                    else
                    {
                        $answer = json_decode($response['body'], true);

                        if($answer['result'] == 'OK')
                        {
                            $return_link = $answer['return_link'];

                            $output .= '<p>Payment OK.</p>';
                            $output .= '<a href="' . $return_link . '"><input type="submit" value="Return to site"/></a>';
                        }
                        else
                        {
                            $output .= '<p>Payment error.</p>';
                            $output .= '<p>Pay_status:<br>' . $answer['result'];
                            $output .= '<p>Message:<br>' . $answer['message'];
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
        else if(!is_null($input_data['payment_request']))
        {
            $payment_request_file = SanitizeInputText($input_data['payment_request']);

            $cheque_request = DecodeAndVerifyPaymentFile($payment_request_file);

            if(is_user_logged_in())
            {
                $output .= '<p>Make a payment:</p>';

                $output .= $this->DrawPaymentForm($cheque_request);

                $output .= '<br><br>';
                $output .= '<br><br>';
                $output .= '<h3>Developer\'s details</h3>';
                $output .= '<p>request:<br> ' . json_encode($cheque_request) . '</p>';
            }
            else
            {
                $output .= 'Error. Not logged in.';
            }
        }
        else
        {
            if(is_user_logged_in())
            {
                $output .= '<h3>Make a payment to a Bitcoin Payment Request</h3>';
                $output .= '<p>Copy and paste in a Bitcoin Payment Request data text in this input form:</p>';
                $output .= '<form>';
                $output .= '<textarea name="payment_request" rows="10" style="width:100%;"></textarea>';
                $output .= '<br><br>';
                $output .= '<input type="submit" value="Read Bitcoin Payment Request data text"/>';
                $output .= '</form>';
            }
            else
            {
                $output .= '<h3>Make payment to Bitcoin Cheque Request</h3>';
                $output .= 'You must log in to pay.';
            }
        }

        return $output;
    }

}
