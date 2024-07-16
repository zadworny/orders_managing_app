<?php
use Dompdf\Dompdf;

class PDFGenerator
{
    private Dompdf $dompdf;

    public function __construct()
    {
        $this->dompdf = new Dompdf();
    }

    public function generateFromHtml(string $html, string $filePath, string $paper = 'A4', string $orientation = 'portrait'): void
    {
        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper($paper, $orientation);
        $this->dompdf->set_option('isRemoteEnabled', TRUE);
        $this->dompdf->render();

        $output = $this->dompdf->output();
        file_put_contents($filePath, $output);
    }

    public function generateFromFile(string $htmlFilePath, string $pdfFilePath, string $paper = 'A4', string $orientation = 'portrait'): void
    {
        $htmlContent = file_get_contents($htmlFilePath);
        if ($htmlContent === false) {
            throw new Exception("Failed to read the HTML file: {$htmlFilePath}");
        }
        $this->generateFromHtml($htmlContent, $pdfFilePath, $paper, $orientation);
    }
}
