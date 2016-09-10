<?php
/**
 * Bitcoin Bank payment interface class.
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


class PaymentInterface
{
    public function ValidateCheque($input_data)
    {
        $has_cheque_no   = ! is_null($input_data['cheque_no']);
        $has_access_code = ! is_null($input_data['access_code']);
        $has_hash        = ! is_null($input_data['hash']);

        if($has_cheque_no and $has_access_code and $has_hash)
        {
            $cheque_handler = new ChequeHandlerClass();
            $result         = $cheque_handler->ValidateCheque($input_data['cheque_no'], $input_data['access_code'], $input_data['hash']);

            if($result == 'OK')
            {
                $response_data = array(
                    'result'    => 'OK',
                    'message'   => '',
                    'cheque_no' => strval($input_data['cheque_no'])
                );
            }
            else
            {
                $response_data = array(
                    'result'    => 'ERROR',
                    'message'   => 'Invalid access.' . $result,
                    'cheque_no' => strval($input_data['cheque_no'])
                );
            }
        }
        else
        {
            $msg = 'Data field missing in validation request [';
            if( ! $has_cheque_no)
            {
                $msg .= 'cheque_no, ';
            }
            if( ! $has_access_code)
            {
                $msg .= 'access_code, ';
            }
            if( ! $has_hash)
            {
                $msg .= 'hash, ';
            }
            $msg .= ']';


            $response_data = array(
                'result'  => 'ERROR',
                'message' => $msg
            );
        }

        return $response_data;
    }

    public function CreateChequePng($input_data)
    {
        $has_cheque_no   = ! is_null($input_data['cheque_no']);
        $has_access_code = ! is_null($input_data['access_code']);
        $has_hash        = ! is_null($input_data['hash']);

        if($has_cheque_no and $has_access_code)
        {
            $cheque_id   = new ChequeIdTypeClass($input_data['cheque_no']);
            $access_code = new TextTypeClass($input_data['access_code']);

            $user_handler = new UserHandlerClass();
            $cheque       = $user_handler->GetCheque($cheque_id, $access_code);

            $currency = $cheque->GetCurrency()->GetString();
            if($currency == '')
            {
                $currency = 'TestBTC';
            }

            if( ! is_null($cheque))
            {
                $hash = $cheque->GetHash();

                header("Content-type: image/png");

                $filename = plugin_dir_path(__FILE__) . '../bank_logo.png';

                $filename = plugin_dir_path(__FILE__) . '../cheque_template2.png';

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

                imagestring($im, 10, 20, 275, 'Cheque No.:' . $input_data['cheque_no'] . '  Access Code:' . $access_code->GetString() . '  Hash:' . $hash, $black);

                imagepng($im);

                imagedestroy($im);
            }
            else
            {
                echo 'Invalid cheque';
            }
        }
    }
}