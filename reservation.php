<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "hotel_reservation");

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

$message = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['make_reservation'])) {
        // Get form data
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $id_number = mysqli_real_escape_string($conn, $_POST['id_number']);
        $date_of_birth = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
        $room_type = mysqli_real_escape_string($conn, $_POST['room_type']);
        $check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
        $check_out = mysqli_real_escape_string($conn, $_POST['check_out']);
        $adults = mysqli_real_escape_string($conn, $_POST['adults']);
        $children = mysqli_real_escape_string($conn, $_POST['children']);
        $special_requests = mysqli_real_escape_string($conn, $_POST['special_requests']);

        // Check if guest already exists
        $guest_check = "SELECT guest_id FROM guests WHERE email = '$email'";
        $guest_result = mysqli_query($conn, $guest_check);
        
        if (mysqli_num_rows($guest_result) > 0) {
            $guest_row = mysqli_fetch_assoc($guest_result);
            $guest_id = $guest_row['guest_id'];
            
            // Update guest information
            $update_guest = "UPDATE guests SET first_name='$first_name', last_name='$last_name', 
                           phone='$phone', address='$address', id_number='$id_number', 
                           date_of_birth='$date_of_birth' WHERE guest_id='$guest_id'";
            mysqli_query($conn, $update_guest);
        } else {
            // Insert new guest
            $insert_guest = "INSERT INTO guests (first_name, last_name, email, phone, address, id_number, date_of_birth) 
                           VALUES ('$first_name', '$last_name', '$email', '$phone', '$address', '$id_number', '$date_of_birth')";
            
            if (mysqli_query($conn, $insert_guest)) {
                $guest_id = mysqli_insert_id($conn);
            } else {
                $error = "Error creating guest record: " . mysqli_error($conn);
            }
        }

        if (empty($error)) {
            // Find available room of the requested type
            $room_query = "SELECT r.room_id, rt.base_price 
                          FROM rooms r 
                          JOIN room_types rt ON r.type_id = rt.type_id 
                          WHERE r.type_id = '$room_type' AND r.status = 'available' 
                          AND r.room_id NOT IN (
                              SELECT room_id FROM reservations 
                              WHERE status IN ('confirmed', 'checked_in') 
                              AND ((check_in_date <= '$check_out' AND check_out_date >= '$check_in'))
                          ) 
                          LIMIT 1";
            
            $room_result = mysqli_query($conn, $room_query);
            
            if (mysqli_num_rows($room_result) > 0) {
                $room_row = mysqli_fetch_assoc($room_result);
                $room_id = $room_row['room_id'];
                $base_price = $room_row['base_price'];
                
                // Calculate total amount
                $date1 = new DateTime($check_in);
                $date2 = new DateTime($check_out);
                $nights = $date2->diff($date1)->days;
                $total_amount = $nights * $base_price;
                
                // Insert reservation
                $insert_reservation = "INSERT INTO reservations (guest_id, room_id, check_in_date, check_out_date, 
                                     adults, children, total_amount, special_requests, status) 
                                     VALUES ('$guest_id', '$room_id', '$check_in', '$check_out', 
                                     '$adults', '$children', '$total_amount', '$special_requests', 'confirmed')";
                
                if (mysqli_query($conn, $insert_reservation)) {
                    $reservation_id = mysqli_insert_id($conn);
                    
                    // Update room status
                    $update_room = "UPDATE rooms SET status = 'reserved' WHERE room_id = '$room_id'";
                    mysqli_query($conn, $update_room);
                    
                    $message = "Reservation created successfully! Reservation ID: $reservation_id. Total Amount: $" . number_format($total_amount, 2);
                } else {
                    $error = "Error creating reservation: " . mysqli_error($conn);
                }
            } else {
                $error = "No available rooms of the selected type for the chosen dates.";
            }
        }
    }
}

// Get room types for dropdown
$room_types_query = "SELECT * FROM room_types ORDER BY base_price";
$room_types_result = mysqli_query($conn, $room_types_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Reservation - Luxury Haven</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
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
                    <a href="reservation.php" class="text-blue-600 font-semibold border-b-2 border-blue-600 pb-1">Reservations</a>
                    <a href="checkin.php" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Check-in/Out</a>
                    <a href="rooms.php" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Rooms</a>
                    <a href="guests.php" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Guests</a>
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

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-6">
                <h1 class="text-3xl font-bold text-white flex items-center">
                    <i class="fas fa-calendar-check mr-3"></i>
                    Make a Reservation
                </h1>
                <p class="text-blue-100 mt-2">Book your perfect stay with us</p>
            </div>

            <div class="p-8">
                <?php if (!empty($message)): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <span class="text-green-800 font-medium"><?php echo $message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-xl mr-3"></i>
                        <span class="text-red-800 font-medium"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <!-- Guest Information Section -->
                    <div class="border-b border-gray-200 pb-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user mr-2 text-blue-600"></i>
                            Guest Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    First Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="first_name" name="first_name" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>

                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Last Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="last_name" name="last_name" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input type="email" id="email" name="email" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Phone Number <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" id="phone" name="phone" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>

                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                    Address
                                </label>
                                <textarea id="address" name="address" rows="3" 
                                          placeholder="Enter your full address"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
                            </div>

                            <div>
                                <label for="id_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    ID Number
                                </label>
                                <input type="text" id="id_number" name="id_number" 
                                       placeholder="Driver's License, Passport, etc."
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>

                            <div>
                                <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date of Birth
                                </label>
                                <input type="date" id="date_of_birth" name="date_of_birth"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>
                        </div>
                    </div>

                    <!-- Reservation Details Section -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-bed mr-2 text-blue-600"></i>
                            Reservation Details
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="room_type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Room Type <span class="text-red-500">*</span>
                                </label>
                                <select id="room_type" name="room_type" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Select Room Type</option>
                                    <?php while($room_type = mysqli_fetch_assoc($room_types_result)): ?>
                                        <option value="<?php echo $room_type['type_id']; ?>">
                                            <?php echo $room_type['type_name']; ?> - $<?php echo number_format($room_type['base_price'], 2); ?>/night
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div></div> <!-- Empty div for spacing -->

                            <div>
                                <label for="check_in" class="block text-sm font-medium text-gray-700 mb-2">
                                    Check-in Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="check_in" name="check_in" required 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>

                            <div>
                                <label for="check_out" class="block text-sm font-medium text-gray-700 mb-2">
                                    Check-out Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="check_out" name="check_out" required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            </div>

                            <div>
                                <label for="adults" class="block text-sm font-medium text-gray-700 mb-2">
                                    Adults <span class="text-red-500">*</span>
                                </label>
                                <select id="adults" name="adults" required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="1">1 Adult</option>
                                    <option value="2">2 Adults</option>
                                    <option value="3">3 Adults</option>
                                    <option value="4">4 Adults</option>
                                    <option value="5">5 Adults</option>
                                    <option value="6">6 Adults</option>
                                </select>
                            </div>

                            <div>
                                <label for="children" class="block text-sm font-medium text-gray-700 mb-2">
                                    Children
                                </label>
                                <select id="children" name="children"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="0">0 Children</option>
                                    <option value="1">1 Child</option>
                                    <option value="2">2 Children</option>
                                    <option value="3">3 Children</option>
                                    <option value="4">4 Children</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label for="special_requests" class="block text-sm font-medium text-gray-700 mb-2">
                                    Special Requests
                                </label>
                                <textarea id="special_requests" name="special_requests" rows="4"
                                          placeholder="Any special requirements or requests..."
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-200">
                        <button type="submit" name="make_reservation" 
                                class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold py-4 px-6 rounded-lg hover:from-blue-700 hover:to-purple-700 transform hover:scale-105 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-calendar-plus mr-2"></i>
                            Make Reservation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date for check-out based on check-in date
        document.getElementById('check_in').addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            document.getElementById('check_out').min = checkinDate.toISOString().split('T')[0];
        });

        // Auto-calculate and display estimated total
        function calculateTotal() {
            const checkin = document.getElementById('check_in').value;
            const checkout = document.getElementById('check_out').value;
            const roomType = document.getElementById('room_type');
            
            if (checkin && checkout && roomType.value) {
                const date1 = new Date(checkin);
                const date2 = new Date(checkout);
                const nights = Math.ceil((date2 - date1) / (1000 * 60 * 60 * 24));
                
                const selectedOption = roomType.options[roomType.selectedIndex];
                const priceText = selectedOption.text.match(/\$(\d+\.?\d*)/);
                
                if (priceText && nights > 0) {
                    const pricePerNight = parseFloat(priceText[1]);
                    const total = nights * pricePerNight;
                    
                    // Display estimated total (you can add this to your form if needed)
                    console.log(`Estimated total: $${total.toFixed(2)} for ${nights} nights`);
                }
            }
        }

        document.getElementById('check_in').addEventListener('change', calculateTotal);
        document.getElementById('check_out').addEventListener('change', calculateTotal);
        document.getElementById('room_type').addEventListener('change', calculateTotal);

        // Mobile menu toggle (if you want to add mobile menu functionality)
        const mobileMenuButton = document.querySelector('.md\\:hidden button');
        if (mobileMenuButton) {
            mobileMenuButton.addEventListener('click', function() {
                // Add mobile menu toggle functionality here
                console.log('Mobile menu clicked');
            });
        }
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>