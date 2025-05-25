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

// Handle form submissions
$message = '';
$error = '';

// Add new guest
if (isset($_POST['add_guest'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO guests (first_name, last_name, email, phone, address, id_number, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['id_number'],
            $_POST['date_of_birth']
        ]);
        $message = "Guest added successfully!";
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Email already exists. Please use a different email address.";
        } else {
            $error = "Error adding guest: " . $e->getMessage();
        }
    }
}

// Update guest
if (isset($_POST['update_guest'])) {
    try {
        $stmt = $pdo->prepare("UPDATE guests SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, id_number = ?, date_of_birth = ? WHERE guest_id = ?");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'],
            $_POST['id_number'],
            $_POST['date_of_birth'],
            $_POST['guest_id']
        ]);
        $message = "Guest updated successfully!";
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Email already exists. Please use a different email address.";
        } else {
            $error = "Error updating guest: " . $e->getMessage();
        }
    }
}

// Delete guest
if (isset($_GET['delete'])) {
    try {
        // Check if guest has any reservations
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE guest_id = ?");
        $check_stmt->execute([$_GET['delete']]);
        $reservation_count = $check_stmt->fetchColumn();
        
        if ($reservation_count > 0) {
            $error = "Cannot delete guest. Guest has existing reservations.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM guests WHERE guest_id = ?");
            $stmt->execute([$_GET['delete']]);
            $message = "Guest deleted successfully!";
        }
    } catch(PDOException $e) {
        $error = "Error deleting guest: " . $e->getMessage();
    }
}

// Get guest for editing
$edit_guest = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM guests WHERE guest_id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_guest = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_query = '';
$search_params = [];

if (!empty($search)) {
    $search_query = " WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $search_term = "%$search%";
    $search_params = [$search_term, $search_term, $search_term, $search_term];
}

// Fetch all guests with search
$stmt = $pdo->prepare("SELECT g.*, 
    (SELECT COUNT(*) FROM reservations r WHERE r.guest_id = g.guest_id) as total_reservations,
    (SELECT COUNT(*) FROM reservations r WHERE r.guest_id = g.guest_id AND r.status = 'confirmed') as confirmed_reservations
    FROM guests g" . $search_query . " ORDER BY g.created_at DESC");
$stmt->execute($search_params);
$guests = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .guest-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
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
        
        .guest-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .toggle-form-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .toggle-form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .guest-form-container {
            display: none;
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .guest-form-container.active {
            display: block;
            animation: fadeInUp 0.5s ease;
        }
        
        .guest-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .guest-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-left-color: #667eea;
        }
        
        .guest-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .guest-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .guest-email {
            color: #667eea;
            font-weight: 500;
        }
        
        .guest-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }
        
        .detail-item i {
            color: #667eea;
            width: 20px;
        }
        
        .guest-stats-mini {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .mini-stat {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .mini-stat strong {
            color: #2c3e50;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            
            .guest-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .guest-header {
                flex-direction: column;
            }
            
            .guest-details {
                grid-template-columns: 1fr;
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

        <?php if ($message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Guest Statistics -->
        <div class="guest-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($guests); ?></div>
                <div class="stat-label">Total Guests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo array_sum(array_column($guests, 'total_reservations')); ?></div>
                <div class="stat-label">Total Reservations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($guests, function($g) { return $g['total_reservations'] > 0; })); ?></div>
                <div class="stat-label">Active Guests</div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <h3><i class="fas fa-search"></i> Search Guests</h3>
            <form method="GET" class="search-form">
                <div class="form-group search-input">
                    <input type="text" name="search" placeholder="Search by name, email, or phone..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="guests.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Guest Actions -->
        <div class="guest-actions">
            <button class="toggle-form-btn" onclick="toggleGuestForm()">
                <i class="fas fa-plus"></i> Add New Guest
            </button>
        </div>

        <!-- Add/Edit Guest Form -->
        <div class="guest-form-container" id="guestFormContainer">
            <h3><?php echo $edit_guest ? 'Edit Guest' : 'Add New Guest'; ?></h3>
            <form method="POST" class="form-grid">
                <?php if ($edit_guest): ?>
                    <input type="hidden" name="guest_id" value="<?php echo $edit_guest['guest_id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required 
                           value="<?php echo $edit_guest ? htmlspecialchars($edit_guest['first_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required 
                           value="<?php echo $edit_guest ? htmlspecialchars($edit_guest['last_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required 
                           value="<?php echo $edit_guest ? htmlspecialchars($edit_guest['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Phone *</label>
                    <input type="tel" name="phone" required 
                           value="<?php echo $edit_guest ? htmlspecialchars($edit_guest['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>ID Number</label>
                    <input type="text" name="id_number" 
                           value="<?php echo $edit_guest ? htmlspecialchars($edit_guest['id_number']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" 
                           value="<?php echo $edit_guest ? $edit_guest['date_of_birth'] : ''; ?>">
                </div>
                
                <div class="form-group form-group-full">
                    <label>Address</label>
                    <textarea name="address" rows="3"><?php echo $edit_guest ? htmlspecialchars($edit_guest['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group form-group-full">
                    <button type="submit" name="<?php echo $edit_guest ? 'update_guest' : 'add_guest'; ?>" class="btn btn-primary">
                        <i class="fas fa-<?php echo $edit_guest ? 'edit' : 'plus'; ?>"></i>
                        <?php echo $edit_guest ? 'Update Guest' : 'Add Guest'; ?>
                    </button>
                    <?php if ($edit_guest): ?>
                        <a href="guests.php" class="btn btn-secondary" style="margin-left: 1rem;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Guests List -->
        <div class="form-container">
            <h3>
                <i class="fas fa-list"></i> Guest Directory
                <?php if ($search): ?>
                    <small>(Search results for "<?php echo htmlspecialchars($search); ?>")</small>
                <?php endif; ?>
            </h3>
            
            <?php if (empty($guests)): ?>
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <p>No guests found<?php echo $search ? ' matching your search' : ''; ?>.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 1.5rem;">
                    <?php foreach ($guests as $guest): ?>
                        <div class="guest-card">
                            <div class="guest-header">
                                <div>
                                    <div class="guest-name">
                                        <?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>
                                    </div>
                                    <div class="guest-email">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($guest['email']); ?>
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <a href="guests.php?edit=<?php echo $guest['guest_id']; ?>" 
                                       class="btn-small btn-edit" title="Edit Guest">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="guest_profile.php?id=<?php echo $guest['guest_id']; ?>" 
                                       class="btn-small btn-view" title="View Profile">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="guests.php?delete=<?php echo $guest['guest_id']; ?>" 
                                       class="btn-small btn-delete" title="Delete Guest"
                                       onclick="return confirm('Are you sure you want to delete this guest?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="guest-details">
                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($guest['phone']); ?></span>
                                </div>
                                <?php if ($guest['date_of_birth']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-birthday-cake"></i>
                                        <span><?php echo date('M d, Y', strtotime($guest['date_of_birth'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($guest['id_number']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-id-card"></i>
                                        <span><?php echo htmlspecialchars($guest['id_number']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <i class="fas fa-calendar-plus"></i>
                                    <span>Joined <?php echo date('M d, Y', strtotime($guest['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($guest['address']): ?>
                                <div class="detail-item" style="margin-top: 1rem;">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($guest['address']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="guest-stats-mini">
                                <div class="mini-stat">
                                    <strong><?php echo $guest['total_reservations']; ?></strong> Total Reservations
                                </div>
                                <div class="mini-stat">
                                    <strong><?php echo $guest['confirmed_reservations']; ?></strong> Confirmed
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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

        // Toggle guest form
        function toggleGuestForm() {
            const formContainer = document.getElementById('guestFormContainer');
            const isVisible = formContainer.classList.contains('active');
            
            if (isVisible) {
                formContainer.classList.remove('active');
            } else {
                formContainer.classList.add('active');
                formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Show form if editing
        <?php if ($edit_guest): ?>
            document.getElementById('guestFormContainer').classList.add('active');
        <?php endif; ?>

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>