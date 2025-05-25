<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "hotel_reservation");

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

$message = "";
$error = "";

// Handle payment processing
if(isset($_POST['process_payment'])) {
    $reservation_id = $_POST['reservation_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    
    // Calculate components for the bill (simplified for example)
    $room_charges = $amount * 0.8; // 80% of total as room charges
    $tax_amount = $amount * 0.1;   // 10% as tax
    $service_charges = $amount * 0.05; // 5% as service charges
    $total_amount = $room_charges + $tax_amount + $service_charges;
    
    // Insert payment record into billing table
    $payment_query = "INSERT INTO billing (
                        reservation_id, 
                        room_charges, 
                        tax_amount, 
                        service_charges, 
                        total_amount, 
                        payment_status, 
                        payment_method, 
                        payment_date
                      ) VALUES (?, ?, ?, ?, ?, 'paid', ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $payment_query);
    mysqli_stmt_bind_param($stmt, "iddddss", 
        $reservation_id,
        $room_charges,
        $tax_amount,
        $service_charges,
        $total_amount,
        $payment_method
    );
    
    if(mysqli_stmt_execute($stmt)) {
        // Update reservation status to confirmed (since we have a separate checked_in status)
        $update_query = "UPDATE reservations SET status = 'confirmed' WHERE reservation_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $reservation_id);
        mysqli_stmt_execute($stmt);
        
        $message = "Payment processed successfully!";
    } else {
        $error = "Error processing payment: " . mysqli_error($conn);
    }
}

// Handle bill generation
if(isset($_POST['generate_bill'])) {
    $reservation_id = $_POST['reservation_id'];
    
    // Get reservation details for bill calculation
    $bill_query = "SELECT 
                      r.*, 
                      rt.type_name, 
                      rt.base_price, 
                      g.first_name, 
                      g.last_name, 
                      g.email,
                      DATEDIFF(r.check_out_date, r.check_in_date) as nights,
                      rm.room_number
                   FROM reservations r
                   JOIN rooms rm ON r.room_id = rm.room_id
                   JOIN room_types rt ON rm.type_id = rt.type_id
                   JOIN guests g ON r.guest_id = g.guest_id
                   WHERE r.reservation_id = ?";
    
    $stmt = mysqli_prepare($conn, $bill_query);
    mysqli_stmt_bind_param($stmt, "i", $reservation_id);
    mysqli_stmt_execute($stmt);
    $bill_result = mysqli_stmt_get_result($stmt);
    $bill_data = mysqli_fetch_assoc($bill_result);
}

// Get pending payments (reservations that are confirmed or checked in)
$pending_query = "SELECT 
                     r.reservation_id, 
                     r.check_in_date, 
                     r.check_out_date, 
                     r.total_amount, 
                     r.status,
                     g.first_name, 
                     g.last_name, 
                     rt.type_name, 
                     rm.room_number,
                     DATEDIFF(r.check_out_date, r.check_in_date) as nights
                  FROM reservations r
                  JOIN guests g ON r.guest_id = g.guest_id
                  JOIN rooms rm ON r.room_id = rm.room_id
                  JOIN room_types rt ON rm.type_id = rt.type_id
                  WHERE r.status IN ('confirmed', 'checked_in')
                  ORDER BY r.check_in_date DESC";
$pending_result = mysqli_query($conn, $pending_query);

// Get recent payments from billing table
$payments_query = "SELECT 
                      b.*, 
                      r.reservation_id, 
                      g.first_name, 
                      g.last_name, 
                      rm.room_number
                   FROM billing b
                   JOIN reservations r ON b.reservation_id = r.reservation_id
                   JOIN guests g ON r.guest_id = g.guest_id
                   JOIN rooms rm ON r.room_id = rm.room_id
                   ORDER BY b.payment_date DESC
                   LIMIT 10";
$payments_result = mysqli_query($conn, $payments_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Head content remains the same -->
    <!-- ... -->
</head>
<body>
    <div class="management-container">
        <!-- Container content remains the same until the stats calculation -->
        
        <?php
        // Calculate financial statistics - updated to match schema
        $stats_query = "SELECT 
                            COUNT(CASE WHEN r.status IN ('confirmed', 'checked_in') THEN 1 END) as pending_payments,
                            COUNT(CASE WHEN b.payment_status = 'paid' THEN 1 END) as completed_payments,
                            COALESCE(SUM(CASE WHEN r.status IN ('confirmed', 'checked_in') THEN r.total_amount END), 0) as pending_amount,
                            COALESCE(SUM(CASE WHEN b.payment_status = 'paid' THEN b.total_amount END), 0) as total_revenue
                        FROM reservations r
                        LEFT JOIN billing b ON r.reservation_id = b.reservation_id";
        $stats_result = mysqli_query($conn, $stats_query);
        $stats = mysqli_fetch_assoc($stats_result);
        ?>

        <!-- Rest of the HTML remains the same, just ensure field names match -->
        <!-- For example, in the pending payments section: -->
        <?php while($reservation = mysqli_fetch_assoc($pending_result)): ?>
        <div class="reservation-card">
            <div class="bill-header">
                <div class="guest-name">
                    <?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?>
                </div>
                <div class="amount">$<?php echo number_format($reservation['total_amount'], 2); ?></div>
            </div>
            
            <div class="bill-details">
                <div><i class="fas fa-bed"></i> Room: <?php echo $reservation['room_number']; ?> (<?php echo $reservation['type_name']; ?>)</div>
                <div><i class="fas fa-calendar"></i> Nights: <?php echo $reservation['nights']; ?></div>
                <div><i class="fas fa-sign-in-alt"></i> Check-in: <?php echo date('M j, Y', strtotime($reservation['check_in_date'])); ?></div>
                <div><i class="fas fa-sign-out-alt"></i> Check-out: <?php echo date('M j, Y', strtotime($reservation['check_out_date'])); ?></div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="status-badge status-<?php echo $reservation['status'] === 'confirmed' ? 'pending' : 'checked_in'; ?>">
                    <?php echo ucfirst($reservation['status']); ?>
                </span>
                <div>
                    <button class="btn btn-primary btn-small" onclick="openPaymentModal(<?php echo $reservation['reservation_id']; ?>, <?php echo $reservation['total_amount']; ?>, '<?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?>')">
                        <i class="fas fa-credit-card"></i> Process Payment
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>

        <!-- In the recent payments section: -->
        <?php while($payment = mysqli_fetch_assoc($payments_result)): ?>
        <div class="payment-card">
            <div class="bill-header">
                <div class="guest-name">
                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                </div>
                <div class="amount">$<?php echo number_format($payment['total_amount'], 2); ?></div>
            </div>
            
            <div class="bill-details">
                <div><i class="fas fa-bed"></i> Room: <?php echo $payment['room_number']; ?></div>
                <div><i class="fas fa-credit-card"></i> Method: <?php echo ucfirst($payment['payment_method']); ?></div>
                <div><i class="fas fa-calendar"></i> Date: <?php echo date('M j, Y H:i', strtotime($payment['payment_date'])); ?></div>
                <div><i class="fas fa-hashtag"></i> Bill ID: #<?php echo $payment['bill_id']; ?></div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                    <?php echo ucfirst($payment['payment_status']); ?>
                </span>
                <button class="btn btn-primary btn-small" onclick="printReceipt(<?php echo $payment['bill_id']; ?>)">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
        <?php endwhile; ?>

        <!-- The rest of the file (modal, scripts) remains the same -->
        <!-- ... -->
    </div>

    <script>
        // Script content remains the same
        // ...
    </script>
</body>
</html>