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

class PaymentHandlerClass extends AccountingClass
{
    public function __construct()
    {
        parent::__construct();
    }

    public function ProcessCheque($cheque_id_int, $access_code_str, $hash_str, $operation)
    {
        error_log('ProcessCheque: ' . $cheque_id_int . $access_code_str . $hash_str . $operation );
        $result = 'ERRORS';

        $cheque_id_obj = new ChequeIdTypeClass($cheque_id_int);
        $access_code_obj = new TextTypeClass($access_code_str);
        $hash_obj = new TextTypeClass($hash_str);

        if (SanitizeChequeId($cheque_id_obj))
        {
            if(SanitizeText($access_code_obj))
            {
                if(SanitizeText($hash_obj))
                {
                    $my_cheque      = $this->DB_GetChequeData($cheque_id_obj);
                    if(!is_null($my_cheque))
                    {
                        $my_access_code_obj = $my_cheque->GetAccessCode();
                        $my_hash_str        = $my_cheque->GetHash();

                        if(($access_code_obj->GetString() == $my_access_code_obj->GetString()) and ($hash_obj->GetString() == $my_hash_str))
                        {
                            if($operation == 'VALIDATE')
                            {
                                $result = 'OK';
                            }
                            elseif($operation == 'CLAIM')
                            {
                                if($this->ClaimCheque($cheque_id_obj, $access_code_obj))
                                {
                                    error_log('ProcessCheque: CLAIM OK' . $cheque_id_int);

                                    $result = 'OK';
                                }
                                else
                                {
                                    error_log('ProcessCheque: CLAIM FAILED' . $cheque_id_int);

                                    $result = 'OK';
                                }
                            }
                            else
                            {
                                die();
                            }
                        }
                        else
                        {
                            $result = 'Invalid access';
                        }
                    }
                    else
                    {
                        $result = 'Invalid cheque serial no.';
                    }
                }
            }
        }

        return $result;
    }

    public function GetUserCheque($cheque_id, $access_code)
    {
        $cheque = null;

        $cheque_id_obj   = new ChequeIdTypeClass($cheque_id);
        $access_code_obj = new TextTypeClass($access_code);

        if(SanitizeChequeId($cheque_id_obj) and SanitizeText($access_code_obj))
        {
            $cheque = $this->GetCheque($cheque_id_obj, $access_code_obj);
        }

        return $cheque;
    }

}

