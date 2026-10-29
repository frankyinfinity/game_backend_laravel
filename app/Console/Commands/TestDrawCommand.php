<?php

namespace App\Console\Commands;

use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Complex\EntityDraw;
use App\Custom\Draw\Complex\ModalDraw;
use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Helper\Helper;
use App\Models\DrawRequest;
use App\Models\Entity;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestDrawCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "test:draw {--player-id=1 : Player ID to send draw events to}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Send test draw events to the test page - EntityDraw only";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requestId = Str::uuid()->toString();
        $sessionId = "test_session_fixed";

        $eventPlayerId = (int) $this->option('player-id');

        ObjectCache::buffer($sessionId);

        $drawItems = [];

        // Cancella gli oggetti precedenti
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $drawItems[] = $objectClear->get();
        }

        ObjectCache::clear($sessionId);

        // Create ModalDraw
        $modalUid = 'objective_modal_test';
        $modal = new ModalDraw($modalUid);
        $modal->setTitle('Test Modal');
        $modal->setScreenSize(1280, 720);
        $modal->setSize(600, 400);
        $modal->setOrigin(220, 50); // Slightly to the right of button (button ends at x=200), same height
        $modal->setRenderable(false); // Initially hidden
        $modal->build();

        foreach ($modal->getDrawItems() as $modalItem) {
            $drawItems[] = (new ObjectDraw($modalItem->buildJson(), $sessionId))->get();
        }

        // Create button to open modal
        $buttonUid = 'open_modal_button';
        $button = new ButtonDraw($buttonUid);
        $button->setOrigin(50, 50);
        $button->setSize(150, 40);
        $button->setString('Apri Modal');
        $button->setColorButton(0x3b82f6);
        $button->setColorString(0xffffff);
        $button->setTextFontSize(16);
        $button->setTextFontFamily(Helper::DEFAULT_FONT_FAMILY);

        // JavaScript to open modal
        $jsOpenModal = file_get_contents(resource_path('js/function/modal/click_open_modal.blade.php'));
        $jsOpenModal = str_replace('__MODAL_UID__', $modalUid, $jsOpenModal);
        $jsOpenModal = Helper::setCommonJsCode($jsOpenModal, Str::random(20));
        $button->setOnClick($jsOpenModal);
        $button->build();

        foreach ($button->getDrawItems() as $buttonItem) {
            $drawItems[] = (new ObjectDraw($buttonItem->buildJson(), $sessionId))->get();
        }

        // Create button to add random elements to modal
        $addElementsButtonUid = 'add_elements_button';
        $addElementsButton = new ButtonDraw($addElementsButtonUid);
        $addElementsButton->setOrigin(50, 100); // Below the first button
        $addElementsButton->setSize(180, 40);
        $addElementsButton->setString('Aggiungi Casuali');
        $addElementsButton->setColorButton(0x22c55e);
        $addElementsButton->setColorString(0xffffff);
        $addElementsButton->setTextFontSize(16);
        $addElementsButton->setTextFontFamily(Helper::DEFAULT_FONT_FAMILY);

        // JavaScript to add random elements for scroll testing
        $jsAddElements = "
            const modalUid = '{$modalUid}';
            const viewportUid = modalUid + '_content_viewport';
            
            console.log('Button clicked');
            console.log('modalUid:', modalUid);
            console.log('viewportUid:', viewportUid);
            console.log('viewportObject:', objects[viewportUid]);
            console.log('mainLayer:', mainLayer);
            
            const viewportObject = objects[viewportUid];
            
            if (!viewportObject) {
                console.error('Viewport object not found!');
                alert('Viewport not found!');
            } else {
                const contentX = viewportObject.attributes.scroll_viewport_left || 0;
                const contentY = viewportObject.attributes.scroll_viewport_top || 0;
                const contentWidth = viewportObject.width || 0;
                const contentHeight = viewportObject.height || 0;
                
                console.log('Content bounds:', { contentX, contentY, contentWidth, contentHeight });
                
                // Initialize scroll attributes if needed
                if (!viewportObject.attributes.scroll_child_uids) {
                    viewportObject.attributes.scroll_child_uids = [];
                }
                if (!viewportObject.attributes.scroll_base_positions_x) {
                    viewportObject.attributes.scroll_base_positions_x = {};
                }
                if (!viewportObject.attributes.scroll_base_positions_y) {
                    viewportObject.attributes.scroll_base_positions_y = {};
                }
                if (!viewportObject.attributes.scroll_item_widths) {
                    viewportObject.attributes.scroll_item_widths = {};
                }
                if (!viewportObject.attributes.scroll_item_heights) {
                    viewportObject.attributes.scroll_item_heights = {};
                }
                if (!viewportObject.attributes.scroll_initial_renderables) {
                    viewportObject.attributes.scroll_initial_renderables = {};
                }
                
                // Generate 20 random elements distributed to test scroll in all directions
                const numElements = 20;
                for (let i = 0; i < numElements; i++) {
                    const type = Math.floor(Math.random() * 4); // 0: square, 1: circle, 2: text, 3: rectangle
                    const uid = modalUid + '_random_' + Date.now() + '_' + i;
                    
                    // Distribute elements beyond viewport to test scrolling
                    const offsetX = (Math.random() - 0.5) * contentWidth * 2;
                    const offsetY = (Math.random() - 0.5) * contentHeight * 2;
                    const x = contentX + contentWidth / 2 + offsetX;
                    const y = contentY + contentHeight / 2 + offsetY;
                    const color = Math.floor(Math.random() * 0xFFFFFF);
                    
                    let shape;
                    let itemWidth, itemHeight;
                    let drawItem;
                    
                    if (type === 0) {
                        const size = 30 + Math.random() * 50;
                        shape = new PIXI.Sprite(PIXI.Texture.WHITE);
                        shape.width = size;
                        shape.height = size;
                        shape.tint = color;
                        shape.x = x;
                        shape.y = y;
                        itemWidth = size;
                        itemHeight = size;
                        drawItem = { type: 'square', size: size };
                    } else if (type === 1) {
                        const radius = 15 + Math.random() * 35;
                        shape = new PIXI.Graphics();
                        shape.beginFill(color);
                        shape.drawCircle(0, 0, radius);
                        shape.endFill();
                        shape.x = x;
                        shape.y = y;
                        itemWidth = radius * 2;
                        itemHeight = radius * 2;
                        drawItem = { type: 'circle', radius: radius };
                    } else if (type === 2) {
                        const texts = ['Ciao', 'Test', 'Scroll', 'Elemento', 'PixiJS', 'Modal', 'Test', 'Random'];
                        const text = texts[Math.floor(Math.random() * texts.length)];
                        const fontSize = 14 + Math.random() * 20;
                        shape = new PIXI.Text(text, {
                            fontFamily: 'Arial',
                            fontSize: fontSize,
                            fill: '#' + color.toString(16).padStart(6, '0')
                        });
                        shape.x = x;
                        shape.y = y;
                        itemWidth = 80;
                        itemHeight = 30;
                        drawItem = { type: 'text', text: text, fontSize: fontSize, fontFamily: 'Arial' };
                    } else {
                        const width = 40 + Math.random() * 60;
                        const height = 30 + Math.random() * 50;
                        shape = new PIXI.Graphics();
                        shape.beginFill(color);
                        shape.drawRect(0, 0, width, height);
                        shape.endFill();
                        shape.x = x;
                        shape.y = y;
                        itemWidth = width;
                        itemHeight = height;
                        drawItem = { type: 'rectangle', width: width, height: height };
                    }
                    
                    shape.renderable = true;
                    shape.zIndex = 20020;
                    
                    if (mainLayer) {
                        mainLayer.addChild(shape);
                        shapes[uid] = shape;
                        objects[uid] = {
                            uid: uid,
                            type: drawItem.type,
                            x: x,
                            y: y,
                            color: color,
                            ...drawItem,
                            attributes: { renderable: true, z_index: 20020 }
                        };
                        
                        // Add to scroll_child_uids
                        viewportObject.attributes.scroll_child_uids.push(uid);
                        viewportObject.attributes.scroll_base_positions_x[uid] = x;
                        viewportObject.attributes.scroll_base_positions_y[uid] = y;
                        viewportObject.attributes.scroll_item_widths[uid] = itemWidth;
                        viewportObject.attributes.scroll_item_heights[uid] = itemHeight;
                        viewportObject.attributes.scroll_initial_renderables[uid] = true;
                        
                        // Update content bounds
                        const itemLeft = x;
                        const itemRight = x + itemWidth;
                        const itemTop = y;
                        const itemBottom = y + itemHeight;
                        
                        if (viewportObject.attributes.scroll_content_left === undefined || itemLeft < viewportObject.attributes.scroll_content_left) {
                            viewportObject.attributes.scroll_content_left = itemLeft;
                        }
                        if (viewportObject.attributes.scroll_content_right === undefined || itemRight > viewportObject.attributes.scroll_content_right) {
                            viewportObject.attributes.scroll_content_right = itemRight;
                        }
                        if (viewportObject.attributes.scroll_content_top === undefined || itemTop < viewportObject.attributes.scroll_content_top) {
                            viewportObject.attributes.scroll_content_top = itemTop;
                        }
                        if (viewportObject.attributes.scroll_content_bottom === undefined || itemBottom > viewportObject.attributes.scroll_content_bottom) {
                            viewportObject.attributes.scroll_content_bottom = itemBottom;
                        }
                    }
                }
                
                // Refresh modal viewport masks
                if (typeof window.refreshAllModalViewportMasks === 'function') {
                    window.refreshAllModalViewportMasks();
                }
                
                console.log('Added ' + numElements + ' random elements for scroll testing');
                alert('Aggiunti ' + numElements + ' elementi casuali per testare lo scroll');
            }
        ";
        $jsAddElements = Helper::setCommonJsCode($jsAddElements, Str::random(20));
        $addElementsButton->setOnClick($jsAddElements);
        $addElementsButton->build();

        foreach ($addElementsButton->getDrawItems() as $addElementsButtonItem) {
            $drawItems[] = (new ObjectDraw($addElementsButtonItem->buildJson(), $sessionId))->get();
        }

        $this->info("Generated " . count($drawItems) . " test draw items (modal and buttons).");

        ObjectCache::flush($sessionId);

        $this->info("Total draw items: " . count($drawItems));

        DrawRequest::query()->create([
            "session_id" => $sessionId,
            "request_id" => $requestId,
            "player_id" => $eventPlayerId,
            "items" => json_encode($drawItems),
        ]);

        $this->info("Test draw event sent. Check the /test page.");
    }
}
