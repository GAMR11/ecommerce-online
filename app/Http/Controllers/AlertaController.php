<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RealRashid\SweetAlert\Facades\Alert;
use App\Services\TwilioService;

class AlertaController extends Controller
{

    protected $twilio;

    public function __construct(TwilioService $twilio)
    {
        $this->twilio = $twilio;
    }

    /**
     * Display a listing of the resource.
     */
    public function sendSMS()
    {
        // $this->twilio->sendSms('+593988703045', 'Mensaje numero 2 desde twilio');
        // dd("mensaje enviado");
        // $categorias = Categoria::all();
        // return view('categoria.categoria-table',compact('categorias'));
    }


}
