<?php
// index.php
require_once 'db.php';
 
// Fetch featured hotels for display
$featured = [];
$res = $mysqli->query("SELECT id, name, city, rating, short_desc, thumbnail FROM hotels ORDER BY rating DESC LIMIT 4");
if ($res) {
    while ($row = $res->fetch_assoc()) $featured[] = $row;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Sheraton Clone — Home</title>
<style>
/* Internal CSS — polished, modern look */
:root{--accent:#0b6efd;--muted:#666;}
*{box-sizing:border-box}
body{font-family:Inter,Segoe UI,Arial; margin:0; background:#f5f7fb; color:#222;}
.header{background:linear-gradient(90deg,#073b8c, #0b6efd); color:#fff; padding:36px 20px; text-align:center;}
.header h1{margin:0 0 8px; font-size:28px;}
.header p{margin:0 0 18px; opacity:.9;}
.container{max-width:1100px;margin: -40px auto 40px;padding:20px;}
.search-card{background:#fff;padding:18px;border-radius:12px;box-shadow:0 6px 18px rgba(5,27,70,.06);display:flex;gap:12px;flex-wrap:wrap;align-items:center;}
.search-card input, .search-card select{padding:12px;border-radius:8px;border:1px solid #e6e9ef;min-width:160px;}
.search-card button{background:var(--accent);color:#fff;border:none;padding:12px 18px;border-radius:10px;cursor:pointer;font-weight:600}
.row{display:flex;gap:18px;align-items:flex-start}
.col{flex:1}
.sidebar{width:260px;}
.filters{background:#fff;padding:14px;border-radius:12px;box-shadow:0 6px 18px rgba(5,27,70,.04);}
.featured{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:18px;}
.card{background:#fff;border-radius:12px;padding:12px;box-shadow:0 8px 20px rgba(12,30,80,.04);overflow:hidden;}
.card img{width:100%;height:110px;object-fit:cover;border-radius:8px;}
.hotel-name{font-weight:700;margin:8px 0 4px}
.hotel-meta{font-size:13px;color:var(--muted)}
.footer{text-align:center;color:var(--muted);margin-top:20px;font-size:14px}
@media(max-width:900px){.featured{grid-template-columns:repeat(2,1fr)} .sidebar{display:none}}
</style>
</head>
<body>
<header class="header">
  <h1>Find your stay — Sheraton Clone</h1>
  <p>Search hotels, compare rooms & book instantly</p>
</header>
 
<div class="container">
  <div class="search-card" role="search">
    <input id="destination" type="text" placeholder="Destination (city or hotel name)" />
    <input id="checkin" type="date" />
    <input id="checkout" type="date" />
    <select id="guests">
      <option value="1">1 guest</option>
      <option value="2" selected>2 guests</option>
      <option value="3">3 guests</option>
      <option value="4">4 guests</option>
    </select>
    <button id="searchBtn">Search hotels</button>
  </div>
 
  <div class="row" style="margin-top:18px">
    <div class="col">
      <h3 style="margin:0 0 8px">Featured Hotels</h3>
      <div class="featured">
        <?php if(count($featured)===0): ?>
          <div class="card">No hotels found - add sample data.</div>
        <?php else: foreach($featured as $f): ?>
          <div class="card">
            <img src="<?php echo htmlspecialchars($f['thumbnail']); ?>" alt="">
            <div class="hotel-name"><?php echo htmlspecialchars($f['name']); ?></div>
            <div class="hotel-meta"><?php echo htmlspecialchars($f['city']); ?> · Rating: <?php echo htmlspecialchars($f['rating']); ?></div>
            <div style="margin-top:8px;font-size:13px;color:var(--muted)"><?php echo htmlspecialchars($f['short_desc']); ?></div>
            <div style="margin-top:10px;text-align:right">
              <button onclick="goToHotel(<?php echo (int)$f['id']; ?>)" style="padding:8px 10px;border-radius:8px;border:none;background:#0b6efd;color:#fff;cursor:pointer">View</button>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
 
    <aside class="sidebar">
      <div class="filters">
        <h4 style="margin:0 0 8px">Quick Filters</h4>
        <label>Price</label>
        <select id="priceFilter" style="width:100%;margin:6px 0 12px">
          <option value="">Any price</option>
          <option value="0-50">Under $50</option>
          <option value="50-100">$50–$100</option>
          <option value="100-9999">Above $100</option>
        </select>
        <label>Min Rating</label>
        <select id="ratingFilter" style="width:100%;margin:6px 0 12px">
          <option value="">Any</option>
          <option value="4">4+ stars</option>
          <option value="3">3+ stars</option>
        </select>
        <label>Amenities</label>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:8px">
          <label><input type="checkbox" value="wifi" class="amen"> Free Wifi</label>
          <label><input type="checkbox" value="pool" class="amen"> Pool</label>
          <label><input type="checkbox" value="parking" class="amen"> Parking</label>
        </div>
        <button style="margin-top:12px;width:100%;padding:10px;border-radius:8px;background:#0b6efd;color:#fff;border:none;cursor:pointer" onclick="applyFilters()">Apply Filters</button>
      </div>
    </aside>
  </div>
 
  <div class="footer">Responsive, real-feel CSS — enjoy building.</div>
</div>
 
<script>
// use JS to redirect to search results page (hotels.php) with query params
function goToHotel(id){
  // redirect to hotel detail
  window.location.href = 'hotel.php?id=' + id;
}
 
document.getElementById('searchBtn').addEventListener('click', () => {
  const dest = encodeURIComponent(document.getElementById('destination').value.trim());
  const checkin = document.getElementById('checkin').value;
  const checkout = document.getElementById('checkout').value;
  const guests = document.getElementById('guests').value;
  const price = document.getElementById('priceFilter').value;
  const rating = document.getElementById('ratingFilter').value;
  // amenities
  const amenities = Array.from(document.querySelectorAll('.amen:checked')).map(c=>c.value).join(',');
  let url = 'hotels.php?';
  const params = [];
  if(dest) params.push('q='+dest);
  if(checkin) params.push('checkin='+checkin);
  if(checkout) params.push('checkout='+checkout);
  params.push('guests=' + guests);
  if(price) params.push('price='+price);
  if(rating) params.push('rating='+rating);
  if(amenities) params.push('amenities='+encodeURIComponent(amenities));
  url += params.join('&');
  window.location.href = url;
});
 
function applyFilters(){
  document.getElementById('searchBtn').click();
}
</script>
</body>
</html>
