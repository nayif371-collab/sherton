<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
 
// process_booking.php
require_once 'db.php';
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid access.");
}
 
// sanitize & get fields
$room_id   = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email     = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone     = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$checkin   = isset($_POST['checkin']) ? $_POST['checkin'] : '';
$checkout  = isset($_POST['checkout']) ? $_POST['checkout'] : '';
$guests    = isset($_POST['guests']) ? (int)$_POST['guests'] : 1;
$notes     = isset($_POST['notes']) ? trim($_POST['notes']) : '';
 
// simple validation
if ($room_id <= 0 || !$full_name || !$email || !$phone || !$checkin || !$checkout || $checkin >= $checkout) {
    die("Please fill required fields correctly.");
}
 
// ensure room exists
$stmt = $mysqli->prepare("SELECT r.price, r.max_guests, h.name as hotel_name 
                          FROM rooms r 
                          JOIN hotels h ON r.hotel_id=h.id 
                          WHERE r.id = ? LIMIT 1");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$res = $stmt->get_result();
$room = $res->fetch_assoc();
$stmt->close();
if (!$room) die("Invalid room.");
 
// calculate nights
$start = new DateTime($checkin);
$end   = new DateTime($checkout);
$interval = $start->diff($end);
$nights   = (int)$interval->format('%a');
if ($nights <= 0) { die("Invalid dates."); }
 
$total = $nights * floatval($room['price']);
 
// insert booking
$insert = $mysqli->prepare("INSERT INTO bookings 
    (room_id, full_name, email, phone, checkin, checkout, guests, notes, nights, total_price, created_at) 
    VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");
 
if (!$insert) {
    die("Prepare failed: " . $mysqli->error);
}
 
// CORRECT bind_param string (10 variables → "isssssiisd")
// i = int, s = string, s = string, s = string, s = string, s = string, i = int, s = string, i = int, d = double
$insert->bind_param(
    "isssssiisd",
    $room_id,
    $full_name,
    $email,
    $phone,
    $checkin,
    $checkout,
    $guests,
    $notes,
    $nights,
    $total
);
 
if ($insert->execute()) {
    $booking_id = $insert->insert_id;
    ?>
    <!doctype html>
    <html>
    <head>
      <meta charset="utf-8" />
      <meta name="viewport" content="width=device-width,initial-scale=1" />
      <title>Booking Confirmed</title>
      <style>
        body {font-family:Inter,Arial;background:#f5f7fb;color:#222;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
        .box {background:#fff;padding:26px;border-radius:12px;box-shadow:0 12px 30px rgba(12,30,80,.07);text-align:center;width:90%;max-width:520px}
        .btn {background:#0b6efd;color:#fff;padding:10px 14px;border-radius:10px;border:none;cursor:pointer;margin-top:10px}
      </style>
    </head>
    <body>
      <div class="box">
        <h2>Booking confirmed ✅</h2>
        <p>Thanks <?php echo htmlspecialchars($full_name); ?> — your reservation has been received.</p>
        <p><strong>Booking ID:</strong> <?php echo (int)$booking_id; ?></p>
        <p><strong>Nights:</strong> <?php echo (int)$nights; ?> · <strong>Total:</strong> $<?php echo number_format($total,2); ?></p>
        <p id="going">Redirecting to confirmation page…</p>
        <button class="btn" onclick="window.location.href='confirm.php?id=<?php echo (int)$booking_id; ?>'">View details</button>
      </div>
 
      <script>
        setTimeout(function(){
          window.location.href = 'confirm.php?id=<?php echo (int)$booking_id; ?>';
        }, 1500);
      </script>
    </body>
    </html>
    <?php
    exit;
} else {
    die("Could not save booking: " . $insert->error);
}
?>
