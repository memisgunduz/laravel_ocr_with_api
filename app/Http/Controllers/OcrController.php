<?php

namespace App\Http\Controllers;

use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Http\Requests\OCRRequest;
use Illuminate\Http\Request;
use PDF;

class OcrController extends Controller
{
    public function pdfToText($getFileName, $type = "PDF")
    {
        try {
            $pdf = new \Spatie\PdfToImage\Pdf('PDF/' . $getFileName);
            $numberOfpages = $pdf->getNumberOfPages();
            $result = [];
            for ($i = 1; $i <= $numberOfpages; $i++) {
                $filename = $i . "_" . $getFileName . ".jpg";
                $pdf->setPage($i)->saveImage('IMG/' . $filename);


                $ocr = new TesseractOCR();
                $ocr->image('IMG/' . $filename);

                $text = $ocr->run();
                $result[] = $ocr->run();
            }
            return response()->json([
                'success' => true,
                'type' => $type,
                'pages' => $result
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message'=>$th
            ]);
        }
    }
    public function fileUpload(OCRRequest $req)
    {
        if ($req->file()) {
            $text = "";
            if ($req->file('file')->extension() == "pdf") { //PDF FILE
                $fileName = time() . '_' . $req->file->getClientOriginalName();
                $filePath = $req->file('file')->storeAs('PDF', $fileName, '');

                return $this->pdfToText($fileName);
            }
            if ($req->file('file')->extension() == "docx") { //DOCX FILE
                $fileName = time() . '_' . $req->file->getClientOriginalName();
                $filePath = $req->file('file')->storeAs('DOCX', $fileName, '');

                $domPdfPath = base_path('vendor/dompdf/dompdf');
                \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
                \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
                $Content = \PhpOffice\PhpWord\IOFactory::load(public_path('DOCX/' . $fileName));
                $PDFWriter = \PhpOffice\PhpWord\IOFactory::createWriter($Content, 'PDF');
                $PDFWriter->save(public_path('PDF/' . $fileName . '.pdf'));

                return $this->pdfToText($fileName . '.pdf', 'DOCX');
            } else { //IMAGE FILES(PNG,JPEG,JPG)
                try {
                    $fileName = time() . '_' . $req->file->getClientOriginalName();
                    $filePath = $req->file('file')->storeAs('IMG', $fileName, '');

                    $ocr = new TesseractOCR();
                    $ocr->image($filePath);

                    $text = $ocr->run();

                    return response()->json([
                        'text' => $text,
                        'success' => true,
                        'type' => $req->file('file')->extension(),
                    ]);
                } catch (\Throwable $th) {
                    return response()->json([
                        'success' => false,
                        'message'=>$th
                    ]);
                }
            }
        }
    }
}
