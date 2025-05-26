<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "hotel_reservation");

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

$message = "";
$error = "";

// Handle Check-in
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkin'])) {
    $reservation_id = mysqli_real_escape_string($conn, $_POST['reservation_id']);
    $actual_checkin = date('Y-m-d H:i:s');
    $early_checkin_fee = floatval($_POST['early_checkin_fee']);
    $notes = mysqli_real_escape_string($conn, $_POST['checkin_notes']);
    
    // Check if reservation exists and is confirmed
    $check_reservation = "SELECT r.*, rt.type_name, rm.room_number 
                         FROM reservations r 
                         JOIN rooms rm ON r.room_id = rm.room_id 
                         JOIN room_types rt ON rm.type_id = rt.type_id 
                         WHERE r.reservation_id = '$reservation_id' AND r.status = 'confirmed'";
    $result = mysqli_query($conn, $check_reservation);
    
    if (mysqli_num_rows($result) > 0) {
        $reservation = mysqli_fetch_assoc($result);
        
        // Insert check-in record
        $insert_checkin = "INSERT INTO checkin_checkout (reservation_id, actual_checkin, early_checkin_fee, notes) 
                          VALUES ('$reservation_id', '$actual_checkin', '$early_checkin_fee', '$notes')";
        
        if (mysqli_query($conn, $insert_checkin)) {
            // Update reservation status
            $update_reservation = "UPDATE reservations SET status = 'checked_in' WHERE reservation_id = '$reservation_id'";
            mysqli_query($conn, $update_reservation);
            
            // Update room status
            $update_room = "UPDATE rooms SET status = 'occupied' WHERE room_id = '{$reservation['room_id']}'";
            mysqli_query($conn, $update_room);
            
            // Update billing if early check-in fee
            if ($early_checkin_fee > 0) {
                $check_billing = "SELECT bill_id FROM billing WHERE reservation_id = '$reservation_id'";
                $billing_result = mysqli_query($conn, $check_billing);
                
                if (mysqli_num_rows($billing_result) > 0) {
                    $bill = mysqli_fetch_assoc($billing_result);
                    $update_billing = "UPDATE billing SET 
                                     additional_charges = additional_charges + $early_checkin_fee,
                                     total_amount = room_charges + tax_amount + service_charges + additional_charges - discount_amount
                                     WHERE bill_id = '{$bill['bill_id']}'";
                    mysqli_query($conn, $update_billing);
                } else {
                    $total_with_fee = $reservation['total_amount'] + $early_checkin_fee;
                    $insert_billing = "INSERT INTO billing (reservation_id, room_charges, additional_charges, total_amount) 
                                     VALUES ('$reservation_id', '{$reservation['total_amount']}', '$early_checkin_fee', '$total_with_fee')";
                    mysqli_query($conn, $insert_billing);
                }
            }
            
            $message = "Check-in successful for Reservation ID: $reservation_id";
        } else {
            $error = "Error during check-in: " . mysqli_error($conn);
        }
    } else {
        $error = "Reservation not found or not confirmed.";
    }
}

// Handle Check-out
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    $reservation_id = mysqli_real_escape_string($conn, $_POST['checkout_reservation_id']);
    $actual_checkout = date('Y-m-d H:i:s');
    $late_checkout_fee = floatval($_POST['late_checkout_fee']);
    $damage_charges = floatval($_POST['damage_charges']);
    $notes = mysqli_real_escape_string($conn, $_POST['checkout_notes']);
    
    // Check if reservation exists and is checked in
    $check_reservation = "SELECT r.*, rt.type_name, rm.room_number 
                         FROM reservations r 
                         JOIN rooms rm ON r.room_id = rm.room_id 
                         JOIN room_types rt ON rm.type_id = rt.type_id 
                         WHERE r.reservation_id = '$reservation_id' AND r.status = 'checked_in'";
    $result = mysqli_query($conn, $check_reservation);
    
    if (mysqli_num_rows($result) > 0) {
        $reservation = mysqli_fetch_assoc($result);
        
        // Update check-in/checkout record
        $update_checkout = "UPDATE checkin_checkout SET 
                           actual_checkout = '$actual_checkout',
                           late_checkout_fee = '$late_checkout_fee',
                           damage_charges = '$damage_charges',
                           notes = CONCAT(IFNULL(notes, ''), ' | Checkout: $notes')
                           WHERE reservation_id = '$reservation_id'";
        
        if (mysqli_query($conn, $update_checkout)) {
            // Update reservation status
            $update_reservation = "UPDATE reservations SET status = 'checked_out' WHERE reservation_id = '$reservation_id'";
            mysqli_query($conn, $update_reservation);
            
            // Update room status
            $update_room = "UPDATE rooms SET status = 'available' WHERE room_id = '{$reservation['room_id']}'";
            mysqli_query($conn, $update_room);
            
            // Update billing with additional charges
            if ($late_checkout_fee > 0 || $damage_charges > 0) {
                $additional_total = $late_checkout_fee + $damage_charges;
                $check_billing = "SELECT bill_id FROM billing WHERE reservation_id = '$reservation_id'";
                $billing_result = mysqli_query($conn, $check_billing);
                
                if (mysqli_num_rows($billing_result) > 0) {
                    $update_billing = "UPDATE billing SET 
                                     additional_charges = additional_charges + $additional_total,
                                     total_amount = room_charges + tax_amount + service_charges + additional_charges - discount_amount
                                     WHERE reservation_id = '$reservation_id'";
                    mysqli_query($conn, $update_billing);
                } else {
                    $total_with_charges = $reservation['total_amount'] + $additional_total;
                    $insert_billing = "INSERT INTO billing (reservation_id, room_charges, additional_charges, total_amount) 
                                     VALUES ('$reservation_id', '{$reservation['total_amount']}', '$additional_total', '$total_with_charges')";
                    mysqli_query($conn, $insert_billing);
                }
            }
            
            $message = "Check-out successful for Reservation ID: $reservation_id";
        } else {
            $error = "Error during check-out: " . mysqli_error($conn);
        }
    } else {
        $error = "Reservation not found or not checked in.";
    }
}

// Get reservations for check-in (confirmed status)
$checkin_query = "SELECT r.reservation_id, r.check_in_date, r.check_out_date, r.adults, r.children, r.total_amount,
                 g.first_name, g.last_name, g.phone, g.email,
                 rt.type_name, rm.room_number
                 FROM reservations r
                 JOIN guests g ON r.guest_id = g.guest_id
                 JOIN rooms rm ON r.room_id = rm.room_id
                 JOIN room_types rt ON rm.type_id = rt.type_id
                 WHERE r.status = 'confirmed'
                 ORDER BY r.check_in_date ASC";
$checkin_result = mysqli_query($conn, $checkin_query);

// Get reservations for check-out (checked_in status)
$checkout_query = "SELECT r.reservation_id, r.check_in_date, r.check_out_date, r.adults, r.children, r.total_amount,
                  g.first_name, g.last_name, g.phone, g.email,
                  rt.type_name, rm.room_number, cc.actual_checkin
                  FROM reservations r
                  JOIN guests g ON r.guest_id = g.guest_id
                  JOIN rooms rm ON r.room_id = rm.room_id
                  JOIN room_types rt ON rm.type_id = rt.type_id
                  JOIN checkin_checkout cc ON r.reservation_id = cc.reservation_id
                  WHERE r.status = 'checked_in'
                  ORDER BY r.check_out_date ASC";
$checkout_result = mysqli_query($conn, $checkout_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in/Check-out - Luxury Haven</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-poppins min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-hotel text-2xl text-blue-600"></i>
                    <span class="text-xl font-bold text-gray-800">Luxury Haven</span>
                </div>
                <div class="hidden md:flex space-x-8">
                    <a href="index.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Home</a>
                    <a href="reservation.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Reservations</a>
                    <a href="checkin.php" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2 text-sm font-medium">Check-in/Out</a>
                    <a href="rooms.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Rooms</a>
                    <a href="guests.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Guests</a>
                    <a href="billing.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors">Billing</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center">
                <i class="fas fa-check-circle text-green-600 mr-3"></i>
                <span class="text-green-800"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-center">
                <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                <span class="text-red-800"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Check-in Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-door-open text-green-600 mr-3"></i>
                    Guest Check-in
                </h1>
            </div>

            <div class="p-6">
                <?php if (mysqli_num_rows($checkin_result) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservation ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guest</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-in Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guests</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while($checkin = mysqli_fetch_assoc($checkin_result)): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $checkin['reservation_id']; ?></td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $checkin['first_name'] . ' ' . $checkin['last_name']; ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $checkin['phone']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $checkin['email']; ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $checkin['type_name']; ?></div>
                                            <div class="text-sm text-gray-500">Room <?php echo $checkin['room_number']; ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M d, Y', strtotime($checkin['check_in_date'])); ?></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $checkin['adults'] . ' Adults, ' . $checkin['children'] . ' Children'; ?></td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <button onclick="showCheckinModal(<?php echo $checkin['reservation_id']; ?>, '<?php echo $checkin['first_name'] . ' ' . $checkin['last_name']; ?>', '<?php echo $checkin['room_number']; ?>')" 
                                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                                                <i class="fas fa-door-open"></i>
                                                <span>Check-in</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-calendar-check text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg">No reservations ready for check-in.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Check-out Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-door-closed text-blue-600 mr-3"></i>
                    Guest Check-out
                </h1>
            </div>

            <div class="p-6">
                <?php 
                // Reset result pointer for checkout
                $checkout_result = mysqli_query($conn, $checkout_query);
                if (mysqli_num_rows($checkout_result) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservation ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guest</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Checked-in</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check-out Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while($checkout = mysqli_fetch_assoc($checkout_result)): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $checkout['reservation_id']; ?></td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $checkout['first_name'] . ' ' . $checkout['last_name']; ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $checkout['type_name']; ?></div>
                                            <div class="text-sm text-gray-500">Room <?php echo $checkout['room_number']; ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M d, Y H:i', strtotime($checkout['actual_checkin'])); ?></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M d, Y', strtotime($checkout['check_out_date'])); ?></td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <button onclick="showCheckoutModal(<?php echo $checkout['reservation_id']; ?>, '<?php echo $checkout['first_name'] . ' ' . $checkout['last_name']; ?>', '<?php echo $checkout['room_number']; ?>')" 
                                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center space-x-2">
                                                <i class="fas fa-door-closed"></i>
                                                <span>Check-out</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-bed text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg">No guests currently checked in.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Check-in Modal -->
    <div id="checkinModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-screen overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Guest Check-in</h3>
                <button onclick="closeModal('checkinModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="" class="p-6">
                <input type="hidden" id="checkin_reservation_id" name="reservation_id" value="">
                <div id="checkin_guest_info" class="mb-6"></div>
                
                <div class="mb-4">
                    <label for="early_checkin_fee" class="block text-sm font-medium text-gray-700 mb-2">Early Check-in Fee ($)</label>
                    <input type="number" id="early_checkin_fee" name="early_checkin_fee" step="0.01" min="0" value="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                </div>
                
                <div class="mb-6">
                    <label for="checkin_notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea id="checkin_notes" name="checkin_notes" rows="3" placeholder="Any special notes for check-in..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors"></textarea>
                </div>
                
                <button type="submit" name="checkin" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2">
                    <i class="fas fa-door-open"></i>
                    <span>Complete Check-in</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Check-out Modal -->
    <div id="checkoutModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-screen overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Guest Check-out</h3>
                <button onclick="closeModal('checkoutModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" action="" class="p-6">
                <input type="hidden" id="checkout_reservation_id" name="checkout_reservation_id" value="">
                <div id="checkout_guest_info" class="mb-6"></div>
                
                <div class="mb-4">
                    <label for="late_checkout_fee" class="block text-sm font-medium text-gray-700 mb-2">Late Check-out Fee ($)</label>
                    <input type="number" id="late_checkout_fee" name="late_checkout_fee" step="0.01" min="0" value="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>
                
                <div class="mb-4">
                    <label for="damage_charges" class="block text-sm font-medium text-gray-700 mb-2">Damage Charges ($)</label>
                    <input type="number" id="damage_charges" name="damage_charges" step="0.01" min="0" value="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                </div>
                
                <div class="mb-6">
                    <label for="checkout_notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea id="checkout_notes" name="checkout_notes" rows="3" placeholder="Any special notes for check-out..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
                </div>
                
                <button type="submit" name="checkout" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2">
                    <i class="fas fa-door-closed"></i>
                    <span>Complete Check-out</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function showCheckinModal(reservationId, guestName, roomNumber) {
            document.getElementById('checkin_reservation_id').value = reservationId;
            document.getElementById('checkin_guest_info').innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Guest: ${guestName}</h4>
                    <p class="text-sm text-gray-600 mb-1">Room: ${roomNumber}</p>
                    <p class="text-sm text-gray-600">Check-in Time: ${new Date().toLocaleString()}</p>
                </div>
            `;
            document.getElementById('checkinModal').classList.remove('hidden');
        }

        function showCheckoutModal(reservationId, guestName, roomNumber) {
            document.getElementById('checkout_reservation_id').value = reservationId;
            document.getElementById('checkout_guest_info').innerHTML = `
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Guest: ${guestName}</h4>
                    <p class="text-sm text-gray-600 mb-1">Room: ${roomNumber}</p>
                    <p class="text-sm text-gray-600">Check-out Time: ${new Date().toLocaleString()}</p>
                </div>
            `;
            document.getElementById('checkoutModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('bg-black')) {
                event.target.classList.add('hidden');
            }
        }
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>