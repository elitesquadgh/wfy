<?php

class PdfRenderer extends ReportRenderer
{
    private $pdf;
    
    public function __construct()
    {
        $this->pdf = new PDFDocument();
    }
    
    public function output() 
    {
        $this->pdf->Output();
    }

    public function renderLogo(\LogoContent $content) 
    {
        $this->pdf->image($content->image,null,null,8,8);
        $this->pdf->sety($this->pdf->getY() - 8);
        $this->pdf->SetFont("Helvetica","B","18");
        $this->pdf->cell(9);$this->pdf->cell(0,8,$content->title);

        $this->pdf->SetFont("Arial",null,7);
        foreach($content->address as $address)
        {
            $this->pdf->setx(($this->pdf->GetStringWidth($address)+10) * -1);
            $this->pdf->cell(0,3,$address);
            $this->pdf->Ln();
        }

        $this->pdf->Ln(5);        
    }

    public function renderTable(\TableContent $content) 
    {
        $params = $content->getDataParams();
        
        $style = array(
            'header:border' => array(200,200,200),
            'header:background' => array(200,200,200),
            'header:text' => array(255,255,255),
            'body:background' => array(255,255,255),
            'body:stripe' => array(250, 250, 250),
            'body:border' => array(200, 200, 200),
            'body:text' => array(0,0,0),
            'decoration' => true
        );  
        
        if($content->getAsTotalsBox())
        {
            $this->pdf->totalsBox($content->getData(), $params);
        }
        else if($content->getAutoTotals())
        {
            $this->pdf->table($content->getHeaders(),$content->getData(), $style, $params);
            $totals = $content->getTotals();
            $totals[0] = "Totals";
            $this->pdf->totalsBox($totals,$params);
        }
        else
        {
            $this->pdf->table($content->getHeaders(),$content->getData(), $style, $params);
        }             
    }
    
    public function renderText(\TextContent $content) 
    {
        switch($content->getStyle())
        {
            case 'title':
                $this->pdf->Ln(5);
                $this->pdf->SetFont('Helvetica', 'B');
                $this->pdf->SetFontSize(16);
                $this->pdf->Cell(0, 0, $content->getText());
                $this->pdf->Ln(5);
                break;
            
            case 'heading':
                $this->pdf->Ln(8);                
                $this->pdf->SetFont('Helvetica', 'B');
                $this->pdf->SetFontSize(12);
                $this->pdf->Cell(0, 0, $content->getText());
                $this->pdf->Ln(3);
                break;
            
            default :
                $this->pdf->SetFont('Helvetica');
                $this->pdf->SetFontSize(12);
                $this->pdf->Cell(0, 0, $content->getText());
                $this->pdf->Ln(12 * 0.353);
                break;
        }        
    }
}
