<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard'; ?> - WAPI SaaS</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Framework Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Modern Dashboard Styles -->
    <link rel="stylesheet" href="<?= asset('assets/css/v2-dashboard.css'); ?>">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary: #25D366;
            --secondary: #128C7E;
        }
        .btn-primary { background-color: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background-color: var(--secondary); border-color: var(--secondary); }
        .text-primary { color: var(--primary) !important; }
    </style>
</head>
<body class="v2-dashboard">
    <div class="dash-layout">
        <?php include __DIR__ . '/v2-sidebar.php'; ?>
        <main class="dash-main">
            <header class="dash-topbar">
                <div class="dash-topbar-left">
                    <div style="font-weight: 700; font-size: 1.125rem;"><?= $pageTitle ?? 'Dashboard'; ?></div>
                    <div style="font-size: 0.8125rem; color: var(--dash-slate-500);">Last sync: Today, 10:45 AM</div>
                </div>
                
                <div class="dash-topbar-right d-flex align-items-center gap-3">
                    <div class="search-bar position-relative d-none d-md-block">
                        <i class="bi bi-search position-absolute" style="left: 12px; top: 50%; transform: translateY(-50%); color: var(--dash-slate-400);"></i>
                        <input type="text" class="form-control form-control-sm" placeholder="Search..." style="padding-left: 36px; border-radius: 8px; border-color: var(--dash-slate-200); width: 240px;">
                    </div>
                    
                    <button class="btn btn-icon" style="color: var(--dash-slate-500);"><i class="bi bi-bell"></i></button>
                    <button class="btn btn-icon" style="color: var(--dash-slate-500);"><i class="bi bi-question-circle"></i></button>
                    
                    <div class="vr h-50 my-auto mx-2" style="background-color: var(--dash-slate-200);"></div>
                    
                    <div class="phone-status d-none d-lg-flex align-items-center gap-2">
                        <div style="width: 8px; height: 8px; background: #22c55e; border-radius: 50%;"></div>
                        <span style="font-size: 0.8125rem; font-weight: 600;">+91 98765 43210</span>
                        <span class="badge bg-success-subtle text-success p-1 px-2" style="font-size: 0.65rem;">CONNECTED</span>
                    </div>
                </div>
            </header>
            <div class="dash-content">
