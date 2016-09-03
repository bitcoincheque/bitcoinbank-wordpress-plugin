<?php
/**
 * Library for formating and sending e-mail with cheque
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

require_once ('email.php');

class EmailCheque extends Email
{
    private $message = '';
    private $receiver_name = '';
    private $cheque_id = 0;
    private $access_code = '';

    public function __construct($receiver_address, $cheque_id, $access_code)
    {
        parent::__construct($receiver_address);
        parent::SetSubject('You have received a Bitcoin Cheque');
        $this->cheque_id = strval($cheque_id);
        $this->access_code = $access_code;
    }

    public function SetReceiverName($receiver_name)
    {
        /* Strip out non utf-8 characters */
        $this->receiver_name = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $receiver_name);
    }

    public function SetMessage($msg_str)
    {
        /* Strip out non utf-8 characters */
        $this->message = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $msg_str);
    }

    public function Send()
    {
        $png_url     = site_url() . '/wp-admin/admin-ajax.php?action=bcf_bitcoinbank_get_cheque_png&cheque_no=' . $this->cheque_id . '&access_code=' . $this->access_code;
        $claim_url   = site_url() . '/index.php/claim-cheque/';
        $collect_url = $claim_url . '?cheque_no=' . $this->cheque_id . '&access_code=' . $this->access_code;

        $body = '<p></p><b>Hello';
        if($this->receiver_name)
        {
            $body .= ' ' . $this->receiver_name;
        }
        $body .= ',</b></p>';
        if($this->message)
        {
            $body .= '<p>' . $this->message . '</p>';
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
        $body .= 'Cheque No.: ' . $this->cheque_id;
        $body .= '<br>Access Code.: ' . $this->access_code;

        $body .= '<p>This Bitcoin Cheque has been issued by</p>';

        $body .= '<p><b>What is Bitcoin?</b><br>Bitcoin is a consensus network that enables a new payment system and a completely digital money. It is the first decentralized peer-to-peer payment network that is powered by its users with no central authority or middlemen. From a user perspective, Bitcoin is pretty much like cash for the Internet.</p>';
        $body .= '<p><b>What is Bitcoin Cheques?</b><br>A Bitcoin Cheque is a new method for sending Bitcoins. The Bitcoin Cheque is a promiss that the issuing bank will pay a certain amount to a receiver. You can read more about Bitcoin Cheque here at <a href="http://www.bitcoincheque.org">www.bitcoincheque.org</a></p>';

        $this->SetBody($body);

        return parent::Send();
    }
}