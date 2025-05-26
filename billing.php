<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "hotel_reservation");

// Check connection
if($conn === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

$message = "";
$error = "";

// Handle payment status update (mark as paid)
if(isset($_POST['mark_paid'])) {
    $bill_id = $_POST['bill_id'];
    $payment_method = $_POST['payment_method'];
    
    // Update payment status in billing table
    $update_query = "UPDATE billing SET 
                     payment_status = 'paid', 
                     payment_method = ?, 
                     payment_date = NOW() 
                     WHERE bill_id = ?";
    
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $payment_method, $bill_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $message = "Payment marked as paid successfully!";
    } else {
        $error = "Error updating payment: " . mysqli_error($conn);
    }
}

// Get all billing records with reservation and guest details
$billing_query = "SELECT 
                     b.*, 
                     r.reservation_id,
                     r.check_in_date,
                     r.check_out_date,
                     g.first_name, 
                     g.last_name,
                     g.email,
                     rm.room_number,
                     rt.type_name,
                     DATEDIFF(r.check_out_date, r.check_in_date) as nights
                  FROM billing b
                  JOIN reservations r ON b.reservation_id = r.reservation_id
                  JOIN guests g ON r.guest_id = g.guest_id
                  JOIN rooms rm ON r.room_id = rm.room_id
                  JOIN room_types rt ON rm.type_id = rt.type_id
                  ORDER BY b.created_at DESC";
$billing_result = mysqli_query($conn, $billing_query);

// Calculate financial statistics
$stats_query = "SELECT 
                    COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_payments,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as completed_payments,
                    COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN total_amount END), 0) as pending_amount,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount END), 0) as total_revenue
                FROM billing";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Payment Management - Luxury Haven Hotel</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .billing-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .billing-header {
            text-align: center;
            margin-bottom: 3rem;
            color: white;
        }

        .billing-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .billing-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #667eea;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }

        .billing-table-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            overflow-x: auto;
        }

        .section-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .tab-button {
            padding: 1rem 2rem;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-button:hover {
            color: #667eea;
        }

        .billing-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .billing-table th,
        .billing-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .billing-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .billing-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-partial {
            background: #ffeaa7;
            color: #6c5ce7;
        }

        .status-refunded {
            background: #fab1a0;
            color: #e17055;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .close {
            color: #aaa;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #667eea;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @media (max-width: 768px) {
            .billing-table {
                font-size: 0.85rem;
            }
            
            .billing-table th,
            .billing-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="billing-container">
        <a href="index.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="billing-header">
            <h1><i class="fas fa-receipt"></i> Billing & Payment Management</h1>
            <p>Manage hotel billing records and payment status</p>
        </div>

        <?php if($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Financial Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $stats['pending_payments']; ?></div>
                <div class="stat-label">Pending Payments</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['completed_payments']; ?></div>
                <div class="stat-label">Completed Payments</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['pending_amount'], 0); ?></div>
                <div class="stat-label">Pending Amount</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Billing Table -->
        <div class="billing-table-container">
            <div class="section-tabs">
                <button class="tab-button active" onclick="showTab('all')">All Bills</button>
                <button class="tab-button" onclick="showTab('pending')">Pending Payments</button>
                <button class="tab-button" onclick="showTab('paid')">Paid Bills</button>
            </div>

            <!-- All Bills Tab -->
            <div id="all-tab" class="tab-content active">
                <?php if(mysqli_num_rows($billing_result) > 0): ?>
                    <table class="billing-table">
                        <thead>
                            <tr>
                                <th>Bill ID</th>
                                <th>Guest Name</th>
                                <th>Room</th>
                                <th>Check-in</th>
                                <th>Nights</th>
                                <th>Room Charges</th>
                                <th>Tax</th>
                                <th>Service</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th>Payment Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Reset the result pointer
                            mysqli_data_seek($billing_result, 0);
                            while($bill = mysqli_fetch_assoc($billing_result)): 
                            ?>
                            <tr>
                                <td>#<?php echo $bill['bill_id']; ?></td>
                                <td><?php echo htmlspecialchars($bill['first_name'] . ' ' . $bill['last_name']); ?></td>
                                <td><?php echo $bill['room_number']; ?><br><small><?php echo $bill['type_name']; ?></small></td>
                                <td><?php echo date('M j, Y', strtotime($bill['check_in_date'])); ?></td>
                                <td><?php echo $bill['nights']; ?></td>
                                <td>$<?php echo number_format($bill['room_charges'], 2); ?></td>
                                <td>$<?php echo number_format($bill['tax_amount'], 2); ?></td>
                                <td>$<?php echo number_format($bill['service_charges'], 2); ?></td>
                                <td><strong>$<?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $bill['payment_status']; ?>">
                                        <?php echo ucfirst($bill['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $bill['payment_method'] ? ucfirst(str_replace('_', ' ', $bill['payment_method'])) : '-'; ?></td>
                                <td><?php echo $bill['payment_date'] ? date('M j, Y', strtotime($bill['payment_date'])) : '-'; ?></td>
                                <td>
                                    <?php if($bill['payment_status'] == 'pending'): ?>
                                        <button class="btn btn-primary" onclick="openPaymentModal(<?php echo $bill['bill_id']; ?>, '<?php echo htmlspecialchars($bill['first_name'] . ' ' . $bill['last_name']); ?>', <?php echo $bill['total_amount']; ?>)">
                                            <i class="fas fa-credit-card"></i> Mark Paid
                                        </button>
                                    <?php else: ?>
                                        <span style="color: #28a745;"><i class="fas fa-check"></i> Paid</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3>No Billing Records</h3>
                        <p>Billing records will appear here once created</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pending Payments Tab -->
            <div id="pending-tab" class="tab-content">
                <table class="billing-table">
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Guest Name</th>
                            <th>Room</th>
                            <th>Total Amount</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="pending-bills">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Paid Bills Tab -->
            <div id="paid-tab" class="tab-content">
                <table class="billing-table">
                    <thead>
                        <tr>
                            <th>Bill ID</th>
                            <th>Guest Name</th>
                            <th>Room</th>
                            <th>Total Amount</th>
                            <th>Payment Method</th>
                            <th>Payment Date</th>
                        </tr>
                    </thead>
                    <tbody id="paid-bills">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Mark Payment as Paid</h3>
                <span class="close" onclick="closePaymentModal()">&times;</span>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="bill_id" id="modal_bill_id">
                
                <div class="form-group">
                    <label>Guest Name</label>
                    <input type="text" id="modal_guest_name" readonly style="background: #f8f9fa;">
                </div>
                
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" id="modal_amount" readonly style="background: #f8f9fa;">
                </div>
                
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="cash">Cash</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="debit_card">Debit Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="digital_wallet">Digital Wallet</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn" onclick="closePaymentModal()" style="background: #6c757d; color: white; flex: 1;">
                        Cancel
                    </button>
                    <button type="submit" name="mark_paid" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-check"></i> Mark as Paid
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Store all bills data for filtering
        const allBills = <?php 
            mysqli_data_seek($billing_result, 0);
            $bills = [];
            while($bill = mysqli_fetch_assoc($billing_result)) {
                $bills[] = $bill;
            }
            echo json_encode($bills);
        ?>;

        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab and activate button
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
            
            // Populate filtered data
            if (tabName === 'pending') {
                populatePendingBills();
            } else if (tabName === 'paid') {
                populatePaidBills();
            }
        }

        function populatePendingBills() {
            const pendingBills = allBills.filter(bill => bill.payment_status === 'pending');
            const tbody = document.getElementById('pending-bills');
            
            if (pendingBills.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: #666;">No pending payments</td></tr>';
                return;
            }
            
            tbody.innerHTML = pendingBills.map(bill => `
                <tr>
                    <td>#${bill.bill_id}</td>
                    <td>${bill.first_name} ${bill.last_name}</td>
                    <td>${bill.room_number}<br><small>${bill.type_name}</small></td>
                    <td><strong>$${parseFloat(bill.total_amount).toFixed(2)}</strong></td>
                    <td>${new Date(bill.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-primary" onclick="openPaymentModal(${bill.bill_id}, '${bill.first_name} ${bill.last_name}', ${bill.total_amount})">
                            <i class="fas fa-credit-card"></i> Mark Paid
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function populatePaidBills() {
            const paidBills = allBills.filter(bill => bill.payment_status === 'paid');
            const tbody = document.getElementById('paid-bills');
            
            if (paidBills.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: #666;">No paid bills</td></tr>';
                return;
            }
            
            tbody.innerHTML = paidBills.map(bill => `
                <tr>
                    <td>#${bill.bill_id}</td>
                    <td>${bill.first_name} ${bill.last_name}</td>
                    <td>${bill.room_number}<br><small>${bill.type_name}</small></td>
                    <td><strong>$${parseFloat(bill.total_amount).toFixed(2)}</strong></td>
                    <td>${bill.payment_method ? bill.payment_method.replace('_', ' ') : '-'}</td>
                    <td>${bill.payment_date ? new Date(bill.payment_date).toLocaleDateString() : '-'}</td>
                </tr>
            `).join('');
        }

        function openPaymentModal(billId, guestName, amount) {
            document.getElementById('modal_bill_id').value = billId;
            document.getElementById('modal_guest_name').value = guestName;
            document.getElementById('modal_amount').value = '$' + parseFloat(amount).toFixed(2);
            document.getElementById('paymentModal').style.display = 'block';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('paymentModal');
            if (event.target == modal) {
                closePaymentModal();
            }
        }

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 5000);
    </script>
</body>
</html>