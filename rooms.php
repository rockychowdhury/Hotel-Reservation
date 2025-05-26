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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        'luxury-gold': '#D4AF37',
                        'luxury-navy': '#1E3A8A',
                        'luxury-gray': '#6B7280',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-hotel text-2xl text-luxury-gold"></i>
                    <span class="text-xl font-bold text-gray-900">Luxury Haven</span>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="index.php" class="text-gray-600 hover:text-luxury-gold px-3 py-2 rounded-md text-sm font-medium transition-colors">Home</a>
                        <a href="reservation.php" class="text-gray-600 hover:text-luxury-gold px-3 py-2 rounded-md text-sm font-medium transition-colors">Reservations</a>
                        <a href="rooms.php" class="bg-luxury-gold text-white px-3 py-2 rounded-md text-sm font-medium">Rooms</a>
                        <a href="guests.php" class="text-gray-600 hover:text-luxury-gold px-3 py-2 rounded-md text-sm font-medium transition-colors">Guests</a>
                        <a href="billing.php" class="text-gray-600 hover:text-luxury-gold px-3 py-2 rounded-md text-sm font-medium transition-colors">Billing</a>
                    </div>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button id="mobile-menu-button" class="text-gray-600 hover:text-luxury-gold focus:outline-none focus:text-luxury-gold">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-gray-200">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="index.php" class="text-gray-600 hover:text-luxury-gold block px-3 py-2 rounded-md text-base font-medium">Home</a>
                <a href="reservation.php" class="text-gray-600 hover:text-luxury-gold block px-3 py-2 rounded-md text-base font-medium">Reservations</a>
                <a href="rooms.php" class="bg-luxury-gold text-white block px-3 py-2 rounded-md text-base font-medium">Rooms</a>
                <a href="guests.php" class="text-gray-600 hover:text-luxury-gold block px-3 py-2 rounded-md text-base font-medium">Guests</a>
                <a href="billing.php" class="text-gray-600 hover:text-luxury-gold block px-3 py-2 rounded-md text-base font-medium">Billing</a>
            </div>
        </div>
    </nav>

    <div class="pt-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center space-x-3 mb-2">
                <i class="fas fa-bed text-3xl text-luxury-gold"></i>
                <h1 class="text-3xl font-bold text-gray-900">Room Management</h1>
            </div>
            <p class="text-gray-600">Manage room status, amenities, and view detailed information</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center space-x-3">
                <i class="fas fa-check-circle text-green-500"></i>
                <span class="text-green-800"><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-center space-x-3">
                <i class="fas fa-exclamation-circle text-red-500"></i>
                <span class="text-red-800"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <!-- Room Statistics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-green-500"></div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-900 mb-2"><?php echo isset($status_counts['available']) ? $status_counts['available'] : 0; ?></div>
                    <div class="text-sm text-gray-600 uppercase font-medium">Available</div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-red-500"></div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-900 mb-2"><?php echo isset($status_counts['occupied']) ? $status_counts['occupied'] : 0; ?></div>
                    <div class="text-sm text-gray-600 uppercase font-medium">Occupied</div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-orange-500"></div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-900 mb-2"><?php echo isset($status_counts['maintenance']) ? $status_counts['maintenance'] : 0; ?></div>
                    <div class="text-sm text-gray-600 uppercase font-medium">Maintenance</div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-blue-500"></div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-900 mb-2"><?php echo isset($status_counts['reserved']) ? $status_counts['reserved'] : 0; ?></div>
                    <div class="text-sm text-gray-600 uppercase font-medium">Reserved</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-3 mb-8">
            <button onclick="showAllRooms()" class="bg-luxury-gold hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                <i class="fas fa-eye"></i>
                <span>Show All Rooms</span>
            </button>
            <button onclick="filterByStatus('available')" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                <i class="fas fa-check"></i>
                <span>Available Only</span>
            </button>
            <button onclick="filterByStatus('occupied')" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center space-x-2">
                <i class="fas fa-user"></i>
                <span>Occupied Only</span>
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center space-x-2">
                <i class="fas fa-filter text-luxury-gold"></i>
                <span>Filter Rooms</span>
            </h3>
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Room Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-luxury-gold focus:border-luxury-gold">
                        <option value="">All Statuses</option>
                        <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="occupied" <?php echo $status_filter === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                        <option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="reserved" <?php echo $status_filter === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Room Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-luxury-gold focus:border-luxury-gold">
                        <option value="">All Types</option>
                        <?php foreach ($room_types as $type): ?>
                            <option value="<?php echo $type['type_id']; ?>" <?php echo $type_filter == $type['type_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Floor</label>
                    <select name="floor" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-luxury-gold focus:border-luxury-gold">
                        <option value="">All Floors</option>
                        <?php foreach ($floors as $floor): ?>
                            <option value="<?php echo $floor; ?>" <?php echo $floor_filter == $floor ? 'selected' : ''; ?>>
                                Floor <?php echo $floor; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-luxury-gold hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                        <i class="fas fa-search"></i>
                        <span>Apply Filters</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Rooms Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
            <?php foreach ($rooms as $room): ?>
                <div class="bg-white rounded-xl shadow-sm border-2 <?php 
                    echo $room['status'] === 'available' ? 'border-green-200 hover:border-green-300' : 
                        ($room['status'] === 'occupied' ? 'border-red-200 hover:border-red-300' : 
                        ($room['status'] === 'maintenance' ? 'border-orange-200 hover:border-orange-300' : 
                        'border-blue-200 hover:border-blue-300')); 
                ?> overflow-hidden transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                    <!-- Room Header -->
                    <div class="bg-gradient-to-r from-gray-800 to-gray-700 text-white p-6 text-center">
                        <div class="text-2xl font-bold mb-1">Room <?php echo htmlspecialchars($room['room_number']); ?></div>
                        <div class="text-gray-200 text-lg"><?php echo htmlspecialchars($room['type_name']); ?></div>
                    </div>
                    
                    <!-- Room Body -->
                    <div class="p-6">
                        <!-- Room Info Grid -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-dollar-sign text-luxury-gold text-lg mb-2 block"></i>
                                <div class="text-xs text-gray-600 mb-1">Price per Night</div>
                                <div class="font-semibold text-gray-900">$<?php echo number_format($room['base_price'], 2); ?></div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-users text-luxury-gold text-lg mb-2 block"></i>
                                <div class="text-xs text-gray-600 mb-1">Max Occupancy</div>
                                <div class="font-semibold text-gray-900"><?php echo $room['max_occupancy']; ?> guests</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-building text-luxury-gold text-lg mb-2 block"></i>
                                <div class="text-xs text-gray-600 mb-1">Floor</div>
                                <div class="font-semibold text-gray-900">Floor <?php echo $room['floor_number']; ?></div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-info-circle text-luxury-gold text-lg mb-2 block"></i>
                                <div class="text-xs text-gray-600 mb-1">Status</div>
                                <div class="font-semibold">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php 
                                        echo $room['status'] === 'available' ? 'bg-green-100 text-green-800' : 
                                            ($room['status'] === 'occupied' ? 'bg-red-100 text-red-800' : 
                                            ($room['status'] === 'maintenance' ? 'bg-orange-100 text-orange-800' : 
                                            'bg-blue-100 text-blue-800')); 
                                    ?>">
                                        <?php echo ucfirst($room['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Amenities -->
                        <?php if ($room['amenities']): ?>
                            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                                <div class="flex items-center space-x-2 mb-2">
                                    <i class="fas fa-star text-luxury-gold"></i>
                                    <span class="font-semibold text-gray-900">Room Amenities</span>
                                </div>
                                <div class="text-gray-700 text-sm">
                                    <?php echo htmlspecialchars($room['amenities']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Description -->
                        <?php if ($room['description']): ?>
                            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                                <div class="flex items-center space-x-2 mb-2">
                                    <i class="fas fa-info text-luxury-gold"></i>
                                    <span class="font-semibold text-gray-900">Description</span>
                                </div>
                                <div class="text-gray-700 text-sm">
                                    <?php echo htmlspecialchars($room['description']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-2 pt-4 border-t border-gray-200">
                            <button onclick="changeStatus(<?php echo $room['room_id']; ?>, '<?php echo $room['status']; ?>')" 
                                    class="flex-1 bg-luxury-gold hover:bg-yellow-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center space-x-2">
                                <i class="fas fa-edit"></i>
                                <span>Change Status</span>
                            </button>
                            <button onclick="editAmenities(<?php echo $room['room_id']; ?>, '<?php echo htmlspecialchars($room['amenities'], ENT_QUOTES); ?>')" 
                                    class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center space-x-2">
                                <i class="fas fa-cog"></i>
                                <span>Edit Amenities</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($rooms)): ?>
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-bed text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No rooms found</h3>
                    <p class="text-gray-600">Try adjusting your filters to see more results.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-900">Change Room Status</h3>
                <button onclick="closeModal('statusModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="room_id" id="statusRoomId">
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
                    <select name="status" id="statusSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-luxury-gold focus:border-luxury-gold">
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="reserved">Reserved</option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('statusModal')" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-luxury-gold hover:bg-yellow-600 text-white rounded-lg font-medium transition-colors">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Amenities Edit Modal -->
    <div id="amenitiesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-900">Edit Room Amenities</h3>
                <button onclick="closeModal('amenitiesModal')" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_amenities">
                <input type="hidden" name="room_id" id="amenitiesRoomId">
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amenities (separate with commas)</label>
                    <textarea name="amenities" id="amenitiesText" rows="4" 
                              placeholder="WiFi, TV, AC, Private Bath, Mini Fridge..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-luxury-gold focus:border-luxury-gold"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('amenitiesModal')" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-luxury-gold hover:bg-yellow-600 text-white rounded-lg font-medium transition-colors">
                        Update Amenities
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mobile navigation toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Modal functions
        function changeStatus(roomId, currentStatus) {
            document.getElementById('statusRoomId').value = roomId;
            document.getElementById('statusSelect').value = currentStatus;
            const modal = document.getElementById('statusModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function editAmenities(roomId, currentAmenities) {
            document.getElementById('amenitiesRoomId').value = roomId;
            document.getElementById('amenitiesText').value = currentAmenities;
            const modal = document.getElementById('amenitiesModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const statusModal = document.getElementById('statusModal');
            const amenitiesModal = document.getElementById('amenitiesModal');
            
            if (event.target === statusModal) {
                closeModal('statusModal');
            }
            if (event.target === amenitiesModal) {
                closeModal('amenitiesModal');
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
            const messages = document.querySelectorAll('.bg-green-50, .bg-red-50');
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