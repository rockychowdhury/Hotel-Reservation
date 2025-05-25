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

// Get recent check-in/out history
$history_query = "SELECT r.reservation_id, r.check_in_date, r.check_out_date, r.status,
                 g.first_name, g.last_name, rt.type_name, rm.room_number,
                 cc.actual_checkin, cc.actual_checkout, cc.early_checkin_fee, 
                 cc.late_checkout_fee, cc.damage_charges
                 FROM reservations r
                 JOIN guests g ON r.guest_id = g.guest_id
                 JOIN rooms rm ON r.room_id = rm.room_id
                 JOIN room_types rt ON rm.type_id = rt.type_id
                 LEFT JOIN checkin_checkout cc ON r.reservation_id = cc.reservation_id
                 WHERE r.status IN ('checked_in', 'checked_out')
                 ORDER BY cc.actual_checkin DESC LIMIT 20";
$history_result = mysqli_query($conn, $history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in/Check-out - Luxury Haven</title>
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
                <li><a href="index.php">Home</a></li>
                <li><a href="reservation.php">Reservations</a></li>
                <li><a href="checkin.php">Check-in/Out</a></li>
                <li><a href="rooms.php">Rooms</a></li>
                <li><a href="guests.php">Guests</a></li>
                <li><a href="billing.php">Billing</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- Success/Error Messages -->
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

        <!-- Check-in Section -->
        <div class="form-container">
            <h1 class="form-title">
                <i class="fas fa-door-open"></i>
                Guest Check-in
            </h1>

            <?php if (mysqli_num_rows($checkin_result) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Guest</th>
                            <th>Contact</th>
                            <th>Room</th>
                            <th>Check-in Date</th>
                            <th>Guests</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($checkin = mysqli_fetch_assoc($checkin_result)): ?>
                            <tr>
                                <td><?php echo $checkin['reservation_id']; ?></td>
                                <td><?php echo $checkin['first_name'] . ' ' . $checkin['last_name']; ?></td>
                                <td>
                                    <?php echo $checkin['phone']; ?><br>
                                    <small><?php echo $checkin['email']; ?></small>
                                </td>
                                <td><?php echo $checkin['type_name'] . ' (' . $checkin['room_number'] . ')'; ?></td>
                                <td><?php echo date('M d, Y', strtotime($checkin['check_in_date'])); ?></td>
                                <td><?php echo $checkin['adults'] . ' Adults, ' . $checkin['children'] . ' Children'; ?></td>
                                <td>
                                    <button class="btn-small btn-view" onclick="showCheckinModal(<?php echo $checkin['reservation_id']; ?>, '<?php echo $checkin['first_name'] . ' ' . $checkin['last_name']; ?>', '<?php echo $checkin['room_number']; ?>')">
                                        Check-in
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; margin: 2rem 0;">No reservations ready for check-in.</p>
            <?php endif; ?>
        </div>

        <!-- Check-out Section -->
        <div class="form-container">
            <h1 class="form-title">
                <i class="fas fa-door-closed"></i>
                Guest Check-out
            </h1>

            <?php 
            // Reset result pointer for checkout
            $checkout_result = mysqli_query($conn, $checkout_query);
            if (mysqli_num_rows($checkout_result) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Checked-in</th>
                            <th>Check-out Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($checkout = mysqli_fetch_assoc($checkout_result)): ?>
                            <tr>
                                <td><?php echo $checkout['reservation_id']; ?></td>
                                <td><?php echo $checkout['first_name'] . ' ' . $checkout['last_name']; ?></td>
                                <td><?php echo $checkout['type_name'] . ' (' . $checkout['room_number'] . ')'; ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($checkout['actual_checkin'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($checkout['check_out_date'])); ?></td>
                                <td>
                                    <button class="btn-small btn-edit" onclick="showCheckoutModal(<?php echo $checkout['reservation_id']; ?>, '<?php echo $checkout['first_name'] . ' ' . $checkout['last_name']; ?>', '<?php echo $checkout['room_number']; ?>')">
                                        Check-out
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; margin: 2rem 0;">No guests currently checked in.</p>
            <?php endif; ?>
        </div>

        <!-- Check-in/out History -->
        <div class="form-container">
            <h2 class="form-title">
                <i class="fas fa-history"></i>
                Recent Check-in/out History
            </h2>

            <?php if (mysqli_num_rows($history_result) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Additional Fees</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($history = mysqli_fetch_assoc($history_result)): ?>
                            <tr>
                                <td><?php echo $history['reservation_id']; ?></td>
                                <td><?php echo $history['first_name'] . ' ' . $history['last_name']; ?></td>
                                <td><?php echo $history['type_name'] . ' (' . $history['room_number'] . ')'; ?></td>
                                <td>
                                    <?php echo $history['actual_checkin'] ? date('M d, H:i', strtotime($history['actual_checkin'])) : '-'; ?>
                                </td>
                                <td>
                                    <?php echo $history['actual_checkout'] ? date('M d, H:i', strtotime($history['actual_checkout'])) : '-'; ?>
                                </td>
                                <td>
                                    <?php 
                                    $total_fees = ($history['early_checkin_fee'] ?? 0) + ($history['late_checkout_fee'] ?? 0) + ($history['damage_charges'] ?? 0);
                                    echo $total_fees > 0 ? '$' . number_format($total_fees, 2) : '-';
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $history['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $history['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666; margin: 2rem 0;">No check-in/out history found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Check-in Modal -->
    <div id="checkinModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('checkinModal')">&times;</span>
            <h3>Guest Check-in</h3>
            <form method="POST" action="">
                <input type="hidden" id="checkin_reservation_id" name="reservation_id" value="">
                <div id="checkin_guest_info"></div>
                
                <div class="form-group">
                    <label for="early_checkin_fee">Early Check-in Fee ($)</label>
                    <input type="number" id="early_checkin_fee" name="early_checkin_fee" step="0.01" min="0" value="0">
                </div>
                
                <div class="form-group">
                    <label for="checkin_notes">Notes</label>
                    <textarea id="checkin_notes" name="checkin_notes" placeholder="Any special notes for check-in..."></textarea>
                </div>
                
                <button type="submit" name="checkin" class="btn btn-primary">
                    <i class="fas fa-door-open"></i> Complete Check-in
                </button>
            </form>
        </div>
    </div>

    <!-- Check-out Modal -->
    <div id="checkoutModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('checkoutModal')">&times;</span>
            <h3>Guest Check-out</h3>
            <form method="POST" action="">
                <input type="hidden" id="checkout_reservation_id" name="checkout_reservation_id" value="">
                <div id="checkout_guest_info"></div>
                
                <div class="form-group">
                    <label for="late_checkout_fee">Late Check-out Fee ($)</label>
                    <input type="number" id="late_checkout_fee" name="late_checkout_fee" step="0.01" min="0" value="0">
                </div>
                
                <div class="form-group">
                    <label for="damage_charges">Damage Charges ($)</label>
                    <input type="number" id="damage_charges" name="damage_charges" step="0.01" min="0" value="0">
                </div>
                
                <div class="form-group">
                    <label for="checkout_notes">Notes</label>
                    <textarea id="checkout_notes" name="checkout_notes" placeholder="Any special notes for check-out..."></textarea>
                </div>
                
                <button type="submit" name="checkout" class="btn btn-primary">
                    <i class="fas fa-door-closed"></i> Complete Check-out
                </button>
            </form>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 10px;
        }

        .close:hover {
            color: #e74c3c;
        }

        .guest-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .status-checked_in { background: #d1ecf1; color: #0c5460; }
        .status-checked_out { background: #d4edda; color: #155724; }
    </style>

    <script>
        function showCheckinModal(reservationId, guestName, roomNumber) {
            document.getElementById('checkin_reservation_id').value = reservationId;
            document.getElementById('checkin_guest_info').innerHTML = `
                <div class="guest-info">
                    <h4>Guest: ${guestName}</h4>
                    <p>Room: ${roomNumber}</p>
                    <p>Check-in Time: ${new Date().toLocaleString()}</p>
                </div>
            `;
            document.getElementById('checkinModal').style.display = 'block';
        }

        function showCheckoutModal(reservationId, guestName, roomNumber) {
            document.getElementById('checkout_reservation_id').value = reservationId;
            document.getElementById('checkout_guest_info').innerHTML = `
                <div class="guest-info">
                    <h4>Guest: ${guestName}</h4>
                    <p>Room: ${roomNumber}</p>
                    <p>Check-out Time: ${new Date().toLocaleString()}</p>
                </div>
            `;
            document.getElementById('checkoutModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>