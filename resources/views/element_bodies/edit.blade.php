@extends('adminlte::page')

@section('title', 'Modifica Corpo Element')

@section('content_header')@stop

@section('content')

    @if($elementBody->isCompleted())
        <div class="alert alert-success shadow-sm mb-4" style="border-left:4px solid #28a745 !important;background-color:#f3fdf6;color:#155724;">
            <i class="fas fa-check-double mr-2 text-success"></i> Corpo in stato <strong>Completato</strong>. Tutto bloccato definitivamente.
        </div>
    @elseif($elementBody->state == \App\Models\ElementBody::STATE_FINISH_ZONE)
        <div class="alert alert-info border shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left:4px solid #17a2b8 !important;background-color:#f3fafd;color:#117a8b;">
            <div><i class="fas fa-lock mr-2 text-info"></i> Stato <strong>Zone Terminate</strong>. Configura le ancore.</div>
            <form action="{{ route('element-bodies.toggle-state') }}" method="POST" class="m-0">@csrf
                <input type="hidden" name="id" value="{{ $elementBody->id }}">
                <input type="hidden" name="state" value="{{ \App\Models\ElementBody::STATE_COMPLETED }}">
                <button type="submit" class="btn btn-primary btn-sm shadow-sm" onclick="return confirm('Bloccare definitivamente?');"><i class="fas fa-check-double"></i> Termina Ancore e Blocca</button>
            </form>
        </div>
    @elseif($elementBody->state == \App\Models\ElementBody::STATE_FINISH_DRAW)
        <div class="alert alert-info shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left:4px solid #17a2b8 !important;background-color:#f3fafd;color:#117a8b;">
            <div><i class="fas fa-pencil-ruler mr-2 text-info"></i> Stato <strong>Disegno Terminato</strong>. Configura le zone.</div>
            @if($elementBody->zones()->count() === 0)
                <button type="button" class="btn btn-info btn-sm shadow-sm disabled" disabled><i class="fas fa-lock mr-1"></i> Termina Zone e Blocca</button>
            @else
                <form action="{{ route('element-bodies.toggle-state') }}" method="POST">@csrf
                    <input type="hidden" name="id" value="{{ $elementBody->id }}">
                    <input type="hidden" name="state" value="{{ \App\Models\ElementBody::STATE_FINISH_ZONE }}">
                    <button type="submit" class="btn btn-info btn-sm shadow-sm text-white" onclick="return confirm('Terminare le zone?');"><i class="fas fa-lock mr-1"></i> Termina Zone e Blocca</button>
                </form>
            @endif
        </div>
    @else
        <div class="alert alert-light border shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left:4px solid #ffc107 !important;">
            <div><i class="fas fa-info-circle mr-2 text-warning"></i> Stato <strong>Creato</strong>. Puoi modificare e disegnare.</div>
            @if(!$elementBody->image || !\Storage::disk('element_bodies')->exists($elementBody->image))
                <button type="button" class="btn btn-success btn-sm shadow-sm disabled" disabled><i class="fas fa-check-circle mr-1"></i> Termina Disegno e Blocca</button>
            @else
                <form action="{{ route('element-bodies.toggle-state') }}" method="POST">@csrf
                    <input type="hidden" name="id" value="{{ $elementBody->id }}">
                    <input type="hidden" name="state" value="{{ \App\Models\ElementBody::STATE_FINISH_DRAW }}">
                    <button type="submit" class="btn btn-success btn-sm shadow-sm" onclick="return confirm('Terminare il disegno?');"><i class="fas fa-check-circle mr-1"></i> Termina Disegno e Blocca</button>
                </form>
            @endif
        </div>
    @endif

    <form action="{{ route('element-bodies.update', $elementBody) }}" method="POST">
        @csrf @method('PUT')
        <div class="card card-primary card-outline card-tabs shadow-sm">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="main-tabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#tab-general" role="tab">Dati Generali</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-graphics" role="tab">Grafica</a></li>
                    @if($elementBody->state >= 1)
                    <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-zones" role="tab">Zone</a></li>
                    @endif
                    @if($elementBody->state >= 2)
                    <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-ancore" role="tab">Ancore</a></li>
                    @endif
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="main-tabs-content">
                    <!-- DATI GENERALI -->
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                        <div class="row">
                            <div class="form-group col-md-6 col-12">
                                <label class="text-dark font-weight-bold">Nome <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                                    value="{{ old('name', $elementBody->name) }}" {{ $elementBody->state >= 1 ? 'disabled readonly' : '' }} required>
                                @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="form-group col-md-6 col-12">
                                <label class="text-dark font-weight-bold">Caratteristica <span class="text-danger">*</span></label>
                                <select name="characteristic" class="form-control" {{ $elementBody->state >= 1 ? 'disabled' : '' }} required>
                                    @foreach(\App\Models\ElementBody::CHARACTERISTIC_TYPES as $val => $lbl)
                                        <option value="{{ $val }}" {{ old('characteristic', $elementBody->characteristic) == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- GRAFICA -->
                    <div class="tab-pane fade" id="tab-graphics" role="tabpanel">
                        @if($elementBody->state >= 1)
                            <div class="row"><div class="col-md-6 col-12">
                                <div class="card card-outline card-secondary shadow-sm text-center py-4"><div class="card-body">
                                    <h5 class="text-muted mb-3 font-weight-bold">Grafica Salvata (Sola Lettura)</h5>
                                    @if($elementBody->image && \Storage::disk('element_bodies')->exists($elementBody->image))
                                        <img src="{{ asset('storage/element_bodies/' . $elementBody->image) }}?v={{ time() }}" style="width:128px;height:128px;image-rendering:pixelated;border:4px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,0.1);border-radius:8px;">
                                    @else
                                        <div class="d-inline-flex align-items-center justify-content-center border rounded bg-light" style="width:128px;height:128px;border-style:dashed !important;"><i class="fas fa-image fa-3x text-muted"></i></div>
                                    @endif
                                </div></div>
                            </div></div>
                        @else
                            @include('shared.graphics_editor', ['modelType' => 'element_body', 'model' => $elementBody, 'availableColors' => ['#000000']])
                        @endif
                    </div>

                    @if($elementBody->state >= 1)
                    <!-- ZONE -->
                    <div class="tab-pane fade" id="tab-zones" role="tabpanel">
                        @include('element_bodies.zones')
                    </div>
                    @endif

                    @if($elementBody->state >= 2)
                    <!-- ANCORE -->
                    <div class="tab-pane fade" id="tab-ancore" role="tabpanel">
                        @include('shared.anchors_editor', ['modelType' => 'element_body', 'model' => $elementBody, 'isLocked' => $elementBody->isCompleted(), 'anchorRoute' => '/element-anchors'])
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-light border-top">
                <div class="row">
                    @if($elementBody->state === 0)
                    <div class="col-md-3 col-sm-6 mb-2"><button type="submit" class="btn btn-primary btn-block btn-sm shadow-sm"><i class="fa fa-save"></i> Aggiorna</button></div>
                    @endif
                    <div class="col-md-3 col-sm-6 mb-2"><a href="{{ route('element-bodies.index') }}" class="btn btn-danger btn-block btn-sm shadow-sm"><i class="fa fa-backward"></i> Indietro</a></div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('js')
<script>
(function () {
    'use strict';
    var bodyId = {{ $elementBody->id }};
    var isZonesLocked = {{ $elementBody->state >= 2 ? 'true' : 'false' }};
    var SCALE = 8;
    var ZONE_COLORS = ['#e53935','#1e88e5','#43a047','#fb8c00','#8e24aa','#00897b','#f4511e','#3949ab','#d4e157','#ff7043','#00acc1','#7e57c2','#c0ca33','#ef5350','#26c6da'];
    var allZoneColors = {}, allZoneDots = {}, allZonePixels = {};
    var currentPoints = [], isDrawing = false, zoneName = '', selectedZoneId = null, pixelBrushMode = null;
    var $canvas, $img, ctx, imgData;

    function zoneColorFor(id) { return allZoneColors[id] || ZONE_COLORS[(id || 0) % ZONE_COLORS.length]; }
    function getCsrf() { return $('meta[name="csrf-token"]').attr('content') || ''; }

    function isPointInPolygon(x, y, poly) { var inside=false; for(var i=0,j=poly.length-1;i<poly.length;j=i++){var xi=poly[i].x,yi=poly[i].y,xj=poly[j].x,yj=poly[j].y;if(((yi>y)!=(yj>y))&&(x<(xj-xi)*(y-yi)/(yj-yi)+xi))inside=!inside;} return inside; }
    function isPointCloseToPolygon(x,y,poly){if(isPointInPolygon(x,y,poly))return true;for(var i=0;i<poly.length;i++)if(poly[i].x===x&&poly[i].y===y)return true;for(var i=0,j=poly.length-1;i<poly.length;j=i++){var dx=poly[i].x-poly[j].x,dy=poly[i].y-poly[j].y,t=((x-poly[j].x)*dx+(y-poly[j].y)*dy)/(dx*dx+dy*dy||1);t=Math.max(0,Math.min(1,t));var px=poly[j].x+t*dx,py=poly[j].y+t*dy;if(Math.sqrt((x-px)*(x-px)+(y-py)*(y-py))<=1)return true;}return false;}
    function isPixelOccupied(gx,gy){var occ=false;$.each(allZonePixels,function(zid){$.each(allZonePixels[zid],function(_,pt){if(pt.x===gx&&pt.y===gy){occ=true;return false;}});if(occ)return false;});return occ;}
    function canvasToImg(cx,cy){return{x:Math.floor(cx/SCALE),y:Math.floor(cy/SCALE)};}
    function imgToCanvas(ix,iy){return{x:ix*SCALE,y:iy*SCALE};}
    function clearCanvas(){ctx.clearRect(0,0,$canvas.width(),$canvas.height());}

    function findBlackPixelsInPolygon(poly){var pixels=[];if(!imgData)return pixels;for(var gy=0;gy<64;gy++)for(var gx=0;gx<64;gx++){if(!isPointCloseToPolygon(gx,gy,poly))continue;if(isPixelOccupied(gx,gy))continue;var cx=Math.floor(gx*SCALE+SCALE/2),cy=Math.floor(gy*SCALE+SCALE/2);if(cx>=0&&cx<imgData.width&&cy>=0&&cy<imgData.height){var idx=(cy*imgData.width+cx)*4;if(imgData.data[idx]===0&&imgData.data[idx+1]===0&&imgData.data[idx+2]===0&&imgData.data[idx+3]>0)pixels.push({x:gx,y:gy});}}return pixels;}
    function isDrawnPixel(cx,cy){if(!imgData)return false;cx=Math.floor(cx);cy=Math.floor(cy);if(cx<0||cx>=imgData.width||cy<0||cy>=imgData.height)return false;var pt=canvasToImg(cx,cy);if(isPixelOccupied(pt.x,pt.y))return false;var idx=(cy*imgData.width+cx)*4;return imgData.data[idx]===0&&imgData.data[idx+1]===0&&imgData.data[idx+2]===0&&imgData.data[idx+3]>0;}

    function redrawZoneDots(){$.each(allZonePixels,function(zid){ctx.fillStyle='rgba(110,110,110,0.7)';$.each(allZonePixels[zid],function(_,pt){var rx=Math.floor(pt.x/2)*2*SCALE,ry=Math.floor(pt.y/2)*2*SCALE;ctx.fillRect(rx,ry,SCALE*2,SCALE*2);});});$.each(allZoneDots,function(zid){var c=zoneColorFor(zid);$.each(allZoneDots[zid],function(_,pt){var p=imgToCanvas(pt.x,pt.y);ctx.beginPath();ctx.arc(p.x+SCALE/2,p.y+SCALE/2,SCALE*0.38,0,2*Math.PI);ctx.fillStyle=c;ctx.lineWidth=1.5;ctx.strokeStyle='#fff';ctx.stroke();ctx.fill();});});}
    function drawZonePolygon(zid,dots,color,lw,dr){if(!dots||dots.length<2)return;lw=lw||2;dr=dr||SCALE*0.38;ctx.beginPath();$.each(dots,function(i,pt){var p=imgToCanvas(pt.x,pt.y);if(i===0)ctx.moveTo(p.x+SCALE/2,p.y+SCALE/2);else ctx.lineTo(p.x+SCALE/2,p.y+SCALE/2);});ctx.closePath();ctx.strokeStyle=color;ctx.lineWidth=lw;ctx.setLineDash([]);ctx.stroke();$.each(dots,function(_,pt){var p=imgToCanvas(pt.x,pt.y);ctx.beginPath();ctx.arc(p.x+SCALE/2,p.y+SCALE/2,dr,0,2*Math.PI);ctx.fillStyle=color;ctx.lineWidth=1.5;ctx.strokeStyle='#fff';ctx.stroke();ctx.fill();});}
    function drawPreview(){clearCanvas();redrawZoneDots();if(!currentPoints.length)return;ctx.strokeStyle='#0056e0';ctx.lineWidth=3;ctx.setLineDash([]);ctx.beginPath();$.each(currentPoints,function(i,pt){var p=imgToCanvas(pt.x,pt.y);if(i===0)ctx.moveTo(p.x+SCALE/2,p.y+SCALE/2);else ctx.lineTo(p.x+SCALE/2,p.y+SCALE/2);});ctx.stroke();$.each(currentPoints,function(_,pt){var p=imgToCanvas(pt.x,pt.y);ctx.beginPath();ctx.arc(p.x+SCALE/2,p.y+SCALE/2,SCALE*0.45,0,2*Math.PI);ctx.fillStyle='#ff3d00';ctx.lineWidth=2;ctx.strokeStyle='#fff';ctx.stroke();ctx.fill();});}

    function resetDrawing(){currentPoints=[];isDrawing=false;zoneName='';selectedZoneId=null;pixelBrushMode=null;$('#eb-btn-brush-add').prop('disabled',true).removeClass('btn-success').addClass('btn-outline-success');$('#eb-btn-brush-remove').prop('disabled',true).removeClass('btn-danger').addClass('btn-outline-danger');$('#eb-new-zone-name').val('');$('#eb-editor-zone-name').text('nessuna').css('color','');$('#eb-btn-cancel-polygon').hide();$('#eb-btn-delete-selected-zone').prop('disabled',true);clearCanvas();redrawZoneDots();}

    function extractImageData(){var doIt=function(){try{var tc=document.createElement('canvas');tc.width=512;tc.height=512;var tctx=tc.getContext('2d');tctx.imageSmoothingEnabled=false;tctx.drawImage($img[0],0,0,512,512);imgData=tctx.getImageData(0,0,512,512);}catch(e){imgData=null;}};if($img[0]&&$img[0].complete&&$img[0].naturalWidth!==0)doIt();else if($img[0])$img.on('load',doIt);}

    function loadZones(){resetDrawing();$('#eb-zones-loading').show();$('#eb-zones-list-wrapper').hide();$('#eb-zones-empty').hide();$.get('/element-bodies/'+bodyId+'/zones',function(data){renderZones(data);}).fail(function(){alert('Errore caricamento zone.');}).always(function(){$('#eb-zones-loading').hide();});}

    function renderZones(zones){var tbody=$('#eb-zones-tbody');tbody.empty();allZoneDots={};allZonePixels={};clearCanvas();if(!zones||!zones.length){$('#eb-zones-empty').show();return;}$('#eb-zones-empty').hide();$('#eb-zones-list-wrapper').show();$.each(zones,function(_,z){allZoneColors[z.id]=z.color||ZONE_COLORS[z.id%ZONE_COLORS.length];allZoneDots[z.id]=$.map(z.details,function(d){return{x:d.x,y:d.y};});allZonePixels[z.id]=$.map(z.pixels||[],function(px){return{x:px.x,y:px.y};});var c=zoneColorFor(z.id);var inp=$('<input>',{type:'text',class:'form-control form-control-sm border-0 bg-transparent px-0 py-0 font-weight-bold',value:z.name});if(isZonesLocked)inp.prop('disabled',true);else inp.on('change',function(){var nn=$(this).val().trim();if(!nn){loadZones();return;}$.ajax({url:'/element-bodies/'+bodyId+'/zones/'+z.id,type:'PUT',data:{name:nn},headers:{'X-CSRF-TOKEN':getCsrf()},success:loadZones,error:function(){loadZones();}});});var tr=$('<tr>').attr('data-zone-id',z.id).append($('<td>').addClass('text-center').html('<span style="color:'+c+';font-size:1.2rem;">●</span>'),$('<td>').append(inp),$('<td>').addClass('text-center').text(z.details?z.details.length:0));tbody.append(tr);});redrawZoneDots();}

    function saveCurrentZone(){if(!zoneName.trim()){alert('Inserisci un nome.');return;}if(currentPoints.length<3){alert('Almeno 3 punti.');return;}var nc=ZONE_COLORS[Object.keys(allZoneDots).length%ZONE_COLORS.length];$.post('/element-bodies/'+bodyId+'/zones',{name:zoneName.trim(),color:nc,_token:getCsrf()},function(zone){allZoneColors[zone.id]=zone.color||nc;drawZonePolygon(zone.id,currentPoints.map(p=>({x:p.x,y:p.y})),zoneColorFor(zone.id));var bpx=findBlackPixelsInPolygon(currentPoints);var promises=[];$.each(currentPoints,function(_,pt){promises.push($.post('/element-bodies/'+bodyId+'/zones/'+zone.id+'/add-detail',{x:pt.x,y:pt.y,_token:getCsrf()}));});promises.push($.ajax({url:'/element-bodies/'+bodyId+'/zones/'+zone.id+'/save-pixels',type:'POST',contentType:'application/json',headers:{'X-CSRF-TOKEN':getCsrf()},data:JSON.stringify({pixels:bpx})}));$.when.apply($,promises).done(function(){resetDrawing();loadZones();});}).fail(function(){alert('Errore salvataggio.');});}

    $(function(){
        $canvas=$('#eb-zone-canvas');$img=$('#eb-body-image');
        if(!$canvas.length||!$img.length)return;
        ctx=$canvas[0].getContext('2d');extractImageData();

        $canvas.on('click',function(e){if(isDrawing)return;var rect=$canvas[0].getBoundingClientRect();var cx=(e.clientX-rect.left)*($canvas.width()/rect.width),cy=(e.clientY-rect.top)*($canvas.height()/rect.height);var pt=canvasToImg(cx,cy);if(isPixelOccupied(pt.x,pt.y)){var fz=null;$.each(allZonePixels,function(zid){$.each(allZonePixels[zid],function(_,px){if(px.x===pt.x&&px.y===pt.y){fz=zid;return false;}});if(fz)return false;});if(fz){if(fz==selectedZoneId&&pixelBrushMode==='remove'&&!isZonesLocked){allZonePixels[selectedZoneId]=$.grep(allZonePixels[selectedZoneId],function(px){return!(px.x===pt.x&&px.y===pt.y);});$.ajax({url:'/element-bodies/'+bodyId+'/zones/'+selectedZoneId+'/save-pixels',type:'POST',contentType:'application/json',headers:{'X-CSRF-TOKEN':getCsrf()},data:JSON.stringify({pixels:allZonePixels[selectedZoneId]}),success:function(){clearCanvas();redrawZoneDots();var d=allZoneDots[selectedZoneId];if(d)drawZonePolygon(selectedZoneId,d,zoneColorFor(selectedZoneId),3,SCALE*0.45);}});}else{var tr=$('#eb-zones-tbody tr[data-zone-id="'+fz+'"]');if(tr.length)tr.trigger('click');}}return;}if(isZonesLocked)return;if(selectedZoneId!==null&&pixelBrushMode==='add'){var idx=(Math.floor(cy)*imgData.width+Math.floor(cx))*4;if(imgData.data[idx]===0&&imgData.data[idx+1]===0&&imgData.data[idx+2]===0&&imgData.data[idx+3]>0){if(!allZonePixels[selectedZoneId])allZonePixels[selectedZoneId]=[];allZonePixels[selectedZoneId].push({x:pt.x,y:pt.y});$.ajax({url:'/element-bodies/'+bodyId+'/zones/'+selectedZoneId+'/save-pixels',type:'POST',contentType:'application/json',headers:{'X-CSRF-TOKEN':getCsrf()},data:JSON.stringify({pixels:allZonePixels[selectedZoneId]}),success:function(){clearCanvas();redrawZoneDots();var d=allZoneDots[selectedZoneId];if(d)drawZonePolygon(selectedZoneId,d,zoneColorFor(selectedZoneId),3,SCALE*0.45);}});}return;}var en=$('#eb-new-zone-name').val().trim();if(en){zoneName=en;$('#eb-editor-zone-name').text(en);isDrawing=true;$('#eb-btn-cancel-polygon').show();return;}$('#ebZoneNameModal').modal('show');$('#eb-zone-name-input').val('').focus();});

        $('#eb-btn-confirm-zone-name').on('click',function(){var n=$('#eb-zone-name-input').val().trim();if(!n){alert('Inserisci un nome.');return;}zoneName=n;$('#eb-new-zone-name').val(n);$('#eb-editor-zone-name').text(n);$('#ebZoneNameModal').modal('hide');isDrawing=true;$('#eb-btn-cancel-polygon').show();});
        $('#eb-new-zone-name').on('input keyup',function(){var n=$(this).val().trim();if(n){zoneName=n;$('#eb-editor-zone-name').text(n);isDrawing=true;$('#eb-btn-cancel-polygon').show();}else if(!currentPoints.length)resetDrawing();});
        $('#eb-zone-name-input').on('keyup',function(e){if(e.key==='Enter')$('#eb-btn-confirm-zone-name').trigger('click');});
        $('#ebZoneNameModal').on('hidden.bs.modal',function(){if(!isDrawing)resetDrawing();});

        $canvas.on('mousedown',function(e){if(!isDrawing)return;e.stopImmediatePropagation();var rect=$canvas[0].getBoundingClientRect();var cx=(e.clientX-rect.left)*($canvas.width()/rect.width),cy=(e.clientY-rect.top)*($canvas.height()/rect.height);if(!isDrawnPixel(cx,cy))return;var pt=canvasToImg(cx,cy);if(currentPoints.length>0){var l=currentPoints[currentPoints.length-1];if(l.x===pt.x&&l.y===pt.y)return;}if(currentPoints.length>=3){var f=imgToCanvas(currentPoints[0].x,currentPoints[0].y);if(Math.sqrt(Math.pow(cx-f.x-SCALE/2,2)+Math.pow(cy-f.y-SCALE/2,2))<=SCALE*1.5){isDrawing=false;saveCurrentZone();drawPreview();return;}}currentPoints.push(pt);drawPreview();});

        $('#eb-btn-cancel-polygon').on('click',resetDrawing);
        $('#eb-btn-delete-selected-zone').on('click',function(){var row=$('#eb-zones-tbody tr.selected');if(!row.length)return;if(!confirm('Eliminare zona?'))return;$.ajax({url:'/element-bodies/'+bodyId+'/zones/'+row.data('zone-id'),type:'DELETE',headers:{'X-CSRF-TOKEN':getCsrf()},success:loadZones});});
        $('#eb-btn-brush-add').on('click',function(){pixelBrushMode='add';$(this).removeClass('btn-outline-success').addClass('btn-success');$('#eb-btn-brush-remove').removeClass('btn-danger').addClass('btn-outline-danger');});
        $('#eb-btn-brush-remove').on('click',function(){pixelBrushMode='remove';$(this).removeClass('btn-outline-danger').addClass('btn-danger');$('#eb-btn-brush-add').removeClass('btn-success').addClass('btn-outline-success');});

        $(document).on('click','#eb-zones-tbody tr',function(e){if($(e.target).is('input,button,a'))return;$('#eb-zones-tbody tr').removeClass('selected');var $tr=$(this).addClass('selected');if(!isZonesLocked){$('#eb-btn-delete-selected-zone').prop('disabled',false);pixelBrushMode='add';$('#eb-btn-brush-add').prop('disabled',false).removeClass('btn-outline-success').addClass('btn-success');$('#eb-btn-brush-remove').prop('disabled',false).removeClass('btn-danger').addClass('btn-outline-danger');}var zid=$tr.data('zone-id');selectedZoneId=zid;$('#eb-editor-zone-name').text($tr.find('input').val()||('#'+zid)).css('color',zoneColorFor(zid));clearCanvas();redrawZoneDots();var d=allZoneDots[zid];if(d)drawZonePolygon(zid,d,zoneColorFor(zid),3,SCALE*0.45);});

        loadZones();
    });
})();
</script>
@endsection
