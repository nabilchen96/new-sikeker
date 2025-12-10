<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">

        
        <li class="nav-item mb-2">
            <div style="
                border-radius: 8px; 
                height: fit-content; 
                width: 100%;
                background-image: url('https://cdn.pixabay.com/photo/2022/10/03/23/41/house-7497002_640.png');
                background-position: center;
                " class="text-white py-1 px-3">
                @php
                    $user = DB::table('users')
                            ->leftjoin('units', 'units.id', '=', 'users.id_unit')
                            ->select(
                                'users.name',
                                'units.unit'
                            )->where('users.id', Auth::id())->first();
                @endphp
                @if($user->unit)
                    <b>Unit:</b><br>
                    {{ $user->unit }}
                @else
                    <b>Name:</b><br>
                    {{ $user->name }}
                @endif
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="{{ url('dashboard') }}">
                <i class="icon-grid menu-icon"></i>
                <span class="menu-title">Dashboard</span>
            </a>

        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
                <i class="icon-layout menu-icon"></i>
                <span class="menu-title">Master</span>
                <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-basic">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('unit') }}">Unit</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('tahun') }}">Tahun</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('user') }}">User</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('approval') }}">Approval</a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="collapse" href="#aktivitas" aria-expanded="false" aria-controls="ui-basic">
                <i class="icon-layout bi bi-box-seam menu-icon"></i>
                <span class="menu-title">Aktivitas</span>
                <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="aktivitas">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('proker') }}">Rencana Proker</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('aksi-proker') }}?id_rencana_proker=">Aksi Proker</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('file-manager') }}">File Manager</a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/">
                <i class="bi bi-file-earmark-text menu-icon"></i>
                <span class="menu-title">Panduan</span>
            </a>

        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ url('logout') }}">
                <i class="bi bi-door-open menu-icon"></i>
                <span class="menu-title">Logout</span>
            </a>

        </li>
        
    </ul>
</nav>
