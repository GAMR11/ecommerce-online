<?php

namespace App\Console\Commands;

// use App\Models\Anuncio;
use App\Models\Cliente;
use App\Models\Producto;
use App\Providers\AppServiceProvider;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Console\Commands\Alerts\notiReposicionStock;
use Symfony\Component\Console\Input\InputOption;

class notificacionReposicionStock extends Command
{
    protected $signature = 'command:notificacionreposicionstock';

    protected $description = 'Comando para el envío de notificación por correo al administrador cuando un determinado articulo posee 0 unidades en stock.';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('command:notificacionreposicionstock')
            ->setDescription('Ejecuta la notificación de reposición de stock.')
            ->addOption('subject', null, InputOption::VALUE_REQUIRED, 'Asunto del reporte')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Correo destinatario')
            ->addOption('cc', null, InputOption::VALUE_OPTIONAL, 'Correo en copia')
            ->addOption('startDate', null, InputOption::VALUE_REQUIRED, 'Fecha de inicio')
            ->addOption('endDate', null, InputOption::VALUE_REQUIRED, 'Fecha de fin');
    }

    public function handle()
    {
        $productosSinStock = Producto::whereHas('inventarios', function ($query)
        {
            $query->where('cantidad', '=', 0);
        })->get();

        if ($productosSinStock->isEmpty())
        {
            $producto = null;
            Mail::send(
                'mail.notificacionReposicionStock',  // Nombre de la vista Blade
                ['producto' => $producto],   // Datos que quieres pasar a la vista
                function ($message) {
                    $message->to($this->option('to'))
                            ->subject($this->option('subject'));
                }
            );
            return 0;
        }

        // if(empty($to))
        // {
        //     $to = 'gamorales@hiberus.com';
        // }

        foreach ($productosSinStock as $producto)
        {
            Mail::send(
                'mail.notificacionReposicionStock',  // Nombre de la vista Blade
                ['producto' => $producto],   // Datos que quieres pasar a la vista
                function ($message) {
                    $message->to($this->option('to'))
                            ->subject($this->option('subject'));
                }
            );
        }
    //    $notiRS = new notiReposicionStock();
    //    $notiRS->execute();

        Storage::append('file.txt','hola-'.date('Y-m-d H:i:s'));
    }


}
