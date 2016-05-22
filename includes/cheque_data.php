<?php
/**
 * Bitcoin Cheque data class library.
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

require_once('data_collection_base_class.php');
require_once('data_types.php');

define ('BCF_BITCOINBANK_CHEQUE_DATA_CLASS_NAME', __NAMESPACE__ . '\ChequeDataClass');

class ChequeDataClass extends DataBaseClass
{
    /* Database table name: */
    const DB_TABLE_NAME = 'bcf_bank_cheques';

    /* List of table field names: */
    const DB_FIELD_CHEQUE_ID = 'cheque_id';
    const DB_FIELD_ISSUER_NAME = 'issuer_name';
    const DB_FIELD_ISSUER_ADDRESS = 'issuer_address';
    const DB_FIELD_NOUNCE = 'nounce';
    const DB_FIELD_VALUE = 'amount';
    const DB_FIELD_CURRENCY = 'currency';
    const DB_FIELD_FIXED_FEE = 'fixed_fee';
    const DB_FIELD_COLLECTION_FEE = 'collect_fee';
    const DB_FIELD_STAMP = 'stamp';
    const DB_FIELD_COLLECT_URL = 'collect_url';
    const DB_FIELD_ISSUE_DATETIME = 'issue_datetime';
    const DB_FIELD_EXPIRE_DATETIME = 'expire_datetime';
    const DB_FIELD_ESCROW_DATETIME = 'escrow_datetime';
    const DB_FIELD_RECEIVER_NAME = 'receiver_name';
    const DB_FIELD_RECEIVER_ADDRESS = 'receiver_url';
    const DB_FIELD_RECEIVER_URL = 'receiver_email';
    const DB_FIELD_RECEIVER_EMAIL = 'receiver_address';
    const DB_FIELD_RECEIVER_BUSINESS_NO = 'receiver_business_no';
    const DB_FIELD_RECEIVER_REG_COUNTRY = 'receiver_reg_country';
    const DB_FIELD_RECEIVER_WALLET = 'receiver_wallet_address';
    const DB_FIELD_RECEIVER_REFERENCE = 'receiver_reference';
    const DB_FIELD_USER_REFERENCE = 'user_ref';
    const DB_FIELD_USER_NAME = 'user_name';
    const DB_FIELD_USER_ADDRESS = 'user_address';
    const DB_FIELD_STATE = 'state';
    const DB_FIELD_USER_ACCOUNT_ID = 'issuer_account_id';
    const DB_FIELD_DESCRIPTION = 'description';

    /* Metadata describing database fields and data properties: */
    protected $MetaData = array(
        self::DB_FIELD_CHEQUE_ID => array(
            'class_type'    => 'ChequeIdTypeClass',
            'db_field_name' => self::DB_FIELD_CHEQUE_ID,
            'db_primary_key'=> true,
            'default_value' => 0,
            'public_data'   => true
        ),
        self::DB_FIELD_ISSUER_NAME => array(
            'class_type'    => 'NameTypeClass',
            'db_field_name' => self::DB_FIELD_ISSUER_NAME,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_ISSUER_ADDRESS => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_ISSUER_ADDRESS,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_NOUNCE => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_NOUNCE,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_VALUE => array(
            'class_type'    => 'ValueTypeClass',
            'db_field_name' => self::DB_FIELD_VALUE,
            'db_primary_key'=> false,
            'default_value' => 0,
            'public_data'   => true
        ),
        self::DB_FIELD_CURRENCY => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_CURRENCY,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_FIXED_FEE => array(
            'class_type'    => 'ValueTypeClass',
            'db_field_name' => self::DB_FIELD_FIXED_FEE,
            'db_primary_key'=> false,
            'default_value' => 0,
            'public_data'   => true
        ),
        self::DB_FIELD_COLLECTION_FEE => array(
            'class_type'    => 'ValueTypeClass',
            'db_field_name' => self::DB_FIELD_COLLECTION_FEE,
            'db_primary_key'=> false,
            'default_value' => 0,
            'public_data'   => true
        ),
        self::DB_FIELD_STAMP => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_STAMP,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_COLLECT_URL => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_COLLECT_URL,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_ISSUE_DATETIME => array(
            'class_type'    => 'DateTimeTypeClass',
            'db_field_name' => self::DB_FIELD_ISSUE_DATETIME,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_EXPIRE_DATETIME => array(
            'class_type'    => 'DateTimeTypeClass',
            'db_field_name' => self::DB_FIELD_EXPIRE_DATETIME,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_ESCROW_DATETIME => array(
            'class_type'    => 'DateTimeTypeClass',
            'db_field_name' => self::DB_FIELD_ESCROW_DATETIME,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_RECEIVER_NAME => array(
            'class_type'    => 'NameTypeClass',
            'db_field_name' => self::DB_FIELD_RECEIVER_NAME,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_RECEIVER_ADDRESS => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_RECEIVER_ADDRESS,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_RECEIVER_URL => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_RECEIVER_URL,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_RECEIVER_EMAIL => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_RECEIVER_EMAIL,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_RECEIVER_BUSINESS_NO => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_RECEIVER_BUSINESS_NO,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_RECEIVER_REG_COUNTRY => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_RECEIVER_REG_COUNTRY,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_RECEIVER_WALLET => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_RECEIVER_WALLET,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_RECEIVER_REFERENCE => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_RECEIVER_REFERENCE,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_USER_REFERENCE => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_USER_REFERENCE,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_USER_NAME => array(
            'class_type'    => 'NameTypeClass',
            'db_field_name' => self::DB_FIELD_USER_NAME,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_USER_ADDRESS => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_USER_ADDRESS,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => true
        ),
        self::DB_FIELD_STATE => array(
            'class_type'    => 'ChequeStateTypeClass',
            'db_field_name' => self::DB_FIELD_STATE,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => false
        ),
        self::DB_FIELD_USER_ACCOUNT_ID => array(
            'class_type'    => 'AccountIdTypeClass',
            'db_field_name' => self::DB_FIELD_USER_ACCOUNT_ID,
            'db_primary_key'=> false,
            'default_value' => 0,
            'public_data'   => false
        ),
        self::DB_FIELD_DESCRIPTION => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_DESCRIPTION,
            'db_primary_key'=> false,
            'default_value' => '',
            'public_data'   => false
        ),
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function GetChequeId()
    {
        return $this->GetDataObjects(self::DB_FIELD_CHEQUE_ID);
    }

    public function SetChequeId($cheque_id)
    {
        $result = false;

        if(SanitizeChequeId($cheque_id))
        {
            $result = $this->SetDataObject(self::DB_FIELD_CHEQUE_ID, $cheque_id);
        }

        return $result;
    }    

    public function GetIssuerName()
    {
        return $this->GetDataObjects(self::DB_FIELD_ISSUER_NAME);
    }

    public function SetIssuerName($issuer_name)
    {
        $result = false;

        if(SanitizeName($issuer_name))
        {
            $result = $this->SetDataObject(self::DB_FIELD_ISSUER_NAME, $issuer_name);
        }

        return $result;
    }

    public function GetIssuerAddress()
    {
        return $this->GetDataObjects(self::DB_FIELD_ISSUER_ADDRESS);
    }

    public function SetIssuerAddress($issuer_address)
    {
        $result = false;

        if(SanitizeText($issuer_address))
        {
            $result = $this->SetDataObject(self::DB_FIELD_ISSUER_ADDRESS, $issuer_address);
        }

        return $result;
    }

    public function GetNounce()
    {
        return $this->GetDataObjects(self::DB_FIELD_NOUNCE);
    }

    public function SetNounce($nounce)
    {
        $result = false;

        if(SanitizeText($nounce))
        {
            $result = $this->SetDataObject(self::DB_FIELD_NOUNCE, $nounce);
        }

        return $result;
    }

    public function GetValue()
    {
        return $this->GetDataObjects(self::DB_FIELD_VALUE);
    }

    public function SetValue($value)
    {
        $result = false;

        if(SanitizeAmount($value))
        {
            $result = $this->SetDataObject(self::DB_FIELD_VALUE, $value);
        }

        return $result;
    }

    public function GetCurrency()
    {
        return $this->GetDataObjects(self::DB_FIELD_CURRENCY);
    }

    public function SetCurrency($currency)
    {
        $result = false;

        if(SanitizeText($currency))
        {
            $result = $this->SetDataObject(self::DB_FIELD_CURRENCY, $currency);
        }

        return $result;
    }

    public function GetFixedFee()
    {
        return $this->GetDataObjects(self::DB_FIELD_FIXED_FEE);
    }

    public function SetFixedFee($fixed_fee)
    {
        $result = false;

        if(SanitizeAmount($fixed_fee))
        {
            $result = $this->SetDataObject(self::DB_FIELD_FIXED_FEE, $fixed_fee);
        }

        return $result;
    }

    public function GetCollectionFee()
    {
        return $this->GetDataObjects(self::DB_FIELD_COLLECTION_FEE);
    }

    public function SetCollectionFee($collection_fee)
    {
        $result = false;

        if(SanitizeAmount($collection_fee))
        {
            $result = $this->SetDataObject(self::DB_FIELD_COLLECTION_FEE, $collection_fee);
        }

        return $result;
    }

    public function GetStamp()
    {
        return $this->GetDataObjects(self::DB_FIELD_STAMP);
    }

    public function SetStamp($stamp)
    {
        $result = false;

        if(SanitizeText($stamp))
        {
            $result = $this->SetDataObject(self::DB_FIELD_STAMP, $stamp);
        }

        return $result;
    }


    public function GetCollectUrl()
    {
        return $this->GetDataObjects(self::DB_FIELD_COLLECT_URL);
    }

    public function SetCollectUrl($collect_url)
    {
        $result = false;

        if(SanitizeText($collect_url))
        {
            $result = $this->SetDataObject(self::DB_FIELD_COLLECT_URL, $collect_url);
        }

        return $result;
    }

    public function GetIssueDateTime()
    {
        return $this->GetDataObjects(self::DB_FIELD_ISSUE_DATETIME);
    }

    public function SetIssueDateTime($issuer_datetime)
    {
        $result = false;

        if(SanitizeDateTime($issuer_datetime))
        {
            $result = $this->SetDataObject(self::DB_FIELD_ISSUE_DATETIME, $issuer_datetime);
        }

        return $result;
    }

    public function GetExpireDateTime()
    {
        return $this->GetDataObjects(self::DB_FIELD_EXPIRE_DATETIME);
    }

    public function SetExpireDateTime($expire_datetime)
    {
        $result = false;

        if(SanitizeDateTime($expire_datetime))
        {
            $result = $this->SetDataObject(self::DB_FIELD_EXPIRE_DATETIME, $expire_datetime);
        }

        return $result;
    }

    public function GetEscrowDateTime()
    {
        return $this->GetDataObjects(self::DB_FIELD_ESCROW_DATETIME);
    }

    public function SetEscrowDateTime($escrow_datetime)
    {
        $result = false;

        if(SanitizeDateTime($escrow_datetime))
        {
            $result = $this->SetDataObject(self::DB_FIELD_ESCROW_DATETIME, $escrow_datetime);
        }

        return $result;
    }

    public function GetReceiverName()
    {
        return $this->GetDataObjects(self::DB_FIELD_RECEIVER_NAME);
    }

    public function SetReceiverName($receiver_name)
    {
        $result = false;

        if(SanitizeName($receiver_name))
        {
            $result = $this->SetDataObject(self::DB_FIELD_RECEIVER_NAME, $receiver_name);
        }

        return $result;
    }

    public function GetReceiverAddress()
    {
        return $this->GetDataObjects(self::DB_FIELD_RECEIVER_ADDRESS);
    }

    public function SetReceiverAddress($receiver_address)
    {
        $result = false;

        if(SanitizeText($receiver_address))
        {
            $result = $this->SetDataObject(self::DB_FIELD_RECEIVER_ADDRESS, $receiver_address);
        }

        return $result;
    }

    public function GetReceiverUrl()
    {
        return $this->GetDataObjects(self::DB_FIELD_RECEIVER_URL);
    }

    public function SetReceiverUrl($receiver_url)
    {
        $result = false;

        if(SanitizeText($receiver_url))
        {
            $result = $this->SetDataObject(self::DB_FIELD_RECEIVER_URL, $receiver_url);
        }

        return $result;
    }

    public function GetReceiverEmail()
    {
        return $this->GetDataObjects(self::DB_FIELD_RECEIVER_EMAIL);
    }

    public function SetReceiverEmail($receiver_email)
    {
        $result = false;

        if(SanitizeText($receiver_email))
        {
            $result = $this->SetDataObject(self::DB_FIELD_RECEIVER_EMAIL, $receiver_email);
        }

        return $result;
    }
    
    public function GetReceiverBusinessNo()
    {
        return $this->GetDataObjects(self::DB_FIELD_RECEIVER_BUSINESS_NO);
    }

    public function SetReceiverBusinessNo($receiver_business_no)
    {
        $result = false;

        if(SanitizeText($receiver_business_no))
        {
            $result = $this->SetDataObject(self::DB_FIELD_RECEIVER_BUSINESS_NO, $receiver_business_no);
        }

        return $result;
    }
    
    public function GetReceiverRegCountry()
    {
        return $this->GetDataObjects(self::DB_FIELD_RECEIVER_REG_COUNTRY);
    }

    public function SetReceiverRegCountry($receiver_reg_country)
    {
        $result = false;

        if(SanitizeText($receiver_reg_country))
        {
            $result = $this->SetDataObject(self::DB_FIELD_RECEIVER_REG_COUNTRY, $receiver_reg_country);
        }

        return $result;
    }

    public function GetReceiverWallet()
    {
        return $this->GetDataObjects(self::DB_FIELD_RECEIVER_WALLET);
    }

    public function SetReceiverWallet($receiver_wallet)
    {
        $result = false;

        if(SanitizeText($receiver_wallet))
        {
            $result = $this->SetDataObject(self::DB_FIELD_RECEIVER_WALLET, $receiver_wallet);
        }

        return $result;
    }

    public function GetReceiverReference()
    {
        return $this->GetDataObjects(self::DB_FIELD_RECEIVER_REFERENCE);
    }

    public function SetReceiverReference($receivers_reference)
    {
        $result = false;

        if(SanitizeText($receivers_reference))
        {
            $result = $this->SetDataObject(self::DB_FIELD_RECEIVER_REFERENCE, $receivers_reference);
        }

        return $result;
    }

    public function GetUserReference()
    {
        return $this->GetDataObjects(self::DB_FIELD_USER_REFERENCE);
    }

    public function SetUserReference($user_reference)
    {
        $result = false;

        if(SanitizeText($user_reference))
        {
            $result = $this->SetDataObject(self::DB_FIELD_USER_REFERENCE, $user_reference);
        }

        return $result;
    }

    public function GetUserName()
    {
        return $this->GetDataObjects(self::DB_FIELD_USER_NAME);
    }

    public function SetUserName($user_name)
    {
        $result = false;

        if(SanitizeName($user_name))
        {
            $result = $this->SetDataObject(self::DB_FIELD_USER_NAME, $user_name);
        }

        return $result;
    }

    public function GetUserAddress()
    {
        return $this->GetDataObjects(self::DB_FIELD_USER_ADDRESS);
    }

    public function SetUserAddress($user_address)
    {
        $result = false;

        if(SanitizeText($user_address))
        {
            $result = $this->SetDataObject(self::DB_FIELD_USER_ADDRESS, $user_address);
        }

        return $result;
    }

    public function GetChequeState()
    {
        return $this->GetDataObjects(self::DB_FIELD_STATE);
    }

    public function SetChequeState($cheque_state)
    {
        $result = false;

        if(SanitizeChequeState($cheque_state))
        {
            $result = $this->SetDataObject(self::DB_FIELD_STATE, $cheque_state);
        }

        return $result;
    }

    public function GetOwnerAccountId()
    {
        return $this->GetDataObjects(self::DB_FIELD_USER_ACCOUNT_ID);
    }

    public function SetOwnerAccountId($users_account_id)
    {
        $result = false;

        if(SanitizeAccountId($users_account_id))
        {
            $result = $this->SetDataObject(self::DB_FIELD_USER_ACCOUNT_ID, $users_account_id);
        }

        return $result;
    }
    
    public function GetDescription()
    {
        return $this->GetDataObjects(self::DB_FIELD_DESCRIPTION);
    }

    public function SetDescription($description)
    {
        $result = false;

        if(SanitizeText($description))
        {
            $result = $this->SetDataObject(self::DB_FIELD_DESCRIPTION, $description);
        }

        return $result;
    }

    public function HasExpired($current_time)
    {
        $has_expired = false;

        if(SanitizeDateTime($current_time))
        {
            $cheque_expired_datetime = $this->GetExpireDateTime();

            $expired_time_in_seconds = $cheque_expired_datetime->GetSeconds();
            $current_time_in_seconds = $current_time->GetSeconds();

            if($current_time_in_seconds > $expired_time_in_seconds)
            {
                $has_expired = true;
            }
        }

        return $has_expired;
    }

    public function GetJson()
    {
        $public_data_only = true;
        $data_array = $this->GetDataArray($public_data_only);

        $json = json_encode($data_array);

        return $json;
    }

    public function CompareCheque($other_data_array)
    {
        $result = 'OK';
        
        foreach($this->MetaData as $key => $attributes)
        {
            if($attributes['public_data'] == true)
            {
                $my_data_object = $this->DataObjects[ $key ];
                $my_data_str    = $my_data_object->GetString();

                $other_data_str = $other_data_array[ $key ];

                if($my_data_str != $other_data_str)
                {
                    $result = 'Cheque mismatch field ' . $key . 'mismatch: ' . $my_data_str . ' != ' . $other_data_array;
                    break;
                }
            }
        }
        
        return $result; 
    }
}

function SanitizeCheque($cheque)
{
    if(gettype($cheque) == 'object')
    {
        if(get_class($cheque) == BCF_BITCOINBANK_CHEQUE_DATA_CLASS_NAME)
        {
            return $cheque->SanitizeData();
        }
    }
    return false;
}