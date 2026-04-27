<?php

use App\Http\Controllers\AmbienteController;
use App\Http\Controllers\AprendizController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompetenciaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiaController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\FichaController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\MunicipioController;
use App\Http\Controllers\ProgramaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ResultadoController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\TipoContratoController;
use App\Http\Controllers\TipoFormacionController;

Route::post('login', [AuthController::class, 'login']);

// Dashboard Metrics Endpoints
Route::get('dashboard/fichas/activas', [FichaController::class, 'countActivas']);
Route::get('dashboard/instructores/count', [FuncionarioController::class, 'countInstructores']);
Route::get('dashboard/aprendices/matriculados', [AprendizController::class, 'countMatriculados']);
Route::get('dashboard/aprendices/programa', [AprendizController::class, 'countByPrograma']);
Route::get('dashboard/ambientes/libres', [AmbienteController::class, 'countLibres']);
Route::get('dashboard/ambientes/ocupacion', [AmbienteController::class, 'ocupacion']);
Route::get('dashboard/horarios/metrics', [HorarioController::class, 'dashboardMetrics']);

Route::get('listarArea', [AreaController::class, 'index']);
Route::post('crearArea', [AreaController::class, 'store']);
Route::get('verArea/{areaModel}', [AreaController::class, 'show']);
Route::put('editarArea/{areaModel}', [AreaController::class, 'update']);
Route::delete('eliminarArea/{areaModel}', [AreaController::class, 'destroy']);  
        


Route::get('listarDia', [DiaController::class,'index']);
Route::post('crearDia',[DiaController::class,'store']);
Route::put('editarDia/{idDia}',[DiaController::class,'update']);
Route::delete('eliminarDia/{idDia}',[DiaController::class,'destroy']);



Route::get('listarTipoContrato',[TipoContratoController::class,'index']);
Route::post('crearTipoContrato',[TipoContratoController::class,'store']);
Route::put('editarTipoContrato/{idTipoContrato}',[TipoContratoController::class,'update']);
Route::delete('eliminarTipoContrato/{idTipoContrato}',[TipoContratoController::class,'destroy']);



Route::get('listarMunicipio',[MunicipioController::class,'index']);
Route::post('crearMunicipio',[MunicipioController::class,'store']);
Route::delete('eliminarMunicipio/{idMunicipio}',[MunicipioController::class,'destroy']);
Route::get('fichas/programa-municipio/{idMunicipio}',[FichaController::class,'listarFichasProgramaMunicipio']);

// ── Flujo jerarquico: Municipio → Programa → Ficha ────────────────────────────
Route::get('municipios-con-fichas',                                        [MunicipioController::class,'municipiosConFichas']);
Route::get('programas-por-municipio/{idMunicipio}',                        [FichaController::class,'programasPorMunicipio']);
Route::get('fichas-por-programa-municipio/{idPrograma}/{idMunicipio}',     [FichaController::class,'fichasPorProgramaMunicipio']);

Route::get('listarProgramasPorSede/{idSede}',[FichaController::class,'programasPorSede']);
Route::get('fichasPorProgramaSede/{idPrograma}/{idMunicipio}',[FichaController::class,'ficharPorProgramaSede']);


Route::get('listarSedes',[SedeController::class,'index']);
Route::post('crearSede',[SedeController::class,'store']);
Route::put('editarSede/{idSede}',[SedeController::class,'update']);
Route::delete('eliminarSede/{idSede}',[SedeController::class,'destroy']);




Route::get('listarTipoFormacion',[TipoFormacionController::class,'index']);
Route::post('crearTipoFormacion',[TipoFormacionController::class,'store']);
Route::put('editarTipoFormacion/{idTipoFormacion}',[TipoFormacionController::class,'update']);
Route::delete('eliminarTipoFormacion/{idTipoFormacion}',[TipoFormacionController::class,'destroy']);



Route::get('listarPrograma',[ProgramaController::class,'index']);
Route::post('crearPrograma',[ProgramaController::class,'store']);
Route::put('editarPrograma/{idPrograma}',[ProgramaController::class,'update']);
Route::delete('eliminarPrograma/{idPrograma}',[ProgramaController::class,'destroy']);


Route::get('listarFuncionario',[FuncionarioController::class,'index']);
Route::post('crearFuncionario',[FuncionarioController::class,'store']);
Route::put('editarFuncionario/{idFuncionario}',[FuncionarioController::class,'update']);
Route::get('listarFuncionatioXDocumento/{documento}',[FuncionarioController::class,'show']);
Route::delete('eliminarFuncionario/{idFuncionario}',[FuncionarioController::class,'destroy']);
Route::post('crearAdmin',[FuncionarioController::class,'crearAdmin']);
Route::post('asignar-area-masivo', [FuncionarioController::class, 'asignarAreaMasivo']);
Route::post('contarHorasInstructor/{idFuncionario}',[FuncionarioController::class,'contarHorasInstructor']);
Route::post('conflicto/reemplazar', [HorarioController::class, 'resolverReemplazando']);
Route::post('conflicto/partir',     [HorarioController::class, 'resolverPartiendo']);

Route::get('listarAmbiente',[AmbienteController::class,'index']);
Route::post('crearAmbiente',[AmbienteController::class,'store']);
Route::put('editarAmbiente/{idAmbiente}',[AmbienteController::class,'update']);
Route::delete('eliminarAmbiente/{idAmbiente}',[AmbienteController::class,'destroy']);
Route::post('/ambientes/disponibles', [AmbienteController::class, 'AmbientesDesocupadosPorFechaYHora']);

Route::get('listarFicha',[FichaController::class,'index']);
Route::post('crearFicha',[FichaController::class,'store']);
Route::put('editarFicha/{idFicha}',[FichaController::class,'update']);
Route::delete('eliminarFicha/{idFicha}',[FichaController::class,'destroy']);
Route::get('mostratFichaXCodigo/{codigoFicha}',[FichaController::class,'show']);


Route::get('listarAprendiz',[AprendizController::class,'index']);
Route::post('crearAprendiz',[AprendizController::class,'store']);
Route::put('editarAprendiz/{idAprendiz}',[AprendizController::class,'update']);
Route::delete('eliminarAprendiz/{idAprendiz}',[AprendizController::class,'destroy']);
Route::get('mostrarAprendizXdocumento/{documento}',[AprendizController::class,'show']);



Route::post('crearBloque',[HorarioController::class, 'storeBloque']);


Route::post('crearAsignacion',[HorarioController::class, 'storeAsignacion']);
Route::get('horariosPorFicha/{idFicha}',[HorarioController::class, 'horariosPorFicha']);

Route::delete('eliminarAsignacion/{idAsignacion}',[HorarioController::class, 'destroyAsignacion']);
Route::delete('eliminarDiaDeBloque/{idBloque}/{idDia}', [HorarioController::class, 'destroyDiaDeBloque']);

Route::get('horariosPorAmbiente/{idAmbiente}',[HorarioController::class,'horariosPorAmbiente']);
Route::get('horarioPorInstructor/{idFuncionario}',[HorarioController::class,'listarFuncionarioPorHorario']); 

Route::get('exportar/funcionarios',                [ExcelController::class, 'exportarFuncionarios']);
Route::get('exportar/fichas',                      [ExcelController::class, 'exportarFichas']);
Route::get('exportar/aprendices',                  [ExcelController::class, 'exportarAprendices']);
Route::get('exportar/aprendices/{idFicha}',        [ExcelController::class, 'exportarAprendicesDeFicha']);
Route::get('exportar/programas',                   [ExcelController::class, 'exportarProgramas']);
 
// ── Imports — el frontend hace POST con multipart/form-data ───────────────────
Route::post('importar/funcionarios',               [ExcelController::class, 'importarFuncionarios']);
Route::post('importar/aprendices',                 [ExcelController::class, 'importarAprendices']);


// Competencias
Route::get('listarCompetencia', [CompetenciaController::class, 'index']);
Route::post('crearCompetencia', [CompetenciaController::class, 'store']);
Route::get('verCompetencia/{idCompetencia}', [CompetenciaController::class, 'show']);
Route::put('editarCompetencia/{idCompetencia}', [CompetenciaController::class, 'update']);
Route::delete('eliminarCompetencia/{idCompetencia}', [CompetenciaController::class, 'destroy']);
Route::get('exportar/competencias/{idTipoFormacion?}',  [ExcelController::class, 'exportarCompetencias']);
Route::post('importar/competencias', [ExcelController::class, 'importarCompetencias']);



// Resultados 

Route::get('listarResultado',[ResultadoController::class,'index']);
Route::post('crearResultado',[ResultadoController::class,'store']);
Route::get('obtenerResultadoXId/{idResultado}',[ResultadoController::class,'show']);
Route::put('editarResultado/{idResultado}',[ResultadoController::class,'update']);
Route::delete('/{idResultado}',[ResultadoController::class,'destroy']);
Route::get('exportar/resultados/{idTipoFormacion?}', [ExcelController::class, 'exportarResultados']);
Route::post('importar/resultados', [ExcelController::class, 'importarResultados']);

// ── Juicios evaluativos ──────────────────────────────────────────────────────
// Análisis rápido (solo Excel, sin BD) — usado por HorarioTitulada (Transversales)
Route::post('analizar/juicios',                  [ReporteController::class, 'analizarJuicios']);
// Análisis completo (Excel + BD: competencias y resultados) — usado por HorarioFormativa
Route::post('reportes/competencias-pendientes',  [ReporteController::class, 'generarReporteCompetencias']);

// Descarga un .txt de reporte ya generado
Route::get('reportes/descargar/{nombre}',        [ReporteController::class, 'descargarReporte']);
Route::post('/enviarHorario/{id}', [HorarioController::class, 'enviarHorario']);
Route::post('enviarHorarioAprendiz/{id}',[HorarioController::class,'enviarHorarioAprendiz']);

