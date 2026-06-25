@extends('adminlte::page')

@section('title', 'Modifica Elemento')

@section('content_header')
<h1>Modifica Elemento</h1>
@stop

@section('content')
@if($element->isFinishAssembler())
<div class="alert alert-success border shadow-sm mb-4" style="border-left:4px solid #28a745 !important;">
    <i class="fas fa-check-circle mr-2 text-success"></i> Stato: <strong>{{ $element->getStateLabel() }}</strong> — Assemblaggio bloccato.
</div>
@else
<div class="alert alert-light border shadow-sm mb-4" style="border-left:4px solid #ffc107 !important;">
    <i class="fas fa-info-circle mr-2 text-warning"></i> Stato: <strong>{{ $element->getStateLabel() }}</strong>
</div>
@endif

<form action="{{ route('elements.update', $element) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card card-primary card-outline shadow-sm">
        <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="main-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-general-link" data-toggle="pill" href="#tab-general" role="tab">Dati Generali</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-assembler-link" data-toggle="pill" href="#tab-assembler" role="tab">Assemblaggio</a>
                </li>
                @if($element->isFinishAssembler() && $element->isConsumable())
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab-reward" role="tab">Ricompensa</a>
                </li>
                @endif
                @if($element->isFinishAssembler() && $element->isInteractive())
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#tab-brain" role="tab">Cervello</a>
                </li>
                @endif
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="main-tabs-content">

                <!-- TAB DATI GENERALI -->
                <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                    @include('elements.tabs.general')
                </div>

                <!-- TAB ASSEMBLAGGIO -->
                <div class="tab-pane fade" id="tab-assembler" role="tabpanel">
                    @include('elements.tabs.assembler')
                </div>

                @if($element->isFinishAssembler() && $element->isConsumable())
                <!-- TAB RICOMPENSA -->
                <div class="tab-pane fade" id="tab-reward" role="tabpanel">
                    @include('elements.tabs.reward')
                </div>
                @endif

                @if($element->isFinishAssembler() && $element->isInteractive())
                <!-- TAB CERVELLO -->
                <div class="tab-pane fade" id="tab-brain" role="tabpanel">
                    @include('elements.tabs.brain_assembler')
                </div>
                @endif

            </div>
        </div>

        <div class="card-footer">
            @if(!$element->isFinishAssembler())
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Aggiorna
            </button>
            @endif
            <a href="{{ route('elements.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Annulla
            </a>
        </div>
    </div>
</form>

@if(!$element->isFinishAssembler())
<!-- Form separato per finishAssembler (fuori dal form principale) -->
<form action="{{ route('elements.finish-assembler', $element) }}" method="POST" id="asm-finish-form" style="display:none;">
    @csrf
    <input type="hidden" name="assembler_json" id="asm-finish-json" value="">
</form>
@endif

@if($element->isFinishAssembler() && $element->isInteractive() && !$element->isCompleted())
<!-- Form separato per complete (fuori dal form principale) -->
<form action="{{ route('elements.complete', $element) }}" method="POST" id="el-brain-complete-form" style="display:none;">
    @csrf
</form>
@endif

@if($element->isFinishAssembler() && $element->isConsumable() && !$element->isCompleted())
<!-- Form separato per complete consumable -->
<form action="{{ route('elements.complete', $element) }}" method="POST" id="el-reward-complete-form" style="display:none;">
    @csrf
    <div id="el-reward-complete-inputs"></div>
</form>
@endif
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const GRID = 32, CELL = 16;
    const mainCanvas = document.getElementById('asm-main-canvas');
    if (!mainCanvas) return;
    const mainCtx = mainCanvas.getContext('2d');
    const bodySelect = document.getElementById('asm-body-select');
    const compBtn = document.getElementById('asm-add-component-btn');
    const jsonOutput = document.getElementById('asm-json-output');

    const elementCharacteristic = {{ $element->characteristic }};

    let allBodies = [], allComponents = [];
    let selectedBody = null;
    let addedComponents = [];
    let zoneColors = {};
    let selectedZoneId = null;

    fetch('{{ route("elements.assembler.bodies") }}').then(r=>r.json()).then(data=>{ allBodies=data; populateBodies(); });
    fetch('{{ route("elements.assembler.components") }}').then(r=>r.json()).then(data=>{ allComponents=data; });

    function populateBodies() {
        bodySelect.innerHTML = '<option value="">-- Seleziona un Corpo --</option>';
        allBodies.filter(b => b.characteristic === elementCharacteristic).forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.id; opt.textContent = b.name + ' (#' + b.id + ')';
            bodySelect.appendChild(opt);
        });
    }

    // Move buttons
    const moveUp = document.getElementById('asm-move-up');
    const moveDown = document.getElementById('asm-move-down');
    const moveLeft = document.getElementById('asm-move-left');
    const moveRight = document.getElementById('asm-move-right');

    function toggleMoveButtons(dis) { moveUp.disabled=dis; moveDown.disabled=dis; moveLeft.disabled=dis; moveRight.disabled=dis; }

    function movePixels(dx, dy) {
        if (!selectedBody) return;
        var blocked = false;
        // Check body pixels bounds
        for (var i=0; i<selectedBody.pixels.length; i++) {
            var nx = selectedBody.pixels[i].x + dx;
            var ny = selectedBody.pixels[i].y + dy;
            if (nx < 0 || nx >= GRID || ny < 0 || ny >= GRID) { blocked = true; break; }
        }
        // Check anchors bounds
        if (!blocked) {
            for (var ai=0; ai<selectedBody.anchors.length; ai++) {
                var anx = selectedBody.anchors[ai].x + dx;
                var any = selectedBody.anchors[ai].y + dy;
                if (anx < 0 || anx >= GRID || any < 0 || any >= GRID) { blocked = true; break; }
            }
        }
        if (blocked) return;
        // Apply movement
        selectedBody.pixels.forEach(function(p){ p.x += dx; p.y += dy; });
        selectedBody.anchors.forEach(function(a){ a.x += dx; a.y += dy; });
        addedComponents.forEach(function(comp){ comp.dx += dx; comp.dy += dy; });
        drawMainCanvas(); refreshAddedTable(); updateJson();
    }

    moveUp.addEventListener('click', function(e){e.preventDefault();e.stopPropagation();movePixels(0,-1);});
    moveDown.addEventListener('click', function(e){e.preventDefault();e.stopPropagation();movePixels(0,1);});
    moveLeft.addEventListener('click', function(e){e.preventDefault();e.stopPropagation();movePixels(-1,0);});
    moveRight.addEventListener('click', function(e){e.preventDefault();e.stopPropagation();movePixels(1,0);});

    bodySelect.addEventListener('change', function() {
        var id = +this.value;
        selectedBody = allBodies.find(function(b){return b.id===id;}) || null;
        addedComponents = [];
        zoneColors = {};
        selectedZoneId = null;
        hideZonePanel();
        compBtn.disabled = !selectedBody;
        toggleMoveButtons(!selectedBody);
        drawMainCanvas(); refreshAddedTable(); updateJson();
    });

    // Zone panel
    var zonePanel = document.getElementById('asm-zone-panel');
    var zoneSwatch = document.getElementById('asm-zone-color-swatch');
    var zoneNameLabel = document.getElementById('asm-zone-name-label');
    var sliderR = document.getElementById('asm-zone-r');
    var sliderG = document.getElementById('asm-zone-g');
    var sliderB = document.getElementById('asm-zone-b');

    function hideZonePanel(){zonePanel.style.display='none';selectedZoneId=null;}
    document.getElementById('asm-zone-close').addEventListener('click', hideZonePanel);

    function showZonePanel(zoneId, zoneName){
        selectedZoneId=zoneId; zoneNameLabel.textContent=zoneName;
        var col=zoneColors[zoneId]||{r:0,g:0,b:0};
        sliderR.value=col.r;sliderG.value=col.g;sliderB.value=col.b;
        zoneSwatch.style.backgroundColor='rgb('+col.r+','+col.g+','+col.b+')';
        zonePanel.style.display='block';
    }

    sliderR.addEventListener('input', onSliderChange);
    sliderG.addEventListener('input', onSliderChange);
    sliderB.addEventListener('input', onSliderChange);
    function onSliderChange(){
        if(selectedZoneId===null)return;
        var r=+sliderR.value,g=+sliderG.value,b=+sliderB.value;
        zoneColors[selectedZoneId]={r:r,g:g,b:b};
        zoneSwatch.style.backgroundColor='rgb('+r+','+g+','+b+')';
        drawMainCanvas();updateJson();
    }

    mainCanvas.addEventListener('click', function(e){
        if(!selectedBody)return;
        var rect=mainCanvas.getBoundingClientRect();
        var x=Math.floor((e.clientX-rect.left)*(512/rect.width)/CELL);
        var y=Math.floor((e.clientY-rect.top)*(512/rect.height)/CELL);
        var pixel=selectedBody.pixels.find(function(p){return p.x===x&&p.y===y;});
        if(pixel&&pixel.has_zone&&pixel.zone_id){
            if(!zoneColors[pixel.zone_id])zoneColors[pixel.zone_id]={r:0,g:0,b:0};
            showZonePanel(pixel.zone_id, pixel.zone_name||'Zona');
        }
    });

    // Draw
    function drawMainCanvas(){
        mainCtx.fillStyle='#fff';mainCtx.fillRect(0,0,512,512);
        if(!selectedBody)return;
        selectedBody.pixels.forEach(function(p){
            if(p.has_zone&&p.zone_id&&zoneColors[p.zone_id]){var c=zoneColors[p.zone_id];mainCtx.fillStyle='rgb('+c.r+','+c.g+','+c.b+')';}
            else{mainCtx.fillStyle='#000';}
            mainCtx.fillRect(p.x*CELL,p.y*CELL,CELL,CELL);
        });
        selectedBody.pixels.forEach(function(p){
            if(!p.has_zone||!p.zone_id)return;
            mainCtx.strokeStyle=p.zone_color||'#888';mainCtx.lineWidth=2;
            var px=p.x*CELL,py=p.y*CELL;
            var sameZone=function(nx,ny){return selectedBody.pixels.find(function(q){return q.x===nx&&q.y===ny&&q.zone_id===p.zone_id;});};
            if(!sameZone(p.x,p.y-1)){mainCtx.beginPath();mainCtx.moveTo(px,py);mainCtx.lineTo(px+CELL,py);mainCtx.stroke();}
            if(!sameZone(p.x,p.y+1)){mainCtx.beginPath();mainCtx.moveTo(px,py+CELL);mainCtx.lineTo(px+CELL,py+CELL);mainCtx.stroke();}
            if(!sameZone(p.x-1,p.y)){mainCtx.beginPath();mainCtx.moveTo(px,py);mainCtx.lineTo(px,py+CELL);mainCtx.stroke();}
            if(!sameZone(p.x+1,p.y)){mainCtx.beginPath();mainCtx.moveTo(px+CELL,py);mainCtx.lineTo(px+CELL,py+CELL);mainCtx.stroke();}
        });
        addedComponents.forEach(function(comp){
            comp.pixels.forEach(function(p){
                var tx=p.x+comp.dx,ty=p.y+comp.dy;
                if(tx<0||tx>=GRID||ty<0||ty>=GRID)return;
                mainCtx.fillStyle='rgb('+p.r+','+p.g+','+p.b+')';
                mainCtx.fillRect(tx*CELL,ty*CELL,CELL,CELL);
            });
        });
        mainCtx.fillStyle='rgba(0,0,255,0.7)';
        selectedBody.anchors.forEach(function(a){mainCtx.beginPath();mainCtx.arc(a.x*CELL+CELL/2,a.y*CELL+CELL/2,CELL/3,0,Math.PI*2);mainCtx.fill();});
        mainCtx.strokeStyle='rgba(200,200,200,0.2)';mainCtx.lineWidth=0.5;
        for(var i=0;i<=GRID;i++){mainCtx.beginPath();mainCtx.moveTo(i*CELL,0);mainCtx.lineTo(i*CELL,512);mainCtx.stroke();mainCtx.beginPath();mainCtx.moveTo(0,i*CELL);mainCtx.lineTo(512,i*CELL);mainCtx.stroke();}
    }

    // Added components DataTable
    var isAssemblerLocked = {{ $element->isFinishAssembler() ? 'true' : 'false' }};
    var asmAddedDT = null;
    var asmAvailableDT = null;
    try {
        var addedColumns = [
            {data:'name'},{data:'body_anchor',render:function(d){return d?'('+d.x+','+d.y+')':'-';}},
            {data:'comp_anchor',render:function(d){return d?'('+d.x+','+d.y+')':'-';}},
            {data:null,render:function(d,t,row){return 'dx:'+row.dx+' dy:'+row.dy;}},
            {data:null,orderable:false,render:function(d,t,row){return '<button type="button" class="btn btn-xs btn-info asm-info-comp" data-id="'+row.id+'"><i class="fas fa-info-circle"></i></button>';}}
        ];
        if (!isAssemblerLocked) {
            addedColumns.push({data:null,orderable:false,render:function(d,t,row,meta){return '<button type="button" class="btn btn-xs btn-danger asm-remove-comp" data-index="'+meta.row+'"><i class="fas fa-trash"></i></button>';}});
        }
        asmAddedDT = $('#asm-added-components-table').DataTable({
            destroy:true, paging:false, searching:false, info:false, ordering:false,
            language:{emptyTable:'Nessun componente aggiunto.'},
            columns: addedColumns
        });
    } catch(e) { console.warn('DataTable init error:', e); }

    function refreshAddedTable(){
        if(!asmAddedDT)return;
        asmAddedDT.clear();
        asmAddedDT.rows.add(addedComponents.map(function(c){return{id:c.id,name:c.name,body_anchor:c.body_anchor,comp_anchor:c.comp_anchor,dx:c.dx,dy:c.dy};})).draw();
    }

    $(document).on('click','.asm-remove-comp',function(){addedComponents.splice(+$(this).data('index'),1);drawMainCanvas();refreshAddedTable();updateJson();});

    // Modal component selection
    compBtn.addEventListener('click', function(){
        if(asmAvailableDT){asmAvailableDT.destroy();asmAvailableDT=null;}
        var filtered=allComponents.filter(function(c){return c.characteristic===elementCharacteristic&&!addedComponents.find(function(a){return a.id===c.id;});});
        var isConsumable=(elementCharacteristic===0);
        var columns=[
            {data:'image_url',orderable:false,searchable:false,render:function(d){return d?'<img src="'+d+'?v='+Date.now()+'" style="width:32px;height:32px;image-rendering:pixelated;border:1px solid #ccc;">':'<i class="fas fa-image text-muted"></i>';}},
            {data:'name'},{data:'type_name'}
        ];
        if(isConsumable){columns.push({data:'consumption_effects',render:function(d){return(d&&d.length)?d.join(', '):'<span class="text-muted">-</span>';}});}
        else{
            columns.push({data:'genes',render:function(d){return(d&&d.length)?d.join(', '):'<span class="text-muted">-</span>';}});
            columns.push({data:'rules',render:function(d){return(d&&d.length)?d.join(', '):'<span class="text-muted">-</span>';}});
            columns.push({data:null,orderable:false,searchable:false,render:function(d,t,row){
                if(!row.has_brain) return '<span class="text-muted">-</span>';
                return '<button type="button" class="btn btn-xs btn-info asm-view-brain" data-id="'+row.id+'"><i class="fas fa-brain"></i> Vedi</button>';
            }});
        }
        columns.push({data:'anchors',render:function(d){return d?d.length:0;}});
        columns.push({data:null,orderable:false,searchable:false,render:function(d,t,row){return '<button type="button" class="btn btn-sm btn-success asm-select-comp" data-id="'+row.id+'"><i class="fas fa-plus"></i></button>';}});
        asmAvailableDT=$('#asm-components-datatable').DataTable({destroy:true,paging:true,pageLength:5,searching:true,info:true,ordering:true,language:{emptyTable:'Nessun componente disponibile.',search:'Cerca:'},data:filtered,columns:columns});
        $('#asmComponentModal').modal('show');
    });

    $(document).on('click','.asm-select-comp',function(){
        var id=+$(this).data('id');
        var comp=allComponents.find(function(c){return c.id===id;});
        if(!comp)return;
        $('#asmComponentModal').modal('hide');
        startLinking(comp);
    });

    // Linking
    var linkingComp=null,linkBodyAnchor=null,linkCompAnchor=null;
    var LCELL=8;

    function startLinking(comp){
        linkingComp=comp;linkBodyAnchor=null;linkCompAnchor=null;
        document.getElementById('asm-link-area').style.display='block';
        document.getElementById('asm-link-body-anchor').textContent='-';
        document.getElementById('asm-link-comp-anchor').textContent='-';
        document.getElementById('asm-link-confirm').disabled=true;
        drawLinkBody();drawLinkComp();
    }

    function drawLinkBody(){
        var c=document.getElementById('asm-link-body-canvas'),ctx=c.getContext('2d');
        ctx.fillStyle='#fff';ctx.fillRect(0,0,256,256);
        if(!selectedBody)return;
        selectedBody.pixels.forEach(function(p){ctx.fillStyle='#000';ctx.fillRect(p.x*LCELL,p.y*LCELL,LCELL,LCELL);});
        selectedBody.anchors.forEach(function(a){ctx.fillStyle=(linkBodyAnchor&&linkBodyAnchor.id===a.id)?'#FF0000':'#0000FF';ctx.fillRect(a.x*LCELL,a.y*LCELL,LCELL,LCELL);});
    }

    function drawLinkComp(){
        var c=document.getElementById('asm-link-comp-canvas'),ctx=c.getContext('2d');
        ctx.fillStyle='#fff';ctx.fillRect(0,0,256,256);
        if(!linkingComp)return;
        linkingComp.pixels.forEach(function(p){ctx.fillStyle='rgb('+p.r+','+p.g+','+p.b+')';ctx.fillRect(p.x*LCELL,p.y*LCELL,LCELL,LCELL);});
        linkingComp.anchors.forEach(function(a){ctx.fillStyle=(linkCompAnchor&&linkCompAnchor.id===a.id)?'#FF0000':'#0000FF';ctx.fillRect(a.x*LCELL,a.y*LCELL,LCELL,LCELL);});
    }

    document.getElementById('asm-link-body-canvas').addEventListener('click',function(e){
        if(!selectedBody)return;
        var rect=this.getBoundingClientRect(),x=Math.floor((e.clientX-rect.left)/LCELL),y=Math.floor((e.clientY-rect.top)/LCELL);
        var anchor=selectedBody.anchors.find(function(a){return a.x===x&&a.y===y;});
        if(anchor){linkBodyAnchor=anchor;document.getElementById('asm-link-body-anchor').textContent='('+anchor.x+','+anchor.y+')';drawLinkBody();checkLinkReady();}
    });

    document.getElementById('asm-link-comp-canvas').addEventListener('click',function(e){
        if(!linkingComp)return;
        var rect=this.getBoundingClientRect(),x=Math.floor((e.clientX-rect.left)/LCELL),y=Math.floor((e.clientY-rect.top)/LCELL);
        var anchor=linkingComp.anchors.find(function(a){return a.x===x&&a.y===y;});
        if(anchor){linkCompAnchor=anchor;document.getElementById('asm-link-comp-anchor').textContent='('+anchor.x+','+anchor.y+')';drawLinkComp();checkLinkReady();}
    });

    function checkLinkReady(){document.getElementById('asm-link-confirm').disabled=!(linkBodyAnchor&&linkCompAnchor);}

    document.getElementById('asm-link-confirm').addEventListener('click',function(){
        if(!linkBodyAnchor||!linkCompAnchor||!linkingComp)return;
        var dx=linkBodyAnchor.x-linkCompAnchor.x,dy=linkBodyAnchor.y-linkCompAnchor.y;
        addedComponents.push({id:linkingComp.id,name:linkingComp.name,pixels:linkingComp.pixels,anchors:linkingComp.anchors,body_anchor:linkBodyAnchor,comp_anchor:linkCompAnchor,dx:dx,dy:dy});
        document.getElementById('asm-link-area').style.display='none';
        linkingComp=null;linkBodyAnchor=null;linkCompAnchor=null;
        drawMainCanvas();refreshAddedTable();updateJson();
    });

    document.getElementById('asm-link-cancel').addEventListener('click',function(){
        document.getElementById('asm-link-area').style.display='none';
        linkingComp=null;linkBodyAnchor=null;linkCompAnchor=null;
    });

    // JSON
    function updateJson(){
        if(!selectedBody){jsonOutput.value='';updateFinishBtn();return;}
        var payload={
            body_selected:{id:selectedBody.id,name:selectedBody.name},
            zones_rgb:Object.keys(zoneColors).map(function(zid){
                var col=zoneColors[zid];
                var zone=selectedBody.pixels.find(function(p){return p.zone_id==zid;});
                return{zone_id:+zid,zone_name:zone?zone.zone_name:null,r:col.r,g:col.g,b:col.b};
            }),
            pixels:buildComposedPixels(),
            components:addedComponents.map(function(c){return{id:c.id,name:c.name,link_to_body:{body_anchor:{x:c.body_anchor.x,y:c.body_anchor.y},component_anchor:{x:c.comp_anchor.x,y:c.comp_anchor.y}}};})
        };
        var jsonStr = JSON.stringify(payload,null,2);
        jsonOutput.value = jsonStr;
        // Sync to finish form
        var finishJson = document.getElementById('asm-finish-json');
        if (finishJson) finishJson.value = jsonStr;
        updateFinishBtn();
    }

    function updateFinishBtn() {
        var btn = document.getElementById('asm-finish-btn');
        if (btn) btn.disabled = !(selectedBody && addedComponents.length > 0);
    }

    function buildComposedPixels(){
        var map={};
        if(selectedBody){selectedBody.pixels.forEach(function(p){var col=(p.has_zone&&p.zone_id&&zoneColors[p.zone_id])?zoneColors[p.zone_id]:{r:0,g:0,b:0};map[p.x+'|'+p.y]={x:p.x,y:p.y,r:col.r,g:col.g,b:col.b};});}
        addedComponents.forEach(function(comp){comp.pixels.forEach(function(p){var tx=p.x+comp.dx,ty=p.y+comp.dy;if(tx<0||tx>=GRID||ty<0||ty>=GRID)return;map[tx+'|'+ty]={x:tx,y:ty,r:p.r,g:p.g,b:p.b};});});
        return Object.values(map);
    }

    // Disable everything if assembler is already finished
    var savedAssemblerData = @json($savedAssemblerData ?? null);

    if (isAssemblerLocked && savedAssemblerData) {
        bodySelect.disabled = true;
        compBtn.disabled = true;
        moveUp.disabled = true; moveDown.disabled = true; moveLeft.disabled = true; moveRight.disabled = true;

        // Populate components list immediately from saved data (no API dependency)
        if (savedAssemblerData.components && savedAssemblerData.components.length) {
            savedAssemblerData.components.forEach(function(sc) {
                addedComponents.push({
                    id: sc.id, name: sc.name || 'Componente #' + sc.id,
                    pixels: [],
                    anchors: [],
                    body_anchor: sc.body_anchor, comp_anchor: sc.comp_anchor,
                    dx: sc.dx, dy: sc.dy
                });
            });
        }
        refreshAddedTable();

        // Draw the saved image directly on canvas
        drawLockedCanvas();

        // Also populate body select label
        if (savedAssemblerData.body_id && savedAssemblerData.body_name) {
            var opt = document.createElement('option');
            opt.value = savedAssemblerData.body_id;
            opt.textContent = savedAssemblerData.body_name + ' (#' + savedAssemblerData.body_id + ')';
            opt.selected = true;
            bodySelect.appendChild(opt);
        }
    } else if (isAssemblerLocked) {
        bodySelect.disabled = true;
        compBtn.disabled = true;
        moveUp.disabled = true; moveDown.disabled = true; moveLeft.disabled = true; moveRight.disabled = true;
    }

    // Draw locked canvas from saved pixel data (final image)
    function drawLockedCanvas() {
        mainCtx.fillStyle = '#fff';
        mainCtx.fillRect(0, 0, 512, 512);
        if (savedAssemblerData && savedAssemblerData.pixels) {
            savedAssemblerData.pixels.forEach(function(p) {
                mainCtx.fillStyle = 'rgb(' + p.r + ',' + p.g + ',' + p.b + ')';
                mainCtx.fillRect(p.x * CELL, p.y * CELL, CELL, CELL);
            });
        }
        mainCtx.strokeStyle = 'rgba(200,200,200,0.2)';
        mainCtx.lineWidth = 0.5;
        for (var i = 0; i <= GRID; i++) {
            mainCtx.beginPath(); mainCtx.moveTo(i*CELL, 0); mainCtx.lineTo(i*CELL, 512); mainCtx.stroke();
            mainCtx.beginPath(); mainCtx.moveTo(0, i*CELL); mainCtx.lineTo(512, i*CELL); mainCtx.stroke();
        }
    }

    // Submit finish assembler form
    window.submitFinishAssembler = function() {
        if (!confirm('Terminare assemblaggio? Questa azione è irreversibile.')) return;
        var finishJson = document.getElementById('asm-finish-json');
        if (finishJson) finishJson.value = jsonOutput.value;
        document.getElementById('asm-finish-form').submit();
    };

    refreshAddedTable();
    updateJson();

    // ── Info modal ─────────────────────────────────────────────────
    $(document).on('click', '.asm-open-brain-from-info', function() {
        var compId = +$(this).data('comp-id');
        $('#asmInfoModal').modal('hide');
        $('#asmInfoModal').one('hidden.bs.modal', function() {
            var btn = $('.asm-view-brain[data-id="' + compId + '"]');
            if (btn.length) { btn.click(); }
            else {
                // Trigger brain view directly if button not in DOM (locked state)
                var comp = allComponents.find(function(c) { return c.id === compId; });
                if (comp && comp.brain) { showBrainModal(comp); }
            }
        });
    });

    $(document).on('click', '.asm-info-comp', function(e) {
        e.stopPropagation();
        var compId = +$(this).data('id');
        var comp = allComponents.find(function(c) { return c.id === compId; });
        if (!comp) {
            // Fallback: cerca nei addedComponents (per lo stato locked)
            comp = addedComponents.find(function(c) { return c.id === compId; });
            if (!comp) return;
            // Se non abbiamo dettagli dall'API, mostra solo il nome
            comp = allComponents.find(function(c) { return c.id === compId; }) || comp;
        }

        var isConsumable = (elementCharacteristic === 0);
        var html = '<h5 class="font-weight-bold mb-3">' + comp.name + '</h5>';

        if (isConsumable) {
            html += '<h6 class="font-weight-bold"><i class="fas fa-utensils mr-1 text-success"></i> Effetti Consumo</h6>';
            if (comp.consumption_effects && comp.consumption_effects.length) {
                html += '<ul class="list-group mb-3">';
                comp.consumption_effects.forEach(function(e) { html += '<li class="list-group-item py-1">' + e + '</li>'; });
                html += '</ul>';
            } else { html += '<p class="text-muted">Nessun effetto consumo.</p>'; }
        } else {
            html += '<h6 class="font-weight-bold"><i class="fas fa-dna mr-1 text-info"></i> Geni</h6>';
            if (comp.genes && comp.genes.length) {
                html += '<ul class="list-group mb-3">';
                comp.genes.forEach(function(g) { html += '<li class="list-group-item py-1">' + g + '</li>'; });
                html += '</ul>';
            } else { html += '<p class="text-muted">Nessun gene.</p>'; }

            html += '<h6 class="font-weight-bold"><i class="fas fa-flask mr-1 text-warning"></i> Elementi Chimici</h6>';
            if (comp.rules && comp.rules.length) {
                html += '<ul class="list-group mb-3">';
                comp.rules.forEach(function(r) { html += '<li class="list-group-item py-1">' + r + '</li>'; });
                html += '</ul>';
            } else { html += '<p class="text-muted">Nessuna regola.</p>'; }

            html += '<h6 class="font-weight-bold"><i class="fas fa-brain mr-1 text-purple"></i> Cervello</h6>';
            if (comp.has_brain) {
                html += '<p class="text-success"><i class="fas fa-check-circle mr-1"></i> Cervello configurato (' + comp.brain.neurons.length + ' neuroni, griglia ' + comp.brain.grid_width + 'x' + comp.brain.grid_height + ')</p>';
                html += '<button type="button" class="btn btn-sm btn-info asm-open-brain-from-info" data-comp-id="' + comp.id + '"><i class="fas fa-brain mr-1"></i> Visualizza Cervello</button>';
            } else { html += '<p class="text-muted">Nessun cervello configurato.</p>'; }
        }

        $('#asm-info-modal-title').html('<i class="fas fa-info-circle mr-2"></i> ' + comp.name);
        $('#asm-info-modal-body').html(html);
        $('#asmInfoModal').modal('show');
    });

    // ── Brain viewer ────────────────────────────────────────────────
    var TYPE_SYMBOLS = @json(\App\Models\Neuron::TYPE_SYMBOLS);
    var TYPE_LABELS = @json(\App\Models\Neuron::TYPE_LABELS);
    var brainNeuronsData = [];
    var brainCellSize = 40;

    $(document).on('click', '.asm-view-brain', function(e) {
        e.stopPropagation();
        var compId = +$(this).data('id');
        var comp = allComponents.find(function(c) { return c.id === compId; });
        if (!comp || !comp.brain) return;
        showBrainModal(comp);
    });

    function showBrainModal(comp) {
        var brain = comp.brain;
        if (!brain) return;
        var canvas = document.getElementById('asm-brain-canvas');
        var ctx = canvas.getContext('2d');
        var gw = brain.grid_width || 5, gh = brain.grid_height || 5;
        brainCellSize = Math.floor(Math.min(640 / gw, 640 / gh));
        var cw = gw * brainCellSize, ch = gh * brainCellSize;
        canvas.width = cw; canvas.height = ch;
        brainNeuronsData = brain.neurons || [];

        // Background
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, cw, ch);

        // Grid lines (dashed)
        ctx.strokeStyle = '#ddd'; ctx.lineWidth = 1; ctx.setLineDash([4, 4]);
        for (var i = 0; i <= gw; i++) { ctx.beginPath(); ctx.moveTo(i*brainCellSize, 0); ctx.lineTo(i*brainCellSize, ch); ctx.stroke(); }
        for (var j = 0; j <= gh; j++) { ctx.beginPath(); ctx.moveTo(0, j*brainCellSize); ctx.lineTo(cw, j*brainCellSize); ctx.stroke(); }
        ctx.setLineDash([]);

        // Draw links
        var links = brain.links || [];
        links.forEach(function(link) {
            var fromN = brainNeuronsData.find(function(n) { return n.id === link.from_neuron_id; });
            var toN = brainNeuronsData.find(function(n) { return n.id === link.to_neuron_id; });
            if (!fromN || !toN) return;
            var fx = fromN.grid_j * brainCellSize + brainCellSize;
            var fy = fromN.grid_i * brainCellSize + brainCellSize / 2;
            var tx = toN.grid_j * brainCellSize;
            var ty = toN.grid_i * brainCellSize + brainCellSize / 2;
            ctx.strokeStyle = link.color || '#16A34A';
            ctx.lineWidth = 3;
            ctx.beginPath(); ctx.moveTo(fx, fy); ctx.lineTo(tx, ty); ctx.stroke();
            // Arrow
            var angle = Math.atan2(ty - fy, tx - fx);
            ctx.fillStyle = link.color || '#16A34A';
            ctx.beginPath();
            ctx.moveTo(tx, ty);
            ctx.lineTo(tx - 10 * Math.cos(angle - 0.4), ty - 10 * Math.sin(angle - 0.4));
            ctx.lineTo(tx - 10 * Math.cos(angle + 0.4), ty - 10 * Math.sin(angle + 0.4));
            ctx.closePath(); ctx.fill();
        });

        // Neurons
        brain.neurons.forEach(function(n) {
            var x = n.grid_j * brainCellSize, y = n.grid_i * brainCellSize;
            ctx.fillStyle = '#fff';
            ctx.strokeStyle = '#111827'; ctx.lineWidth = 2;
            ctx.fillRect(x+2, y+2, brainCellSize-4, brainCellSize-4);
            ctx.strokeRect(x+2, y+2, brainCellSize-4, brainCellSize-4);
            var symbol = TYPE_SYMBOLS[n.type] || '?';
            ctx.fillStyle = '#1f2937';
            ctx.font = 'bold ' + Math.floor(brainCellSize * 0.5) + 'px Consolas';
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText(symbol, x + brainCellSize/2, y + brainCellSize/2);
        });

        document.getElementById('asm-brain-info').textContent = 'Griglia: ' + gw + 'x' + gh + ' — Neuroni: ' + brain.neurons.length + ' — Link: ' + links.length;

        // Tooltip on hover
        canvas.onmousemove = function(ev) {
            var rect = canvas.getBoundingClientRect();
            var mx = ev.clientX - rect.left, my = ev.clientY - rect.top;
            var ci = Math.floor(my / brainCellSize), cj = Math.floor(mx / brainCellSize);
            var neuron = brainNeuronsData.find(function(n) { return n.grid_i === ci && n.grid_j === cj; });
            var tip = document.getElementById('asm-brain-tooltip');
            if (neuron) {
                tip.textContent = (TYPE_LABELS[neuron.type] || neuron.type) + (neuron.tooltip ? ' — ' + neuron.tooltip : '');
                tip.style.display = 'block';
                tip.style.left = (mx + 12) + 'px';
                tip.style.top = (my - 20) + 'px';
            } else {
                tip.style.display = 'none';
            }
        };
        canvas.onmouseleave = function() { document.getElementById('asm-brain-tooltip').style.display = 'none'; };

        $('#asmBrainModal .modal-title').html('<i class="fas fa-brain mr-2"></i> Cervello: ' + comp.name);
        $('#asmBrainModal').modal('show');
    }
});
</script>
@if($element->isFinishAssembler() && $element->isInteractive())
@include('elements.tabs.brain_assembler_js')
@endif
@stop
