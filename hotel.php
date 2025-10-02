<?php
// hotel.php - show room and booking form
require_once 'db.php';
 
$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if($room_id <= 0) die("Room not specified.");
 
$stmt = $mysqli->prepare("SELECT r.*, h.name as hotel_name, h.city, h.rating, h.thumbnail, h.short_desc
                          FROM rooms r JOIN hotels h ON r.hotel_id = h.id WHERE r.id = ? LIMIT 1");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$res = $stmt->get_result();
$room = $res->fetch_assoc();
$stmt->close();
 
if(!$room) die("Room not found.");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title><?php echo htmlspecialchars($room['hotel_name'] . ' — ' . $room['title']); ?></title>
<style>
body{font-family:Inter,Arial; background:#f5f7fb;margin:0;color:#222}
.container{max-width:980px;margin:20px auto;padding:16px}
.top{display:flex;gap:16px;align-items:flex-start}
.thumb{flex:1}
.thumb img{width:100%;height:320px;object-fit:cover;border-radius:12px}
.details{flex:1.1;background:#fff;padding:16px;border-radius:12px;box-shadow:0 10px 24px rgba(12,30,80,.04)}
.price{font-weight:800;font-size:22px;color:#0b6efd}
.meta{color:#666;margin-top:6px}
.book-form{background:#fff;padding:14px;border-radius:12px;box-shadow:0 10px 24px rgba(12,30,80,.04);margin-top:14px}
input, select, textarea{width:100%;padding:10px;border-radius:8px;border:1px solid #e6e9ef;margin-top:8px}
.btn{background:#0b6efd;color:#fff;padding:12px;border-radius:10px;border:none;cursor:pointer;margin-top:10px;width:100%}
@media(max-width:800px){.top{flex-direction:column}.thumb img{height:220px}}
</style>
</head>
<body>
<div class="container">
  <div class="top">
    <div class="thumb">
      <img src="<?php echo htmlspecialchars($room['thumbnail']); ?>" alt="">
    </div>
    <div class="details">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
          <div style="font-weight:800;font-size:18px"><?php echo htmlspecialchars($room['hotel_name']); ?></div>
          <div class="meta"><?php echo htmlspecialchars($room['city']); ?> · Rating: <?php echo htmlspecialchars($room['rating']); ?></div>
        </div>
        <div style="text-align:right">
          <div class="price">$<?php echo htmlspecialchars(number_format($room['price'],2)); ?> / night</div>
          <div class="meta">Max guests: <?php echo htmlspecialchars($room['max_guests']); ?></div>
        </div>
      </div>
 
      <div style="margin-top:12px" class="meta"><?php echo htmlspecialchars($room['short_desc']); ?></div>
      <div style="margin-top:10px" class="meta"><strong>Amenities:</strong> <?php echo htmlspecialchars($room['amenities']); ?></div>
 
      <div class="book-form">
        <form id="bookingForm" method="post" action="process_booking.php">
          <input type="hidden" name="room_id" value="<?php echo (int)$room['id']; ?>" />
          <label>Full name</label>
          <input name="full_name" required placeholder="Your full name" />
          <label>Email</label>
          <input name="email" type="email" required placeholder="email@example.com" />
          <label>Phone</label>
          <input name="phone" required placeholder="+92..." />
          <div style="display:flex;gap:8px">
            <div style="flex:1">
              <label>Check-in</label>
              <input name="checkin" type="date" required />
            </div>
            <div style="flex:1">
              <label>Check-out</label>
              <input name="checkout" type="date" required />
            </div>
          </div>
          <label>Guests</label>
          <select name="guests">
            <?php for($i=1;$i<=$room['max_guests'];$i++): ?>
              <option value="<?php echo $i; ?>"><?php echo $i; ?> guest<?php if($i>1) echo 's'; ?></option>
            <?php endfor; ?>
          </select>
 
          <label>Special requests (optional)</label>
          <textarea name="notes" rows="3" placeholder="e.g., late check-in, bed preference"></textarea>
 
          <button type="submit" class="btn">Book now — $<?php echo htmlspecialchars(number_format($room['price'],2)); ?>/night</button>
        </form>
      </div>
    </div>
  </div>
</div>
 
<script>
// We will trust server for final validation, but do a little front-end checking
document.getElementById('bookingForm').addEventListener('submit', function(e){
  // simple check: checkin < checkout
  const checkin = this.checkin.value;
  const checkout = this.checkout.value;
  if(!checkin || !checkout || checkin >= checkout){
    e.preventDefault();
    alert('Please select valid check-in and check-out dates (check-out must be after check-in).');
    return false;
  }
  // form submits to process_booking.php which will redirect using JS upon success.
});
</script>
</body>
</html>
