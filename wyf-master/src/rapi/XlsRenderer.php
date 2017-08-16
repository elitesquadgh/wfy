<?php

class XlsRenderer extends ReportRenderer
{
    private $spreadsheet;
    private $worksheet;
    private $row = 1;
    
    public function __construct()
    {
        $this->spreadsheet = new PHPExcel($file);
        $this->spreadsheet->getProperties()
            ->setCreator('WYF PHP Framework')
            ->setTitle('Report');
        
        $this->worksheet = $this->spreadsheet->getActiveSheet();
        $this->worksheet->getHeaderFooter()
            ->setEvenFooter("Generated on ".date("jS F, Y @ g:i:s A")." by ".$_SESSION["user_lastname"]." ".$_SESSION["user_firstname"]);
        $this->worksheet->getHeaderFooter()
            ->setOddFooter("Generated on ".date("jS F, Y @ g:i:s A")." by ".$_SESSION["user_lastname"]." ".$_SESSION["user_firstname"]);        
    }
    
    public function output() 
    {
        $writer = new PHPExcel_Writer_Excel2007($this->spreadsheet);        
        $file = "app/temp/" . uniqid() . "_report.xlsx";
        $writer->save($file);
        Application::redirect("/$file");        
    }

    public function renderLogo(LogoContent $content) 
    {
        
    }
    
    private function renderRow($rowData, $types, $style, $fill)
    {
        foreach($rowData as $i => $field)
        {
            switch($types[$i])
            {
                case "number":
                    $field = str_replace(",", "", $field);
                    $field = $field === null || $field == "" ? "0" : round($field, 0);
                    $this->worksheet->getCellByColumnAndRow($col, $this->row)
                        ->getStyle()->getNumberFormat()
                        ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
                     break;
                case "double":
                    $field = str_replace(",", "", $field);
                    $field = $field === null || $field == "" ? "0.00" : round($field, 2);
                    $this->worksheet->getCellByColumnAndRow($col, $this->row)
                        ->getStyle()->getNumberFormat()
                        ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                    break;
                default:
                    if(is_numeric($field))
                    {
                        $field = "'$field";
                    }
                    break;
            }
            $this->worksheet->setCellValueByColumnAndRow($col, $this->row, trim($field));
            $this->worksheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            $this->worksheet->getStyleByColumnAndRow($col, $this->row)
                ->getBorders()->getAllBorders()
                ->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN)
                ->getColor()->setRGB($style['body:border']);

            if($fill)
            {
                $this->worksheet->getStyleByColumnAndRow($col, $this->row)
                    ->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                $this->worksheet->getStyleByColumnAndRow($col, $this->row)
                    ->getFill()->getStartColor()->setARGB($style['body:stripe']);
            }
            $col++;
        }        
    }

    public function renderTable(TableContent $content) 
    {
        $style = array(
            'header:border' => $this->convertColor(array(200,200,200)),
            'header:background' => $this->convertColor(array(200,200,200)),
            'header:text' => $this->convertColor(array(255,255,255)),
            'body:background' => $this->convertColor(array(255,255,255)),
            'body:stripe' => $this->convertColor(array(250, 250, 250)),
            'body:border' => $this->convertColor(array(200, 200, 200)),
            'body:text' => $this->convertColor(array(0,0,0))
        );  
        
        if($content->getAsTotalsBox())
        {
            $totals = $content->getData();
            for($i = 0; $i<$this->numColumns; $i++)
            {
                $this->worksheet->setCellValueByColumnAndRow($i,$this->row,$totals[$i]);
                $this->worksheet->getStyleByColumnAndRow($i, $this->row)
                    ->getFont()
                    ->setBold(true);
                
                $this->worksheet->getStyleByColumnAndRow($i, $this->row)
                    ->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THICK)->getColor()->setRGB($style['body:border']);
            }
        }
        else
        {
            $headers = $content->getHeaders();
            $this->numColumns = count($headers);

            $col = 0;
            foreach($headers as $header)
            {
                $this->worksheet->setCellValueByColumnAndRow($col,$this->row,str_replace("\\n","\n",$header));
                $this->worksheet->getStyleByColumnAndRow($col, $this->row)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
                $this->worksheet->getStyleByColumnAndRow($col, $this->row)->getFill()->getStartColor()->setRGB($style['header:background']);
                $this->worksheet->getStyleByColumnAndRow($col, $this->row)->getFont()->setBold(true)->getColor()->setRGB($style['header:text']);
                $col++;
            }

            $fill = false;
            $types = $content->getDataTypes();
            $widths = $content->getTableWidths();

            foreach($content->getData() as $rowData)
            {
                $this->row++;
                $col = 0;
                $this->renderRow($rowData, $types, $style, $fill);
                $fill = !$fill;
            }
        }
        $this->row++;
    }

    public function renderText(TextContent $content) 
    {
        switch($content->getStyle())
        {
            case 'title':
                $this->worksheet->setCellValueByColumnAndRow(0, $this->row, $content->getText());
                $this->worksheet->getStyleByColumnAndRow(0, $this->row)
                    ->getFont()
                        ->setBold(true)
                        ->setSize(16)
                        ->setName('Helvetica');
                $this->worksheet->getRowDimension($this->row)
                    ->setRowHeight(36);     
                break;
            
            case 'heading':
                $this->row++;
                $this->worksheet->setCellValueByColumnAndRow(0, $this->row, $content->getText());
                $this->worksheet->getStyleByColumnAndRow(0, $this->row)
                    ->getFont()
                        ->setBold(true)
                        ->setSize(12)
                        ->setName('Helvetica');
                $this->worksheet->getRowDimension($this->row)
                    ->setRowHeight(26);                     
        }
        $this->row++;
    }
    
    private function convertColor($color)
    {
        return dechex($color[0]) . dechex($color[1]) . dechex($color[2]);
    }    
}

