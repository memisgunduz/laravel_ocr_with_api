<?php

namespace App\Jobs;

use App\Http\Controllers\OcrController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\GetData;

class OcrJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileName;
    protected $filePath;
    protected $extension;
    protected $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fileName, $filePath, $extension, $id)
    {
        $this->fileName = $fileName;
        $this->filePath = $filePath;
        $this->id = $id;
        $this->extension = $extension;
        $this->onQueue('ocr');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $ocrController = new OcrController();
        $result = $ocrController->ocr($this->fileName, $this->filePath, $this->extension, $this->id);
        $data = new GetData();
        if($result->getData()->success){
            $data->json_data = json_encode($result->getData());
            $data->save();
        }else{
            $fileType=$this->extension;
            $ocrController->deleteFile(public_path($ocrController->getFolderName($fileType)  . $this->fileName));
        }
        $ocrController->unblockJob($this->id);
    }
    public function uniqueId()
    {
        return $this->id;
    }
}
