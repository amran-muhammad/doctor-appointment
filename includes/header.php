<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= SITE_NAME ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="<?= SITE_URL ?>">
            <i class="bi bi-heart-pulse-fill me-2"></i><?= SITE_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <?php if (isLoggedIn()): ?>
                    <?php if (isDoctor()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/doctor/dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/doctor/availability.php"><i class="bi bi-calendar-week me-1"></i>Availability</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/doctor/appointments.php"><i class="bi bi-clipboard2-pulse me-1"></i>Appointments</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/patient/dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/patient/doctors.php"><i class="bi bi-search me-1"></i>Find Doctors</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/patient/appointments.php"><i class="bi bi-calendar-check me-1"></i>My Appointments</a></li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <div class="avatar-sm me-2"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></div>
                            <?= e($_SESSION['user_name'] ?? '') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/<?= currentUserRole() ?>/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-light btn-sm px-3 ms-2" href="<?= SITE_URL ?>/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="py-4">
<div class="container">
