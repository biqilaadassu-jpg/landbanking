let activeMap = null;
let markers = [];

function togglePanel(id){
  const el=document.getElementById(id);
  if(el) el.classList.toggle('hidden');
}

async function loadWoredas(subcityId, targetId){
  const target=document.getElementById(targetId);
  target.innerHTML='<option>Loading...</option>';
  if(!subcityId){target.innerHTML='<option value="">Select</option>';return;}
  const r=await fetch('api/woredas.php?subcity_id='+encodeURIComponent(subcityId));
  const rows=await r.json();
  target.innerHTML='<option value="">Select</option>'+rows.map(x=>`<option value="${x.id}">${escapeHtml(x.name)}</option>`).join('');
}

function escapeHtml(v){
  return String(v??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

function getGPS(){
  const status=document.getElementById('gps-status');
  if(!navigator.geolocation){status.textContent='GPS is not supported.';return;}
  status.textContent='Getting GPS...';
  navigator.geolocation.getCurrentPosition(pos=>{
    document.getElementById('latitude').value=pos.coords.latitude.toFixed(10);
    document.getElementById('longitude').value=pos.coords.longitude.toFixed(10);
    status.textContent='GPS accuracy: ±'+Math.round(pos.coords.accuracy)+' m';
    if(activeMap){
      const p={lat:pos.coords.latitude,lng:pos.coords.longitude};
      activeMap.setCenter(p); activeMap.setZoom(18);
      if(window.entryMarker) window.entryMarker.setPosition(p);
      else window.entryMarker=new google.maps.Marker({map:activeMap,position:p,draggable:true,title:'Land location'});
      window.entryMarker.addListener('dragend',e=>{
        document.getElementById('latitude').value=e.latLng.lat().toFixed(10);
        document.getElementById('longitude').value=e.latLng.lng().toFixed(10);
      });
    }
  },err=>status.textContent='GPS error: '+err.message,{enableHighAccuracy:true,timeout:15000});
}

function loadMap(elementId, mode){
  window.mapMode=mode;
  if(typeof google==='undefined' || !google.maps){
    document.getElementById(elementId).innerHTML='<div class="map-error">Google Maps could not load. Add GOOGLE_MAPS_API_KEY in config/config.php.</div>';
    return;
  }
  activeMap=new google.maps.Map(document.getElementById(elementId),{
    center:{lat:9.03,lng:38.74},zoom:11,mapTypeId:'roadmap',
    mapTypeControl:true,streetViewControl:false,fullscreenControl:true
  });
  if(mode==='entry'){
    activeMap.addListener('click',e=>{
      const p=e.latLng;
      document.getElementById('latitude').value=p.lat().toFixed(10);
      document.getElementById('longitude').value=p.lng().toFixed(10);
      if(window.entryMarker) window.entryMarker.setPosition(p);
      else window.entryMarker=new google.maps.Marker({map:activeMap,position:p,draggable:true,title:'Selected land location'});
    });
  } else {
    refreshMap();
  }
}

async function refreshMap(){
  if(!activeMap) return;
  const type=document.getElementById('map-type')?.value || 'ALL';
  const status=document.getElementById('map-status')?.value || 'ALL';
  const rows=await (await fetch(`api/transactions.php?type=${encodeURIComponent(type)}&status=${encodeURIComponent(status)}`)).json();
  markers.forEach(m=>m.setMap(null)); markers=[];
  const bounds=new google.maps.LatLngBounds();
  rows.forEach(r=>{
    const p={lat:Number(r.latitude),lng:Number(r.longitude)};
    const color=r.transaction_type==='DEPOSIT'?'green':'red';
    const marker=new google.maps.Marker({map:activeMap,position:p,title:r.transaction_number});
    const info=new google.maps.InfoWindow({content:
      `<div style="min-width:220px"><b>${escapeHtml(r.transaction_number)}</b><br>
      <b>${escapeHtml(r.transaction_type)}</b> — ${Number(r.area_m2).toLocaleString()} m²<br>
      ${escapeHtml(r.subcity)} / ${escapeHtml(r.woreda)}<br>
      Status: <b>${escapeHtml(r.status)}</b><br>
      ${escapeHtml(r.address||'')}<br>
      <a target="_blank" href="https://www.google.com/maps?q=${r.latitude},${r.longitude}">Open in Google Maps</a></div>`
    });
    marker.addListener('click',()=>info.open({map:activeMap,anchor:marker}));
    markers.push(marker); bounds.extend(p);
  });
  if(rows.length) activeMap.fitBounds(bounds);
}

function locateMe(){
  if(!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(p=>{
    const pos={lat:p.coords.latitude,lng:p.coords.longitude};
    activeMap.setCenter(pos);activeMap.setZoom(17);
    new google.maps.Marker({map:activeMap,position:pos,title:'My GPS location'});
  },()=>alert('Unable to get current location.'),{enableHighAccuracy:true});
}
