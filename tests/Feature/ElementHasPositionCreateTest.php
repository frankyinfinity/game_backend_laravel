<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Player;
use App\Models\Element;
use App\Models\ElementInformation;
use App\Models\Score;
use App\Models\Gene;

class ElementHasPositionCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable CSRF middleware for testing API routes
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /** @test */
    public function it_can_create_element_has_position_for_interactive_element()
    {
        // Create a player
        $player = new Player();
        $player->name = 'Test Player';
        $player->email = 'test' . time() . '@example.com';
        $player->password = bcrypt('password');
        $player->save();

        // Create an interactive element
        $element = new Element();
        $element->name = 'Interactive Element';
        $element->element_type_id = 1;
        $element->characteristic = Element::INTERACTIVE;
        $element->save();

        // Add required information genes to satisfy observer
        $gene = new Gene();
        $gene->name = 'Test Gene';
        $gene->key = 'test_gene';
        $gene->type = 'dynamic_max';
        $gene->min = 0;
        $gene->max = 100;
        $gene->max_from = 0;
        $gene->max_to = 100;
        $gene->save();

        ElementInformation::create([
            'element_id' => $element->id,
            'gene_id' => $gene->id,
            'min_value' => 0,
            'max_value' => 100,
            'value' => 50,
        ]);

        // Create a score for the element
        $score = new Score();
        $score->name = 'Test Score';
        $score->save();
        $element->scores()->attach($score->id, ['amount' => 10]);

        $data = [
            'player_id' => $player->id,
            'element_id' => $element->id,
            'tile_i' => 5,
            'tile_j' => 3,
        ];

        $response = $this->postJson('/game/element-has-position/create', $data);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('element_has_positions', [
            'player_id' => $player->id,
            'element_id' => $element->id,
            'tile_i' => 5,
            'tile_j' => 3,
        ]);
    }

    /** @test */
    public function it_fails_when_element_is_not_interactive()
    {
        $player = new Player();
        $player->name = 'Test Player 2';
        $player->email = 'test2' . time() . '@example.com';
        $player->password = bcrypt('password');
        $player->save();

        $element = new Element();
        $element->name = 'Consumable Element';
        $element->element_type_id = 1;
        $element->characteristic = Element::CONSUMABLE; // Not interactive
        $element->save();

        $data = [
            'player_id' => $player->id,
            'element_id' => $element->id,
            'tile_i' => 5,
            'tile_j' => 3,
        ];

        $response = $this->postJson('/game/element-has-position/create', $data);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_fails_when_element_has_position_already_exists()
    {
        $player = new Player();
        $player->name = 'Test Player 3';
        $player->email = 'test3' . time() . '@example.com';
        $player->password = bcrypt('password');
        $player->save();

        $element = new Element();
        $element->name = 'Interactive Element 2';
        $element->element_type_id = 1;
        $element->characteristic = Element::INTERACTIVE;
        $element->save();

        // Create existing ElementHasPosition
        $existingData = [
            'player_id' => $player->id,
            'element_id' => $element->id,
            'tile_i' => 5,
            'tile_j' => 3,
            'uid' => 'test-uid-' . time(),
            'session_id' => 'test-session',
        ];

        \App\Models\ElementHasPosition::create($existingData);

        $data = [
            'player_id' => $player->id,
            'element_id' => $element->id,
            'tile_i' => 5,
            'tile_j' => 3,
        ];

        $response = $this->postJson('/game/element-has-position/create', $data);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/game/element-has-position/create', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['player_id', 'element_id', 'tile_i', 'tile_j']);
    }
}
