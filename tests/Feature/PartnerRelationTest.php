<?php

namespace Tests\Feature;

use App\Models\PartnerRelation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_index_returns_relation_id_for_connected_partner(): void
    {
        $requester = User::factory()->create();
        $receiver = User::factory()->create();

        $relation = PartnerRelation::create([
            'requester_user_id' => $requester->id,
            'receiver_user_id' => $receiver->id,
            'status' => 'accepted',
        ]);

        $response = $this
            ->actingAs($requester, 'api')
            ->getJson('/api/partners');

        $response
            ->assertOk()
            ->assertJsonPath('data.partners.0.id', $receiver->id)
            ->assertJsonPath('data.partners.0.relation_id', $relation->id);
    }
}
