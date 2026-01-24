<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RealRashid\SweetAlert\Facades\Alert;

class ReporteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reportes = Reporte::all();
        return view('report.list', data: compact('reportes'));
    }

    public function update(Request $request, $id)
    {
        // Desestructuración de la solicitud



        $subject = $request->get('subject');
        $to = $request->get('to');
        // $cc = $request->get('cc');
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');
        $time = $request->get('time');
        $period = $request->get('period');
        $status = $request->get('status');

        $cc = 'gamorales@hiberus.com';



        // Intentar encontrar la categoría
        $reporte = Reporte::find($id);

        if (!$reporte) {
            // Manejar el caso donde la categoría no existe
            Alert::toast('Reporte no encontrado', 'error');
            return redirect()->route('reporte.index');
        }

        try {
            // Actualizar la categoría
            $reporte->update([
                'subject'=>$subject,
                'to'=>$to,
                'cc'=>$cc,
                'startDate'=>$startDate,
                'endDate'=>$endDate,
                'time'=>$time,
                'period'=>$period,
                'status'=>$status
            ]);

            // Mensaje de éxito informativo
            Alert::toast("Reporte actualizado con éxito", 'success');
        } catch (\Exception $e) {
            // Manejo de errores
            Alert::toast('Error al actualizar el reporte: ' . $e->getMessage(), 'error');
            return redirect()->back()->withInput();
        }

        return redirect()->route('reporte.index');
    }

}
