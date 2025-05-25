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

// Get recent reservations
$recent_reservations = "SELECT r.reservation_id, r.check_in_date, r.check_out_date, r.total_amount, r.status,
                       g.first_name, g.last_name, g.email, rt.type_name, rm.room_number
                       FROM reservations r
                       JOIN guests g ON r.guest_id = g.guest_id
                       JOIN rooms rm ON r.room_id = rm.room_id
                       JOIN room_types rt ON rm.type_id = rt.type_id
                       ORDER BY r.created_at DESC LIMIT 10";
$reservations_result = mysqli_query($conn, $recent_reservations);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Reservation - Luxury Haven</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                <li><a href="index.html">Home</a></li>
                <li><a href="reservation.php">Reservations</a></li>
                <li><a href="checkin.php">Check-in/Out</a></li>
                <li><a href="rooms.php">Rooms</a></li>
                <li><a href="guests.php">Guests</a></li>
                <li><a href="billing.php">Billing</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <h1 class="form-title">
                <i class="fas fa-calendar-check"></i>
                Make a Reservation
            </h1>

            <?php if (!empty($message)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>

                    <div class="form-group form-group-full">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" placeholder="Enter your full address"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="id_number">ID Number</label>
                        <input type="text" id="id_number" name="id_number" placeholder="Driver's License, Passport, etc.">
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth">
                    </div>

                    <div class="form-group">
                        <label for="room_type">Room Type *</label>
                        <select id="room_type" name="room_type" required>
                            <option value="">Select Room Type</option>
                            <?php while($room_type = mysqli_fetch_assoc($room_types_result)): ?>
                                <option value="<?php echo $room_type['type_id']; ?>">
                                    <?php echo $room_type['type_name']; ?> - $<?php echo number_format($room_type['base_price'], 2); ?>/night
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="check_in">Check-in Date *</label>
                        <input type="date" id="check_in" name="check_in" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="check_out">Check-out Date *</label>
                        <input type="date" id="check_out" name="check_out" required>
                    </div>

                    <div class="form-group">
                        <label for="adults">Adults *</label>
                        <select id="adults" name="adults" required>
                            <option value="1">1 Adult</option>
                            <option value="2">2 Adults</option>
                            <option value="3">3 Adults</option>
                            <option value="4">4 Adults</option>
                            <option value="5">5 Adults</option>
                            <option value="6">6 Adults</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="children">Children</label>
                        <select id="children" name="children">
                            <option value="0">0 Children</option>
                            <option value="1">1 Child</option>
                            <option value="2">2 Children</option>
                            <option value="3">3 Children</option>
                            <option value="4">4 Children</option>
                        </select>
                    </div>

                    <div class="form-group form-group-full">
                        <label for="special_requests">Special Requests</label>
                        <textarea id="special_requests" name="special_requests" placeholder="Any special requirements or requests..."></textarea>
                    </div>
                </div>

                <button type="submit" name="make_reservation" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i> Make Reservation
                </button>
            </form>
        </div>

        <!-- Recent Reservations -->
        <div class="form-container">
            <h2 class="form-title">
                <i class="fas fa-list"></i>
                Recent Reservations
            </h2>

            <?php if (mysqli_num_rows($reservations_result) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Guest</th>
                            <th>Email</th>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($reservation = mysqli_fetch_assoc($reservations_result)): ?>
                            <tr>
                                <td><?php echo $reservation['reservation_id']; ?></td>
                                <td><?php echo $reservation['first_name'] . ' ' . $reservation['last_name']; ?></td>
                                <td><?php echo $reservation['email']; ?></td>
                                <td><?php echo $reservation['type_name'] . ' (' . $reservation['room_number'] . ')'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($reservation['check_in_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($reservation['check_out_date'])); ?></td>
                                <td>$<?php echo number_format($reservation['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $reservation['status']; ?>">
                                        <?php echo ucfirst($reservation['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; margin: 2rem 0;">No reservations found.</p>
            <?php endif; ?>
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
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>