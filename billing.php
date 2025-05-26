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
                    colors: {
                        'primary': '#667eea',
                        'secondary': '#764ba2',
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
        }
        .glass-effect {
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="font-poppins">
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
                    <a href="guests.php" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Guests</a>
                    <a href="billing.php" class="text-blue-600 font-semibold border-b-2 border-blue-600 pb-1">Billing</a>
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
    <div class="gradient-bg min-h-screen mt-20">
        <div class="max-w-7xl mx-auto p-8">
            

            <!-- Header -->
            <div class="text-center mb-12  text-primary">
                <h1 class="text-4xl font-bold mb-2 drop-shadow-lg">
                    <i class="fas fa-receipt text-primary mr-3"></i>Billing & Payment Management
                </h1>
                <p class="text-lg opacity-90">Manage hotel billing records and payment status</p>
            </div>

            <!-- Alerts -->
            <?php if($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <!-- Financial Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="bg-white bg-opacity-95 rounded-xl p-6 text-center shadow-xl glass-effect border border-white border-opacity-30">
                    <div class="text-5xl text-primary mb-4">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-2"><?php echo $stats['pending_payments']; ?></div>
                    <div class="text-gray-600 font-medium uppercase tracking-wide text-sm">Pending Payments</div>
                </div>

                <div class="bg-white bg-opacity-95 rounded-xl p-6 text-center shadow-xl glass-effect border border-white border-opacity-30">
                    <div class="text-5xl text-primary mb-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-2"><?php echo $stats['completed_payments']; ?></div>
                    <div class="text-gray-600 font-medium uppercase tracking-wide text-sm">Completed Payments</div>
                </div>

                <div class="bg-white bg-opacity-95 rounded-xl p-6 text-center shadow-xl glass-effect border border-white border-opacity-30">
                    <div class="text-5xl text-primary mb-4">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-2">$<?php echo number_format($stats['pending_amount'], 0); ?></div>
                    <div class="text-gray-600 font-medium uppercase tracking-wide text-sm">Pending Amount</div>
                </div>

                <div class="bg-white bg-opacity-95 rounded-xl p-6 text-center shadow-xl glass-effect border border-white border-opacity-30">
                    <div class="text-5xl text-primary mb-4">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="text-3xl font-bold text-gray-800 mb-2">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                    <div class="text-gray-600 font-medium uppercase tracking-wide text-sm">Total Revenue</div>
                </div>
            </div>

            <!-- Billing Table Container -->
            <div class="bg-white bg-opacity-95 rounded-2xl p-8 shadow-2xl glass-effect border border-white border-opacity-30 overflow-x-auto">
                <!-- Section Tabs -->
                <div class="flex border-b-2 border-gray-200 mb-8">
                    <button class="tab-button px-8 py-4 font-semibold text-gray-600 border-b-3 border-transparent hover:text-primary transition-colors duration-300 active" onclick="showTab('all')">
                        All Bills
                    </button>
                    <button class="tab-button px-8 py-4 font-semibold text-gray-600 border-b-3 border-transparent hover:text-primary transition-colors duration-300" onclick="showTab('pending')">
                        Pending Payments
                    </button>
                    <button class="tab-button px-8 py-4 font-semibold text-gray-600 border-b-3 border-transparent hover:text-primary transition-colors duration-300" onclick="showTab('paid')">
                        Paid Bills
                    </button>
                </div>

                <!-- All Bills Tab -->
                <div id="all-tab" class="tab-content">
                    <?php if(mysqli_num_rows($billing_result) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Bill ID</th>
                                        <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Guest Name</th>
                                        <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Room</th>
                                        <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Check-in</th>
                                        <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Nights</th>
                                        <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Total Amount</th>
                                        <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Status</th>
                                        <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Payment Method</th>
                                        <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Payment Date</th>
                                        <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Reset the result pointer
                                    mysqli_data_seek($billing_result, 0);
                                    while($bill = mysqli_fetch_assoc($billing_result)): 
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="p-4 border-b border-gray-200">#<?php echo $bill['bill_id']; ?></td>
                                        <td class="p-4 border-b border-gray-200"><?php echo htmlspecialchars($bill['first_name'] . ' ' . $bill['last_name']); ?></td>
                                        <td class="p-4 border-b border-gray-200">
                                            <div><?php echo $bill['room_number']; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $bill['type_name']; ?></div>
                                        </td>
                                        <td class="p-4 border-b border-gray-200"><?php echo date('M j, Y', strtotime($bill['check_in_date'])); ?></td>
                                        <td class="p-4 border-b border-gray-200"><?php echo $bill['nights']; ?></td>
                                        <td class="p-4 border-b border-gray-200 font-bold">$<?php echo number_format($bill['total_amount'], 2); ?></td>
                                        <td class="p-4 border-b border-gray-200">
                                            <?php
                                            $statusClasses = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'paid' => 'bg-blue-100 text-blue-800',
                                                'partial' => 'bg-purple-100 text-purple-800',
                                                'refunded' => 'bg-red-100 text-red-800'
                                            ];
                                            $statusClass = $statusClasses[$bill['payment_status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($bill['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td class="p-4 border-b border-gray-200"><?php echo $bill['payment_method'] ? ucfirst(str_replace('_', ' ', $bill['payment_method'])) : '-'; ?></td>
                                        <td class="p-4 border-b border-gray-200"><?php echo $bill['payment_date'] ? date('M j, Y', strtotime($bill['payment_date'])) : '-'; ?></td>
                                        <td class="p-4 border-b border-gray-200">
                                            <?php if($bill['payment_status'] == 'pending'): ?>
                                                <button class="gradient-btn text-white px-4 py-2 rounded-lg hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 text-sm font-semibold" onclick="openPaymentModal(<?php echo $bill['bill_id']; ?>, '<?php echo htmlspecialchars($bill['first_name'] . ' ' . $bill['last_name']); ?>', <?php echo $bill['total_amount']; ?>)">
                                                    <i class="fas fa-credit-card"></i> Mark Paid
                                                </button>
                                            <?php else: ?>
                                                <span class="text-green-600 flex items-center gap-2">
                                                    <i class="fas fa-check"></i> Paid
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-receipt text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold mb-2">No Billing Records</h3>
                            <p>Billing records will appear here once created</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pending Payments Tab -->
                <div id="pending-tab" class="tab-content hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Bill ID</th>
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Guest Name</th>
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Room</th>
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Total Amount</th>
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Created Date</th>
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Action</th>
                                </tr>
                            </thead>
                            <tbody id="pending-bills">
                                <!-- Populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Paid Bills Tab -->
                <div id="paid-tab" class="tab-content hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Bill ID</th>
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Guest Name</th>
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Room</th>
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Total Amount</th>
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Payment Method</th>
                                    <th class="text-left p-4 font-semibold text-gray-700 uppercase tracking-wide text-sm border-b border-gray-200">Payment Date</th>
                                </tr>
                            </thead>
                            <tbody id="paid-bills">
                                <!-- Populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 glass-effect">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl">
                <div class="flex items-center justify-between p-6 border-b-2 border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-800">Mark Payment as Paid</h3>
                    <button class="text-gray-400 hover:text-primary text-2xl font-bold transition-colors duration-200" onclick="closePaymentModal()">&times;</button>
                </div>
                
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="bill_id" id="modal_bill_id">
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-semibold mb-2">Guest Name</label>
                        <input type="text" id="modal_guest_name" readonly class="w-full p-3 bg-gray-50 border-2 border-gray-200 rounded-lg">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-semibold mb-2">Amount</label>
                        <input type="text" id="modal_amount" readonly class="w-full p-3 bg-gray-50 border-2 border-gray-200 rounded-lg">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-semibold mb-2">Payment Method</label>
                        <select name="payment_method" required class="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-primary focus:outline-none">
                            <option value="">Select Payment Method</option>
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="digital_wallet">Digital Wallet</option>
                        </select>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="button" class="flex-1 px-4 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 font-semibold" onclick="closePaymentModal()">
                            Cancel
                        </button>
                        <button type="submit" name="mark_paid" class="flex-1 gradient-btn text-white px-4 py-3 rounded-lg hover:shadow-lg transition-all duration-300 font-semibold flex items-center justify-center gap-2">
                            <i class="fas fa-check"></i> Mark as Paid
                        </button>
                    </div>
                </form>
            </div>
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
                tab.classList.add('hidden');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active', 'text-primary', 'border-primary');
                btn.classList.add('text-gray-600', 'border-transparent');
            });
            
            // Show selected tab and activate button
            document.getElementById(tabName + '-tab').classList.remove('hidden');
            event.target.classList.remove('text-gray-600', 'border-transparent');
            event.target.classList.add('active', 'text-primary', 'border-primary');
            
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
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No pending payments</td></tr>';
                return;
            }
            
            tbody.innerHTML = pendingBills.map(bill => `
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="p-4 border-b border-gray-200">#${bill.bill_id}</td>
                    <td class="p-4 border-b border-gray-200">${bill.first_name} ${bill.last_name}</td>
                    <td class="p-4 border-b border-gray-200">
                        <div>${bill.room_number}</div>
                        <div class="text-sm text-gray-500">${bill.type_name}</div>
                    </td>
                    <td class="p-4 border-b border-gray-200 font-bold">$${parseFloat(bill.total_amount).toFixed(2)}</td>
                    <td class="p-4 border-b border-gray-200">${new Date(bill.created_at).toLocaleDateString()}</td>
                    <td class="p-4 border-b border-gray-200">
                        <button class="gradient-btn text-white px-4 py-2 rounded-lg hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 flex items-center gap-2 text-sm font-semibold" onclick="openPaymentModal(${bill.bill_id}, '${bill.first_name} ${bill.last_name}', ${bill.total_amount})">
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
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No paid bills</td></tr>';
                return;
            }
            
            tbody.innerHTML = paidBills.map(bill => `
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="p-4 border-b border-gray-200">#${bill.bill_id}</td>
                    <td class="p-4 border-b border-gray-200">${bill.first_name} ${bill.last_name}</td>
                    <td class="p-4 border-b border-gray-200">
                        <div>${bill.room_number}</div>
                        <div class="text-sm text-gray-500">${bill.type_name}</div>
                    </td>
                    <td class="p-4 border-b border-gray-200 font-bold">$${parseFloat(bill.total_amount).toFixed(2)}</td>
                    <td class="p-4 border-b border-gray-200">${bill.payment_method ? bill.payment_method.replace('_', ' ') : '-'}</td>
                    <td class="p-4 border-b border-gray-200">${bill.payment_date ? new Date(bill.payment_date).toLocaleDateString() : '-'}</td>
                </tr>
            `).join('');
        }

        function openPaymentModal(billId, guestName, amount) {
            document.getElementById('modal_bill_id').value = billId;
            document.getElementById('modal_guest_name').value = guestName;
            document.getElementById('modal_amount').value = '$' + parseFloat(amount).toFixed(2);
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
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
            const alerts = document.querySelectorAll('.bg-green-100, .bg-red-100');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 5000);

        // Initialize first tab as active
        document.addEventListener('DOMContentLoaded', function() {
            const firstTab = document.querySelector('.tab-button');
            firstTab.classList.add('text-primary', 'border-primary');
            firstTab.classList.remove('text-gray-600', 'border-transparent');
        });
    </script>
</body>
</html>