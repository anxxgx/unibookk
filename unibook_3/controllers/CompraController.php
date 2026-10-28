<?php

require_once "models/Compra.php";
require_once "models/Libro.php";
require_once "models/Usuario.php";
require_once "models/Notificacion.php";


class CompraController
{

    private $modelo;
    private $libroModelo;
    private $usuarioModelo;
    private $notificacionModelo;
    private $conexion;


    public function __construct($conexion)
    {
        $this->conexion = $conexion;

        $this->modelo =
            new Compra($conexion);

        $this->libroModelo =
            new Libro($conexion);

        $this->usuarioModelo =
            new Usuario($conexion);

        $this->notificacionModelo =
            new Notificacion($conexion);
    }


    public function prepararCheckout($id)
    {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        $resultado = [
            'valido'   => false,
            'motivo'   => null,
            'libro'    => null,
            'vendedor' => null,
        ];

        if(!isset($_SESSION['id_usuario'])){
            $resultado['motivo'] = 'sesion';
            return $resultado;
        }

        $id = (int)$id;

        if($id <= 0){
            $resultado['motivo'] = 'invalido';
            return $resultado;
        }

        $libro = $this->libroModelo->obtenerPorId($id);

        if(!$libro){
            $resultado['motivo'] = 'invalido';
            return $resultado;
        }

        $usuario_id = (int)$_SESSION['id_usuario'];

        if((int)$libro['usuario_id'] === $usuario_id){
            $resultado['motivo'] = 'propio';
            return $resultado;
        }

        if(strtolower($libro['tipo_publicacion']) !== 'venta'){
            $resultado['motivo'] = 'tipo_no_venta';
            return $resultado;
        }

        $disponibilidad =
            $libro['estado_disponibilidad'] ?? 'disponible';

        if($disponibilidad !== 'disponible'){
            $resultado['motivo'] = 'no_disponible';
            return $resultado;
        }

        $vendedor =
            $this->usuarioModelo->buscarPorId(
                $libro['usuario_id']
            );

        $resultado['valido']   = true;
        $resultado['libro']    = $libro;
        $resultado['vendedor'] = $vendedor;

        return $resultado;
    }


    /*
     * Registrar una compra simulada.
     */
    public function confirmar()
    {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['id_usuario'])){
            return [
                'ok' => false,
                'motivo' => 'sesion',
                'libro_id' => 0
            ];
        }

        $libro_id =
            (int)($_POST['libro_id'] ?? 0);

        $comprador_id =
            (int)$_SESSION['id_usuario'];

        $metodosValidos = [
            'pse',
            'nequi',
            'bancolombia',
            'daviplata'
        ];

        $metodo_pago =
            $_POST['metodo_pago'] ?? '';

        if(!in_array(
            $metodo_pago,
            $metodosValidos,
            true
        )){
            $metodo_pago = 'pse';
        }

        if($libro_id <= 0){
            return [
                'ok' => false,
                'motivo' => 'invalido',
                'libro_id' => $libro_id
            ];
        }

        $libro =
            $this->libroModelo->obtenerPorId(
                $libro_id
            );

        if(!$libro){
            return [
                'ok' => false,
                'motivo' => 'invalido',
                'libro_id' => $libro_id
            ];
        }

        $comprador =
            $this->usuarioModelo->buscarPorId(
                $comprador_id
            );

        if(!$comprador){
            return [
                'ok' => false,
                'motivo' => 'sesion',
                'libro_id' => $libro_id
            ];
        }

        $vendedor_id =
            (int)$libro['usuario_id'];

        if($vendedor_id === $comprador_id){
            return [
                'ok' => false,
                'motivo' => 'propio',
                'libro_id' => $libro_id
            ];
        }

        $vendedor =
            $this->usuarioModelo->buscarPorId(
                $vendedor_id
            );

        if(!$vendedor){
            return [
                'ok' => false,
                'motivo' => 'error',
                'libro_id' => $libro_id
            ];
        }

        if(
            strtolower($libro['tipo_publicacion'])
            !== 'venta'
        ){
            return [
                'ok' => false,
                'motivo' => 'tipo_no_venta',
                'libro_id' => $libro_id
            ];
        }

        /* Reservar el libro y crear la compra es una sola operación. */
        $this->conexion->begin_transaction();

        $reservado =
            $this->libroModelo
                ->reservarSiDisponible(
                    $libro_id
                );

        if(!$reservado){
            $this->conexion->rollback();
            return [
                'ok' => false,
                'motivo' => 'no_disponible',
                'libro_id' => $libro_id
            ];
        }

        $precio =
            $libro['precio'];

        $compra_id =
            $this->modelo->crear(
                $libro_id,
                $comprador_id,
                $vendedor_id,
                $precio,
                $metodo_pago
            );

        if(!$compra_id){
            $this->conexion->rollback();

            return [
                'ok' => false,
                'motivo' => 'error',
                'libro_id' => $libro_id
            ];
        }

        $this->conexion->commit();


        /*
         * NOTIFICACIÓN AL VENDEDOR
         *
         * La compra quedó registrada y
         * está esperando su confirmación.
         */
        $nombreComprador =
            trim(
                ($comprador['nombre'] ?? '') . ' ' .
                ($comprador['apellido'] ?? '')
            );

        $tituloLibro =
            $libro['titulo'] ?? 'tu libro';

        $mensajeVendedor =
            $nombreComprador .
            " ha realizado una compra de \"" .
            $tituloLibro .
            "\". Revisa la operación y confirma la venta.";

        $this->notificacionModelo->crear(
            $vendedor_id,
            'nueva_compra',
            'Nueva compra',
            $mensajeVendedor,
            'index.php?accion=detalle_compra&id=' . $compra_id
        );


        return [
            'ok' => true,
            'motivo' => 'reservada',
            'libro_id' => $libro_id,
            'compra_id' => $compra_id
        ];
    }


    /*
     * El vendedor confirma la venta.
     */
    public function aceptarVenta($compra_id)
    {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['id_usuario'])){
            return [
                'ok' => false,
                'motivo' => 'sesion'
            ];
        }

        $compra_id =
            (int)$compra_id;

        $vendedor_id =
            (int)$_SESSION['id_usuario'];

        if($compra_id <= 0){
            return [
                'ok' => false,
                'motivo' => 'invalido'
            ];
        }

        $detalle =
            $this->modelo
                ->obtenerDetalleParaUsuario(
                    $compra_id,
                    $vendedor_id
                );

        if(!$detalle){
            return [
                'ok' => false,
                'motivo' => 'no_autorizado'
            ];
        }

        if(
            (int)$detalle['vendedor_id']
            !== $vendedor_id
        ){
            return [
                'ok' => false,
                'motivo' => 'no_autorizado'
            ];
        }

        if($detalle['estado'] !== 'pendiente'){
            return [
                'ok' => false,
                'motivo' => 'estado'
            ];
        }

        /*
         * Compra::confirmarVenta() es la única responsable de la
         * transacción: confirma la compra y cambia el libro de reservado a
         * vendido. Hacer el cambio aquí también dejaba el libro vendido y
         * hacía fallar la segunda actualización.
         */
        $compraActualizada =
            $this->modelo
                ->confirmarVenta(
                    $compra_id,
                    $vendedor_id
                );

        if(!$compraActualizada){
            return [
                'ok' => false,
                'motivo' => 'compra'
            ];
        }

        /*
         * NOTIFICACIÓN AL COMPRADOR
         *
         * El vendedor aceptó la operación.
         */
        $tituloLibro =
            $detalle['titulo'] ?? 'tu libro';

        $mensajeComprador =
            "El vendedor ha confirmado tu compra de \"" .
            $tituloLibro .
            "\". La operación fue aceptada.";

        $this->notificacionModelo->crear(
            $detalle['comprador_id'],
            'compra_confirmada',
            '¡Compra confirmada!',
            $mensajeComprador,
            'index.php?accion=detalle_compra&id=' . $compra_id
        );


        return [
            'ok' => true,
            'motivo' => 'confirmada',
            'compra_id' => $compra_id
        ];
    }


    /*
     * El vendedor rechaza la operación.
     */
    public function rechazarVenta($compra_id)
    {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['id_usuario'])){
            return [
                'ok' => false,
                'motivo' => 'sesion'
            ];
        }

        $compra_id =
            (int)$compra_id;

        $vendedor_id =
            (int)$_SESSION['id_usuario'];

        if($compra_id <= 0){
            return [
                'ok' => false,
                'motivo' => 'invalido'
            ];
        }

        $detalle =
            $this->modelo
                ->obtenerDetalleParaUsuario(
                    $compra_id,
                    $vendedor_id
                );

        if(!$detalle){
            return [
                'ok' => false,
                'motivo' => 'no_autorizado'
            ];
        }

        if(
            (int)$detalle['vendedor_id']
            !== $vendedor_id
        ){
            return [
                'ok' => false,
                'motivo' => 'no_autorizado'
            ];
        }

        if($detalle['estado'] !== 'pendiente'){
            return [
                'ok' => false,
                'motivo' => 'estado'
            ];
        }

        /* cancelarVenta() libera el libro dentro de su propia transacción. */
        $compraActualizada =
            $this->modelo
                ->cancelarVenta(
                    $compra_id,
                    $vendedor_id
                );

        if(!$compraActualizada){
            return [
                'ok' => false,
                'motivo' => 'compra'
            ];
        }

        /*
         * NOTIFICACIÓN AL COMPRADOR
         *
         * El vendedor rechazó la operación.
         */
        $tituloLibro =
            $detalle['titulo'] ?? 'tu libro';

        $mensajeComprador =
            "El vendedor canceló la compra de \"" .
            $tituloLibro .
            "\". El libro volvió a estar disponible.";

        $this->notificacionModelo->crear(
            $detalle['comprador_id'],
            'compra_cancelada',
            'Compra cancelada',
            $mensajeComprador,
            'index.php?accion=detalle_libro&id=' . $detalle['libro_id']
        );


        return [
            'ok' => true,
            'motivo' => 'cancelada',
            'compra_id' => $compra_id
        ];
    }


    public function confirmacion(
        $compra_id,
        $usuario_id
    ){

        $compra_id =
            (int)$compra_id;

        $usuario_id =
            (int)$usuario_id;

        if(
            $compra_id <= 0 ||
            $usuario_id <= 0
        ){
            return null;
        }

        return $this->modelo
            ->obtenerDetalleParaUsuario(
                $compra_id,
                $usuario_id
            );
    }


    public function misCompras()
    {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['id_usuario'])){
            return null;
        }

        return $this->modelo
            ->obtenerPorComprador(
                (int)$_SESSION['id_usuario']
            );
    }


    public function misVentas()
    {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['id_usuario'])){
            return null;
        }

        return $this->modelo
            ->obtenerPorVendedor(
                (int)$_SESSION['id_usuario']
            );
    }


    /*
    ============================================================
    PANEL DE VENTAS ("MIS VENTAS")
    ============================================================

    Solo lectura. Usa SIEMPRE el vendedor de la sesión.

    REGLAS DE CÁLCULO (todas usan la misma base de filas):

    - Fuente: tabla compras, filtrada por vendedor_id. Cada fila es
      una operación distinta, así que nada se cuenta dos veces.
      Los intercambios están en otra tabla y no entran aquí.
    - Fecha de la operación: fecha_compra (cuándo se registró).
      Es la única fecha usada en KPIs, gráficas y tabla, para que
      los totales coincidan entre sí.
    - Periodo "últimos N días": desde el inicio del día (hoy - N + 1)
      hasta hoy (CURDATE() de MySQL). El periodo anterior son los N
      días inmediatamente previos, de igual duración.
      "Todo el tiempo" no tiene periodo anterior.
    - Completada = estado 'confirmada'. Solo estas suman ingresos.
      'pendiente', 'cancelada' y 'rechazada' nunca suman dinero.
    - Ingresos = suma de compras.precio (copia del precio al
      comprar) de las operaciones completadas. Es el valor vendido,
      no ganancia neta.
    - Tasa de finalización = completadas / total de operaciones del
      periodo (todas las que se registraron, sea cual sea su estado).
    - Promedio por venta = ingresos / completadas.
    - Libros vendidos: cada publicación es una unidad, por lo que
      equivale a las operaciones completadas (no se muestra aparte).
    - Variación: (actual - anterior) / anterior. Si el anterior es 0
      no se calcula (se devuelve null y la vista lo indica). La tasa
      se compara en puntos porcentuales.
    */

    public function panelVentas($periodo = '30')
    {

        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION['id_usuario'])){
            return null;
        }

        $vendedor_id = (int)$_SESSION['id_usuario'];

        // ---------- Periodo (lista blanca) ----------

        $periodosDias = [
            '7'    => 7,
            '30'   => 30,
            '90'   => 90,
            'todo' => null,
        ];

        $periodo = (string)$periodo;

        if(!array_key_exists($periodo, $periodosDias)){
            $periodo = '30';
        }

        $dias = $periodosDias[$periodo];

        $etiquetasPeriodo = [
            '7'    => 'Últimos 7 días',
            '30'   => 'Últimos 30 días',
            '90'   => 'Últimos 90 días',
            'todo' => 'Todo el tiempo',
        ];

        // ---------- Fechas de referencia (reloj de MySQL) ----------

        $bd = $this->modelo->obtenerFechaActualBD();

        $utc = new DateTimeZone('UTC');

        $hoyObj = new DateTimeImmutable($bd['hoy'] . ' 00:00:00', $utc);

        $hoy = $hoyObj->format('Y-m-d');

        $desde = null;

        if($dias !== null){
            $desde = $hoyObj
                ->modify('-' . ($dias - 1) . ' day')
                ->format('Y-m-d');
        }

        // ---------- Una sola consulta: periodo + periodo anterior ----------

        $filas = $this->modelo->obtenerOperacionesVendedor(
            $vendedor_id,
            $dias === null ? null : (2 * $dias - 1)
        );

        $actual   = [];
        $anterior = [];

        foreach($filas as $fila){

            $dia = substr((string)$fila['fecha_compra'], 0, 10);

            if($desde === null || $dia >= $desde){
                $actual[] = $fila;
            } else {
                $anterior[] = $fila;
            }
        }

        // ---------- Resumen de un conjunto de filas ----------

        $resumir = function ($lista) {

            $r = [
                'total'       => 0,
                'completadas' => 0,
                'pendientes'  => 0,
                'canceladas'  => 0,
                'rechazadas'  => 0,
                'centavos'    => 0,
            ];

            foreach($lista as $f){

                $r['total']++;

                switch(strtolower((string)$f['estado'])){

                    case 'confirmada':
                        $r['completadas']++;
                        $r['centavos'] += (int)round(((float)$f['precio']) * 100);
                    break;

                    case 'pendiente':
                        $r['pendientes']++;
                    break;

                    case 'cancelada':
                        $r['canceladas']++;
                    break;

                    case 'rechazada':
                        $r['rechazadas']++;
                    break;
                }
            }

            $r['ingresos'] = $r['centavos'] / 100;

            $r['tasa'] = $r['total'] > 0
                ? ($r['completadas'] / $r['total']) * 100
                : null;

            $r['promedio'] = $r['completadas'] > 0
                ? $r['ingresos'] / $r['completadas']
                : null;

            return $r;
        };

        $act  = $resumir($actual);
        $prev = $dias !== null ? $resumir($anterior) : null;

        // ---------- Variaciones (null = no calculable) ----------

        $variacion = function ($a, $p) {

            if($a === null || $p === null || $p <= 0){
                return null;
            }

            return round((($a - $p) / $p) * 100, 1);
        };

        $deltas = [
            'ingresos'    => null,
            'completadas' => null,
            'promedio'    => null,
            'tasa_pp'     => null,
        ];

        if($prev !== null){

            $deltas['ingresos']    = $variacion($act['ingresos'], $prev['ingresos']);
            $deltas['completadas'] = $variacion($act['completadas'], $prev['completadas']);
            $deltas['promedio']    = $variacion($act['promedio'], $prev['promedio']);

            if($act['tasa'] !== null && $prev['tasa'] !== null){
                $deltas['tasa_pp'] = round($act['tasa'] - $prev['tasa'], 1);
            }
        }

        // ---------- Etiquetas en español ----------

        $meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];

        $mesesLargos = [
            'enero','febrero','marzo','abril','mayo','junio',
            'julio','agosto','septiembre','octubre','noviembre','diciembre'
        ];

        $diasSemana = ['dom','lun','mar','mié','jue','vie','sáb'];

        $diaCorto = function (DateTimeImmutable $d) use ($meses) {
            return (int)$d->format('j') . ' ' . $meses[(int)$d->format('n') - 1];
        };

        // ---------- Serie temporal de ingresos (solo completadas) ----------

        $completadasActual = [];

        foreach($actual as $f){
            if(strtolower((string)$f['estado']) === 'confirmada'){
                $completadasActual[] = $f;
            }
        }

        $puntos = [];
        $agrupacion = 'dia';

        if($dias !== null && $dias <= 30){

            // Un punto por día, de "desde" a "hoy".

            $agrupacion = 'dia';

            $mapa = [];

            for($i = 0; $i < $dias; $i++){

                $d = (new DateTimeImmutable($desde . ' 00:00:00', $utc))
                        ->modify('+' . $i . ' day');

                $mapa[$d->format('Y-m-d')] = [
                    'etiqueta' => $diaCorto($d),
                    'detalle'  => $diasSemana[(int)$d->format('w')] . ' '
                                  . $diaCorto($d) . ' ' . $d->format('Y'),
                    'centavos' => 0,
                    'ops'      => 0,
                ];
            }

            foreach($completadasActual as $f){

                $k = substr((string)$f['fecha_compra'], 0, 10);

                if(isset($mapa[$k])){
                    $mapa[$k]['centavos'] += (int)round(((float)$f['precio']) * 100);
                    $mapa[$k]['ops']++;
                }
            }

            $puntos = array_values($mapa);

        } elseif($dias !== null) {

            // 90 días: ventanas de 7 días que terminan hoy
            // (la más antigua queda recortada al inicio del periodo).

            $agrupacion = 'semana';

            $nBloques = (int)ceil($dias / 7);

            $bloques = [];

            for($k = $nBloques - 1; $k >= 0; $k--){

                $fin = $hoyObj->modify('-' . (7 * $k) . ' day');
                $ini = $fin->modify('-6 day');

                if($ini->format('Y-m-d') < $desde){
                    $ini = new DateTimeImmutable($desde . ' 00:00:00', $utc);
                }

                $mismoMes = $ini->format('n') === $fin->format('n');

                $etiqueta = $mismoMes
                    ? (int)$ini->format('j') . '–' . $diaCorto($fin)
                    : $diaCorto($ini) . '–' . $diaCorto($fin);

                $bloques[$k] = [
                    'etiqueta' => $etiqueta,
                    'detalle'  => 'Semana del ' . $diaCorto($ini) . ' al '
                                  . $diaCorto($fin) . ' ' . $fin->format('Y'),
                    'centavos' => 0,
                    'ops'      => 0,
                ];
            }

            foreach($completadasActual as $f){

                $dia = new DateTimeImmutable(
                    substr((string)$f['fecha_compra'], 0, 10) . ' 00:00:00',
                    $utc
                );

                $dif = (int)$hoyObj->diff($dia)->days;

                $k = intdiv($dif, 7);

                if(isset($bloques[$k])){
                    $bloques[$k]['centavos'] += (int)round(((float)$f['precio']) * 100);
                    $bloques[$k]['ops']++;
                }
            }

            $puntos = array_values($bloques);

        } else {

            // Todo el tiempo: un punto por mes, del primer mes con
            // operaciones hasta el mes actual.

            $agrupacion = 'mes';

            if(!empty($actual)){

                $primera = $actual[0]['fecha_compra'];

                foreach($actual as $f){
                    if($f['fecha_compra'] < $primera){
                        $primera = $f['fecha_compra'];
                    }
                }

                $cursor = new DateTimeImmutable(
                    substr((string)$primera, 0, 7) . '-01 00:00:00',
                    $utc
                );

                $finMes = new DateTimeImmutable(
                    $hoyObj->format('Y-m') . '-01 00:00:00',
                    $utc
                );

                $mapa = [];

                while($cursor <= $finMes){

                    $mapa[$cursor->format('Y-m')] = [
                        'etiqueta' => $meses[(int)$cursor->format('n') - 1]
                                      . ' ' . $cursor->format('y'),
                        'detalle'  => ucfirst($mesesLargos[(int)$cursor->format('n') - 1])
                                      . ' de ' . $cursor->format('Y'),
                        'centavos' => 0,
                        'ops'      => 0,
                    ];

                    $cursor = $cursor->modify('+1 month');
                }

                foreach($completadasActual as $f){

                    $k = substr((string)$f['fecha_compra'], 0, 7);

                    if(isset($mapa[$k])){
                        $mapa[$k]['centavos'] += (int)round(((float)$f['precio']) * 100);
                        $mapa[$k]['ops']++;
                    }
                }

                $puntos = array_values($mapa);
            }
        }

        foreach($puntos as $i => $pt){
            $puntos[$i]['valor'] = $pt['centavos'] / 100;
            unset($puntos[$i]['centavos']);
        }

        // ---------- Distribución por estado ----------

        $definicionEstados = [
            ['clave' => 'confirmada', 'etiqueta' => 'Completadas', 'campo' => 'completadas', 'siempre' => true],
            ['clave' => 'pendiente',  'etiqueta' => 'Pendientes',  'campo' => 'pendientes',  'siempre' => true],
            ['clave' => 'cancelada',  'etiqueta' => 'Canceladas',  'campo' => 'canceladas',  'siempre' => true],
            ['clave' => 'rechazada',  'etiqueta' => 'Rechazadas',  'campo' => 'rechazadas',  'siempre' => false],
        ];

        $estados = [];

        foreach($definicionEstados as $def){

            $n = $act[$def['campo']];

            if(!$def['siempre'] && $n === 0){
                continue;
            }

            $estados[] = [
                'clave'    => $def['clave'],
                'etiqueta' => $def['etiqueta'],
                'n'        => $n,
                'pct'      => $act['total'] > 0 ? ($n / $act['total']) * 100 : 0,
            ];
        }

        // ---------- Ingresos por método de pago (solo completadas) ----------

        $porMetodo = [];

        foreach($completadasActual as $f){

            $m = strtolower((string)$f['metodo_pago']);

            if(!isset($porMetodo[$m])){
                $porMetodo[$m] = ['clave' => $m, 'n' => 0, 'centavos' => 0];
            }

            $porMetodo[$m]['n']++;
            $porMetodo[$m]['centavos'] += (int)round(((float)$f['precio']) * 100);
        }

        $metodos = array_values($porMetodo);

        usort($metodos, function ($a, $b) {

            if($a['centavos'] === $b['centavos']){
                return strcmp($a['clave'], $b['clave']);
            }

            return $b['centavos'] <=> $a['centavos'];
        });

        foreach($metodos as $i => $m){

            $metodos[$i]['importe'] = $m['centavos'] / 100;

            $metodos[$i]['pct'] = $act['centavos'] > 0
                ? ($m['centavos'] / $act['centavos']) * 100
                : 0;
        }

        // ---------- Mayores ventas completadas ----------

        $mayores = $completadasActual;

        usort($mayores, function ($a, $b) {

            $pa = (int)round(((float)$a['precio']) * 100);
            $pb = (int)round(((float)$b['precio']) * 100);

            if($pa === $pb){
                return strcmp((string)$b['fecha_compra'], (string)$a['fecha_compra']);
            }

            return $pb <=> $pa;
        });

        $mayores = array_slice($mayores, 0, 3);

        // ---------- ¿Tiene historial el vendedor? ----------

        if($dias === null){
            $tieneHistorial = count($actual) > 0;
        } elseif(count($actual) > 0 || count($anterior) > 0){
            $tieneHistorial = true;
        } else {
            $tieneHistorial = $this->modelo->contarOperacionesVendedor($vendedor_id) > 0;
        }

        // ---------- Rango legible del periodo ----------

        $hoyEtiqueta = $diaCorto($hoyObj) . ' ' . $hoyObj->format('Y');

        if($desde === null){
            $rango = 'Todo el historial hasta el ' . $hoyEtiqueta;
        } else {
            $d0 = new DateTimeImmutable($desde . ' 00:00:00', $utc);
            $rango = $diaCorto($d0)
                . ($d0->format('Y') !== $hoyObj->format('Y') ? ' ' . $d0->format('Y') : '')
                . ' – ' . $hoyEtiqueta;
        }

        return [
            'periodo'          => $periodo,
            'periodo_etiqueta' => $etiquetasPeriodo[$periodo],
            'periodos'         => $etiquetasPeriodo,
            'rango'            => $rango,
            'hoy'              => $hoy,
            'ahora'            => $bd['ahora'],
            'tiene_historial'  => $tieneHistorial,

            'kpi'              => $act,
            'previo'           => $prev,
            'deltas'           => $deltas,
            'comparable'       => $prev !== null,

            'serie'            => [
                'agrupacion' => $agrupacion,
                'puntos'     => $puntos,
            ],

            'estados'          => $estados,
            'metodos'          => $metodos,
            'mayores'          => $mayores,
            'operaciones'      => $actual,
        ];
    }

}
