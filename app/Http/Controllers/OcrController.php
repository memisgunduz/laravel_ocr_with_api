<?php

namespace App\Http\Controllers;

use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Http\Requests\OCRRequest;
use App\Jobs\OcrJob;
use App\Models\OcrJobBlock;
use Illuminate\Http\Request;
use Spatie\PdfToImage\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OcrController extends Controller
{

    public function dispatchJob(OCRRequest $request){
        $id = $request->id;
        $post = OcrJobBlock::where('block_id',$id)->first();
        /* if(!$post){ */
            //$this->blockJob($id);
            $fileFolder="";
            $fileType=$request->file('file')->extension();
            if($fileType=="pdf"){
                $fileFolder="PDF";
            }else if($fileType=="docx"){
                $fileFolder="DOCX";
            }else{
                $fileFolder="IMG";
            }
            $fileName = Str::random(32) . '.' . $fileType;
            $filePath = $request->file('file')->storeAs($fileFolder, $fileName, '');

            $veri=OcrJob::dispatch($fileName, $filePath, $fileType, $request->id);

            return response()->json([
                "success"=>true,
                "message"=>"Datei wurde erfolgreich zur Queue hinzugefügt!"
            ]);
        /* }

        return response()->json([
            "success"=>false,
            "message"=>"Datei bereits in der OCR Queue!"
        ]); */
    }

    function blockJob($id) {
        $job = new OcrJobBlock();
        $job->block_id = $id;
        $job->save();
    }

    function unblockJob($id) {
        OcrJobBlock::where('block_id', $id)->delete();
    }

    public function pdfToText($getFileName, $id, $type = "PDF")
    {
        try {
            $pdf = new Pdf(public_path('PDF/' . $getFileName.$type));
            $numberOfpages = $pdf->getNumberOfPages();
            $result = [];

            for ($i = 1; $i <= $numberOfpages; $i++) {
                $filename = $i . "_" . $getFileName . ".jpg";
                $pdf->setPage($i)->saveImage(public_path('IMG/' . $filename));

                $ocr = new TesseractOCR();
                $ocr->image(public_path('IMG/' . $filename));

                $text = $ocr->run();
                $result[] = $ocr->run();
            }

            $this->unblockJob($id);

            return response()->json([
                'success' => true,
                'type' => $type,
                'pages' => $result
            ]);
        } catch (\Throwable $th) {
            $this->unblockJob($id);

            return $th;

            return response()->json([
                'success' => false,
                'message'=>$th
            ]);
        }
    }

    public function fileUpload(OCRRequest $request)
    {
        $fileName = Str::random(32);
        $filePath = $request->file('file')->storeAs('PDF', $fileName, '');
        return $this->ocr($fileName, $filePath, $request->file('file')->extension(), $request->id);
    }

    public function ocr($fileName, $filePath, $extension, $id) {

        $text = "";
        if ($extension == "pdf") {
            return $this->pdfToText($fileName, $id);
        }
        if ($extension == "docx") {
            try {
                $domPdfPath = base_path('vendor/dompdf/dompdf');
                \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
                \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
                $Content = \PhpOffice\PhpWord\IOFactory::load(public_path('DOCX/' . $fileName));
                $PDFWriter = \PhpOffice\PhpWord\IOFactory::createWriter($Content, 'PDF');
                $PDFWriter->save(public_path('PDF/' . $fileName . '.pdf'));
            } catch (\Throwable $th) {
                return response()->json([
                    'success' => false,
                    'message'=>$th
                ]);
            }
            return $this->pdfToText($fileName, $id, ".pdf");
        } else {
            try {
                $ocr = new TesseractOCR();
                $ocr->image(public_path($filePath));
                $text = $ocr->run();
                $this->unblockJob($id);
                return response()->json([
                    'success' => true,
                    'type' => $extension,
                    'text' => $text,
                ]);
            } catch (\Throwable $th) {
                $this->unblockJob($id);
                return response()->json([
                    'success' => false,
                    'message'=>$th
                ]);
            }
        }
    }
}
