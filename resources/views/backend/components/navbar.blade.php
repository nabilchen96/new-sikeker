<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
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
                        <a class="nav-link" href="">Rencana Proker</a>
                    </li>
                </ul>
            </div>
        </li>
        
    </ul>
</nav>
