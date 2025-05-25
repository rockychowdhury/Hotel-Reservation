<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "hotel_reservation");

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

$message = "";
$error = "";

// Handle room status updates
if(isset($_POST['update_status'])) {
    $room_id = $_POST['room_id'];
    $new_status = $_POST['new_status'];
    
    $update_query = "UPDATE rooms SET status = ? WHERE room_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $room_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $message = "Room status updated successfully!";
    } else {
        $error = "Error updating room status.";
    }
}

// Handle adding new room
if(isset($_POST['add_room'])) {
    $room_number = $_POST['room_number'];
    $type_id = $_POST['type_id'];
    $floor = $_POST['floor'];
    $status = 'available';
    
    $add_query = "INSERT INTO rooms (room_number, type_id, floor, status) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $add_query);
    mysqli_stmt_bind_param($stmt, "siis", $room_number, $type_id, $floor, $status);
    
    if(mysqli_stmt_execute($stmt)) {
        $message = "New room added successfully!";
    } else {
        $error = "Error adding new room. Room number might already exist.";
    }
}

// Get all rooms with their types
$rooms_query = "SELECT r.*, rt.type_name, rt.base_price 
                FROM rooms r 
                JOIN room_types rt ON r.type_id = rt.type_id 
                ORDER BY r.room_number";
$rooms_result = mysqli_query($conn, $rooms_query);

// Get room types for dropdown
$types_query = "SELECT * FROM room_types ORDER BY type_name";
$types_result = mysqli_query($conn, $types_query);
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
        .management-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .management-header {
            text-align: center;
            margin-bottom: 3rem;
            color: white;
        }

        .management-header h1 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-card i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ffd700;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .add-room-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .rooms-list-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .rooms-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .rooms-table th,
        .rooms-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .rooms-table th {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            font-weight: 600;
        }

        .rooms-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-available { background: #d4edda; color: #155724; }
        .status-occupied { background: #f8d7da; color: #721c24; }
        .status-maintenance { background: #fff3cd; color: #856404; }
        .status-cleaning { background: #cce7ff; color: #004085; }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
        }

        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .management-container {
                padding: 1rem;
            }
            
            .rooms-table {
                font-size: 0.9rem;
            }
            
            .rooms-table th,
            .rooms-table td {
                padding: 0.5rem;
            }
        }
        .input-group{
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="management-container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="management-header">
            <h1><i class="fas fa-bed"></i> Room Management</h1>
            <p>Manage room availability, status, and information</p>
        </div>

        <?php
        // Calculate room statistics
        $stats_query = "SELECT 
                            COUNT(*) as total_rooms,
                            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
                            SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms,
                            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms
                        FROM rooms";
        $stats_result = mysqli_query($conn, $stats_query);
        $stats = mysqli_fetch_assoc($stats_result);
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-home"></i>
                <div class="stat-number"><?php echo $stats['total_rooms']; ?></div>
                <div>Total Rooms</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <div class="stat-number"><?php echo $stats['available_rooms']; ?></div>
                <div>Available</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-user"></i>
                <div class="stat-number"><?php echo $stats['occupied_rooms']; ?></div>
                <div>Occupied</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-tools"></i>
                <div class="stat-number"><?php echo $stats['maintenance_rooms']; ?></div>
                <div>Maintenance</div>
            </div>
        </div>

        <?php if($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Add New Room Section -->
            <div class="add-room-section">
                <h3 class="section-title"><i class="fas fa-plus"></i> Add New Room</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Room Number</label>
                        <input type="text" name="room_number" required placeholder="e.g., 101, 201A">
                    </div>
                    
                    <div class="form-group">
                        <label>Room Type</label>
                        <select name="type_id" required>
                            <option value="">Select Room Type</option>
                            <?php 
                            mysqli_data_seek($types_result, 0);
                            while($type = mysqli_fetch_assoc($types_result)): 
                            ?>
                                <option value="<?php echo $type['type_id']; ?>">
                                    <?php echo $type['type_name']; ?> - $<?php echo $type['base_price']; ?>/night
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Floor</label>
                        <input type="number" name="floor" required min="1" max="50" placeholder="Floor number">
                    </div>
                    
                    <button type="submit" name="add_room" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Room
                    </button>
                </form>
            </div>

            <!-- Rooms List Section -->
            <div class="rooms-list-section">
                <h3 class="section-title"><i class="fas fa-list"></i> All Rooms</h3>
                
                <div style="overflow-x: auto;">
                    <table class="rooms-table">
                        <thead>
                            <tr>
                                <th>Room #</th>
                                <th>Type</th>
                                <th>Floor</th>
                                <th>Price/Night</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($room = mysqli_fetch_assoc($rooms_result)): ?>
                            <tr>
                                <td><strong><?php echo $room['room_number']; ?></strong></td>
                                <td><?php echo $room['type_name']; ?></td>
                                <td><?php echo $room['floor']; ?></td>
                                <td>$<?php echo number_format($room['base_price'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $room['status']; ?>">
                                        <?php echo ucfirst($room['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST"  >
                                        <div class="input-group">
                                            <div>
                                            <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                        <select name="new_status" style="padding: 0.3rem; border-radius: 5px; margin-right: 0.5rem;">
                                            <option value="available" <?php echo $room['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="occupied" <?php echo $room['status'] == 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                                            <option value="maintenance" <?php echo $room['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            <option value="cleaning" <?php echo $room['status'] == 'cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                                        </select>
                                        </div>
                                        <button type="submit" name="update_status" class="btn btn-primary btn-small">
                                            Update
                                        </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>