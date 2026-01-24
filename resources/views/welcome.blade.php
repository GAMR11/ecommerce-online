@extends('layout')
@section('content')
    <div class="content-wrapper">
        <!-- Content -->

        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="col-xxl-12 mb-6 order-0">
                  <div class="card">
                    <div class="d-flex align-items-start row">
                      <div class="col-sm-7">
                        <div class="card-body">
                          <h5 class="card-title text-primary mb-3">Bienvenido {{ Auth::user()->name }}!!</h5>
                          <p class="mb-6">
                            Tu perfil de Comercial Gusmor esta listo para empezar a gestionar tus ventas y compras.
                          </p>

                          <a href="{{ route('venta.historial') }}" class="btn btn-sm btn-outline-primary">Visualizar ventas</a>
                        </div>
                      </div>
                      <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-6">
                          <img
                            src="../assets/img/illustrations/man-with-laptop.png"
                            height="175"
                            class="scaleX-n1-rtl"
                            alt="View Badge User" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-lg-12 col-md-4 order-1">
                  <div class="row">
                    @foreach($categorias_con_totales as $item)
                        <div class="col-lg-3 col-md-12 col-6 mb-6">
                        <div class="card h-100">
                            <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between mb-4">
                                <div class="avatar flex-shrink-0">
                                <img
                                    src="../assets/img/icons/unicons/chart-success.png"
                                    alt="chart success"
                                    class="rounded" />
                                </div>
                                <div class="dropdown">
                                {{-- <button
                                    class="btn p-0"
                                    type="button"
                                    id="cardOpt3"
                                    data-bs-toggle="dropdown"
                                    aria-haspopup="true"
                                    aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded text-muted"></i>
                                </button> --}}
                                {{-- <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                                    <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                    <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                </div> --}}
                                </div>
                            </div>
                            <p class="mb-1">{{ $item['categoria'] }}</p>
                            <h4 class="card-title mb-3">${{ $item['total_precio'] }}</h4>
                            <small class="text-success fw-medium"><i class="bx bx-up-arrow-alt"></i>{{ $item['total_productos'] }} unidades</small>
                            </div>
                        </div>
                        </div>
                    @endforeach

                  </div>
                </div>
              </div>
              <div class="row">
                <!-- Order Statistics -->
                <div class="col-md-6 col-lg-4 col-xl-4 order-0 mb-6">
                  <div class="card h-100">
                    <div class="card-header d-flex justify-content-between">
                      <div class="card-title mb-0">
                        <h5 class="mb-1 me-2">Estadística de ventas</h5>
                        <p class="card-subtitle">{{ $totalVentas }} Total Ventas</p>
                      </div>
                      {{-- <div class="dropdown">
                        <button
                          class="btn text-muted p-0"
                          type="button"
                          id="orederStatistics"
                          data-bs-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false">
                          <i class="bx bx-dots-vertical-rounded bx-lg"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="orederStatistics">
                          <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                          <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                          <a class="dropdown-item" href="javascript:void(0);">Share</a>
                        </div>
                      </div> --}}
                    </div>
                    <input type="hidden" name="topCategorias" id="topCategorias" value="{{ json_encode($topCategorias) }}">
                    <input type="hidden" name="valorCategorias" id="valorCategorias" value="{{ json_encode($ventasPorCategoria) }}">
                    <input type="hidden" name="pagosPorMes" id="pagosPorMes" value="{{ json_encode($pagosPorMes) }}">
                    <input type="hidden" name="totalPagosGeneral" id="totalPagosGeneral" value="{{ json_encode($totalPagosGeneral) }}">

                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-center mb-6">
                        <div class="d-flex flex-column align-items-center gap-1">
                          <h3 class="mb-1">{{ $totalNumeroVentas }}</h3>
                          <small>Número de Ventas</small>
                        </div>
                        <div id="orderStatisticsChart"></div>
                      </div>
                      <ul class="p-0 m-0">
                        @foreach($ventasPorCategoriaOrdenadas as $vpc)
                        <li class="d-flex align-items-center mb-5">
                          <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary"
                              ><i class="bx bx-mobile-alt"></i
                            ></span>
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <h6 class="mb-0">{{ $vpc->categoria }}</h6>
                              <small>Mobile, Earbuds, TV</small>
                            </div>
                            <div class="user-progress">
                              <h6 class="mb-0">{{ $vpc->total_ventas }}</h6>
                            </div>
                          </div>
                        </li>
                        @endforeach
                      </ul>
                    </div>
                  </div>
                </div>
                <!--/ Order Statistics -->

                <!-- Expense Overview -->
                <div class="col-md-6 col-lg-4 order-1 mb-6">
                  <div class="card h-100">
                    <div class="card-header nav-align-top">
                      <ul class="nav nav-pills" role="tablist">
                        {{-- <li class="nav-item">
                          <button
                            type="button"
                            class="nav-link active"
                            role="tab"
                            data-bs-toggle="tab"
                            data-bs-target="#navs-tabs-line-card-income"
                            aria-controls="navs-tabs-line-card-income"
                            aria-selected="true">
                            Income
                          </button>
                        </li>
                        <li class="nav-item">
                          <button type="button" class="nav-link" role="tab">Expenses</button>
                        </li>
                        <li class="nav-item">
                          <button type="button" class="nav-link" role="tab">Profit</button>
                        </li> --}}
                      </ul>
                    </div>
                    <div class="card-body">
                      <div class="tab-content p-0">
                        <div class="tab-pane fade show active" id="navs-tabs-line-card-income" role="tabpanel">
                          <div class="d-flex mb-6">
                            <div class="avatar flex-shrink-0 me-3">
                              <img src="../assets/img/icons/unicons/wallet.png" alt="User" />
                            </div>
                            <div>
                              <p class="mb-0">Total de Ventas</p>
                              <div class="d-flex align-items-center">
                                <h6 class="mb-0 me-1">${{ $totalPagosGeneral }}</h6>
                                {{-- <small class="text-success fw-medium">
                                  <i class="bx bx-chevron-up bx-lg"></i>
                                  42.9%
                                </small> --}}
                              </div>
                            </div>
                          </div>
                          <div id="incomeChart"></div>
                          <div class="d-flex align-items-center justify-content-center mt-6 gap-3">
                            {{-- <div class="flex-shrink-0">
                              <div id="expensesOfWeek"></div>
                            </div> --}}
                            {{-- <div>
                              <h6 class="mb-0">Income this week</h6>
                              <small>$39k less than last week</small>
                            </div> --}}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Expense Overview -->

                <!-- Transactions -->
                <div class="col-md-6 col-lg-4 order-2 mb-6">
                  <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                      <h5 class="card-title m-0 me-2">Transacciones</h5>
                      <div class="dropdown">
                        <button
                          class="btn text-muted p-0"
                          type="button"
                          id="transactionID"
                          data-bs-toggle="dropdown"
                          aria-haspopup="true"
                          aria-expanded="false">
                          <i class="bx bx-dots-vertical-rounded bx-lg"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="transactionID">
                          <a class="dropdown-item" href="javascript:void(0);">Last 28 Days</a>
                          <a class="dropdown-item" href="javascript:void(0);">Last Month</a>
                          <a class="dropdown-item" href="javascript:void(0);">Last Year</a>
                        </div>
                      </div>
                    </div>
                    <div class="card-body pt-4">
                      <ul class="p-0 m-0">
                        @foreach($metodosPago as $metodoPago)
                            <li class="d-flex align-items-center mb-6">
                            <div class="avatar flex-shrink-0 me-3">
                                <img src="../assets/img/icons/unicons/paypal.png" alt="User" class="rounded" />
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                <small class="d-block">{{ $metodoPago->metodo_pago }}</small>
                                <h6 class="fw-normal mb-0">{{ $metodoPago->total_usos }} pagos </h6>
                                </div>
                                <div class="user-progress d-flex align-items-center gap-2">
                                <h6 class="fw-normal mb-0">{{ $metodoPago->total_pagado }}</h6>
                                <span class="text-muted">USD</span>
                                </div>
                            </div>
                            </li>
                        @endforeach
                        {{-- <li class="d-flex align-items-center mb-6">
                          <div class="avatar flex-shrink-0 me-3">
                            <img src="../assets/img/icons/unicons/wallet.png" alt="User" class="rounded" />
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <small class="d-block">Wallet</small>
                              <h6 class="fw-normal mb-0">Mac'D</h6>
                            </div>
                            <div class="user-progress d-flex align-items-center gap-2">
                              <h6 class="fw-normal mb-0">+270.69</h6>
                              <span class="text-muted">USD</span>
                            </div>
                          </div>
                        </li>
                        <li class="d-flex align-items-center mb-6">
                          <div class="avatar flex-shrink-0 me-3">
                            <img src="../assets/img/icons/unicons/chart.png" alt="User" class="rounded" />
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <small class="d-block">Transfer</small>
                              <h6 class="fw-normal mb-0">Refund</h6>
                            </div>
                            <div class="user-progress d-flex align-items-center gap-2">
                              <h6 class="fw-normal mb-0">+637.91</h6>
                              <span class="text-muted">USD</span>
                            </div>
                          </div>
                        </li>
                        <li class="d-flex align-items-center mb-6">
                          <div class="avatar flex-shrink-0 me-3">
                            <img src="../assets/img/icons/unicons/cc-primary.png" alt="User" class="rounded" />
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <small class="d-block">Credit Card</small>
                              <h6 class="fw-normal mb-0">Ordered Food</h6>
                            </div>
                            <div class="user-progress d-flex align-items-center gap-2">
                              <h6 class="fw-normal mb-0">-838.71</h6>
                              <span class="text-muted">USD</span>
                            </div>
                          </div>
                        </li>
                        <li class="d-flex align-items-center mb-6">
                          <div class="avatar flex-shrink-0 me-3">
                            <img src="../assets/img/icons/unicons/wallet.png" alt="User" class="rounded" />
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <small class="d-block">Wallet</small>
                              <h6 class="fw-normal mb-0">Starbucks</h6>
                            </div>
                            <div class="user-progress d-flex align-items-center gap-2">
                              <h6 class="fw-normal mb-0">+203.33</h6>
                              <span class="text-muted">USD</span>
                            </div>
                          </div>
                        </li>
                        <li class="d-flex align-items-center">
                          <div class="avatar flex-shrink-0 me-3">
                            <img src="../assets/img/icons/unicons/cc-warning.png" alt="User" class="rounded" />
                          </div>
                          <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2">
                              <small class="d-block">Mastercard</small>
                              <h6 class="fw-normal mb-0">Ordered Food</h6>
                            </div>
                            <div class="user-progress d-flex align-items-center gap-2">
                              <h6 class="fw-normal mb-0">-92.45</h6>
                              <span class="text-muted">USD</span>
                            </div>
                          </div>
                        </li> --}}
                      </ul>
                    </div>
                  </div>
                </div>
                <!--/ Transactions -->
              </div>

            </div>
        <!-- / Content -->

        <!-- Footer -->
        @include('templates.footer')
        <!-- / Footer -->

        <div class="content-backdrop fade"></div>
    </div>
@endsection
