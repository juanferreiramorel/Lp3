@php
    $isReportOpen = Request::is('reporte-*') || Request::is('auditoria*');
    $isConfigOpen = Request::is('users*')
        || Request::is('cargos*')
        || Request::is('departamentos*')
        || Request::is('ciudades*')
        || Request::is('sucursales*')
        || Request::is('empresas*')
        || Request::is('timbrados*')
        || Request::is('taxes*')
        || Request::is('permissions*')
        || Request::is('cajas*')
        || Request::is('roles*')
        || Request::is('ayuda*');

    $isVentasOpen = Request::is('ventas*') || Request::is('apertura-cierre-caja*') || Request::is('pedidos*') || Request::is('clientes*') || Request::is('cuentasacobrar*') || Request::is('recibossimples*') || Request::is('notas_debito*');
    $isComprasOpen = Request::is('compras*') || Request::is('ordencompra*') || Request::is('proveedores*') || Request::is('cuentasapagar*');
    $isInventarioOpen = Request::is('productos*') || Request::is('stock*') || Request::is('marcas*');
@endphp

{{-- MODULO VENTAS --}}
@if(auth()->user()->can('ventas index') || auth()->user()->can('pedidos index') || auth()->user()->can('clientes index'))
<li class="nav-item {{ $isVentasOpen ? 'menu-is-opening menu-open' : '' }}">
    <a href="#" class="nav-link">
        <i class="nav-icon fas fa-cash-register"></i>
        <p>
            Ventas
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        @can('ventas index')
        <li class="nav-item">
            <a href="{{ route('ventas.index') }}" class="nav-link {{ Request::is('ventas*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-receipt"></i>
                <p>Ventas</p>
            </a>
        </li>
        @endcan

        @can('ventas index')
        <li class="nav-item">
            <a href="{{ route('notas_debito.index') }}" class="nav-link {{ Request::is('notas_debito*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-file-invoice-dollar"></i>
                <p>Notas de Débito</p>
            </a>
        </li>
        @endcan

        @can('aperturacierrecaja index')
        <li class="nav-item">
            <a href="{{ route('apertura-cierre-caja.index') }}" class="nav-link {{ Request::is('apertura-cierre-caja*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-file-invoice-dollar"></i>
                <p>Arqueo de Caja</p>
            </a>
        </li>
        @endcan

        @can('pedidos index')
        <li class="nav-item">
            <a href="{{ route('pedidos.index') }}" class="nav-link {{ Request::is('pedidos*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-clipboard-check"></i>
                <p>Pedidos</p>
            </a>
        </li>
        @endcan

        @can('clientes index')
        <li class="nav-item">
            <a href="{{ route('clientes.index') }}" class="nav-link {{ Request::is('clientes*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-user-friends"></i>
                <p>Clientes</p>
            </a>
        </li>
        @endcan

        @can('cuentasacobrar index')
        <li class="nav-item">
            <a href="{{ route('cuentasacobrar.index') }}" class="nav-link {{ Request::is('cuentasacobrar*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-hand-holding-usd"></i>
                <p>Cuentas A Cobrar</p>
            </a>
        </li>
        @endcan

        @can('recibossimples index')
        <li class="nav-item">
            <a href="{{ route('recibossimples.index') }}" class="nav-link {{ Request::is('recibossimples*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-file-invoice"></i>
                <p>Recibos Simples</p>
            </a>
        </li>
        @endcan
    </ul>
</li>
@endif

{{-- MODULO COMPRAS --}}
@if(auth()->user()->can('compras index') || auth()->user()->can('ordencompra index') || auth()->user()->can('proveedores index'))
<li class="nav-item {{ $isComprasOpen ? 'menu-is-opening menu-open' : '' }}">
    <a href="#" class="nav-link">
        <i class="nav-icon fas fa-shopping-cart"></i>
        <p>
            Compras
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        @can('compras index')
        <li class="nav-item">
            <a href="{{ route('compras.index') }}" class="nav-link {{ Request::is('compras*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-cart-arrow-down"></i>
                <p>Compras</p>
            </a>
        </li>
        @endcan

        @can('ordencompra index')
        <li class="nav-item">
            <a href="{{ route('ordencompra.index') }}" class="nav-link {{ Request::is('ordencompra*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-file-signature"></i>
                <p>Ordenes De Compra</p>
            </a>
        </li>
        @endcan

        @can('proveedores index')
        <li class="nav-item">
            <a href="{{ route('proveedores.index') }}" class="nav-link {{ Request::is('proveedores*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-truck"></i>
                <p>Proveedores</p>
            </a>
        </li>
        @endcan

        @can('cuentasapagar index')
        <li class="nav-item">
            <a href="{{ route('cuentasapagar.index') }}" class="nav-link {{ Request::is('cuentasapagar*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-money-bill-wave"></i>
                <p>Cuentas A Pagar</p>
            </a>
        </li>
        @endcan
    </ul>
</li>
@endif

{{-- MODULO INVENTARIO --}}
@if(auth()->user()->can('productos index') || auth()->user()->can('stock index') || auth()->user()->can('marcas index'))
<li class="nav-item {{ $isInventarioOpen ? 'menu-is-opening menu-open' : '' }}">
    <a href="#" class="nav-link">
        <i class="nav-icon fas fa-boxes"></i>
        <p>
            Inventario
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        @can('productos index')
        <li class="nav-item">
            <a href="{{ route('productos.index') }}" class="nav-link {{ Request::is('productos*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-box-open"></i>
                <p>Productos</p>
            </a>
        </li>
        @endcan

        @can('stock index')
        <li class="nav-item">
            <a href="{{ route('stock.index') }}" class="nav-link {{ Request::is('stock*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-warehouse"></i>
                <p>Stock</p>
            </a>
        </li>
        @endcan

        @can('marcas index')
        <li class="nav-item">
            <a href="{{ route('marcas.index') }}" class="nav-link {{ Request::is('marcas*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-tags"></i>
                <p>Marcas</p>
            </a>
        </li>
        @endcan
    </ul>
</li>
@endif

{{-- Plantillas de Contratos --}}
@can('plantillas_contratos index')
<li class="nav-item">
    <a href="{{ route('plantillas_contratos.index') }}" class="nav-link {{ Request::is('plantillas-contratos*') || Request::is('plantillas_contratos*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-file-contract"></i>
        <p>Plantillas Contratos</p>
    </a>
</li>
@endcan

{{-- Reportes --}}
<li class="nav-item {{ $isReportOpen ? 'menu-is-opening menu-open' : '' }}">
    <a href="#" class="nav-link">
        <i class="nav-icon fas fa-chart-pie"></i>
        <p>
            Reportes
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        @can('cargos index')
        <li class="nav-item">
            <a href="{{ url('reporte-cargos') }}" class="nav-link {{ Request::is('reporte-cargos*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-id-badge"></i>
                <p>Reporte cargos</p>
            </a>
        </li>
        @endcan
        @can('auditoria index')
        <li class="nav-item">
            <a href="{{ url('auditoria') }}" class="nav-link {{ Request::is('auditoria*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-user-secret"></i>
                <p>Auditoria</p>
            </a>
        </li>
        @endcan
        @can('clientes index')
        <li class="nav-item">
            <a href="{{ url('reporte-clientes') }}" class="nav-link {{ Request::is('reporte-clientes*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-user-check"></i>
                <p>Reporte clientes</p>
            </a>
        </li>
        @endcan
        @can('proveedores index')
        <li class="nav-item">
            <a href="{{ url('reporte-proveedores') }}" class="nav-link {{ Request::is('reporte-proveedores*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-truck-loading"></i>
                <p>Reporte proveedores</p>
            </a>
        </li>
        @endcan
        @can('productos index')
        <li class="nav-item">
            <a href="{{ url('reporte-productos') }}" class="nav-link {{ Request::is('reporte-productos*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-boxes"></i>
                <p>Reporte productos</p>
            </a>
        </li>
        @endcan
        @can('sucursales index')
        <li class="nav-item">
            <a href="{{ url('reporte-sucursales') }}" class="nav-link {{ Request::is('reporte-sucursales*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-store-alt"></i>
                <p>Reporte sucursales</p>
            </a>
        </li>
        @endcan
        @can('ventas index')
        <li class="nav-item">
            <a href="{{ url('reporte-ventas') }}" class="nav-link {{ Request::is('reporte-ventas*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-chart-line"></i>
                <p>Reporte ventas</p>
            </a>
        </li>
        @endcan
        @can('compras index')
        <li class="nav-item">
            <a href="{{ url('reporte-compras') }}" class="nav-link {{ Request::is('reporte-compras*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-shopping-cart"></i>
                <p>Reporte compras</p>
            </a>
        </li>
        @endcan
        @can('pedidos index')
        <li class="nav-item">
            <a href="{{ url('reporte-pedidos') }}" class="nav-link {{ Request::is('reporte-pedidos*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-clipboard-list"></i>
                <p>Reporte pedidos</p>
            </a>
        </li>
        @endcan
        @can('ordencompra index')
        <li class="nav-item">
            <a href="{{ url('reporte-ordenes-compra') }}" class="nav-link {{ Request::is('reporte-ordenes-compra*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-file-invoice"></i>
                <p>Reporte órdenes compra</p>
            </a>
        </li>
        @endcan
    </ul>
</li>

{{-- Configuraciones --}}
@if(auth()->user()->can('departamentos index') ||
    auth()->user()->can('ciudades index') ||
    auth()->user()->can('sucursales index') ||
    auth()->user()->can('empresas index') ||
    auth()->user()->can('timbrados index') ||
    auth()->user()->can('cajas index') ||
    auth()->user()->can('cargos index') ||
    auth()->user()->can('taxes index') ||
    auth()->user()->can('users index') ||
    auth()->user()->can('permissions index') ||
    auth()->user()->can('roles index') ||
    auth()->user()->can('ayuda index'))
<li class="nav-item {{ $isConfigOpen ? 'menu-is-opening menu-open' : '' }}">
    <a href="#" class="nav-link">
        <i class="nav-icon fas fa-tools"></i>
        <p>
            Configuraciones
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        @can('departamentos index')
        <li class="nav-item">
            <a href="{{ route('departamentos.index') }}" class="nav-link {{ Request::is('departamentos*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-layer-group"></i>
                <p>Departamentos</p>
            </a>
        </li>
        @endcan
        @can('ciudades index')
        <li class="nav-item">
            <a href="{{ route('ciudades.index') }}" class="nav-link {{ Request::is('ciudades*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-city"></i>
                <p>Ciudades</p>
            </a>
        </li>
        @endcan
        @can('sucursales index')
        <li class="nav-item">
            <a href="{{ route('sucursales.index') }}" class="nav-link {{ Request::is('sucursales*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-store"></i>
                <p>Sucursales</p>
            </a>
        </li>
        @endcan
        @can('empresas index')
        <li class="nav-item">
            <a href="{{ route('empresas.index') }}" class="nav-link {{ Request::is('empresas*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-building"></i>
                <p>Empresas</p>
            </a>
        </li>
        @endcan
        @can('timbrados index')
        <li class="nav-item">
            <a href="{{ route('timbrados.index') }}" class="nav-link {{ Request::is('timbrados*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-stamp"></i>
                <p>Timbrados</p>
            </a>
        </li>
        @endcan
        
        @can('cajas index')
        <li class="nav-item">
            <a href="{{ route('cajas.index') }}" class="nav-link {{ Request::is('cajas*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-cash-register"></i>
                <p>Cajas</p>
            </a>
        </li>
        @endcan

        @can('cargos index')
        <li class="nav-item">
            <a href="{{ route('cargos.index') }}" class="nav-link {{ Request::is('cargos*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-id-card-alt"></i>
                <p>Cargos</p>
            </a>
        </li>
        @endcan

        @can('taxes index')
        <li class="nav-item">
            <a href="{{ route('taxes.index') }}" class="nav-link {{ Request::is('taxes*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-percentage"></i>
                <p>Impuestos</p>
            </a>
        </li>
        @endcan

        @can('users index')
        <li class="nav-item">
            <a href="{{ route('users.index') }}" class="nav-link {{ Request::is('users*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-user-cog"></i>
                <p>Usuarios</p>
            </a>
        </li>
        @endcan

        @can('permissions index')
        <li class="nav-item">
            <a href="{{ route('permissions.index') }}" class="nav-link {{ Request::is('permissions*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-user-shield"></i>
                <p>Permisos</p>
            </a>
        </li>
        @endcan

        @can('roles index')
        <li class="nav-item">
            <a href="{{ route('roles.index') }}" class="nav-link {{ Request::is('roles*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-user-tag"></i>
                <p>Roles</p>
            </a>
        </li>
        @endcan

        @can('ayuda index')
        <li class="nav-item">
            <a href="{{ route('ayuda.index') }}" class="nav-link {{ Request::is('ayuda*') ? 'active' : '' }}">
                <i class="nav-icon fas fa-question-circle"></i>
                <p>Ayuda (PDF)</p>
            </a>
        </li>
        @endcan
    </ul>
</li>
@endif
