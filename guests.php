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

// Handle delete guest action
if (isset($_POST['delete_guest']) && isset($_POST['guest_id'])) {
    $guest_id = $_POST['guest_id'];
    
    try {
        // Check if guest has reservations
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE guest_id = ?");
        $check_stmt->execute([$guest_id]);
        $reservation_count = $check_stmt->fetchColumn();
        
        if ($reservation_count > 0) {
            $error_message = "Cannot delete guest. Guest has existing reservations.";
        } else {
            // Delete guest
            $delete_stmt = $pdo->prepare("DELETE FROM guests WHERE guest_id = ?");
            $delete_stmt->execute([$guest_id]);
            $success_message = "Guest deleted successfully.";
        }
    } catch(PDOException $e) {
        $error_message = "Error deleting guest: " . $e->getMessage();
    }
}

// Handle add new guest
if (isset($_POST['add_guest'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $id_number = trim($_POST['id_number']) ?: null;
    
    try {
        $add_stmt = $pdo->prepare("INSERT INTO guests (first_name, last_name, email, phone, id_number) VALUES (?, ?, ?, ?, ?)");
        $add_stmt->execute([$first_name, $last_name, $email, $phone, $id_number]);
        $success_message = "Guest added successfully.";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $error_message = "Email address already exists.";
        } else {
            $error_message = "Error adding guest: " . $e->getMessage();
        }
    }
}

// Handle edit guest
if (isset($_POST['edit_guest'])) {
    $guest_id = $_POST['guest_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $id_number = trim($_POST['id_number']) ?: null;
    
    try {
        $edit_stmt = $pdo->prepare("UPDATE guests SET first_name = ?, last_name = ?, email = ?, phone = ?, id_number = ? WHERE guest_id = ?");
        $edit_stmt->execute([$first_name, $last_name, $email, $phone, $id_number, $guest_id]);
        $success_message = "Guest updated successfully.";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $error_message = "Email address already exists.";
        } else {
            $error_message = "Error updating guest: " . $e->getMessage();
        }
    }
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-hotel text-2xl text-blue-600"></i>
                    <a href="index.php" class="text-xl font-bold text-gray-800 hover:text-blue-600 transition-colors">
                        Luxury Haven
                    </a>
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="index.php" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Home</a>
                    <a href="reservation.php" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Reservations</a>
                    <a href="checkin.php" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Check-in/Out</a>
                    <a href="rooms.php" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Rooms</a>
                    <a href="guests.php" class="text-blue-600 font-semibold border-b-2 border-blue-600 pb-1">Guests</a>
                    <a href="billing.php" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Billing</a>
                </div>
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                <i class="fas fa-users text-blue-600"></i>
                Guest Management System
            </h1>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($success_message)): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium uppercase tracking-wide">Total Guests</p>
                        <p class="text-3xl font-bold"><?php echo $total_guests; ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium uppercase tracking-wide">Total Reservations</p>
                        <p class="text-3xl font-bold"><?php echo $total_reservations; ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-3">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New Guest Button -->
        <div class="mb-6">
            <button onclick="openAddGuestModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium flex items-center gap-2 transition-colors">
                <i class="fas fa-plus"></i>
                Add New Guest
            </button>
        </div>

        <!-- Guest Search Section -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-search text-blue-600"></i>
                Search Guests
            </h3>
            <form method="GET" class="flex gap-4">
                <input type="hidden" name="res_search" value="<?php echo htmlspecialchars($reservations_search); ?>">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Search guests by name, email, or phone..." 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                    <i class="fas fa-search"></i>
                    Search
                </button>
                <?php if ($search): ?>
                    <a href="guests.php<?php echo $reservations_search ? '?res_search=' . urlencode($reservations_search) : ''; ?>" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                        <i class="fas fa-times"></i>
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Guests Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-users"></i>
                    Guest Directory
                    <?php if ($search): ?>
                        <span class="text-sm opacity-80">(Search results for "<?php echo htmlspecialchars($search); ?>")</span>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($guests)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-users text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-500 text-lg">No guests found<?php echo $search ? ' matching your search' : ''; ?>.</p>
                    </div>
                <?php else: ?>
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guest</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservations</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($guests as $guest): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                                                    <?php echo strtoupper(substr($guest['first_name'], 0, 1) . substr($guest['last_name'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($guest['email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($guest['phone']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $guest['id_number'] ? htmlspecialchars($guest['id_number']) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo $guest['total_reservations']; ?> reservations
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($guest['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="openEditGuestModal(<?php echo htmlspecialchars(json_encode($guest)); ?>)" 
                                                    class="text-blue-600 hover:text-blue-900 bg-blue-100 hover:bg-blue-200 px-3 py-1 rounded-md transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="confirmDeleteGuest(<?php echo $guest['guest_id']; ?>, '<?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>', <?php echo $guest['total_reservations']; ?>)" 
                                                    class="text-red-600 hover:text-red-900 bg-red-100 hover:bg-red-200 px-3 py-1 rounded-md transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reservations Search Section -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-search text-blue-600"></i>
                Search Reservations
            </h3>
            <form method="GET" class="flex gap-4">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <div class="flex-1">
                    <input type="text" name="res_search" placeholder="Search reservations by guest name, email, reservation ID, or room number..." 
                           value="<?php echo htmlspecialchars($reservations_search); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                    <i class="fas fa-search"></i>
                    Search
                </button>
                <?php if ($reservations_search): ?>
                    <a href="guests.php<?php echo $search ? '?search=' . urlencode($search) : ''; ?>" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                        <i class="fas fa-times"></i>
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Reservations Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-teal-600 px-6 py-4">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-calendar-alt"></i>
                    All Reservations
                    <?php if ($reservations_search): ?>
                        <span class="text-sm opacity-80">(Search results for "<?php echo htmlspecialchars($reservations_search); ?>")</span>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <?php if (empty($reservations)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-calendar-alt text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-500 text-lg">No reservations found<?php echo $reservations_search ? ' matching your search' : ''; ?>.</p>
                    </div>
                <?php else: ?>
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservation ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guest</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-in</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-out</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guests</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($reservations as $reservation): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 font-mono">
                                            #<?php echo str_pad($reservation['reservation_id'], 6, '0', STR_PAD_LEFT); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                <div class="h-8 w-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-xs">
                                                    <?php 
                                                    $names = explode(' ', $reservation['guest_name']);
                                                    echo strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($reservation['guest_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($reservation['guest_email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($reservation['room_number']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($reservation['type_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($reservation['check_in_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($reservation['check_out_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center gap-1">
                                                <i class="fas fa-users text-xs"></i>
                                                <?php echo $reservation['adults']; ?>
                                            </span>
                                            <?php if ($reservation['children'] > 0): ?>
                                                <span class="inline-flex items-center gap-1">
                                                    <i class="fas fa-child text-xs"></i>
                                                    <?php echo $reservation['children']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                        $<?php echo number_format($reservation['total_amount'] ?: $reservation['base_price'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_classes = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'confirmed' => 'bg-green-100 text-green-800',
                                            'checked_in' => 'bg-blue-100 text-blue-800',
                                            'checked_out' => 'bg-gray-100 text-gray-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        $status_class = $status_classes[$reservation['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $reservation['status'])); ?>
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($reservation['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Add Guest Modal -->
    <div id="addGuestModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 rounded-t-xl">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-user-plus"></i>
                    Add New Guest
                </h3>
            </div>
            <form method="POST" class="p-6">
                <div class="space-y-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" id="first_name" name="first_name" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="tel" id="phone" name="phone" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="id_number" class="block text-sm font-medium text-gray-700">ID Number (Optional)</label>
                        <input type="text" id="id_number" name="id_number" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeAddGuestModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button type="submit" name="add_guest" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Add Guest
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Guest Modal -->
    <div id="editGuestModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 rounded-t-xl">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-user-edit"></i>
                    Edit Guest
                </h3>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" id="edit_guest_id" name="guest_id">
                <div class="space-y-4">
                    <div>
                        <label for="edit_first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" id="edit_first_name" name="first_name" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="edit_last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" id="edit_last_name" name="last_name" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="edit_email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="edit_email" name="email" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="edit_phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="tel" id="edit_phone" name="phone" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="edit_id_number" class="block text-sm font-medium text-gray-700">ID Number (Optional)</label>
                        <input type="text" id="edit_id_number" name="id_number" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditGuestModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button type="submit" name="edit_guest" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Update Guest
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteGuestModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <div class="bg-gradient-to-r from-red-600 to-pink-600 px-6 py-4 rounded-t-xl">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirm Deletion
                </h3>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" id="delete_guest_id" name="guest_id">
                <p class="text-gray-700 mb-4">Are you sure you want to delete <span id="guest_name_to_delete" class="font-semibold"></span>?</p>
                <p id="reservation_warning" class="text-red-600 mb-4 hidden">This guest has <span id="reservation_count" class="font-semibold"></span> reservations and cannot be deleted.</p>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeDeleteGuestModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button type="submit" id="delete_button" name="delete_guest" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Delete Guest
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Guest Modals Functions
        function openAddGuestModal() {
            document.getElementById('addGuestModal').classList.remove('hidden');
        }

        function closeAddGuestModal() {
            document.getElementById('addGuestModal').classList.add('hidden');
        }

        function openEditGuestModal(guest) {
            document.getElementById('edit_guest_id').value = guest.guest_id;
            document.getElementById('edit_first_name').value = guest.first_name;
            document.getElementById('edit_last_name').value = guest.last_name;
            document.getElementById('edit_email').value = guest.email;
            document.getElementById('edit_phone').value = guest.phone;
            document.getElementById('edit_id_number').value = guest.id_number || '';
            document.getElementById('editGuestModal').classList.remove('hidden');
        }

        function closeEditGuestModal() {
            document.getElementById('editGuestModal').classList.add('hidden');
        }

        function confirmDeleteGuest(guestId, guestName, reservationCount) {
            document.getElementById('delete_guest_id').value = guestId;
            document.getElementById('guest_name_to_delete').textContent = guestName;
            
            const warningElement = document.getElementById('reservation_warning');
            const deleteButton = document.getElementById('delete_button');
            
            if (reservationCount > 0) {
                warningElement.classList.remove('hidden');
                document.getElementById('reservation_count').textContent = reservationCount;
                deleteButton.disabled = true;
                deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                warningElement.classList.add('hidden');
                deleteButton.disabled = false;
                deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            
            document.getElementById('deleteGuestModal').classList.remove('hidden');
        }

        function closeDeleteGuestModal() {
            document.getElementById('deleteGuestModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('addGuestModal')) {
                closeAddGuestModal();
            }
            if (event.target === document.getElementById('editGuestModal')) {
                closeEditGuestModal();
            }
            if (event.target === document.getElementById('deleteGuestModal')) {
                closeDeleteGuestModal();
            }
        });
    </script>
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