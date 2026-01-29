<?php
/* $cliente = new PPP();
$todosLosClientes = $cliente->traerCuenta($cliente); */
/* $todosLosClientes = json_decode($todosLosClientes); */
// var_dump($todosLosClientes);
// echo $todosLosClientes[0]->CUPO_CRED;
?>

<!-- Modal Estado de Cuenta - Bootstrap 5 Mejorado -->
<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="modalEstadoCuentaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                <div>
                    <h5 class="modal-title fw-bold" id="modalEstadoCuentaLabel">
                        <i class="bi bi-wallet2 me-2"></i>Estado de Cuenta
                    </h5>
                    <h6 class="text-white mb-0 mt-1 opacity-90" id='codigoCliente' style="font-size: 0.9rem;">Cargando...</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <div class="modal-body p-4" style="background-color: #f8f9fa;">
                <!-- Resumen Principal -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="rounded-circle p-2 me-3" style="background-color: #e3f2fd;">
                                        <i class="bi bi-wallet2 fs-4" style="color: #1976d2;"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0 small">Cupo de Crédito</p>
                                        <h4 class="mb-0 fw-bold" id="spanCupoCred" style="color: #1976d2;">$0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="rounded-circle p-2 me-3" style="background-color: #e8f5e9;">
                                        <i class="bi bi-piggy-bank fs-4" style="color: #388e3c;"></i>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0 small">Disponible</p>
                                        <h4 class="mb-0 fw-bold" id="totalDisponible" style="color: #388e3c;">$0</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalle de Cuenta -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Detalle de Movimientos</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span><i class="bi bi-cash-stack me-2 text-info"></i>Saldo en Cuenta Corriente</span>
                                <span class="badge bg-info rounded-pill px-3 py-2" id="saldo">$0</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Facturas Vencidas</span>
                                <span class="badge bg-danger rounded-pill px-3 py-2" id="vencidas">$0</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span><i class="bi bi-cart-check me-2 text-warning"></i>Pedidos Abiertos</span>
                                <span class="badge bg-warning rounded-pill px-3 py-2" id="montoPedidos">$0</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span><i class="bi bi-receipt me-2 text-secondary"></i>Total en Cheques</span>
                                <span class="badge bg-secondary rounded-pill px-3 py-2" id="cheque">$0</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <span><i class="bi bi-calendar-check me-2 text-primary"></i>Cheques a 10 Días</span>
                                <span class="badge bg-primary rounded-pill px-3 py-2" id="cheques10Dias">$0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total de Deuda -->
                <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1 opacity-90"><i class="bi bi-cash-coin me-2"></i>Total de Deuda</p>
                                <h3 class="mb-0 fw-bold" id="totalDeuda">$0</h3>
                                <small class="opacity-75">Uso del crédito: <span id="porcentajeDeuda" class="fw-bold">0%</span></small>
                            </div>
                            <i class="bi bi-graph-down-arrow fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>

                <!-- Leyenda de Colores -->
                <div class="mt-3 p-3 bg-white rounded border">
                    <h6 class="mb-2 small text-muted fw-bold"><i class="bi bi-info-circle me-1"></i>Indicadores de Estado Crediticio</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle me-2" style="width: 16px; height: 16px; background-color: #28a745;"></div>
                            <small class="text-muted">Óptimo (&lt;50%)</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle me-2" style="width: 16px; height: 16px; background-color: #ffc107;"></div>
                            <small class="text-muted">Moderado (50-75%)</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle me-2" style="width: 16px; height: 16px; background-color: #dc3545;"></div>
                            <small class="text-muted">Crítico (&gt;75%)</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-white border-top-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
