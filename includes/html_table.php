<?php
/**
 * Generic html table library written in php.
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

class HtmlTableClass
{
    private $table_rows = array();
    private $current_line = array();

    public function AddLineItem($text)
    {
        $this->current_line[] = $text;
    }

    public function RowFeed()
    {
        $this->table_rows[] = $this->current_line;
        $this->current_line = array();
    }
    
    public function GetHtmlTable()
    {
        $html = '<table>';

        foreach($this->table_rows as $row)
        {
            $html .= '<tr>';
            foreach($row as $line_item)
            {
                $html .= '<td>' . $line_item . '</td>';
                
            }
            $html .= '</tr>';
        }

        $html .= '</table>';
        
        return $html;
    }
    
}