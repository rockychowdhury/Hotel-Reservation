<?php
// Database connection
$host = 'localhost';
$dbname = 'hotel_reservation';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Search functionality for guests
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_query = '';
$search_params = [];

if (!empty($search)) {
    $search_query = " WHERE g.first_name LIKE ? OR g.last_name LIKE ? OR g.email LIKE ? OR g.phone LIKE ?";
    $search_term = "%$search%";
    $search_params = [$search_term, $search_term, $search_term, $search_term];
}

// Fetch all guests with search and reservation count
$stmt = $pdo->prepare("SELECT g.*, 
    COUNT(r.reservation_id) as total_reservations
    FROM guests g
    LEFT JOIN reservations r ON g.guest_id = r.guest_id
    " . $search_query . " 
    GROUP BY g.guest_id 
    ORDER BY g.created_at DESC");
$stmt->execute($search_params);
$guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total counts for statistics
$total_guests_stmt = $pdo->query("SELECT COUNT(*) FROM guests");
$total_guests = $total_guests_stmt->fetchColumn();

$total_reservations_stmt = $pdo->query("SELECT COUNT(*) FROM reservations");
$total_reservations = $total_reservations_stmt->fetchColumn();

// Fetch all reservations with guest and room details
$reservations_search = isset($_GET['res_search']) ? $_GET['res_search'] : '';
$res_search_query = '';
$res_search_params = [];

if (!empty($reservations_search)) {
    $res_search_query = " WHERE g.first_name LIKE ? OR g.last_name LIKE ? OR g.email LIKE ? OR r.reservation_id LIKE ? OR rm.room_number LIKE ?";
    $res_search_term = "%$reservations_search%";
    $res_search_params = [$res_search_term, $res_search_term, $res_search_term, $res_search_term, $res_search_term];
}

$reservations_stmt = $pdo->prepare("SELECT r.*, 
    CONCAT(g.first_name, ' ', g.last_name) as guest_name,
    g.email as guest_email,
    g.phone as guest_phone,
    rm.room_number,
    rt.type_name,
    rt.base_price
    FROM reservations r
    JOIN guests g ON r.guest_id = g.guest_id
    JOIN rooms rm ON r.room_id = rm.room_id
    JOIN room_types rt ON rm.type_id = rt.type_id
    " . $res_search_query . "
    ORDER BY r.created_at DESC");
$reservations_stmt->execute($res_search_params);
$reservations = $reservations_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Management - Luxury Haven Hotel</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.2);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            align-items: end;
        }
        
        .search-input {
            flex: 1;
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .data-table {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 3rem;
        }
        
        .table-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
        }
        
        .table-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .table-content {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            color: #666;
            vertical-align: middle;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
            transition: background 0.2s ease;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-checked-in {
            background: #cce7ff;
            color: #0066cc;
        }
        
        .status-checked-out {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-data {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
        
        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        .guest-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        
        .guest-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .guest-details h4 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .guest-details p {
            margin: 0;
            font-size: 0.8rem;
            color: #666;
        }
        
        .reservation-id {
            font-family: 'Courier New', monospace;
            background: #f1f3f4;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .amount {
            font-weight: 600;
            color: #27ae60;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .table-content {
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 0.8rem 0.5rem;
            }
            
            .guest-info {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-hotel"></i>
                <span>Luxury Haven</span>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="reservation.php">Reservations</a></li>
                <li><a href="rooms.php">Rooms</a></li>
                <li><a href="guests.php" class="active">Guests</a></li>
                <li><a href="checkin.php">Check-in/out</a></li>
                <li><a href="billing.php">Billing</a></li>
            </ul>
            <div class="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 120px; padding-bottom: 2rem;">
        <h1 class="form-title">
            <i class="fas fa-users"></i> Guest Management System
        </h1>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $total_guests; ?></div>
                <div class="stat-label">Total Guests</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number"><?php echo $total_reservations; ?></div>
                <div class="stat-label">Total Reservations</div>
            </div>
        </div>

        <!-- Guest Search Section -->
        <div class="search-section">
            <h3 class="section-title">
                <i class="fas fa-search"></i> Search Guests
            </h3>
            <form method="GET" class="search-form">
                <input type="hidden" name="res_search" value="<?php echo htmlspecialchars($reservations_search); ?>">
                <div class="form-group search-input">
                    <input type="text" name="search" placeholder="Search guests by name, email, or phone..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="guests.php<?php echo $reservations_search ? '?res_search=' . urlencode($reservations_search) : ''; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Guests Table -->
        <div class="data-table">
            <div class="table-header">
                <h3>
                    <i class="fas fa-users"></i> Guest Directory
                    <?php if ($search): ?>
                        <small>(Search results for "<?php echo htmlspecialchars($search); ?>")</small>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="table-content">
                <?php if (empty($guests)): ?>
                    <div class="no-data">
                        <i class="fas fa-users"></i>
                        <p>No guests found<?php echo $search ? ' matching your search' : ''; ?>.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Contact</th>
                                <th>ID Number</th>
                                <th>Date of Birth</th>
                                <th>Address</th>
                                <th>Reservations</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($guests as $guest): ?>
                                <tr>
                                    <td>
                                        <div class="guest-info">
                                            <div class="guest-avatar">
                                                <?php echo strtoupper(substr($guest['first_name'], 0, 1) . substr($guest['last_name'], 0, 1)); ?>
                                            </div>
                                            <div class="guest-details">
                                                <h4><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($guest['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($guest['phone']); ?></td>
                                    <td><?php echo $guest['id_number'] ? htmlspecialchars($guest['id_number']) : '-'; ?></td>
                                    <td>
                                        <?php echo $guest['date_of_birth'] ? date('M d, Y', strtotime($guest['date_of_birth'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($guest['address']) {
                                            echo htmlspecialchars(strlen($guest['address']) > 50 ? substr($guest['address'], 0, 50) . '...' : $guest['address']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $guest['total_reservations']; ?></strong>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($guest['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reservations Search Section -->
        <div class="search-section">
            <h3 class="section-title">
                <i class="fas fa-search"></i> Search Reservations
            </h3>
            <form method="GET" class="search-form">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <div class="form-group search-input">
                    <input type="text" name="res_search" placeholder="Search reservations by guest name, email, reservation ID, or room number..." 
                           value="<?php echo htmlspecialchars($reservations_search); ?>" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($reservations_search): ?>
                    <a href="guests.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Reservations Table -->
        <div class="data-table">
            <div class="table-header">
                <h3>
                    <i class="fas fa-calendar-alt"></i> All Reservations
                    <?php if ($reservations_search): ?>
                        <small>(Search results for "<?php echo htmlspecialchars($reservations_search); ?>")</small>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="table-content">
                <?php if (empty($reservations)): ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-alt"></i>
                        <p>No reservations found<?php echo $reservations_search ? ' matching your search' : ''; ?>.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Reservation ID</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Guests</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td>
                                        <span class="reservation-id">#<?php echo str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td>
                                        <div class="guest-info">
                                            <div class="guest-avatar">
                                                <?php 
                                                $names = explode(' ', $reservation['guest_name']);
                                                echo strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                                ?>
                                            </div>
                                            <div class="guest-details">
                                                <h4><?php echo htmlspecialchars($reservation['guest_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($reservation['guest_email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($reservation['room_number']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($reservation['type_name']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($reservation['check_in_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($reservation['check_out_date'])); ?></td>
                                    <td>
                                        <i class="fas fa-users"></i> <?php echo $reservation['adults']; ?>
                                        <?php if ($reservation['children'] > 0): ?>
                                            <br><i class="fas fa-child"></i> <?php echo $reservation['children']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($reservation['total_amount']): ?>
                                            <span class="amount">$<?php echo number_format($reservation['total_amount'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="amount">$<?php echo number_format($reservation['base_price'], 2); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $reservation['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $reservation['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($reservation['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Mobile navigation toggle
        const navToggle = document.querySelector('.nav-toggle');
        const navMenu = document.querySelector('.nav-menu');

        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            navToggle.classList.toggle('active');
        });
    </script>
</body>
</html>