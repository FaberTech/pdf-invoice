<?php
/**
 * Contains the InvoicePrinter class.
 *
 * @author      Farjad Tahir
 * @see         http://www.splashpk.com
 * @license     GPL
 * @since       2017-12-15
 *
 */

namespace FaberTech\PdfInvoice;

use FPDF;
use voku\helper\ASCII;

class InvoicePrinter extends FPDF
{
    public $angle = 0;

    public $font            = 'helvetica';        /* Font Name : See inc/fpdf/font for all supported fonts */
    public $columnOpacity   = 0.06;            /* Items table background color opacity. Range (0.00 - 1) */
    public $columnSpacing   = 0.5;                /* Spacing between Item Tables */
    public $referenceformat = ['.', ','];    /* Currency formater */
    public $margins         = [
        'l' => 15,
        't' => 15,
        'r' => 15
    ]; /* l: Left Side , t: Top Side , r: Right Side */

    public $lang;
    public $document;
    public $type;
    public $reference;
    public $po_number;
    public $logo;
    public $color;
    public $date;
    public $time;
    public $due;
    public $paymentDate;
    public $paymentMethod;
    public $start_date;
    public $end_date;
    public $address;
    public $from;
    public $to;
    public $sections;
    public $totals;
    public $badge;
    public $addText;
    public $footernote;
    public $dimensions;
    public $display_tofrom = true;

    /******************************************
     * Class Constructor                     *
     * param : Page Size , Currency, Language *
     ******************************************/
    public function __construct($size = 'A4', $currency = '$', $language = 'en')
    {
        $this->columns            = 5;
        $this->sections              = [];
        $this->totals             = [];
        $this->addText            = [];
        $this->firstColumnWidth   = 70;
        $this->currency           = $currency;
        $this->maxImageDimensions = [230, 130];
        $this->setLanguage($language);
        $this->setDocumentSize($size);
        $this->setColor("#222222");

        parent::__construct('P', 'mm', [$this->document['w'], $this->document['h']]);

        $this->AliasNbPages();
        $this->SetMargins($this->margins['l'], $this->margins['t'], $this->margins['r']);
    }

    private function setLanguage($language)
    {
        $this->language = $language;
        include(dirname(__DIR__) . '/inc/languages/' . $language . '.inc');
        $this->lang = $lang;
    }

    private function setDocumentSize($dsize)
    {
        switch ($dsize) {
            case 'A4':
                $document['w'] = 210;
                $document['h'] = 297;
                break;
            case 'letter':
                $document['w'] = 215.9;
                $document['h'] = 279.4;
                break;
            case 'legal':
                $document['w'] = 215.9;
                $document['h'] = 355.6;
                break;
            default:
                $document['w'] = 210;
                $document['h'] = 297;
                break;
        }
        $this->document = $document;
    }

    private function resizeToFit($image)
    {
        list($width, $height) = getimagesize($image);
        $newWidth  = $this->maxImageDimensions[0] / $width;
        $newHeight = $this->maxImageDimensions[1] / $height;
        $scale     = min($newWidth, $newHeight);

        return [
            round($this->pixelsToMM($scale * $width)),
            round($this->pixelsToMM($scale * $height))
        ];
    }

    private function pixelsToMM($val)
    {
        $mm_inch = 25.4;
        $dpi     = 96;

        return ($val * $mm_inch) / $dpi;
    }

    private function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = [$r, $g, $b];

        return $rgb;
    }

    private function br2nl($string)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }

    public function isValidTimezoneId($zone)
    {
        try {
            new DateTimeZone($zone);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function setTimeZone($zone = "")
    {
        if (!empty($zone) and $this->isValidTimezoneId($zone) === true) {
            date_default_timezone_set($zone);
        }
    }

    public function setType($title)
    {
        $this->title = $title;
    }

    public function setColor($rgbcolor)
    {
        $this->color = $this->hex2rgb($rgbcolor);
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    public function setTime($time)
    {
        $this->time = $time;
    }

    public function setDue($date)
    {
        $this->due = $date;
    }

    public function  setPaymentDate($date)
    {
        $this->paymentDate = $date;
    }

    public function setPaymentMethod($method)
    {
        $this->paymentMethod = $method;
    }

    public function setPeriod($start_date, $end_date)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }


    public function setAddress($address)
    {
        $this->address = $address;
    }

    public function setLogo($logo = 0, $maxWidth = 0, $maxHeight = 0)
    {
        if ($maxWidth and $maxHeight) {
            $this->maxImageDimensions = [$maxWidth, $maxHeight];
        }
        $this->logo       = $logo;
        $this->dimensions = $this->resizeToFit($logo);
    }

    public function hide_tofrom()
    {
        $this->display_tofrom = false;
    }

    public function setFrom($data)
    {
        foreach($data as $i => $item){
            $data[$i] = ASCII::to_ascii($item);
        }

        $this->from = $data;
    }

    public function setTo($data)
    {
        foreach($data as $i => $item){
            $data[$i] = ASCII::to_ascii($item);
        }

        $this->to = $data;
    }

    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    public function setPONumber($po_number)
    {
        $this->po_number = $po_number;
    }

    public function setNumberFormat($decimals, $thousands_sep)
    {
        $this->referenceformat = [$decimals, $thousands_sep];
    }

    public function flipflop()
    {
        $this->flipflop = true;
    }

    public function addItem($project_id, $project_name, $project_address, $project_supervisor, $project_total, $item, $description = "", $total_quantity, $quantity, $quantity_ot, $price, $price_ot = 0, $total, $po_number = null, $skills = [])
    {
        $p['item']        = ASCII::to_ascii($item);
        $p['skills']      = $skills;
        $p['description'] = is_array($description) ? $description : $this->br2nl($description);

        if ($quantity_ot !== false) {
            $p['quantity_ot'] = $quantity_ot;
            $this->otQuantityField = true;
            $this->columns  = 6;
        }
        $p['total_quantity'] = $total_quantity;
        $p['quantity'] = $quantity;
        $p['price']    = $price;
        $p['total']    = $total;

        if ($price_ot !== false) {
            $this->firstColumnWidth = 58;
            $p['price_ot']          = $price_ot;
            if (is_numeric($price_ot)) {
                $p['price_ot'] = $this->currency . ' ' . number_format($price_ot, 2, $this->referenceformat[0],
                        $this->referenceformat[1]);
            }
            $this->otPriceField = true;
            $this->columns       = 7;
        }

        if(!isset($this->sections[$project_id])){
            $this->sections[$project_id] = [];
            $this->sections[$project_id]['total'] =  $project_total;
            $this->sections[$project_id]['name'] = ASCII::to_ascii($project_name);
            $this->sections[$project_id]['address'] = ASCII::to_ascii($project_address);
            $this->sections[$project_id]['supervisor'] = ASCII::to_ascii($project_supervisor);
            $this->sections[$project_id]['po_number'] = $po_number;
            $this->sections[$project_id]['items'] = [];
        }

        $this->sections[$project_id]['items'][] = $p;
    }

    public function addTotal($name, $value, $colored = false)
    {
        $t['name']  = $name;
        $t['value'] = $value;
        if (is_numeric($value)) {
            $t['value'] = $this->currency . ' ' . number_format($value, 2, $this->referenceformat[0],
                    $this->referenceformat[1]);
        }
        $t['colored']   = $colored;
        $this->totals[] = $t;
    }

    public function addTitle($title)
    {
        $this->addText[] = ['title', $title];
    }

    public function addParagraph($paragraph)
    {
        $paragraph       = $this->br2nl($paragraph);
        $this->addText[] = ['paragraph', $paragraph];
    }

    public function addBadge($badge)
    {
        $this->badge = $badge;
    }

    public function setFooternote($note)
    {
        $this->footernote = ASCII::to_ascii($note);
    }

    public function render($name = '', $destination = '')
    {
        $this->AddPage();
        $this->Body();
        $this->AliasNbPages();
        return $this->Output($destination, $name);
    }

    public function Header()
    {




        //First page
        if ($this->PageNo() == 1) {

            if (isset($this->logo) and !empty($this->logo)) {
                $this->Image($this->logo, $this->margins['l'], $this->margins['t'], $this->dimensions[0],
                    $this->dimensions[1]);
            }

            //Title
            $this->SetTextColor(0, 0, 0);
            $this->SetFont($this->font, 'B', 20);
            if(isset($this->title) and !empty($this->title)) {
                $this->Cell(0, 5, iconv("UTF-8", "ISO-8859-1", mb_strtoupper($this->title, 'UTF-8')), 0, 1, 'R');
            }
            $this->SetFont($this->font, '', 9);


            $this->Ln(2);


            //Address
            if (!empty($this->address)) {
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 9);
                $this->Cell(0, 5, $this->address, 0, 1, 'R');
            }


            $this->Ln(3);

            $lineheight = 5;
            //Calculate position of strings
            $this->SetFont($this->font, 'B', 9);
            $positionX = $this->document['w'] - $this->margins['l'] - $this->margins['r'] - max(mb_strtoupper($this->GetStringWidth($this->lang['number'], 'UTF-8')),
                    mb_strtoupper($this->GetStringWidth($this->lang['date'], 'UTF-8')),
                    mb_strtoupper($this->GetStringWidth($this->lang['due'], 'UTF-8'))) - 50;


            //Number
            if (!empty($this->reference)) {
                $this->Cell($positionX, $lineheight);
                $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Cell(47, $lineheight, iconv("UTF-8", "ISO-8859-1", mb_strtoupper($this->lang['number'], 'UTF-8') . ':'), 0, 0,
                    'L');
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 9);
                $this->Cell(0, $lineheight, $this->reference, 0, 1, 'R');
            }


            //PO Number
            if (!empty($this->po_number)) {
                $this->Cell($positionX, $lineheight);
                $this->SetFont($this->font, 'B', 9);
                $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Cell(47, $lineheight, iconv("UTF-8", "ISO-8859-1", mb_strtoupper($this->lang['po_number'], 'UTF-8') . ':'), 0, 0,
                    'L');
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 9);
                $this->Cell(0, $lineheight, $this->po_number, 0, 1, 'R');
            }

            //Date
            $this->Cell($positionX, $lineheight);
            $this->SetFont($this->font, 'B', 9);
            $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
            $this->Cell(47, $lineheight, iconv("UTF-8", "ISO-8859-1", mb_strtoupper($this->lang['date'], 'UTF-8')) . ':', 0, 0, 'L');
            $this->SetTextColor(50, 50, 50);
            $this->SetFont($this->font, '', 9);
            $this->Cell(0, $lineheight, $this->date, 0, 1, 'R');

            //Time
            if (!empty($this->time)) {
                $this->Cell($positionX, $lineheight);
                $this->SetFont($this->font, 'B', 9);
                $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Cell(47, $lineheight, iconv("UTF-8", "ISO-8859-1", mb_strtoupper($this->lang['time'], 'UTF-8')) . ':', 0, 0,
                    'L');
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 9);
                $this->Cell(0, $lineheight, $this->time, 0, 1, 'R');
            }
            //Due date
            if (!empty($this->due)) {
                $this->Cell($positionX, $lineheight);
                $this->SetFont($this->font, 'B', 9);
                $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Cell(47, $lineheight, iconv("UTF-8", "ISO-8859-1", mb_strtoupper($this->lang['due'], 'UTF-8')) . ':', 0, 0, 'L');
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 9);
                $this->Cell(0, $lineheight, $this->due, 0, 1, 'R');
            }
            //Payment date
            if (!empty($this->paymentDate)) {
                $this->Cell($positionX, $lineheight);
                $this->SetFont($this->font, 'B', 9);
                $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Cell(47, $lineheight, iconv("UTF-8", "ISO-8859-1", mb_strtoupper($this->lang['payment_date'], 'UTF-8')) . ':', 0, 0, 'L');
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 9);
                $this->Cell(0, $lineheight, $this->paymentDate, 0, 1, 'R');
            }
            //Period
            if (!empty($this->start_date) && !empty($this->end_date)) {
                $this->Cell($positionX, $lineheight);
                $this->SetFont($this->font, 'B', 9);
                $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Cell(47, $lineheight, iconv("UTF-8", "ISO-8859-1", mb_strtoupper($this->lang['period'], 'UTF-8')) . ':', 0, 0, 'L');
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 9);
                $this->Cell(0, $lineheight, $this->start_date . ' - '. $this->end_date, 0, 1, 'R');
            }
            //Payment Method
            if (!empty($this->paymentMethod)) {
                $this->Cell($positionX, $lineheight);
                $this->SetFont($this->font, 'B', 9);
                $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Cell(47, $lineheight, iconv("UTF-8", "ISO-8859-1", mb_strtoupper($this->lang['payment_method'], 'UTF-8')) . ':', 0, 0, 'L');
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, '', 9);
                $this->Cell(0, $lineheight, $this->paymentMethod, 0, 1, 'R');
            }

            if (($this->margins['t'] + $this->dimensions[1]) > $this->GetY()) {
                $this->SetY($this->margins['t'] + $this->dimensions[1] + 5);
            } else {
                $this->SetY($this->GetY() + 10);
            }
            $this->Ln(5);
            $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
            $this->SetTextColor($this->color[0], $this->color[1], $this->color[2]);

            $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
            $this->SetFont($this->font, 'B', 10);
            $width = ($this->document['w'] - $this->margins['l'] - $this->margins['r']) / 2;
            if (isset($this->flipflop)) {
                $to                 = $this->lang['to'];
                $from               = $this->lang['from'];
                $this->lang['to']   = $from;
                $this->lang['from'] = $to;
                $to                 = $this->to;
                $from               = $this->from;
                $this->to           = $from;
                $this->from         = $to;
            }

            if ($this->display_tofrom === true) {
                $this->Cell($width, $lineheight, iconv("UTF-8", "ISO-8859-1//TRANSLIT", mb_strtoupper($this->lang['from'], 'UTF-8')), 0, 0, 'L');
                $this->Cell(0, $lineheight, iconv("UTF-8", "ISO-8859-1//TRANSLIT", mb_strtoupper($this->lang['to'], 'UTF-8')), 0, 0, 'L');
                $this->Ln(7);
                $this->SetLineWidth(0.4);
                $this->Line($this->margins['l'], $this->GetY(), $this->margins['l'] + $width - 10, $this->GetY());
                $this->Line($this->margins['l'] + $width, $this->GetY(), $this->margins['l'] + $width + $width,
                    $this->GetY());

                //Information
                $this->Ln(5);
                $this->SetTextColor(50, 50, 50);
                $this->SetFont($this->font, 'B', 10);
                $this->Cell($width, $lineheight, $this->from[0], 0, 0, 'L');
                $this->Cell(0, $lineheight, iconv("UTF-8", "ISO-8859-1//TRANSLIT", $this->to[0]), 0, 0, 'L');
                $this->SetFont($this->font, '', 8);
                $this->SetTextColor(100, 100, 100);
                $this->Ln(7);
                for ($i = 1; $i < max($this->from === null ? 0 : count($this->from), $this->to === null ? 0 : count($this->to)); $i++) {
                    $this->Cell($width, $lineheight, iconv("UTF-8", "ISO-8859-1//TRANSLIT", $this->from[$i]), 0, 0, 'L');
                    $this->Cell(0, $lineheight, iconv("UTF-8", "ISO-8859-1//TRANSLIT", $this->to[$i]), 0, 0, 'L');
                    $this->Ln(5);
                }
                $this->Ln(-6);
                $this->Ln(5);
            } else {
                $this->Ln(-10);
            }
        }
    }



    //Section Header

    public function section_header($section){

        $lineheight = 5;
        $width = ($this->document['w'] - $this->margins['l'] - $this->margins['r']);



        $this->Ln(12);

        //Information
        $this->SetTextColor(50, 50, 50);
        $this->SetFont($this->font, 'B', 10);

        $title = $section['address'];

        $this->Cell($width, $lineheight, $section['name'], 0, 0, 'L');
        $this->Ln(4);
        $this->Cell($width, $lineheight, $title, 0, 0, 'L');
        $this->SetFont($this->font, '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Ln(4);
        // project supervisor
        $this->Cell($width, $lineheight, iconv("UTF-8", "ISO-8859-1//TRANSLIT", 'Supervisor: '.$section['supervisor']), 0, 0, 'L');

        if ($section['po_number']){
            $this->Ln(4);
            // project supervisor
            $this->Cell($width, $lineheight, iconv("UTF-8", "ISO-8859-1//TRANSLIT", 'PO#: '.$section['po_number']), 0, 0, 'L');
        }
        $this->Ln(6);
        $this->SetLineWidth(0.4);
        $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
        $this->Line($this->margins['l'], $this->GetY(), $this->margins['l'] + $width, $this->GetY());

//        $this->Ln(2);

    }




    public function table_header(){

        //Table header
        if (!isset($this->productsEnded)) {
            $width_other = ($this->document['w'] - $this->margins['l'] - $this->margins['r'] - $this->firstColumnWidth - ($this->columns * $this->columnSpacing)) / ($this->columns - 1);
            $this->SetTextColor(50, 50, 50);
            $this->SetFont($this->font, 'B', 9);
            $this->Cell(1, 10, '', 0, 0, 'L', 0);
            $this->Cell($this->firstColumnWidth, 10, iconv("UTF-8", "ISO-8859-1//TRANSLIT", mb_strtoupper($this->lang['product'], 'UTF-8')),
                0, 0, 'L', 0);
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1//TRANSLIT", mb_strtoupper($this->lang['price'], 'UTF-8')), 0, 0, 'C', 0);
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1//TRANSLIT", mb_strtoupper($this->lang['total_qty'], 'UTF-8')), 0, 0, 'C', 0);
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1//TRANSLIT", mb_strtoupper($this->lang['qty'], 'UTF-8')), 0, 0, 'C', 0);
            if (isset($this->otPriceField)) {
                $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
                $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1//TRANSLIT", mb_strtoupper($this->lang['price_ot'], 'UTF-8')), 0, 0,
                    'C', 0);
            }
            if (isset($this->otQuantityField)) {
                $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
                $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1//TRANSLIT", mb_strtoupper($this->lang['quantity_ot'], 'UTF-8')), 0, 0, 'C',
                    0);
            }
            $this->Cell($this->columnSpacing, 10, '', 0, 0, 'L', 0);
            $this->Cell($width_other, 10, iconv("UTF-8", "ISO-8859-1//TRANSLIT", mb_strtoupper($this->lang['total'], 'UTF-8')), 0, 0, 'C', 0);
            $this->Ln();
            $this->SetLineWidth(0.3);
            $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
            $this->Line($this->margins['l'], $this->GetY(), $this->document['w'] - $this->margins['r'], $this->GetY());
            $this->Ln(2);
        } else {
            $this->Ln(12);
        }
    }






    //Totals section

    public function totals_section(){
        $lineheight = 5;
        $width = ($this->document['w'] - $this->margins['l'] - $this->margins['r']) / 2;



        //Information
        $this->Ln(5);
        $this->SetTextColor(50, 50, 50);
        $this->SetFont($this->font, 'B', 10);
        $this->setLeftMargin($this->margins['l'] + $width);
        $this->Cell(0, $lineheight, iconv("UTF-8", "ISO-8859-1//TRANSLIT", $this->lang['grand_total']), 0, 0, 'L');

        $this->setLeftMargin($this->margins['l'] );
        $this->Ln(7);
        $this->SetLineWidth(0.4);
        $this->Line($this->margins['l'] + $width, $this->GetY(), $this->margins['l'] + $width + $width,
            $this->GetY());


        $this->SetFont($this->font, '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Ln(7);

        $width_other = ($this->document['w'] - $this->margins['l'] - $this->margins['r'] - $this->firstColumnWidth - ($this->columns * $this->columnSpacing)) / ($this->columns - 1);
        $cellHeight  = 8;
        $bgcolor     = (1 - $this->columnOpacity) * 255;


        //Add totals
        if ($this->totals) {
            foreach ($this->totals as $total) {
                $this->SetTextColor(50, 50, 50);
                $this->SetFillColor($bgcolor, $bgcolor, $bgcolor);
                $this->Cell(1 + $this->firstColumnWidth, $cellHeight, '', 0, 0, 'L', 0);
                for ($i = 0; $i < $this->columns - 3; $i++) {
                    $this->Cell($width_other, $cellHeight, '', 0, 0, 'L', 0);
                    $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
                }
                $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
                if ($total['colored']) {
                    $this->SetTextColor(255, 255, 255);
                    $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
                }
                $this->SetFont($this->font, 'b', 8);
                $this->Cell(1, $cellHeight, '', 0, 0, 'L', 1);
                $this->Cell($width_other - 1, $cellHeight, iconv('UTF-8', 'windows-1252', $total['name']), 0, 0, 'L',
                    1);
                $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
                $this->SetFont($this->font, 'b', 8);
                $this->SetFillColor($bgcolor, $bgcolor, $bgcolor);
                if ($total['colored']) {
                    $this->SetTextColor(255, 255, 255);
                    $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
                }
                $this->Cell($width_other, $cellHeight, iconv('UTF-8', 'windows-1252', $total['value']), 0, 0, 'C', 1);
                $this->Ln();
                $this->Ln($this->columnSpacing);
            }
        }
    }



    public function Body()
    {


        $width_other = ($this->document['w'] - $this->margins['l'] - $this->margins['r'] - $this->firstColumnWidth - ($this->columns * $this->columnSpacing)) / ($this->columns - 1);
        $cellHeight  = 8;
        $bgcolor     = (1 - $this->columnOpacity) * 255;
        $colWidth = $this->firstColumnWidth;
        if ($this->sections) {
            foreach ($this->sections as $section){

                $this->section_header($section);
                $this->table_header();

                foreach($section['items'] as $item) {

                    $this->Ln(1);
                    $x = $this->GetX();
                    $cHeight = $cellHeight;
                    $this->SetFont($this->font, 'b', 8);
                    $this->SetTextColor(50, 50, 50);
                    $this->SetFillColor($bgcolor, $bgcolor, $bgcolor);

                    $skill_height_estimate = 0;
                    if($item['skills'] ){
                        $skill_count = count($item['skills']);
                        $skill_line_estimate = ceil($skill_count/3);
                        $skill_height_estimate = $skill_line_estimate * 3 + 2; // 2 = spacer
                    }

                    $description_height = 0;
                    if($item['description']){
                        $description_item_count = count($item['description']);
                        $description_height = $description_item_count * 12;
                        $spacer_height = $description_item_count * 2; // 2 = spacer
                        $description_height = $description_height + $spacer_height;

                    }

                    $height = $description_height + $skill_height_estimate + 20;
                    $room_left = $this->GetPageHeight() - $this->GetY();

                    if($height >= $room_left){
                        $this->AddPage();
                    }

                    $this->Cell(1, $cHeight, '', 0, 0, 'L', 1);
                    $x = $this->GetX();
                    $this->Cell($this->firstColumnWidth, $cHeight, iconv("UTF-8", "ISO-8859-1//TRANSLIT", $item['item']), 0, 0, 'L', 1);
                    if ($item['description'] || $item['skills']) {
                        $colWidth = ($this->firstColumnWidth - 2) / count(max(1, $item['description'][0]));
                        $resetX = $this->GetX();
                        $resetY = $this->GetY();
                        $this->SetTextColor(120, 120, 120);
                        $this->SetXY($x, $this->GetY() + 8);


                        if($item['skills'] && count($item['skills'])) {
                            $this->SetFont($this->font, '', 6);
                            $skills_string = implode(', ', array_map(function($el){ return $el['description']; }, $item['skills']));
                            $this->MultiCell($this->firstColumnWidth, 3, "Performed Tasks:\n".$skills_string, 0, 'L', 1);
                            $this->SetX($x);
                        }
                        if($item['description']) {
                            if (!is_array($item['description'])) {
                                $this->SetFont($this->font, '', 7);
                                $this->MultiCell($this->firstColumnWidth, 3, iconv("UTF-8", "ISO-8859-1//TRANSLIT", $item['description']), 0, 'L', 1);
                            } else {
                                $this->SetFont($this->font, '', 6);
                                // Data
                                foreach ($item['description'] as $row) {
                                    foreach ($row as $idx => $col) {
                                        // Make 3rd row longer when 3 cols
                                        $colWidthMultiplier = 1;
                                        if (count($row) == 3) {
                                            $colWidthMultiplier = $idx == 2 ? 1.5 : 0.75;
                                        }
                                        $this->Cell($colWidth * $colWidthMultiplier, 6, $col, 0, 0, null, true);
                                    }
                                    // Add spacer (=2) to left side of table
                                    $this->Cell(2, 6, '', 0, 0, null, true);
                                    $this->Ln();
                                    $this->SetX($x);
                                }
                            }
                        }
                        //Calculate Height
                        $newY = $this->GetY();
                        $cHeight = $newY - $resetY + 2;
                        //Make our spacer cell the same height
                        $this->SetXY($x - 1, $resetY);
                        $this->Cell(1, $cHeight, '', 0, 0, 'L', 1);
                        //Draw empty cell
                        $this->SetXY($x, $newY);
                        $this->Cell($this->firstColumnWidth, 2, '', 0, 0, 'L', 1);
                        $this->SetXY($resetX, $resetY);
                    }


                    $this->SetTextColor(50, 50, 50);
                    $this->SetFont($this->font, '', 8);
                    $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                    $this->Cell($width_other, $cHeight, iconv('UTF-8', 'windows-1252',
                        $this->currency . ' ' . number_format($item['price'], 2, $this->referenceformat[0],
                            $this->referenceformat[1])), 0, 0, 'C', 1);
                    $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                    $this->Cell($width_other, $cHeight, $item['total_quantity'], 0, 0, 'C', 1);
                    $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                    $this->Cell($width_other, $cHeight, $item['quantity'], 0, 0, 'C', 1);
                    if (isset($this->otPriceField)) {
                        $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                        if (isset($item['price_ot'])) {
                            $this->Cell($width_other, $cHeight, iconv('UTF-8', 'windows-1252', $item['price_ot']), 0, 0,
                                'C', 1);
                        } else {
                            $this->Cell($width_other, $cHeight, '', 0, 0, 'C', 1);
                        }
                    }
                    if (isset($this->otQuantityField)) {
                        $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                        if (isset($item['quantity_ot'])) {
                            $this->Cell($width_other, $cHeight, iconv('UTF-8', 'windows-1252', $item['quantity_ot']), 0, 0, 'C', 1);
                        } else {
                            $this->Cell($width_other, $cHeight, '', 0, 0, 'C', 1);
                        }

                    }
                    $this->Cell($this->columnSpacing, $cHeight, '', 0, 0, 'L', 0);
                    $this->Cell($width_other, $cHeight, iconv('UTF-8', 'windows-1252',
                        $this->currency . ' ' . number_format($item['total'], 2, $this->referenceformat[0],
                            $this->referenceformat[1])), 0, 0, 'C', 1);
                    $this->Ln();
                    $this->Ln($this->columnSpacing);
                }

                // Section Total Add totals

                $this->SetTextColor(50, 50, 50);
                $this->SetFillColor($bgcolor, $bgcolor, $bgcolor);
                $this->Cell(1 + $this->firstColumnWidth, $cellHeight, '', 0, 0, 'L', 0);
                for ($i = 0; $i < $this->columns - 3; $i++) {
                    $this->Cell($width_other, $cellHeight, '', 0, 0, 'L', 0);
                    $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
                }
                $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
//                if ($total['colored']) {
//                    $this->SetTextColor(255, 255, 255);
//                    $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
//                }
                $this->SetFont($this->font, 'b', 8);
                $this->Cell(1, $cellHeight, '', 0, 0, 'L', 1);
                $this->Cell($width_other - 1, $cellHeight, iconv('UTF-8', 'windows-1252', 'Total'), 0, 0, 'L',
                    1);
                $this->Cell($this->columnSpacing, $cellHeight, '', 0, 0, 'L', 0);
                $this->SetFont($this->font, 'b', 8);
                $this->SetFillColor($bgcolor, $bgcolor, $bgcolor);
//                if ($total['colored']) {
//                    $this->SetTextColor(255, 255, 255);
//                    $this->SetFillColor($this->color[0], $this->color[1], $this->color[2]);
//                }
                $this->Cell($width_other, $cellHeight, iconv('UTF-8', 'windows-1252', '$'.$section['total']), 0, 0, 'C', 1);
                $this->Ln();
                $this->Ln($this->columnSpacing);





            }

        }
        $badgeX = $this->getX();
        $badgeY = $this->getY();



        $this->totals_section();

        $this->productsEnded = true;
        $this->Ln();
        $this->Ln(3);


        //Badge
        if ($this->badge) {
            $badge  = ' ' . mb_strtoupper($this->badge, 'UTF-8') . ' ';
            $resetX = $this->getX();
            $resetY = $this->getY();
            $this->setXY($badgeX, $badgeY + 15);
            $this->SetLineWidth(0.4);
            $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
            $this->setTextColor($this->color[0], $this->color[1], $this->color[2]);
            $this->SetFont($this->font, 'b', 15);
            $this->Rotate(10, $this->getX(), $this->getY());
            $this->Rect($this->GetX(), $this->GetY(), $this->GetStringWidth($badge) + 2, 10);
            $this->Write(10,  iconv('UTF-8', 'windows-1252',mb_strtoupper($badge, 'UTF-8')));
            $this->Rotate(0);
            if ($resetY > $this->getY() + 20) {
                $this->setXY($resetX, $resetY);
            } else {
                $this->Ln(18);
            }
        }


        //Add information
        foreach ($this->addText as $text) {
            if ($text[0] == 'title') {
                $this->SetFont($this->font, 'b', 9);
                $this->SetTextColor(50, 50, 50);
                $this->Cell(0, 10, iconv("UTF-8", "ISO-8859-1//TRANSLIT", mb_strtoupper($text[1], 'UTF-8')), 0, 0, 'L', 0);
                $this->Ln();
                $this->SetLineWidth(0.3);
                $this->SetDrawColor($this->color[0], $this->color[1], $this->color[2]);
                $this->Line($this->margins['l'], $this->GetY(), $this->document['w'] - $this->margins['r'],
                    $this->GetY());
                $this->Ln(4);
            }
            if ($text[0] == 'paragraph') {
                $this->SetTextColor(80, 80, 80);
                $this->SetFont($this->font, '', 8);
                $this->MultiCell(0, 4, iconv("UTF-8", "ISO-8859-1//TRANSLIT", $text[1]), 0, 'L', 0);
                $this->Ln(4);
            }
        }
    }

    public function Footer()
    {
        $this->SetY(-$this->margins['t']);
        $this->SetFont($this->font, '', 8);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(0, 10, $this->footernote, 0, 0, 'L');
        $this->Cell(0, 10, $this->lang['page'] . ' ' . $this->PageNo() . ' ' . $this->lang['page_of'] . ' {nb}', 0, 0,
            'R');
    }

    public function Rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1) {
            $x = $this->x;
        }
        if ($y == -1) {
            $y = $this->y;
        }
        if ($this->angle != 0) {
            $this->_out('Q');
        }
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c     = cos($angle);
            $s     = sin($angle);
            $cx    = $x * $this->k;
            $cy    = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy,
                -$cx, -$cy));
        }
    }

    public function _endpage()
    {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

}
