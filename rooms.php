<?php
// Database configuration
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

// Handle room status updates
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status' && isset($_POST['room_id']) && isset($_POST['status'])) {
        try {
            $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE room_id = ?");
            $stmt->execute([$_POST['status'], $_POST['room_id']]);
            $success_message = "Room status updated successfully!";
        } catch(PDOException $e) {
            $error_message = "Error updating room status: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] === 'update_amenities' && isset($_POST['room_id']) && isset($_POST['amenities'])) {
        try {
            $stmt = $pdo->prepare("UPDATE rooms SET amenities = ? WHERE room_id = ?");
            $stmt->execute([$_POST['amenities'], $_POST['room_id']]);
            $success_message = "Room amenities updated successfully!";
        } catch(PDOException $e) {
            $error_message = "Error updating room amenities: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$floor_filter = isset($_GET['floor']) ? $_GET['floor'] : '';

// Build query with filters
$query = "SELECT r.*, rt.type_name, rt.base_price, rt.max_occupancy, rt.description 
          FROM rooms r 
          JOIN room_types rt ON r.type_id = rt.type_id 
          WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND r.status = ?";
    $params[] = $status_filter;
}

if ($type_filter) {
    $query .= " AND r.type_id = ?";
    $params[] = $type_filter;
}

if ($floor_filter) {
    $query .= " AND r.floor_number = ?";
    $params[] = $floor_filter;
}

$query .= " ORDER BY r.floor_number, r.room_number";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching rooms: " . $e->getMessage();
    $rooms = [];
}

// Get room types for filter
try {
    $stmt = $pdo->query("SELECT * FROM room_types ORDER BY type_name");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $room_types = [];
}

// Get unique floors for filter
try {
    $stmt = $pdo->query("SELECT DISTINCT floor_number FROM rooms ORDER BY floor_number");
    $floors = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    $floors = [];
}

// Count rooms by status
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM rooms GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch(PDOException $e) {
    $status_counts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Luxury Haven Hotel</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .room-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .room-card.available { border-color: #27ae60; }
        .room-card.occupied { border-color: #e74c3c; }
        .room-card.maintenance { border-color: #f39c12; }
        .room-card.reserved { border-color: #3498db; }
        
        .room-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            text-align: center;
        }
        
        .room-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .room-type {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .room-body {
            padding: 1.5rem;
        }
        
        .room-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            text-align: center;
            padding: 0.8rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-item i {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #e74c3c;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.2rem;
        }
        
        .info-value {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .amenities-list {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .amenities-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .amenities-text {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
        }
        
        .room-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .filters-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }
        
        .stat-card.available::before { background: #27ae60; }
        .stat-card.occupied::before { background: #e74c3c; }
        .stat-card.maintenance::before { background: #f39c12; }
        .stat-card.reserved::before { background: #3498db; }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            text-transform: uppercase;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #e74c3c;
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
                <li><a href="rooms.php" style="color: #e74c3c;">Rooms</a></li>
                <li><a href="guests.php">Guests</a></li>
                <li><a href="billing.php">Billing</a></li>
            </ul>
            <div class="nav-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 120px;">
        <div class="form-title">
            <i class="fas fa-bed"></i> Room Management
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Room Statistics -->
        <div class="stats-grid">
            <div class="stat-card available">
                <div class="stat-number"><?php echo isset($status_counts['available']) ? $status_counts['available'] : 0; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card occupied">
                <div class="stat-number"><?php echo isset($status_counts['occupied']) ? $status_counts['occupied'] : 0; ?></div>
                <div class="stat-label">Occupied</div>
            </div>
            <div class="stat-card maintenance">
                <div class="stat-number"><?php echo isset($status_counts['maintenance']) ? $status_counts['maintenance'] : 0; ?></div>
                <div class="stat-label">Maintenance</div>
            </div>
            <div class="stat-card reserved">
                <div class="stat-number"><?php echo isset($status_counts['reserved']) ? $status_counts['reserved'] : 0; ?></div>
                <div class="stat-label">Reserved</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <button class="btn btn-primary" onclick="showAllRooms()">
                <i class="fas fa-eye"></i> Show All Rooms
            </button>
            <button class="btn btn-secondary" onclick="filterByStatus('available')">
                <i class="fas fa-check"></i> Available Only
            </button>
            <button class="btn btn-secondary" onclick="filterByStatus('occupied')">
                <i class="fas fa-user"></i> Occupied Only
            </button>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <h3 style="margin-bottom: 1.5rem; color: #2c3e50;">
                <i class="fas fa-filter"></i> Filter Rooms
            </h3>
            <form method="GET" class="filters-grid">
                <div class="form-group">
                    <label>Room Status</label>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="occupied" <?php echo $status_filter === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                        <option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="reserved" <?php echo $status_filter === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Room Type</label>
                    <select name="type">
                        <option value="">All Types</option>
                        <?php foreach ($room_types as $type): ?>
                            <option value="<?php echo $type['type_id']; ?>" <?php echo $type_filter == $type['type_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Floor</label>
                    <select name="floor">
                        <option value="">All Floors</option>
                        <?php foreach ($floors as $floor): ?>
                            <option value="<?php echo $floor; ?>" <?php echo $floor_filter == $floor ? 'selected' : ''; ?>>
                                Floor <?php echo $floor; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Rooms Grid -->
        <div class="room-grid">
            <?php foreach ($rooms as $room): ?>
                <div class="room-card <?php echo $room['status']; ?>">
                    <div class="room-header">
                        <div class="room-number">Room <?php echo htmlspecialchars($room['room_number']); ?></div>
                        <div class="room-type"><?php echo htmlspecialchars($room['type_name']); ?></div>
                    </div>
                    
                    <div class="room-body">
                        <div class="room-info">
                            <div class="info-item">
                                <i class="fas fa-dollar-sign"></i>
                                <div class="info-label">Price per Night</div>
                                <div class="info-value">$<?php echo number_format($room['base_price'], 2); ?></div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-users"></i>
                                <div class="info-label">Max Occupancy</div>
                                <div class="info-value"><?php echo $room['max_occupancy']; ?> guests</div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-building"></i>
                                <div class="info-label">Floor</div>
                                <div class="info-value">Floor <?php echo $room['floor_number']; ?></div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-info-circle"></i>
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span class="status-badge status-<?php echo $room['status']; ?>">
                                        <?php echo ucfirst($room['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($room['amenities']): ?>
                            <div class="amenities-list">
                                <div class="amenities-title">
                                    <i class="fas fa-star"></i> Room Amenities
                                </div>
                                <div class="amenities-text">
                                    <?php echo htmlspecialchars($room['amenities']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($room['description']): ?>
                            <div class="amenities-list">
                                <div class="amenities-title">
                                    <i class="fas fa-info"></i> Description
                                </div>
                                <div class="amenities-text">
                                    <?php echo htmlspecialchars($room['description']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="room-actions">
                            <button class="btn-small btn-edit" onclick="changeStatus(<?php echo $room['room_id']; ?>, '<?php echo $room['status']; ?>')">
                                <i class="fas fa-edit"></i> Change Status
                            </button>
                            <button class="btn-small btn-view" onclick="editAmenities(<?php echo $room['room_id']; ?>, '<?php echo htmlspecialchars($room['amenities'], ENT_QUOTES); ?>')">
                                <i class="fas fa-cog"></i> Edit Amenities
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($rooms)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #666;">
                    <i class="fas fa-bed" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3>No rooms found</h3>
                    <p>Try adjusting your filters to see more results.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('statusModal')">&times;</span>
            <h3 style="margin-bottom: 1.5rem; color: #2c3e50;">Change Room Status</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="room_id" id="statusRoomId">
                
                <div class="form-group">
                    <label>New Status</label>
                    <select name="status" id="statusSelect" required>
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="reserved">Reserved</option>
                    </select>
                </div>
                
                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Amenities Edit Modal -->
    <div id="amenitiesModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('amenitiesModal')">&times;</span>
            <h3 style="margin-bottom: 1.5rem; color: #2c3e50;">Edit Room Amenities</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_amenities">
                <input type="hidden" name="room_id" id="amenitiesRoomId">
                
                <div class="form-group">
                    <label>Amenities (separate with commas)</label>
                    <textarea name="amenities" id="amenitiesText" rows="4" placeholder="WiFi, TV, AC, Private Bath, Mini Fridge..."></textarea>
                </div>
                
                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('amenitiesModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Amenities</button>
                </div>
            </form>
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

        // Modal functions
        function changeStatus(roomId, currentStatus) {
            document.getElementById('statusRoomId').value = roomId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }

        function editAmenities(roomId, currentAmenities) {
            document.getElementById('amenitiesRoomId').value = roomId;
            document.getElementById('amenitiesText').value = currentAmenities;
            document.getElementById('amenitiesModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const statusModal = document.getElementById('statusModal');
            const amenitiesModal = document.getElementById('amenitiesModal');
            
            if (event.target === statusModal) {
                statusModal.style.display = 'none';
            }
            if (event.target === amenitiesModal) {
                amenitiesModal.style.display = 'none';
            }
        }

        // Quick filter functions
        function showAllRooms() {
            window.location.href = 'rooms.php';
        }

        function filterByStatus(status) {
            window.location.href = 'rooms.php?status=' + status;
        }

        // Auto-hide success/error messages
        setTimeout(function() {
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(function(message) {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';
                setTimeout(function() {
                    message.style.display = 'none';
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>