<?php
// hotels.php
require_once 'db.php';
 
// Collect filters from GET
$q = isset($_GET['q']) ? $mysqli->real_escape_string($_GET['q']) : '';
$checkin = isset($_GET['checkin']) ? $_GET['checkin'] : '';
$checkout = isset($_GET['checkout']) ? $_GET['checkout'] : '';
$guests = isset($_GET['guests']) ? (int)$_GET['guests'] : 1;
$price = isset($_GET['price']) ? $_GET['price'] : '';
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$amenities = isset($_GET['amenities']) ? explode(',', $_GET['amenities']) : [];
 
// Build SQL - basic but safe (prepared below)
$sql = "SELECT r.id as room_id, r.title as room_title, r.price, r.max_guests, r.amenities as room_amenities,
               h.id as hotel_id, h.name as hotel_name, h.city, h.rating, h.thumbnail
        FROM rooms r
        JOIN hotels h ON r.hotel_id = h.id
        WHERE 1=1 ";
 
// We'll add conditions dynamically and bind params
$params = [];
$types = "";
 
// q: search city or hotel name
if($q !== ''){
    $sql .= " AND (h.city LIKE CONCAT('%',?,'%') OR h.name LIKE CONCAT('%',?,'%')) ";
    $types .= "ss";
    array_push($params, $q, $q);
}
 
// rating
if($rating > 0){
    $sql .= " AND h.rating >= ? ";
    $types .= "i";
    array_push($params, $rating);
}
 
// price
if($price !== ''){
    $parts = explode('-', $price);
    if(count($parts) === 2){
        $sql .= " AND r.price BETWEEN ? AND ? ";
        $types .= "dd";
        array_push($params, (float)$parts[0], (float)$parts[1]);
    }
}
 
// guests
$sql .= " AND r.max_guests >= ? ";
$types .= "i";
array_push($params, $guests);
 
// amenities: simple check string contains
if(count($amenities)>0 && strlen($amenities[0])>0){
    foreach($amenities as $am){
        $sql .= " AND r.amenities LIKE CONCAT('%',?,'%') ";
        $types .= "s";
        array_push($params, $am);
    }
}
 
// sort
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'recommended';
if($sort === 'price_asc') $sql .= " ORDER BY r.price ASC ";
else if($sort === 'price_desc') $sql .= " ORDER BY r.price DESC ";
else if($sort === 'rating') $sql .= " ORDER BY h.rating DESC ";
else $sql .= " ORDER BY h.rating DESC, r.price ASC ";
 
// prepare
$stmt = $mysqli->prepare($sql);
if($stmt){
    if(!empty($params)){
        // bind dynamically
        $bindNames[] = $types;
        for($i=0;$i<count($params);$i++){
            $bindNames[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindNames);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rooms = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("Query error: " . $mysqli->error);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Search results — Sheraton Clone</title>
<style>
/* Internal CSS for listing page */
body{font-family:Inter,Arial,Helvetica; background:#f5f7fb; margin:0;color:#222}
.header{padding:18px 20px;background:#fff;border-bottom:1px solid #eee}
.container{max-width:1100px;margin:20px auto;padding:0 16px}
.listing{display:flex;gap:18px}
.left{flex:3}
.right{flex:1}
.card{background:#fff;border-radius:12px;padding:12px;margin-bottom:14px;display:flex;gap:12px;box-shadow:0 10px 24px rgba(12,30,80,.04)}
.card img{width:220px;height:140px;border-radius:10px;object-fit:cover}
.card .info{flex:1}
.price{font-weight:800;color:#0b6efd;font-size:18px}
.meta{color:#666;font-size:13px}
.controls{display:flex;gap:10px;align-items:center;margin-bottom:12px}
.controls select, .controls button{padding:8px;border-radius:8px;border:1px solid #e6e9ef}
.controls button{background:#0b6efd;color:#fff;border:none;cursor:pointer}
.book-btn{padding:10px 12px;border:none;border-radius:8px;background:#0b6efd;color:#fff;cursor:pointer}
@media(max-width:900px){.listing{flex-direction:column}.right{order:2}.left{order:1}}
</style>
</head>
<body>
<header class="header">
  <div class="container">
    <strong>Search results</strong>
  </div>
</header>
 
<div class="container">
  <div style="margin:12px 0" class="controls">
    <div>Showing <?php echo count($rooms); ?> rooms</div>
    <div style="margin-left:auto">
      <select id="sort" onchange="applySort()">
        <option value="recommended" <?php if($sort==='recommended') echo 'selected'; ?>>Recommended</option>
        <option value="price_asc" <?php if($sort==='price_asc') echo 'selected'; ?>>Price low → high</option>
        <option value="price_desc" <?php if($sort==='price_desc') echo 'selected'; ?>>Price high → low</option>
        <option value="rating" <?php if($sort==='rating') echo 'selected'; ?>>Best rated</option>
      </select>
    </div>
  </div>
 
  <div class="listing">
    <div class="left">
      <?php if(count($rooms)===0): ?>
        <div class="card"><div>No rooms match your search. Try different dates or filters.</div></div>
      <?php else: foreach($rooms as $r): ?>
        <div class="card">
          <img src="<?php echo htmlspecialchars($r['thumbnail']); ?>" alt="">
          <div class="info">
            <div style="display:flex;justify-content:space-between;align-items:center">
              <div>
                <div style="font-weight:800"><?php echo htmlspecialchars($r['hotel_name']); ?> — <?php echo htmlspecialchars($r['room_title']); ?></div>
                <div class="meta"><?php echo htmlspecialchars($r['city']); ?> · Rating: <?php echo htmlspecialchars($r['rating']); ?></div>
              </div>
              <div style="text-align:right">
                <div class="price">$<?php echo htmlspecialchars(number_format($r['price'],2)); ?></div>
                <div class="meta">per night</div>
              </div>
            </div>
            <div style="margin-top:10px" class="meta">Amenities: <?php echo htmlspecialchars($r['room_amenities']); ?></div>
            <div style="margin-top:10px;text-align:right">
              <button class="book-btn" onclick="viewRoom(<?php echo (int)$r['room_id']; ?>)">View & Book</button>
            </div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
 
    <aside class="right">
      <div class="card">
        <div style="font-weight:700">Your search</div>
        <div class="meta" style="margin-top:8px;">
          Destination: <?php echo htmlspecialchars($q?:'Any'); ?><br/>
          Check-in: <?php echo htmlspecialchars($checkin?:'—'); ?><br/>
          Check-out: <?php echo htmlspecialchars($checkout?:'—'); ?><br/>
          Guests: <?php echo htmlspecialchars($guests); ?>
        </div>
      </div>
      <div class="card">
        <div style="font-weight:700">Need help?</div>
        <div class="meta" style="margin-top:8px">Contact support@example.com</div>
      </div>
    </aside>
  </div>
</div>
 
<script>
function viewRoom(id){
  window.location.href = 'hotel.php?room_id=' + id;
}
 
function applySort(){
  const s = document.getElementById('sort').value;
  const params = new URLSearchParams(window.location.search);
  params.set('sort', s);
  window.location.search = params.toString();
}
</script>
</body>
</html>
