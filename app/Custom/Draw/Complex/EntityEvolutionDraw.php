<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Helper\Helper;
use App\Models\Entity;
use App\Models\EntityBody;
use App\Models\EntityDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EntityEvolutionDraw
{
    private Entity $dbEntity;
    private string $modalUid;
    private array $drawItems = [];
    private string $initJs = '';

    public function __construct(Entity $dbEntity)
    {
        $this->dbEntity = $dbEntity;
        $this->modalUid = $dbEntity->uid . '_evolution_modal';
    }

    public function getDrawItems(): array { return $this->drawItems; }
    public function getInitJs(): string { return $this->initJs; }

    public function build(): void
    {
        $entity = $this->dbEntity;
        $modalUid = $this->modalUid;

        $entityDetail = EntityDetail::where('entity_id', $entity->id)
            ->where('detailable_type', EntityBody::class)->first();
        if (!$entityDetail) return;
        $body = EntityBody::with('zones.pixels')->find($entityDetail->detailable_id);
        // Build zone pixel map
        $zonePixelMap = []; $zoneColors = []; $zoneNames = [];
        foreach ($body->zones as $zone) {
            $zoneColors[$zone->id] = (int)($zone->color ?? 0);
            $zoneNames[$zone->id] = $zone->name ?? 'Unknown';
            foreach ($zone->pixels as $pixel) {
                $zonePixelMap[(int)$pixel->x][(int)$pixel->y] = $zone->id;
            }
        }

        // Read the ACTUAL colors of the Entity from its current image (disk entity_images).
        // When the modal opens, zone pixels must reflect the entity's current appearance,
        // falling back to the default zone color (EntityBodyZone.color) where no pixel exists.
        $entityColors = []; $entityMask = [];
        if ($entity->image && Storage::disk('entity_images')->exists($entity->image)) {
            $entImage = @imagecreatefromstring(Storage::disk('entity_images')->get($entity->image));
            if ($entImage) {
                $eow = imagesx($entImage); $eoh = imagesy($entImage);
                $ers = imagecreatetruecolor(32, 32);
                imagefill($ers, 0, 0, imagecolorallocate($ers, 255, 255, 255));
                imagecopyresampled($ers, $entImage, 0, 0, 0, 0, 32, 32, $eow, $eoh);
                for ($y = 0; $y < 32; $y++) {
                    for ($x = 0; $x < 32; $x++) {
                        $er = imagecolorat($ers, $x, $y);
                        $erA = ($er >> 24) & 0x7F;
                        $erR = ($er >> 16) & 0xFF;
                        $erG = ($er >> 8) & 0xFF;
                        $erB = $er & 0xFF;
                        // Skip background: alpha alto (trasparente) o quasi bianco
                        if ($erA < 100 && !($erR > 250 && $erG > 250 && $erB > 250)) {
                            $entityColors[$x][$y] = ($erR << 16) | ($erG << 8) | $erB;
                            $entityMask[$y][$x] = true;
                        }
                    }
                }
                imagedestroy($ers); imagedestroy($entImage);
            }
        }

        // Process entity body image -> 32x32 pixel array
        $pixels = [];
        $eOffX = 0; $eOffY = 0;
        if ($body->image && Storage::disk('entity_bodies')->exists($body->image)) {
            $imgPath = Storage::disk('entity_bodies')->path($body->image);
            $img = @imagecreatefromstring(file_get_contents($imgPath));
            if ($img) {
                $ow = imagesx($img); $oh = imagesy($img);
                $rs = imagecreatetruecolor(32, 32);
                imagefill($rs, 0, 0, imagecolorallocate($rs, 255, 255, 255));
                imagecopyresampled($rs, $img, 0, 0, 0, 0, 32, 32, $ow, $oh);
                // Maschera della sagoma del body (pixel neri) per l'allineamento con l'entity
                $bodyMask = [];
                for ($y = 0; $y < 32; $y++) {
                    for ($x = 0; $x < 32; $x++) {
                        $rgb = imagecolorat($rs, $x, $y);
                        $r = ($rgb>>16)&0xFF;$g=($rgb>>8)&0xFF;$b=$rgb&0xFF;
                        if ($r < 50 && $g < 50 && $b < 50) {
                            $bodyMask[$y][$x] = true;
                        }
                    }
                }
                // Auto-allineamento: l'immagine dell'Entity (creatura assemblata) puo' avere il body
                // posizionato con un offset rispetto all'immagine del body. Trovo lo shift (dx,dy)
                // che massimizza la sovrapposizione delle due sagome, cosi' ogni cella di zona
                // legge il colore dell'Entity esattamente alla sua posizione visiva.
                if (!empty($entityMask) && !empty($bodyMask)) {
                    $bestScore = -1; $bestDist = PHP_INT_MAX;
                    for ($dy = -8; $dy <= 8; $dy++) {
                        for ($dx = -8; $dx <= 8; $dx++) {
                            $inter = 0; $total = 0;
                            foreach ($bodyMask as $by => $brow) {
                                foreach ($brow as $bx => $v) {
                                    $total++;
                                    if (isset($entityMask[$by + $dy][$bx + $dx])) $inter++;
                                }
                            }
                            if ($total === 0 || ($inter / $total) < 0.5) continue;
                            $dist = abs($dx) + abs($dy);
                            if ($inter > $bestScore || ($inter === $bestScore && $dist < $bestDist)) {
                                $bestScore = $inter; $bestDist = $dist;
                                $eOffX = $dx; $eOffY = $dy;
                            }
                        }
                    }
                }
                for ($y = 0; $y < 32; $y++) {
                    for ($x = 0; $x < 32; $x++) {
                        $rgb = imagecolorat($rs, $x, $y);
                        $r = ($rgb>>16)&0xFF;$g=($rgb>>8)&0xFF;$b=$rgb&0xFF;
                        if ($r < 50 && $g < 50 && $b < 50) {
                            $zid = $zonePixelMap[$x*2][$y*2] ?? null;
                            $has = $zid !== null;
                            // Colore attuale dell'Entity (dalla sua immagine, letto allineato alla
                            // posizione del body), fallback sul colore di default della zona
                            $pixelColor = $has ? ($entityColors[$x + $eOffX][$y + $eOffY] ?? ($zoneColors[$zid] ?? 0)) : null;
                            $pixels[] = [
                                'x'=>$x,'y'=>$y,'has_zone'=>$has,'zone_id'=>$zid,
                                'color'=>$pixelColor,
                                'name'=>$has?($zoneNames[$zid]??''):null,
                                'bt'=>$has&&($zonePixelMap[$x*2][($y-1)*2]??null)!==$zid,
                                'bb'=>$has&&($zonePixelMap[$x*2][($y+1)*2]??null)!==$zid,
                                'bl'=>$has&&($zonePixelMap[($x-1)*2][$y*2]??null)!==$zid,
                                'br'=>$has&&($zonePixelMap[($x+1)*2][$y*2]??null)!==$zid,
                            ];
                        }
                    }
                }
                imagedestroy($rs); imagedestroy($img);
            }
        }

        // Modal positions
        $sw=1280;$sh=720;$mw=960;$mh=680;
        $mx=(int)floor(($sw-$mw)/2);
        $my=(int)floor(($sh-$mh)/2);
        $cx=$mx+16;$cy=$my+60+16;

        $modal = new ModalDraw($modalUid);
        $modal->setTitle('Evoluzione - '.$entity->uid);
        $modal->setSize($mw,$mh);
        $modal->setOrigin($mx,$my);
        $modal->setRenderable(false);

        // ===== GRID 32x32 =====
        $cs=15;$ci=$cs-1;$gs=32;
        $gtw=$cs*$gs;$gth=$cs*$gs;
        $gx=$cx+8;$gy=$cy+8;

        $gridBg = new Rectangle($modalUid.'_grid_bg');
        $gridBg->setOrigin($gx,$gy);
        $gridBg->setSize($gtw,$gth);
        $gridBg->setColor(0x404040);
        $gridBg->setRenderable(false);
        $gridBg->addAttributes('z_index',20040);
        $this->drawItems[]=$gridBg;

        $pixDb=[];$gridUids=[];$tintJs='';
        foreach($pixels as $p){$pixDb[$p['y'].'_'.$p['x']]=$p;}
        for($r=0;$r<$gs;$r++){
            for($c=0;$c<$gs;$c++){
                $cell=new Rectangle($modalUid.'_grid_cell_'.$r.'_'.$c);
                $cell->setOrigin($gx+$c*$cs+1,$gy+$r*$cs+1);
                $cell->setSize($ci,$ci);
                $p=$pixDb[$r.'_'.$c]??null;
                $cell->setColor(0xFFFFFF);
                $cell->setRenderable(false);
                $cell->addAttributes('z_index',20041);
                if($p&&$p['has_zone']){
                    $zc=$p['color']??0x000000;
                    $tintJs.="var tu=shapes['{$cell->getUid()}'];if(tu)tu.tint=".$zc.";";
                    $js="window['clickZoneCell_{$modalUid}']('{$cell->getUid()}');";
                    $cell->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN,$js);
                    $bd=2;
                    $ox=$gx+$c*$cs+1;$oy=$gy+$r*$cs+1;
                    if($p['bt']){$b=new Rectangle($modalUid.'_zbd_'.$r.'_'.$c.'_t');$b->setOrigin($ox,$oy);$b->setSize($ci,$bd);$b->setColor(0x808080);$b->setRenderable(false);$b->addAttributes('z_index',20045);$this->drawItems[]=$b;$gridUids[]=$b->getUid();}
                    if($p['bb']){$b=new Rectangle($modalUid.'_zbd_'.$r.'_'.$c.'_b');$b->setOrigin($ox,$oy+$ci-$bd+1);$b->setSize($ci,$bd);$b->setColor(0x808080);$b->setRenderable(false);$b->addAttributes('z_index',20045);$this->drawItems[]=$b;$gridUids[]=$b->getUid();}
                    if($p['bl']){$b=new Rectangle($modalUid.'_zbd_'.$r.'_'.$c.'_l');$b->setOrigin($ox,$oy);$b->setSize($bd,$ci);$b->setColor(0x808080);$b->setRenderable(false);$b->addAttributes('z_index',20045);$this->drawItems[]=$b;$gridUids[]=$b->getUid();}
                    if($p['br']){$b=new Rectangle($modalUid.'_zbd_'.$r.'_'.$c.'_r');$b->setOrigin($ox+$ci-$bd+1,$oy);$b->setSize($bd,$ci);$b->setColor(0x808080);$b->setRenderable(false);$b->addAttributes('z_index',20045);$this->drawItems[]=$b;$gridUids[]=$b->getUid();}
                }
                $this->drawItems[]=$cell;
                $gridUids[]=$cell->getUid();
            }
        }
        $gridUids[]=$gridBg->getUid();
        $bt=2;
        foreach([['t',$gx,$gy,$gtw,$bt],['b',$gx,$gy+$gth-$bt,$gtw,$bt],['l',$gx,$gy,$bt,$gth],['r',$gx+$gtw-$bt,$gy,$bt,$gth]] as $b){
            $br=new Rectangle($modalUid.'_grid_border_'.$b[0]);
            $br->setOrigin($b[1],$b[2]);$br->setSize($b[3],$b[4]);$br->setColor(0x000000);
            $br->setRenderable(false);$br->addAttributes('z_index',20042);
            $this->drawItems[]=$br;$gridUids[]=$br->getUid();
        }
        $gridUidsJson=json_encode(array_values(array_unique($gridUids)));

        // showGrid JS (with tint init)
        $gsJs="window['showGrid_{$modalUid}']=function(s){";
        $gsJs.="var uz=".$gridUidsJson.";";
        $gsJs.="uz.forEach(function(u){if(shapes[u])shapes[u].renderable=s;if(objects[u]&&objects[u].attributes)objects[u].attributes.renderable=s;});";
        $gsJs.="if(s){".$tintJs."}";
        $gsJs.="if(!s){window['closeZonePanel_{$modalUid}']();}";
        $gsJs.="};";
        $gsJs.="window['resetEntityBodyGrid_{$modalUid}']=function(){window['showGrid_{$modalUid}'](false);};";

        // ===== ZONE PANEL =====
        $pw=320;$ph=240;
        $px=$gx+$gtw+20;$py=$gy;

        $zp=new Rectangle($modalUid.'_zone_panel');
        $zp->setOrigin($px+2,$py+2);$zp->setSize($pw-4,$ph-4);$zp->setColor(0xFFFFFF);
        $zp->setRenderable(false);$zp->addAttributes('z_index',50000);
        $this->drawItems[]=$zp;
        $zbt=2;
        foreach([['t',$px,$py,$pw,$zbt],['b',$px,$py+$ph-$zbt,$pw,$zbt],['l',$px,$py,$zbt,$ph],['r',$px+$pw-$zbt,$py,$zbt,$ph]] as $b){
            $br=new Rectangle($modalUid.'_zone_border_'.$b[0]);
            $br->setOrigin($b[1],$b[2]);$br->setSize($b[3],$b[4]);$br->setColor(0x000000);
            $br->setRenderable(false);$br->addAttributes('z_index',49999);
            $this->drawItems[]=$br;
        }
        $sq=new Rectangle($modalUid.'_zone_color_square');
        $sq->setOrigin($px+15,$py+13);$sq->setSize(24,24);$sq->setColor(0xFFFFFF);
        $sq->setRenderable(false);$sq->addAttributes('z_index',50010);
        $this->drawItems[]=$sq;
        $zt=new Text($modalUid.'_zone_name_text');
        $zt->setCenterAnchor(false);$zt->setOrigin($px+51,$py+16);
        $zt->setText('Zona');$zt->setColor(0x000000);$zt->setFontSize(16);
        $zt->setFontFamily(Helper::DEFAULT_FONT_FAMILY);$zt->setRenderable(false);
        $zt->addAttributes('z_index',50020);
        $this->drawItems[]=$zt;
        $cs2=20;$cx2=$px+$pw-$cs2-8;$cy2=$py+8;
        $zc=new Rectangle($modalUid.'_zone_close_button');
        $zc->setOrigin($cx2,$cy2);$zc->setSize($cs2,$cs2);$zc->setColor(0x666666);
        $zc->setBorderRadius(3);$zc->setRenderable(false);$zc->addAttributes('z_index',50030);
        $this->drawItems[]=$zc;
        $zct=new Text($modalUid.'_zone_close_text');
        $zct->setCenterAnchor(true);$zct->setOrigin($cx2+10,$cy2+10);
        $zct->setText('X');$zct->setFontSize(14);$zct->setColor(0xFFFFFF);
        $zct->setRenderable(false);$zct->addAttributes('z_index',50040);
        $this->drawItems[]=$zct;

        // Close button handler
        $zcClose="window['closeZonePanel_{$modalUid}']();";
        $zcCloseJs=Helper::setCommonJsCode($zcClose,Str::random(20));
        $zc->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN,$zcCloseJs);
        $zct->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN,$zcCloseJs);

        // ===== RGB SLIDERS with inline onChange =====
        $slCfgs=[
            ['s'=>'slider_red','c'=>0xFF0000,'t'=>'Rosso'],
            ['s'=>'slider_green','c'=>0x00FF00,'t'=>'Verde'],
            ['s'=>'slider_blue','c'=>0x0000FF,'t'=>'Blu'],
        ];
        $slUids=[];
        foreach($slCfgs as $si=>$sc){
            $inline="(function(){";
            $inline.="var r=0,g=0,b=0;";
            $inline.="var tr=shapes['{$modalUid}_slider_red_track_bg'];var kr=shapes['{$modalUid}_slider_red_knob'];";
            $inline.="var tg=shapes['{$modalUid}_slider_green_track_bg'];var kg=shapes['{$modalUid}_slider_green_knob'];";
            $inline.="var tb=shapes['{$modalUid}_slider_blue_track_bg'];var kb=shapes['{$modalUid}_slider_blue_knob'];";
            $inline.="if(tr&&kr)r=Math.round(Math.max(0,Math.min(1,(kr.x-tr.x)/tr.width))*255);";
            $inline.="if(tg&&kg)g=Math.round(Math.max(0,Math.min(1,(kg.x-tg.x)/tg.width))*255);";
            $inline.="if(tb&&kb)b=Math.round(Math.max(0,Math.min(1,(kb.x-tb.x)/tb.width))*255);";
            $inline.="var pc=(r<<16)|(g<<8)|b;";
            $inline.="var sq=shapes['{$modalUid}_zone_color_square'];if(sq)sq.tint=pc;";
            $inline.="var zid=window['_curZoneId_{$modalUid}'];if(zid){";
            $inline.="var px=window['_evoPixels_{$modalUid}'];";
            $inline.="for(var i=0;i<px.length;i++){if(px[i].zone_id===zid){var s=shapes[px[i].cell];if(s)s.tint=pc;}}";
            $inline.="if(!window['_evoModifiedZones_{$modalUid}'])window['_evoModifiedZones_{$modalUid}']={};";
            $inline.="window['_evoModifiedZones_{$modalUid}'][zid]=pc;";
            $inline.="}";
            $inline.="})();";
            $sl=new SliderDraw($modalUid.'_'.$sc['s']);
            $sl->setOrigin($px+10,$py+50+$si*55);
            $sl->setWidth($pw-20);$sl->setMin(0);$sl->setMax(255);$sl->setValue(0);
            $sl->setColor($sc['c']);$sl->setTitle($sc['t']);
            $sl->setOnChange($inline);
            $sl->build();
            foreach($sl->getDrawItems() as $it){
                $it->addAttributes('z_index',50050+$si);
                $it->setRenderable(false);
                $this->drawItems[]=$it;
                $slUids[]=$it->getUid();
            }
        }
        $slUidsJson=json_encode($slUids);

        // ===== SALVA BUTTON =====
        $jsPathSave = resource_path('js/function/entity/save_evolution_zones.blade.php');
        $jsContentSave = file_get_contents($jsPathSave);
        $jsContentSave = str_replace('__MODAL_UID__', $modalUid, $jsContentSave);

        // Info websocket del container Evolution legato al player
        $gatewayBaseUrl = 'ws://' . (string) config('remote_docker.docker_host_ip') . ':' . (int) config('remote_docker.websocket_gateway_port', 9001) . '/?port=';
        $playerId = (int) $entity->specie->player_id;
        $evolutionContainer = \App\Models\Container::query()
            ->where('parent_type', \App\Models\Container::PARENT_TYPE_EVOLUTION)
            ->where('parent_id', $playerId)
            ->first();
        $evolutionWsPort = $evolutionContainer ? $evolutionContainer->ws_port : null;

        $jsContentSave = str_replace('__gateway_base__', $gatewayBaseUrl, $jsContentSave);
        $jsContentSave = str_replace('__port__', $evolutionWsPort ?? '', $jsContentSave);
        $jsContentSave = str_replace('__PLAYER_ID__', (string) $playerId, $jsContentSave);
        $jsContentSave = str_replace('__ENTITY_ID__', (string) $entity->id, $jsContentSave);
        $jsContentSave = Helper::setCommonJsCode($jsContentSave, Str::random(20));
        $btnW=180;$btnH=30;
        // Bottom-right corner of the modal
        $btnX=$mx+$mw-$btnW-20;
        $btnY=$my+$mh-$btnH-20;
        $saveBtn=new ButtonDraw($modalUid.'_save_button');
        $saveBtn->setSize($btnW,$btnH);
        $saveBtn->setOrigin($btnX,$btnY);
        $saveBtn->setString('Salva');
        $saveBtn->setColorButton(0x2E7D32);
        $saveBtn->setColorString(0xFFFFFF);
        $saveBtn->setTextFontSize(18);
        $saveBtn->setOnClick($jsContentSave);
        $saveBtn->setRenderable(false);
        $saveBtn->build();
        foreach($saveBtn->getDrawItems() as $it){
            $it->addAttributes('z_index',50060);
            $this->drawItems[]=$it;
        }

        // ===== JS HANDLERS =====
        $pixelJs=[];
        foreach($pixels as $p){
            if($p['has_zone']){
                $pixelJs[]=[
                    'cell'=>$modalUid.'_grid_cell_'.$p['y'].'_'.$p['x'],
                    'zone_id'=>$p['zone_id'],'color'=>$p['color'],'name'=>$p['name'],
                    'y'=>$p['y'],'x'=>$p['x'],
                ];
            }
        }
        $pixelJsJson=json_encode($pixelJs);

        $js="window['_evoPixels_{$modalUid}']=".$pixelJsJson.";";
        $js.="window['_curZoneId_{$modalUid}']=null;";
        // clickZoneCell
        $js.="window['clickZoneCell_{$modalUid}']=function(cellUid){";
        $js.="var px=window['_evoPixels_{$modalUid}'];var found=null;";
        $js.="for(var i=0;i<px.length;i++){if(px[i].cell===cellUid){found=px[i];break;}}";
        $js.="if(!found)return;window['_curZoneId_{$modalUid}']=found.zone_id;";
        $js.="var uids=['{$modalUid}_zone_panel','{$modalUid}_zone_border_t','{$modalUid}_zone_border_b','{$modalUid}_zone_border_l','{$modalUid}_zone_border_r','{$modalUid}_zone_color_square','{$modalUid}_zone_name_text','{$modalUid}_zone_close_button','{$modalUid}_zone_close_text'];";
        $js.="var suids=".$slUidsJson.";";
        $js.="suids.forEach(function(u){if(shapes[u])shapes[u].renderable=true;if(objects[u]&&objects[u].attributes)objects[u].attributes.renderable=true;});";
        $js.="uids.forEach(function(u){if(shapes[u])shapes[u].renderable=true;if(objects[u]&&objects[u].attributes)objects[u].attributes.renderable=true;});";
        $js.="var zc=found.color;if(window['_evoModifiedZones_{$modalUid}']&&window['_evoModifiedZones_{$modalUid}'][found.zone_id]!==undefined)zc=window['_evoModifiedZones_{$modalUid}'][found.zone_id];";
        $js.="var sq=shapes['{$modalUid}_zone_color_square'];if(sq)sq.tint=zc;";
        $js.="var nm=objects['{$modalUid}_zone_name_text'];if(nm){nm.text=found.name;if(shapes['{$modalUid}_zone_name_text'])shapes['{$modalUid}_zone_name_text'].text=found.name;}";
        $js.="var cr=(zc>>16)&255,cg=(zc>>8)&255,cb=zc&255;";
        $js.="window['_setSliderVal_{$modalUid}'](cr,cg,cb);";
        $js.="};";
        // _setSliderVal
        $trackXNum = $px + 10;
        $trackWNum = $pw - 20;
        $js.="window['_setSliderVal_{$modalUid}']=function(r,g,b){";
        $js.="var trackX={$trackXNum},trackW={$trackWNum};";
        $js.="if(typeof window['_sliderFill_{$modalUid}']!=='function'){";
        $js.="window['_sliderFill_{$modalUid}']=function(trackUid,fillUid,val,color){";
        $js.="var tk=shapes[trackUid];var f=shapes[fillUid];if(!tk||!f)return;";
        $js.="var w=Math.max(1,Math.round((val/255)*trackW));";
        $js.="f.clear();f.beginFill(color);f.drawRect(0,0,w,8);f.endFill();";
        $js.="f.x=tk.x;f.y=tk.y;";
        $js.="};}";
        $js.="var kr=shapes['{$modalUid}_slider_red_knob'];";
        $js.="if(kr)kr.x=trackX+(r/255)*trackW;";
        $js.="window['_sliderFill_{$modalUid}']('{$modalUid}_slider_red_track_bg','{$modalUid}_slider_red_track_fill',r,0xFF0000);";
        $js.="var kg=shapes['{$modalUid}_slider_green_knob'];";
        $js.="if(kg)kg.x=trackX+(g/255)*trackW;";
        $js.="window['_sliderFill_{$modalUid}']('{$modalUid}_slider_green_track_bg','{$modalUid}_slider_green_track_fill',g,0x00FF00);";
        $js.="var kb=shapes['{$modalUid}_slider_blue_knob'];";
        $js.="if(kb)kb.x=trackX+(b/255)*trackW;";
        $js.="window['_sliderFill_{$modalUid}']('{$modalUid}_slider_blue_track_bg','{$modalUid}_slider_blue_track_fill',b,0x0000FF);";
        $js.="};";
        // closeZonePanel
        $js.="window['closeZonePanel_{$modalUid}']=function(){";
        $js.="var uids=['{$modalUid}_zone_panel','{$modalUid}_zone_border_t','{$modalUid}_zone_border_b','{$modalUid}_zone_border_l','{$modalUid}_zone_border_r','{$modalUid}_zone_color_square','{$modalUid}_zone_name_text','{$modalUid}_zone_close_button','{$modalUid}_zone_close_text'];";
        $js.="var suids=".$slUidsJson.";";
        $js.="suids.forEach(function(u){if(shapes[u])shapes[u].renderable=false;if(objects[u]&&objects[u].attributes)objects[u].attributes.renderable=false;});";
        $js.="uids.forEach(function(u){if(shapes[u])shapes[u].renderable=false;if(objects[u]&&objects[u].attributes)objects[u].attributes.renderable=false;});";
        $js.="window['_curZoneId_{$modalUid}']=null;};";

        $this->initJs.=$gsJs.$js;

        $modal->build();
        foreach($modal->getDrawItems() as $item){
            $this->drawItems[]=$item;
        }
    }
}